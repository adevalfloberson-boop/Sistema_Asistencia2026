<?php

use App\Models\Attendance;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('administrator can generate and revoke a public dashboard link', function () {
    $school = School::query()->create(['code' => 'PUB001', 'name' => 'Escuela Pública']);
    $admin = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);
    $session = ['user' => ['id' => $admin->id, 'role' => 'admin', 'school_id' => $school->id]];

    $this->withSession($session)->post(route('schools.public-dashboard.generate', $school))->assertRedirect();

    $token = $school->fresh()->public_dashboard_token;
    expect($token)->toBeString()->toHaveLength(64);
    $this->get(route('public.dashboard.show', $token))->assertOk()->assertSee('Asistencia en tiempo real');

    $this->withSession($session)->delete(route('schools.public-dashboard.revoke', $school))->assertRedirect();
    $this->get(route('public.dashboard.show', $token))->assertNotFound();
});

test('public dashboard shows live attendance without authentication', function () {
    $school = School::query()->create([
        'code' => 'LIVE001',
        'name' => 'Centro Escolar en Vivo',
        'public_dashboard_token' => str_repeat('a', 64),
    ]);
    $student = Student::query()->create([
        'school_id' => $school->id,
        'matricula' => 'EST-002',
        'nombre' => 'María',
        'apellido' => 'Pérez',
        'curso' => '5to A',
        'sexo' => 'Femenino',
        'id_lector' => '2',
    ]);
    Student::query()->create([
        'school_id' => $school->id,
        'matricula' => 'EST-003',
        'nombre' => 'José',
        'apellido' => 'Ausente',
        'curso' => '5to A',
        'sexo' => 'Masculino',
        'id_lector' => '3',
    ]);
    $internshipStudent = Student::query()->create([
        'school_id' => $school->id,
        'matricula' => 'EST-004',
        'nombre' => 'Laura',
        'apellido' => 'Pasantía',
        'curso' => '5to A',
        'sexo' => 'Femenino',
        'id_lector' => '4',
    ]);
    $internshipStudent->attendanceExceptions()->create(['date' => today(), 'reason' => 'Pasantía individual']);
    Attendance::query()->create([
        'school_id' => $school->id,
        'student_id' => $student->id,
        'matricula' => $student->matricula,
        'id_lector' => '2',
        'fecha_hora' => now(),
        'curso' => '5to A',
        'estado' => 'A tiempo',
        'tipo' => 'Entrada',
        'reader_name' => 'Entrada principal',
        'is_late' => false,
        'is_ignored' => false,
    ]);

    $this->get(route('public.dashboard.show', $school->public_dashboard_token))
        ->assertOk()
        ->assertSee('Centro Escolar en Vivo')
        ->assertSee('María Pérez')
        ->assertSee('José Ausente')
        ->assertSee('Actividad en vivo')
        ->assertSee('data-open-dialog="public-total-dialog"', escape: false)
        ->assertSee('data-open-dialog="public-absent-dialog"', escape: false)
        ->assertSee('Hembras')
        ->assertSee('Varones')
        ->assertDontSee('Eventos ADMS sin filtrar');

    $this->getJson(route('public.dashboard.activity', $school->public_dashboard_token))
        ->assertOk()
        ->assertJsonPath('summary.present', 1)
        ->assertJsonPath('summary.absent', 1)
        ->assertJsonPath('summary.female', 2)
        ->assertJsonPath('summary.male', 1)
        ->assertJsonPath('courses.0.present_female', 1)
        ->assertJsonPath('courses.0.male', 1)
        ->assertJsonPath('rosters.absent.0.name', 'José Ausente')
        ->assertJsonMissing(['name' => 'Laura Pasantía'])
        ->assertJsonPath('records.0.name', 'María Pérez');
});
