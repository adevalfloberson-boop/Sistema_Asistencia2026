<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\ClassAttendanceVerification;
use App\Models\ClassSession;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class TeacherDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $teacher = User::query()
            ->with(['school', 'courses.school'])
            ->findOrFail((int) $request->session()->get('user.id'));

        $courses = $teacher->courses
            ->where('is_active', true)
            ->sortBy('name')
            ->values();
        $selectedCourse = $courses->firstWhere('id', $request->integer('course')) ?? $courses->first();
        $selectedDate = $this->selectedDate($request);

        $students = $selectedCourse === null
            ? collect()
            : Student::query()
                ->where('school_id', $selectedCourse->school_id)
                ->where('is_active', true)
                ->where(function ($query) use ($selectedCourse): void {
                    $query->where('course_id', $selectedCourse->id)
                        ->orWhere(function ($legacyQuery) use ($selectedCourse): void {
                            $legacyQuery->whereNull('course_id')->where('curso', $selectedCourse->name);
                        });
                })
                ->orderBy('apellido')
                ->orderBy('nombre')
                ->get();

        $classSession = $selectedCourse === null
            ? null
            : ClassSession::query()
                ->with('verifications')
                ->where('course_id', $selectedCourse->id)
                ->where('teacher_id', $teacher->id)
                ->whereDate('scheduled_at', $selectedDate)
                ->latest('scheduled_at')
                ->first();

        $campusRecords = $this->campusRecords($students, $selectedDate);
        $verifications = $classSession?->verifications->keyBy('student_id') ?? collect();
        $roster = $students->map(function (Student $student) use ($campusRecords, $verifications): array {
            $lastCampusRecord = $campusRecords->get($student->id);
            $campusStatus = match ($lastCampusRecord?->tipo) {
                'Entrada' => 'on_campus',
                'Salida' => 'left_campus',
                default => 'no_record',
            };

            return [
                'student' => $student,
                'campus_status' => $campusStatus,
                'campus_time' => $lastCampusRecord?->fecha_hora,
                'verification' => $verifications->get($student->id),
            ];
        });

        $verified = $roster->whereNotNull('verification')->count();
        $present = $roster->filter(
            fn (array $row): bool => in_array($row['verification']?->status, ['present', 'late'], true),
        )->count();
        $campusMissingClass = $roster->filter(
            fn (array $row): bool => $row['verification']?->status === ClassAttendanceVerification::StatusCampusAbsentClass,
        )->count();

        $recentReports = ClassAttendanceVerification::query()
            ->with(['student', 'classSession.course'])
            ->where('teacher_id', $teacher->id)
            ->where('status', ClassAttendanceVerification::StatusCampusAbsentClass)
            ->latest('verified_at')
            ->take(8)
            ->get();

        return view('dashboards.teacher', [
            'teacher' => $teacher,
            'courses' => $courses,
            'selectedCourse' => $selectedCourse,
            'selectedDate' => $selectedDate,
            'classSession' => $classSession,
            'roster' => $roster,
            'statusLabels' => ClassAttendanceVerification::statusLabels(),
            'recentReports' => $recentReports,
            'summary' => [
                'students' => $roster->count(),
                'on_campus' => $roster->where('campus_status', 'on_campus')->count(),
                'verified' => $verified,
                'pending' => max(0, $roster->count() - $verified),
                'present' => $present,
                'campus_missing_class' => $campusMissingClass,
            ],
        ]);
    }

    private function selectedDate(Request $request): Carbon
    {
        $date = $request->string('date')->toString();

        if ($date === '') {
            return today();
        }

        return Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
    }

    /**
     * @param  Collection<int, Student>  $students
     * @return Collection<int, Attendance>
     */
    private function campusRecords(Collection $students, Carbon $date): Collection
    {
        return Attendance::query()
            ->whereIn('student_id', $students->pluck('id'))
            ->whereDate('fecha_hora', $date)
            ->where('is_ignored', false)
            ->oldest('fecha_hora')
            ->get()
            ->groupBy('student_id')
            ->map(fn (Collection $records): Attendance => $records->last());
    }
}
