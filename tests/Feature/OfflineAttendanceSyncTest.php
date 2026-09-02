<?php

use App\Models\Attendance;
use App\Models\BiometricDevice;
use App\Models\School;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('attendance.api_token', 'offline-sync-secret');

    $this->school = School::query()->create([
        'code' => 'OFFLINE001',
        'name' => 'Escuela de prueba sin conexión',
    ]);
    $this->device = BiometricDevice::query()->create([
        'school_id' => $this->school->id,
        'key' => 'entrada-offline',
        'name' => 'Entrada offline',
        'mac_address' => '00:17:61:11:18:e3',
        'network' => '192.168.100',
    ]);
    $this->student = Student::query()->create([
        'school_id' => $this->school->id,
        'matricula' => 'MAT-OFFLINE-1',
        'nombre' => 'Ada',
        'apellido' => 'Prueba',
        'curso' => '5to A',
        'id_lector' => '101',
    ]);
});

test('agent receives an empty attendance checkpoint before the first punch', function () {
    $this->withHeader('X-Biometric-Token', 'offline-sync-secret')
        ->getJson(route('api.biometric.attendance.checkpoint', $this->device->key))
        ->assertOk()
        ->assertJsonPath('last_event_at', null)
        ->assertJsonPath('last_event_key', null);
});

test('historical punches preserve the device time and are idempotent', function () {
    $payload = [
        'id_lector' => $this->student->id_lector,
        'reader_key' => $this->device->key,
        'reader_name' => $this->device->name,
        'reader_mac' => $this->device->mac_address,
        'reader_ip' => '192.168.100.10',
        'event_key' => str_repeat('a', 64),
        'event_timestamp' => '2026-08-14 07:42:13',
        'event_source' => 'history',
        'device_status' => 1,
        'device_punch' => 0,
    ];

    $this->withHeader('X-Biometric-Token', 'offline-sync-secret')
        ->postJson(route('api.asistencia'), $payload)
        ->assertOk()
        ->assertJsonPath('duplicate', false)
        ->assertJsonPath('fecha_hora', '2026-08-14 07:42:13')
        ->assertJsonPath('source', 'history');

    $this->withHeader('X-Biometric-Token', 'offline-sync-secret')
        ->postJson(route('api.asistencia'), $payload)
        ->assertOk()
        ->assertJsonPath('duplicate', true);

    $this->assertDatabaseCount('attendances', 1);
    $this->assertDatabaseHas('attendances', [
        'device_event_key' => str_repeat('a', 64),
        'fecha_hora' => '2026-08-14 07:42:13',
        'sync_source' => 'history',
    ]);

    $this->withHeader('X-Biometric-Token', 'offline-sync-secret')
        ->getJson(route('api.biometric.attendance.checkpoint', $this->device->key))
        ->assertOk()
        ->assertJsonPath('last_event_at', '2026-08-14 07:42:13')
        ->assertJsonPath('last_event_key', str_repeat('a', 64));
});

test('a recovered punch rebuilds the entry and exit chronology', function () {
    $sendPunch = function (string $key, string $timestamp): void {
        $this->withHeader('X-Biometric-Token', 'offline-sync-secret')
            ->postJson(route('api.asistencia'), [
                'id_lector' => $this->student->id_lector,
                'reader_key' => $this->device->key,
                'event_key' => $key,
                'event_timestamp' => $timestamp,
                'event_source' => 'history',
            ])
            ->assertOk();
    };

    $sendPunch(str_repeat('1', 64), '2026-08-14 08:00:00');
    $sendPunch(str_repeat('3', 64), '2026-08-14 10:00:00');
    $sendPunch(str_repeat('2', 64), '2026-08-14 09:00:00');

    expect(
        Attendance::query()
            ->where('student_id', $this->student->id)
            ->orderBy('fecha_hora')
            ->pluck('tipo')
            ->all()
    )->toBe(['Entrada', 'Salida', 'Entrada']);
});
