<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\School;
use App\Models\Student;
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
     * @return array{summary: array<string, int|float>, records: Collection<int, array<string, mixed>>, courses: Collection<int, array<string, int|float>>}
     */
    private function dashboardData(School $school): array
    {
        $students = Student::query()->where('school_id', $school->id)->where('is_active', true);
        $attendances = Attendance::query()
            ->where('school_id', $school->id)
            ->where('is_ignored', false)
            ->whereDate('fecha_hora', today());
        $total = (clone $students)->count();
        $present = (clone $attendances)->where('tipo', 'Entrada')->distinct()->count('student_id');
        $late = (clone $attendances)->where('tipo', 'Entrada')->where('is_late', true)->distinct()->count('student_id');

        $courses = (clone $students)->whereNotNull('curso')->distinct()->orderBy('curso')->pluck('curso')
            ->map(function (string $course) use ($students, $attendances): array {
                $courseTotal = (clone $students)->where('curso', $course)->count();
                $coursePresent = (clone $attendances)->where('curso', $course)->where('tipo', 'Entrada')->distinct()->count('student_id');

                return [
                    'name' => $course,
                    'present' => $coursePresent,
                    'total' => $courseTotal,
                    'percentage' => $courseTotal === 0 ? 0 : round(($coursePresent / $courseTotal) * 100, 1),
                ];
            });

        $records = (clone $attendances)->with('student:id,nombre,apellido')->latest('fecha_hora')->take(15)->get()
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
                'absent' => max(0, $total - $present),
                'percentage' => $total === 0 ? 0 : round(($present / $total) * 100, 1),
            ],
            'records' => $records,
            'courses' => $courses,
        ];
    }
}
