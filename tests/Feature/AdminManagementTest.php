<?php

use App\Models\Course;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->school = School::query()->create(['code' => 'PILOTO', 'name' => 'Escuela Piloto']);
    $this->admin = User::factory()->create(['role' => 'superadmin']);
    $this->session = ['user' => [
        'id' => $this->admin->id,
        'username' => $this->admin->username,
        'role' => 'superadmin',
        'institution_code' => $this->school->code,
    ]];
});

test('superadministrator manages courses students teachers and the punch interval', function () {
    $this->withSession($this->session)->post(route('courses.store'), [
        'school_id' => $this->school->id,
        'code' => '1A',
        'name' => 'Primero A',
        'grade' => 'Primero',
        'area' => 'Primaria',
        'section' => 'A',
        'shift' => 'Matutina',
    ])->assertRedirect();

    $course = Course::query()->where('code', '1A')->firstOrFail();

    $this->withSession($this->session)->post(route('students.store'), [
        'school_id' => $this->school->id,
        'course_id' => $course->id,
        'matricula' => 'P-001',
        'nombre' => 'Ana',
        'apellido' => 'Pérez',
        'numero_lista' => 1,
        'id_lector' => '101',
    ])->assertRedirect();

    $this->assertDatabaseHas('students', [
        'matricula' => 'P-001',
        'numero_lista' => 1,
        'area' => 'Primaria',
        'curso' => 'Primero A',
        'seccion' => 'A',
    ]);

    $this->withSession($this->session)->post(route('teachers.store'), [
        'school_id' => $this->school->id,
        'name' => 'Docente Piloto',
        'username' => 'docente.piloto',
        'email' => 'docente@piloto.test',
        'password' => 'clave-segura-2026',
        'password_confirmation' => 'clave-segura-2026',
        'course_ids' => [$course->id],
    ])->assertRedirect();

    $teacher = User::query()->where('username', 'docente.piloto')->firstOrFail();
    expect($teacher->courses)->toHaveCount(1);

    $this->withSession($this->session)->put(route('schools.settings.update', $this->school), [
        'attendance_entry_time' => '07:30',
        'attendance_exit_time' => '13:30',
        'attendance_late_grace_minutes' => 5,
    ])->assertRedirect();

    expect($this->school->fresh()->attendance_entry_time->format('H:i'))->toBe('07:30')
        ->and($this->school->fresh()->attendance_late_grace_minutes)->toBe(5);
});

test('student list number is unique inside its course', function () {
    $course = Course::factory()->create(['school_id' => $this->school->id]);
    $payload = [
        'school_id' => $this->school->id,
        'course_id' => $course->id,
        'matricula' => 'P-001',
        'nombre' => 'Ana',
        'apellido' => 'Pérez',
        'numero_lista' => 1,
        'id_lector' => '101',
    ];

    $this->withSession($this->session)->post(route('students.store'), $payload)->assertRedirect();

    $this->withSession($this->session)->post(route('students.store'), [
        ...$payload,
        'matricula' => 'P-002',
        'id_lector' => '102',
    ])->assertSessionHasErrors('numero_lista');
});

test('school administrator registers a student with optional parent emails without selecting a school', function () {
    $admin = User::factory()->create([
        'school_id' => $this->school->id,
        'role' => 'admin',
    ]);
    $course = Course::factory()->create(['school_id' => $this->school->id]);
    $session = ['user' => [
        'id' => $admin->id,
        'username' => $admin->username,
        'role' => 'admin',
        'school_id' => $this->school->id,
        'institution_code' => $this->school->code,
    ]];

    $this->withSession($session)->post(route('students.store'), [
        'course_id' => $course->id,
        'matricula' => 'P-EMAIL-001',
        'nombre' => 'Laura',
        'apellido' => 'Gómez',
        'sexo' => 'Femenino',
        'numero_lista' => 8,
        'id_lector' => '808',
        'father_email' => 'padre@example.com',
        'mother_email' => 'madre@example.com',
    ])->assertRedirect(route('dashboard.admin.page', 'students'));

    $this->assertDatabaseHas('students', [
        'school_id' => $this->school->id,
        'matricula' => 'P-EMAIL-001',
        'sexo' => 'Femenino',
        'father_email' => 'padre@example.com',
        'mother_email' => 'madre@example.com',
    ]);

    $this->withSession($session)->get(route('dashboard.admin.page', 'students'))
        ->assertOk()
        ->assertSee('Registrar estudiante')
        ->assertSee('Femenino')
        ->assertSee('Correo del padre')
        ->assertSee('Correo de la madre')
        ->assertDontSee('Todas las escuelas');
});

test('parent emails are optional and validated when provided', function () {
    $admin = User::factory()->create(['school_id' => $this->school->id, 'role' => 'admin']);
    $course = Course::factory()->create(['school_id' => $this->school->id]);
    $session = ['user' => ['id' => $admin->id, 'role' => 'admin', 'school_id' => $this->school->id]];
    $payload = [
        'course_id' => $course->id,
        'matricula' => 'OPTIONAL-001',
        'nombre' => 'Luis',
        'apellido' => 'Díaz',
        'id_lector' => '909',
    ];

    $this->withSession($session)->post(route('students.store'), $payload)->assertSessionHasNoErrors();

    $this->withSession($session)->post(route('students.store'), [
        ...$payload,
        'matricula' => 'INVALID-001',
        'id_lector' => '910',
        'father_email' => 'correo-invalido',
    ])->assertSessionHasErrors('father_email');
});
