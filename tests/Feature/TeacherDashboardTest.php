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
    ])->assertRedirect();

    $this->assertDatabaseHas('class_attendance_verifications', [
        'class_session_id' => $classSession->id,
        'student_id' => $student->id,
        'teacher_id' => $teacher->id,
        'status' => ClassAttendanceVerification::StatusCampusAbsentClass,
        'was_on_campus' => true,
    ]);
});

test('teacher uses the editable monthly cell during an open class', function () {
    ['school' => $school, 'teacher' => $teacher, 'course' => $course] = teacherPanelFixture();

    ClassSession::query()->create([
        'course_id' => $course->id,
        'teacher_id' => $teacher->id,
        'scheduled_at' => now(),
        'started_at' => now(),
        'status' => 'open',
    ]);

    $this->withSession(teacherPanelSession($teacher, $school))
        ->get(route('dashboard.docente', ['course' => $course->id]))
        ->assertOk()
        ->assertSee('data-monthly-status-select', escape: false)
        ->assertSee('Quien todavía no haya ponchado se muestra como A provisional.')
        ->assertSee('Guardar y cerrar asistencia')
        ->assertSee('La entrada tardía a la escuela no marca tardanza en esta clase.');
});

test('starting attendance automatically marks a student present from a school punch without copying tardiness', function () {
    ['school' => $school, 'teacher' => $teacher, 'course' => $course, 'student' => $student] = teacherPanelFixture();

    Attendance::query()->create([
        'school_id' => $school->id,
        'student_id' => $student->id,
        'matricula' => $student->matricula,
        'id_lector' => $student->id_lector,
        'fecha_hora' => now(),
        'curso' => $course->name,
        'estado' => 'Tardanza',
        'tipo' => 'Entrada',
    ]);

    $this->withSession(teacherPanelSession($teacher, $school))
        ->post(route('teacher.sessions.start'), ['course_id' => $course->id])
        ->assertRedirectContains('take_attendance=1');

    $this->assertDatabaseHas('class_attendance_verifications', [
        'student_id' => $student->id,
        'status' => ClassAttendanceVerification::StatusPresent,
        'was_on_campus' => true,
    ]);
    $this->assertDatabaseMissing('class_attendance_verifications', [
        'student_id' => $student->id,
        'status' => ClassAttendanceVerification::StatusLate,
    ]);
});

test('starting attendance keeps a student without a punch as a provisional absence', function () {
    ['school' => $school, 'teacher' => $teacher, 'course' => $course, 'student' => $student] = teacherPanelFixture();

    $this->withSession(teacherPanelSession($teacher, $school))
        ->post(route('teacher.sessions.start'), ['course_id' => $course->id])
        ->assertRedirectContains('take_attendance=1');

    $this->assertDatabaseMissing('class_attendance_verifications', [
        'student_id' => $student->id,
    ]);

    $this->withSession(teacherPanelSession($teacher, $school))
        ->get(route('dashboard.docente', ['course' => $course->id]))
        ->assertOk()
        ->assertSee('Quien todavía no haya ponchado se muestra como A provisional.');
});

test('teacher can synchronize a later school punch without overwriting a manual decision', function () {
    ['school' => $school, 'teacher' => $teacher, 'course' => $course, 'student' => $student] = teacherPanelFixture();
    $classSession = ClassSession::query()->create([
        'course_id' => $course->id,
        'teacher_id' => $teacher->id,
        'scheduled_at' => now(),
        'started_at' => now(),
        'status' => 'open',
    ]);

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

    $session = teacherPanelSession($teacher, $school);
    $this->withSession($session)
        ->post(route('teacher.sessions.synchronize', $classSession))
        ->assertRedirectContains('take_attendance=1');

    $this->assertDatabaseHas('class_attendance_verifications', [
        'student_id' => $student->id,
        'status' => ClassAttendanceVerification::StatusPresent,
    ]);

    $this->withSession($session)->post(route('teacher.verifications.store'), [
        'class_session_id' => $classSession->id,
        'student_id' => $student->id,
        'status' => ClassAttendanceVerification::StatusExcused,
    ])->assertRedirect();

    $this->withSession($session)
        ->post(route('teacher.sessions.synchronize', $classSession))
        ->assertRedirect();

    $this->assertDatabaseHas('class_attendance_verifications', [
        'student_id' => $student->id,
        'status' => ClassAttendanceVerification::StatusExcused,
    ]);
});

test('closing a class finalizes every provisional absence', function () {
    ['school' => $school, 'teacher' => $teacher, 'course' => $course, 'student' => $student] = teacherPanelFixture();
    $classSession = ClassSession::query()->create([
        'course_id' => $course->id,
        'teacher_id' => $teacher->id,
        'scheduled_at' => now(),
        'started_at' => now(),
        'status' => 'open',
    ]);

    $this->withSession(teacherPanelSession($teacher, $school))
        ->post(route('teacher.sessions.close', $classSession))
        ->assertRedirect();

    $this->assertDatabaseHas('class_attendance_verifications', [
        'student_id' => $student->id,
        'status' => ClassAttendanceVerification::StatusAbsentCampus,
        'was_on_campus' => false,
    ]);
    expect($classSession->fresh()->status)->toBe('closed');
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

test('teacher saves the class roster in one operation and closes the session', function () {
    ['school' => $school, 'teacher' => $teacher, 'course' => $course, 'student' => $student] = teacherPanelFixture();
    $secondStudent = Student::query()->create([
        'school_id' => $school->id,
        'course_id' => $course->id,
        'matricula' => 'MAT-101',
        'nombre' => 'María',
        'apellido' => 'Pérez',
        'curso' => $course->name,
        'id_lector' => '6',
        'is_active' => true,
    ]);
    $classSession = ClassSession::query()->create([
        'course_id' => $course->id,
        'teacher_id' => $teacher->id,
        'scheduled_at' => now(),
        'started_at' => now(),
        'status' => 'open',
    ]);

    $this->withSession(teacherPanelSession($teacher, $school))
        ->post(route('teacher.verifications.roster'), [
            'class_session_id' => $classSession->id,
            'attendance' => [
                $student->id => 'present',
                $secondStudent->id => 'absent',
            ],
        ])
        ->assertRedirect(route('dashboard.docente', [
            'course' => $course->id,
            'date' => $classSession->scheduled_at->toDateString(),
        ]));

    $this->assertDatabaseHas('class_attendance_verifications', [
        'student_id' => $student->id,
        'status' => ClassAttendanceVerification::StatusPresent,
    ]);
    $this->assertDatabaseHas('class_attendance_verifications', [
        'student_id' => $secondStudent->id,
        'status' => ClassAttendanceVerification::StatusAbsentCampus,
    ]);
    expect($classSession->fresh()->status)->toBe('closed');
});

test('teacher attendance roster is unified into the monthly register', function () {
    ['school' => $school, 'teacher' => $teacher, 'course' => $course] = teacherPanelFixture();

    ClassSession::query()->create([
        'course_id' => $course->id,
        'teacher_id' => $teacher->id,
        'scheduled_at' => now(),
        'started_at' => now(),
        'status' => 'open',
    ]);

    $this->withSession(teacherPanelSession($teacher, $school))
        ->get(route('dashboard.docente', ['course' => $course->id]))
        ->assertOk()
        ->assertSee('data-monthly-status-select', escape: false)
        ->assertSee('Guardar y cerrar asistencia')
        ->assertSee('class="hidden"', escape: false);
});

test('teacher monthly report converts each three tardies and excuses into one absence', function () {
    ['school' => $school, 'teacher' => $teacher, 'course' => $course, 'student' => $student] = teacherPanelFixture();
    $statuses = [
        'present', 'present', 'late', 'late', 'late', 'present', 'present',
        'excused', 'excused', 'absent_campus', 'present', 'present', 'present', 'excused',
    ];

    foreach ($statuses as $index => $status) {
        $session = ClassSession::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'scheduled_at' => now()->startOfMonth()->addDays($index)->setTime(8, 0),
            'status' => 'closed',
        ]);
        ClassAttendanceVerification::query()->create([
            'class_session_id' => $session->id,
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'status' => $status,
            'was_on_campus' => $status !== 'absent_campus',
            'verified_at' => $session->scheduled_at,
        ]);
    }

    $this->withSession(teacherPanelSession($teacher, $school))
        ->get(route('dashboard.docente', [
            'course' => $course->id,
            'report_month' => now()->format('Y-m'),
            'monthly_classes' => 5,
        ]))
        ->assertOk()
        ->assertSee('Reporte por estudiantes')
        ->assertSee('data-monthly-student-report', escape: false)
        ->assertSee('data-month-date-cell', escape: false)
        ->assertSee('11/14')
        ->assertSee('78.57%')
        ->assertDontSee('name="monthly_classes"', escape: false)
        ->assertViewHas('monthlySessions', fn ($sessions): bool => $sessions->count() === 14)
        ->assertViewHas('monthlyReport', function ($report): bool {
            $firstStudent = $report->first();

            return $firstStudent['equivalent_absences'] === 3
                && $firstStudent['credited_attendance'] === 11
                && $firstStudent['percentage'] === 78.57
                && $firstStudent['daily_statuses']->pluck('code')->all() === [
                    'P', 'P', 'T', 'T', 'T', 'P', 'P', 'E', 'E', 'A', 'P', 'P', 'P', 'E',
                ];
        });
});
