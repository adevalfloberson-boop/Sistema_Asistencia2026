<?php

use App\Models\Attendance;
use App\Models\School;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('missing exits are closed automatically using the date specific exit time', function () {
    $school = School::query()->create([
        'code' => 'AUTO-EXIT',
        'name' => 'Centro de cierre',
        'attendance_entry_time' => '08:00',
        'attendance_exit_time' => '16:00',
    ]);
    $school->scheduleExceptions()->create([
        'date' => '2026-09-29',
        'exit_time' => '14:00',
        'reason' => 'Salida especial',
    ]);
    $student = Student::query()->create([
        'school_id' => $school->id,
        'matricula' => 'AUTO-001',
        'nombre' => 'Ana',
        'apellido' => 'Cierre',
        'curso' => '5to A',
        'id_lector' => 'AUTO-1',
    ]);
    Attendance::query()->create([
        'school_id' => $school->id,
        'student_id' => $student->id,
        'matricula' => $student->matricula,
        'id_lector' => $student->id_lector,
        'fecha_hora' => '2026-09-29 08:00:00',
        'curso' => $student->curso,
        'estado' => 'A tiempo',
        'tipo' => 'Entrada',
    ]);

    $this->artisan('attendance:close-missing-exits', ['--date' => '2026-09-29'])
        ->expectsOutput('Salidas automáticas creadas: 1')
        ->assertSuccessful();

    $this->assertDatabaseHas('attendances', [
        'student_id' => $student->id,
        'tipo' => 'Salida',
        'fecha_hora' => '2026-09-29 14:00:00',
        'sync_source' => 'automatic_closure',
    ]);

    $this->artisan('attendance:close-missing-exits', ['--date' => '2026-09-29'])
        ->expectsOutput('Salidas automáticas creadas: 0')
        ->assertSuccessful();
});
