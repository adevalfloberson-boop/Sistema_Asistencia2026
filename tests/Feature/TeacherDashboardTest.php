<?php

use App\Models\Attendance;
use App\Models\ClassAttendanceVerification;
use App\Models\ClassSession;
use App\Models\Course;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function teacherPanelFixture(): array
{
    $school = School::query()->create(['code' => 'INST001', 'name' => 'Escuela Central']);
    $teacher = User::factory()->create([
        'school_id' => $school->id,
        'username' => 'docente',
        'role' => 'teacher',
    ]);
    $course = Course::query()->create([
        'school_id' => $school->id,
        'code' => '5TO-A',
        'name' => '5to A',
    ]);
    $teacher->courses()->attach($course);
    $student = Student::query()->create([
        'school_id' => $school->id,
        'course_id' => $course->id,
        'matricula' => 'MAT-100',
        'nombre' => 'Robinson',
        'apellido' => 'Cano',
        'curso' => $course->name,
        'id_lector' => '5',
        'is_active' => true,
    ]);

    return compact('school', 'teacher', 'course', 'student');
}

function teacherPanelSession(User $teacher, School $school): array
{
    return ['user' => [
        'id' => $teacher->id,
        'name' => $teacher->name,
        'username' => $teacher->username,
        'role' => 'teacher',
        'school_id' => $school->id,
        'institution_code' => $school->code,
    ]];
}

test('teacher only sees an assigned course and the biometric campus state', function () {
    ['school' => $school, 'teacher' => $teacher, 'course' => $course, 'student' => $student] = teacherPanelFixture();

    Attendance::query()->create([
        'school_id' => $school->id,
        'student_id' => $student->id,
        'matricula' => $student->matricula,
        'id_lector' => $student->id_lector,
        'fecha_hora' => now(),
        'curso' => $course->name,
        'estado' => 'Entrada',
        'tipo' => 'Entrada',
    ]);

    $this->withSession(teacherPanelSession($teacher, $school))
        ->get(route('dashboard.docente', ['course' => $course->id]))
        ->assertOk()
        ->assertSee('5to A')
        ->assertSee('Robinson Cano')
        ->assertSee('En el plantel');
});

test('teacher can report a student who is on campus but absent from class', function () {
    ['school' => $school, 'teacher' => $teacher, 'course' => $course, 'student' => $student] = teacherPanelFixture();
    $session = teacherPanelSession($teacher, $school);

    Attendance::query()->create([
        'school_id' => $school->id,
        'student_id' => $student->id,
        'matricula' => $student->matricula,
        'id_lector' => $student->id_lector,
        'fecha_hora' => now(),
        'curso' => $course->name,
        'estado' => 'Entrada',
        'tipo' => 'Entrada',
    ]);

    $this->withSession($session)->post(route('teacher.sessions.start'), [
        'course_id' => $course->id,
        'subject' => 'Matemática',
    ])->assertRedirect();

    $classSession = ClassSession::query()->sole();

    $this->withSession($session)->post(route('teacher.verifications.store'), [
        'class_session_id' => $classSession->id,
        'student_id' => $student->id,
        'status' => ClassAttendanceVerification::StatusCampusAbsentClass,
        'note' => 'Está en el plantel, pero no se presentó al aula.',
    ])->assertRedirect();

    $this->assertDatabaseHas('class_attendance_verifications', [
        'class_session_id' => $classSession->id,
        'student_id' => $student->id,
        'teacher_id' => $teacher->id,
        'status' => ClassAttendanceVerification::StatusCampusAbsentClass,
        'was_on_campus' => true,
    ]);
});

test('teacher cannot label a student as on campus when there is no active entry', function () {
    ['school' => $school, 'teacher' => $teacher, 'course' => $course, 'student' => $student] = teacherPanelFixture();
    $classSession = ClassSession::query()->create([
        'course_id' => $course->id,
        'teacher_id' => $teacher->id,
        'scheduled_at' => now(),
        'started_at' => now(),
        'status' => 'open',
    ]);

    $this->withSession(teacherPanelSession($teacher, $school))
        ->from(route('dashboard.docente', ['course' => $course->id]))
        ->post(route('teacher.verifications.store'), [
            'class_session_id' => $classSession->id,
            'student_id' => $student->id,
            'status' => ClassAttendanceVerification::StatusCampusAbsentClass,
            'note' => 'No llegó al aula.',
        ])
        ->assertSessionHasErrors('status');

    $this->assertDatabaseCount('class_attendance_verifications', 0);
});
