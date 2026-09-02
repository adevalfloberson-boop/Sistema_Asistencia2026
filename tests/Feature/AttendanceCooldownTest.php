<?php

use App\Models\Attendance;
use App\Models\BiometricDevice;
use App\Models\School;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('attendance.api_token', 'cooldown-secret');
    $this->school = School::query()->create([
        'code' => 'COOLDOWN',
        'name' => 'Escuela Cooldown',
        'attendance_cooldown_minutes' => 10,
    ]);
    $this->device = BiometricDevice::query()->create([
        'school_id' => $this->school->id,
        'key' => 'entrada-principal',
        'name' => 'Entrada principal',
        'mac_address' => '00:17:61:11:18:e3',
        'network' => '192.168.10',
    ]);
    $this->student = Student::query()->create([
        'school_id' => $this->school->id,
        'matricula' => 'P-001',
        'nombre' => 'Ana',
        'apellido' => 'Pérez',
        'curso' => 'Primero A',
        'id_lector' => '101',
    ]);
});

test('keyed punches inside ten minutes are synchronized but ignored for entry and exit', function () {
    $send = function (string $key, string $timestamp) {
        return $this->withHeader('X-Biometric-Token', 'cooldown-secret')->postJson(route('api.asistencia'), [
            'id_lector' => '101',
            'reader_key' => $this->device->key,
            'event_key' => str_repeat($key, 64),
            'event_timestamp' => $timestamp,
            'event_source' => 'history',
        ]);
    };

    $send('a', '2026-08-21 08:00:00')->assertOk()->assertJsonPath('tipo', 'Entrada');
    $send('b', '2026-08-21 08:05:00')->assertOk()->assertJsonPath('ignored', true);
    $send('c', '2026-08-21 08:10:00')->assertOk()->assertJsonPath('tipo', 'Salida');

    expect(Attendance::query()->count())->toBe(3)
        ->and(Attendance::query()->where('is_ignored', false)->orderBy('fecha_hora')->pluck('tipo')->all())
        ->toBe(['Entrada', 'Salida']);

    $this->assertDatabaseHas('attendances', [
        'device_event_key' => str_repeat('b', 64),
        'is_ignored' => true,
        'ignored_reason' => 'cooldown',
    ]);
});

test('offline out of order punch is compared with the closest accepted event', function () {
    foreach ([
        ['a', '2026-08-21 08:00:00'],
        ['b', '2026-08-21 10:00:00'],
        ['c', '2026-08-21 09:55:00'],
    ] as [$key, $timestamp]) {
        $this->withHeader('X-Biometric-Token', 'cooldown-secret')->postJson(route('api.asistencia'), [
            'id_lector' => '101',
            'reader_key' => $this->device->key,
            'event_key' => str_repeat($key, 64),
            'event_timestamp' => $timestamp,
            'event_source' => 'history',
        ])->assertOk();
    }

    expect(Attendance::query()->where('is_ignored', true)->value('fecha_hora')->format('H:i:s'))->toBe('09:55:00')
        ->and(Attendance::query()->where('is_ignored', false)->orderBy('fecha_hora')->pluck('tipo')->all())
        ->toBe(['Entrada', 'Salida']);
});
