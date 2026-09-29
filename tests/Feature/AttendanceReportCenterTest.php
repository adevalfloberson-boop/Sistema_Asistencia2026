<?php

use App\Models\ClassAttendanceVerification;
use App\Models\ClassSession;
use App\Models\Course;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function reportCenterFixture(): array
{
    $school = School::query()->create(['code' => 'REP-CENTER', 'name' => 'Centro de Reportes']);
    $admin = User::factory()->create(['school_id' => $school->id, 'role' => 'admin', 'username' => 'report-admin']);
    $teacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
    $course = Course::query()->create(['school_id' => $school->id, 'code' => '4A', 'name' => '4to A', 'grade' => '4to', 'section' => 'A', 'area' => 'Académica']);
    $student = Student::query()->create(['school_id' => $school->id, 'course_id' => $course->id, 'matricula' => 'REP-001', 'nombre' => 'Ana', 'apellido' => 'Reporte', 'curso' => $course->name, 'sexo' => 'Femenino', 'is_active' => true]);

    foreach (range(1, 3) as $day) {
        $classSession = ClassSession::query()->create(['course_id' => $course->id, 'teacher_id' => $teacher->id, 'scheduled_at' => "2026-09-0{$day} 08:00:00", 'status' => 'closed']);
        ClassAttendanceVerification::query()->create(['class_session_id' => $classSession->id, 'student_id' => $student->id, 'teacher_id' => $teacher->id, 'status' => 'late', 'was_on_campus' => true, 'verified_at' => $classSession->scheduled_at]);
    }

    return compact('admin', 'course', 'school', 'student');
}

function reportCenterSession(User $admin, School $school): array
{
    return ['user' => ['id' => $admin->id, 'username' => $admin->username, 'role' => 'admin', 'school_id' => $school->id, 'institution_code' => $school->code]];
}

test('generated report opens a modal with academic calculations and risk follow up', function () {
    ['admin' => $admin, 'course' => $course, 'school' => $school, 'student' => $student] = reportCenterFixture();

    $this->withSession(reportCenterSession($admin, $school))
        ->get(route('dashboard.admin.page', ['page' => 'reports', 'show_report' => 1, 'report_type' => 'course', 'report_start' => '2026-09-01', 'report_end' => '2026-09-30', 'course' => $course->name, 'report_student_id' => $student->id, 'report_risk_threshold' => 80]))
        ->assertOk()
        ->assertSee('data-generated-report-dialog', escape: false)
        ->assertSee('data-report-autopen', escape: false)
        ->assertSee('Asistencia individual por curso')
        ->assertSee('2/3')
        ->assertSee('66.67%')
        ->assertViewHas('reportClassSummary', fn (array $summary): bool => $summary['sessions'] === 3 && $summary['late'] === 3)
        ->assertViewHas('reportRiskStudents', fn ($students): bool => $students->count() === 1);
});

test('administrator exports the filtered class report as an excel compatible csv', function () {
    ['admin' => $admin, 'course' => $course, 'school' => $school, 'student' => $student] = reportCenterFixture();

    $this->withSession(reportCenterSession($admin, $school))
        ->get(route('reports.export', ['report_start' => '2026-09-01', 'report_end' => '2026-09-30', 'course' => $course->name, 'report_student_id' => $student->id]))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8')
        ->assertDownload();
});
