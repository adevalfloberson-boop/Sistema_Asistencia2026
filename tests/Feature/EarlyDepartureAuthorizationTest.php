<?php

use App\Models\BiometricDevice;
use App\Models\EarlyDepartureAuthorization;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\BiometricAttendanceRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->school = School::query()->create(['code' => 'EXIT', 'name' => 'Escuela Salidas', 'attendance_cooldown_minutes' => 10, 'attendance_entry_time' => '08:00', 'attendance_exit_time' => '13:30', 'attendance_late_grace_minutes' => 5, 'early_departure_token' => str_repeat('a', 64)]);
    $this->student = Student::query()->create(['school_id' => $this->school->id, 'matricula' => 'E-001', 'nombre' => 'Ana', 'apellido' => 'Pérez', 'curso' => 'Primero A', 'id_lector' => '101']);
    $this->secondStudent = Student::query()->create(['school_id' => $this->school->id, 'matricula' => 'E-002', 'nombre' => 'Luis', 'apellido' => 'Díaz', 'curso' => 'Primero A', 'id_lector' => '102']);
    $this->device = BiometricDevice::query()->create(['school_id' => $this->school->id, 'key' => 'exit-reader', 'name' => 'Salida', 'mac_address' => '00:17:61:11:18:f7', 'network' => '192.168.30']);
});

test('public school link authorizes several students for the current day', function () {
    $this->post(route('public.early-departures.store', $this->school->early_departure_token), [
        'student_ids' => [$this->student->id, $this->secondStudent->id],
        'authorized_by_name' => 'María Secretaría',
        'reason' => 'Cita médica',
    ])->assertRedirect();

    expect(EarlyDepartureAuthorization::query()->count())->toBe(2)
        ->and(EarlyDepartureAuthorization::query()->where('authorized_by_name', 'María Secretaría')->count())->toBe(2);

    $this->get(route('public.early-departures.show', $this->school->early_departure_token))
        ->assertOk()->assertSee('Ana Pérez')->assertSee('Luis Díaz')->assertSee('Autorizado');
});

test('public link cannot authorize a student from another school', function () {
    $otherSchool = School::query()->create(['code' => 'OTHER-EXIT', 'name' => 'Otra Escuela']);
    $outsider = Student::query()->create(['school_id' => $otherSchool->id, 'matricula' => 'O-001', 'nombre' => 'Otro', 'apellido' => 'Alumno', 'curso' => 'Segundo', 'id_lector' => '999']);

    $this->post(route('public.early-departures.store', $this->school->early_departure_token), [
        'student_ids' => [$outsider->id], 'authorized_by_name' => 'Intento externo',
    ])->assertStatus(422);
    expect(EarlyDepartureAuthorization::query()->count())->toBe(0);
});

test('unauthorized second punch before exit time is ignored but official exit is accepted', function () {
    $this->travelTo('2026-09-23 08:00:00');
    $recorder = app(BiometricAttendanceRecorder::class);
    $recorder->record($this->student, $this->device, ['event_source' => 'live']);
    $this->travelTo('2026-09-23 08:01:00');
    $earlyAttempt = $recorder->record($this->student, $this->device, ['event_source' => 'live']);
    $this->travelTo('2026-09-23 13:30:00');
    $officialExit = $recorder->record($this->student, $this->device, ['event_source' => 'live']);
    $this->travelBack();

    expect($earlyAttempt['ignored'])->toBeTrue()
        ->and($earlyAttempt['attendance']->ignored_reason)->toBe('early_departure_not_authorized')
        ->and($officialExit['ignored'])->toBeFalse()
        ->and($officialExit['attendance']->tipo)->toBe('Salida')
        ->and($officialExit['attendance']->is_early_departure)->toBeFalse();
});

test('authorized student can record an early departure and authorization is consumed', function () {
    $this->travelTo('2026-09-23 08:00:00');
    $recorder = app(BiometricAttendanceRecorder::class);
    $recorder->record($this->student, $this->device, ['event_source' => 'live']);
    $authorization = EarlyDepartureAuthorization::query()->create(['school_id' => $this->school->id, 'student_id' => $this->student->id, 'authorized_for' => today(), 'authorized_by_name' => 'Dirección']);
    $this->travelTo('2026-09-23 08:01:00');
    $exit = $recorder->record($this->student, $this->device, ['event_source' => 'live']);
    $this->travelBack();

    expect($exit['ignored'])->toBeFalse()
        ->and($exit['attendance']->tipo)->toBe('Salida')
        ->and($exit['attendance']->is_early_departure)->toBeTrue()
        ->and($authorization->fresh()->attendance_id)->toBe($exit['attendance']->id)
        ->and($authorization->fresh()->used_at)->not->toBeNull();
});

test('school administrator can generate and revoke its public exit link', function () {
    $this->school->update(['early_departure_token' => null]);
    $admin = User::factory()->create(['role' => 'admin', 'school_id' => $this->school->id]);
    $session = ['user' => ['id' => $admin->id, 'role' => 'admin', 'school_id' => $this->school->id]];

    $this->withSession($session)->post(route('schools.early-departures.generate', $this->school))->assertRedirect();
    expect($this->school->fresh()->early_departure_token)->toHaveLength(64);
    $this->withSession($session)->delete(route('schools.early-departures.revoke', $this->school))->assertRedirect();
    expect($this->school->fresh()->early_departure_token)->toBeNull();
});
