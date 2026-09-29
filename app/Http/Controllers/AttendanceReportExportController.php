<?php

namespace App\Http\Controllers;

use App\Models\ClassAttendanceVerification;
use App\Models\ClassSession;
use App\Models\Course;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceReportExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'report_start' => ['required', 'date'],
            'report_end' => ['required', 'date', 'after_or_equal:report_start'],
            'course' => ['nullable', 'string', 'max:120'],
            'report_area' => ['nullable', 'string', 'max:120'],
            'report_sex' => ['nullable', 'in:all,Femenino,Masculino,Sin especificar'],
            'report_student_id' => ['nullable', 'integer', 'exists:students,id'],
        ]);

        $user = $request->session()->get('user');
        $schoolId = ($user['role'] ?? null) === 'admin' ? (int) ($user['school_id'] ?? 0) : null;
        abort_unless(($user['role'] ?? null) === 'superadmin' || $schoolId > 0, 403);
        $start = Carbon::parse($validated['report_start'])->startOfDay();
        $end = Carbon::parse($validated['report_end'])->endOfDay();
        $courseQuery = Course::query()
            ->when($schoolId, fn ($query, int $id) => $query->where('school_id', $id))
            ->when($validated['course'] ?? null, fn ($query, string $course) => $query->where('name', $course))
            ->when($validated['report_area'] ?? null, fn ($query, string $area) => $query->where('area', $area));
        $courses = $courseQuery->get();
        $sessions = ClassSession::query()
            ->with('verifications')
            ->whereIn('course_id', $courses->modelKeys())
            ->whereBetween('scheduled_at', [$start, $end])
            ->get();
        $students = Student::query()
            ->where('is_active', true)
            ->when($schoolId, fn ($query, int $id) => $query->where('school_id', $id))
            ->when($validated['course'] ?? null, fn ($query, string $course) => $query->where('curso', $course))
            ->when($validated['report_student_id'] ?? null, fn ($query, int $id) => $query->whereKey($id))
            ->when(($validated['report_sex'] ?? 'all') === 'Femenino', fn ($query) => $query->where('sexo', 'Femenino'))
            ->when(($validated['report_sex'] ?? 'all') === 'Masculino', fn ($query) => $query->where('sexo', 'Masculino'))
            ->when(($validated['report_sex'] ?? 'all') === 'Sin especificar', fn ($query) => $query->whereNull('sexo'))
            ->orderBy('curso')
            ->orderBy('apellido')
            ->get();
        $verificationsByStudent = $sessions->flatMap->verifications->groupBy('student_id');
        $sessionsByCourse = $sessions->groupBy('course_id');
        $filename = "reporte-asistencia-{$start->format('Ymd')}-{$end->format('Ymd')}.csv";

        return response()->streamDownload(function () use ($end, $sessionsByCourse, $start, $students, $verificationsByStudent): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['REPORTE DE ASISTENCIA', $start->format('d/m/Y').' - '.$end->format('d/m/Y')]);
            fputcsv($output, ['Matrícula', 'Apellidos', 'Nombres', 'Curso', 'Sexo', 'Clases', 'P', 'T', 'E', 'A', 'Inasistencias equivalentes', 'Asistencia válida', 'Porcentaje']);

            foreach ($students as $student) {
                $statuses = $verificationsByStudent->get($student->id, collect())->pluck('status');
                $classes = $sessionsByCourse->get($student->course_id, collect())->count();
                $present = $statuses->filter(fn (string $status): bool => $status === ClassAttendanceVerification::StatusPresent)->count();
                $late = $statuses->filter(fn (string $status): bool => $status === ClassAttendanceVerification::StatusLate)->count();
                $excused = $statuses->filter(fn (string $status): bool => $status === ClassAttendanceVerification::StatusExcused)->count();
                $absent = $statuses->filter(fn (string $status): bool => in_array($status, [ClassAttendanceVerification::StatusCampusAbsentClass, ClassAttendanceVerification::StatusAbsentCampus], true))->count();
                $equivalent = $absent + intdiv($late, 3) + intdiv($excused, 3);
                $credited = max(0, $classes - $equivalent);
                $percentage = $classes === 0 ? 0 : round(($credited / $classes) * 100, 2);
                fputcsv($output, [$student->matricula, $student->apellido, $student->nombre, $student->curso, $student->sexo, $classes, $present, $late, $excused, $absent, $equivalent, $credited, $percentage.'%']);
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
