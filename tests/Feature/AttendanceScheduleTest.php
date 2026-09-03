<?php

use App\Models\Attendance;
use App\Models\BiometricDevice;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('attendance.api_token', 'schedule-secret');
    $this->school = School::query()->create([
        'code' => 'HORARIO',
        'name' => 'Escuela Horario',
        'attendance_cooldown_minutes' => 5,
        'attendance_entry_time' => '08:00',
        'attendance_exit_time' => '14:00',
        'attendance_late_grace_minutes' => 5,
    ]);
    $this->device = BiometricDevice::query()->create([
        'school_id' => $this->school->id,
        'key' => 'horario-principal',
        'name' => 'Entrada principal',
        'mac_address' => '00:17:61:11:18:e3',
        'network' => '192.168.10',
    ]);
    $this->student = Student::query()->create([
        'school_id' => $this->school->id,
        'matricula' => 'H-001',
        'nombre' => 'Ana',
        'apellido' => 'Pérez',
        'curso' => 'Primero A',
        'id_lector' => '101',
    ]);
});

test('live punches use server time and flag lateness and early departure without blocking exit', function () {
    $this->travelTo('2026-09-03 08:06:00');
    $this->withHeader('X-Biometric-Token', 'schedule-secret')->postJson(route('api.asistencia'), [
        'id_lector' => '101',
        'reader_key' => $this->device->key,
        'event_timestamp' => '2000-01-01 00:00:00',
    ])->assertOk()->assertJsonPath('hora', '08:06:00');

    $this->travel(5)->minutes();
    $this->withHeader('X-Biometric-Token', 'schedule-secret')->postJson(route('api.asistencia'), [
        'id_lector' => '101',
        'reader_key' => $this->device->key,
    ])->assertOk()->assertJsonPath('tipo', 'Salida');
    $this->travelBack();

    expect(Attendance::query()->where('tipo', 'Entrada')->value('is_late'))->toBeTrue()
        ->and(Attendance::query()->where('tipo', 'Salida')->value('is_early_departure'))->toBeTrue();
});

test('administrator can justify a late entry for their school', function () {
    $attendance = Attendance::query()->create([
        'school_id' => $this->school->id,
        'biometric_device_id' => $this->device->id,
        'student_id' => $this->student->id,
        'matricula' => $this->student->matricula,
        'id_lector' => $this->student->id_lector,
        'fecha_hora' => now(),
        'curso' => $this->student->curso,
        'estado' => 'Entrada',
        'tipo' => 'Entrada',
        'is_late' => true,
    ]);
    $admin = User::factory()->create(['role' => 'admin', 'school_id' => $this->school->id]);

    $this->withSession(['user' => ['id' => $admin->id, 'role' => 'admin', 'school_id' => $this->school->id]])
        ->post(route('attendances.excuses.store', $attendance), [
            'excuse_type' => 'late',
            'excuse_note' => 'Cita médica.',
        ])->assertRedirect();

    expect($attendance->fresh()->excuse_type)->toBe('late')
        ->and($attendance->fresh()->excuse_note)->toBe('Cita médica.')
        ->and($attendance->fresh()->excused_by)->toBe($admin->id);
});
