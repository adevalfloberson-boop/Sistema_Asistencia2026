<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentAttendanceException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PublicDashboardController extends Controller
{
    public function show(string $token): View
    {
        $school = $this->schoolForToken($token);

        return view('dashboards.public-attendance', [
            'school' => $school,
            ...$this->dashboardData($school),
        ]);
    }

    public function activity(string $token): JsonResponse
    {
        $school = $this->schoolForToken($token);

        return response()->json([
            ...$this->dashboardData($school),
            'generated_at' => now()->format('H:i:s'),
        ]);
    }

    public function generate(School $school): RedirectResponse
    {
        $school->update(['public_dashboard_token' => Str::random(64)]);

        return back()->with('success', 'Enlace público generado. Ya puedes compartirlo con dirección.');
    }

    public function revoke(School $school): RedirectResponse
    {
        $school->update(['public_dashboard_token' => null]);

        return back()->with('success', 'El enlace público fue desactivado.');
    }

    private function schoolForToken(string $token): School
    {
        return School::query()
            ->where('public_dashboard_token', $token)
            ->where('is_active', true)
            ->firstOrFail();
    }

    /**
     * @return array{summary: array<string, int|float>, records: Collection<int, array<string, mixed>>, courses: Collection<int, array<string, int|float>>, rosters: array<string, Collection<int, array<string, mixed>>>}
     */
    private function dashboardData(School $school): array
    {
        $students = Student::query()
            ->where('school_id', $school->id)
            ->where('is_active', true)
            ->orderBy('curso')
            ->orderBy('nombre')
            ->orderBy('apellido')
            ->get(['id', 'nombre', 'apellido', 'matricula', 'curso', 'sexo']);
        $attendances = Attendance::query()
            ->where('school_id', $school->id)
            ->where('is_ignored', false)
            ->whereDate('fecha_hora', today());
        $presentStudentIds = (clone $attendances)->where('tipo', 'Entrada')->distinct()->pluck('student_id');
        $lateStudentIds = (clone $attendances)->where('tipo', 'Entrada')->where('is_late', true)->distinct()->pluck('student_id');
        $presentStudents = $students->whereIn('id', $presentStudentIds)->values();
        $lateStudents = $students->whereIn('id', $lateStudentIds)->values();
        $internshipCourseNames = Course::query()
            ->where('school_id', $school->id)
            ->where('internship_weekday', today()->isoWeekday())
            ->pluck('name');
        $excusedStudentIds = StudentAttendanceException::query()
            ->whereDate('date', today())
            ->whereHas('student', fn ($query) => $query->where('school_id', $school->id))
            ->pluck('student_id');
        $absentStudents = $students
            ->whereNotIn('id', $presentStudentIds)
            ->reject(fn (Student $student): bool => $internshipCourseNames->contains($student->curso) || $excusedStudentIds->contains($student->id))
            ->values();
        $total = $students->count();
        $present = $presentStudents->count();
        $late = $lateStudents->count();

        $courses = $students->pluck('curso')->filter()->unique()->sort()->values()
            ->map(function (string $course) use ($students, $presentStudentIds): array {
                $courseStudents = $students->where('curso', $course);
                $coursePresentStudents = $courseStudents->whereIn('id', $presentStudentIds);
                $courseTotal = $courseStudents->count();
                $coursePresent = $coursePresentStudents->count();

                return [
                    'name' => $course,
                    'present' => $coursePresent,
                    'total' => $courseTotal,
                    'percentage' => $courseTotal === 0 ? 0 : round(($coursePresent / $courseTotal) * 100, 1),
                    'female' => $courseStudents->where('sexo', 'Femenino')->count(),
                    'male' => $courseStudents->where('sexo', 'Masculino')->count(),
                    'present_female' => $coursePresentStudents->where('sexo', 'Femenino')->count(),
                    'present_male' => $coursePresentStudents->where('sexo', 'Masculino')->count(),
                ];
            });

        $records = (clone $attendances)->with('student:id,nombre,apellido')->latest('fecha_hora')->get()
            ->map(fn (Attendance $attendance): array => [
                'id' => $attendance->id,
                'name' => $attendance->student
                    ? "{$attendance->student->nombre} {$attendance->student->apellido}"
                    : "ID lector {$attendance->id_lector}",
                'course' => $attendance->curso ?: 'Sin curso',
                'time' => $attendance->fecha_hora->format('H:i:s'),
                'type' => $attendance->tipo,
                'status' => $attendance->is_late ? 'Tardanza' : ($attendance->tipo === 'Entrada' ? 'A tiempo' : 'Salida'),
                'reader' => $attendance->reader_name ?: 'Lector principal',
            ]);

        return [
            'summary' => [
                'total' => $total,
                'present' => $present,
                'late' => $late,
                'absent' => $absentStudents->count(),
                'percentage' => $total === 0 ? 0 : round(($present / $total) * 100, 1),
                'female' => $students->where('sexo', 'Femenino')->count(),
                'male' => $students->where('sexo', 'Masculino')->count(),
                'present_female' => $presentStudents->where('sexo', 'Femenino')->count(),
                'present_male' => $presentStudents->where('sexo', 'Masculino')->count(),
            ],
            'records' => $records,
            'courses' => $courses,
            'rosters' => [
                'present' => $this->studentRoster($presentStudents),
                'late' => $this->studentRoster($lateStudents),
                'absent' => $this->studentRoster($absentStudents),
            ],
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    private function studentRoster(Collection $students): Collection
    {
        return $students->map(fn (Student $student): array => [
            'id' => $student->id,
            'name' => $student->nombre.' '.$student->apellido,
            'registration' => $student->matricula,
            'course' => $student->curso ?: 'Sin curso',
            'sex' => $student->sexo ?: 'Sin especificar',
        ])->values();
    }
}
