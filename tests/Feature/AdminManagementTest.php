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
        'attendance_cooldown_minutes' => 12,
    ])->assertRedirect();

    expect($this->school->fresh()->attendance_cooldown_minutes)->toBe(12);
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
