<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\ClassAttendanceVerification;
use App\Models\ClassSession;
use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ClassAttendanceController extends Controller
{
    public function start(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'subject' => ['nullable', 'string', 'max:120'],
        ]);

        $teacher = $this->teacher($request);
        $course = $this->assignedCourse($teacher, (int) $validated['course_id']);

        $classSession = ClassSession::query()
            ->where('course_id', $course->id)
            ->where('teacher_id', $teacher->id)
            ->whereDate('scheduled_at', today())
            ->latest('scheduled_at')
            ->first();

        if ($classSession === null) {
            $classSession = ClassSession::query()->create([
                'course_id' => $course->id,
                'teacher_id' => $teacher->id,
                'subject' => $validated['subject'] ?? null,
                'scheduled_at' => now(),
                'started_at' => now(),
                'status' => 'open',
            ]);
        } elseif ($classSession->status === 'closed') {
            $classSession->update([
                'subject' => $validated['subject'] ?? $classSession->subject,
                'started_at' => now(),
                'ended_at' => null,
                'status' => 'open',
            ]);
        }

        return redirect()
            ->route('dashboard.docente', ['course' => $course->id, 'date' => today()->toDateString()])
            ->with('success', "Verificación iniciada para {$course->name}.");
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'class_session_id' => ['required', 'integer', 'exists:class_sessions,id'],
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'status' => ['required', Rule::in(array_keys(ClassAttendanceVerification::statusLabels()))],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $teacher = $this->teacher($request);
        $classSession = ClassSession::query()->with('course')->findOrFail($validated['class_session_id']);
        abort_unless($classSession->teacher_id === $teacher->id, 403);
        abort_if($classSession->status === 'closed', 422, 'La sesión de clase ya está cerrada.');

        $student = Student::query()->findOrFail($validated['student_id']);
        abort_unless(
            $student->course_id === $classSession->course_id
                || ($student->course_id === null && $student->curso === $classSession->course->name),
            403,
        );

        $lastCampusRecord = Attendance::query()
            ->where('student_id', $student->id)
            ->whereDate('fecha_hora', $classSession->scheduled_at)
            ->where('is_ignored', false)
            ->latest('fecha_hora')
            ->first();
        $isOnCampus = $lastCampusRecord?->tipo === 'Entrada';

        if ($validated['status'] === ClassAttendanceVerification::StatusCampusAbsentClass && ! $isOnCampus) {
            return back()->withErrors([
                'status' => 'Ese reporte solo puede usarse si el lector confirma que el estudiante sigue en el plantel.',
            ]);
        }

        ClassAttendanceVerification::query()->updateOrCreate(
            [
                'class_session_id' => $classSession->id,
                'student_id' => $student->id,
            ],
            [
                'teacher_id' => $teacher->id,
                'status' => $validated['status'],
                'was_on_campus' => $isOnCampus,
                'note' => $validated['note'] ?? null,
                'verified_at' => now(),
            ],
        );

        return redirect()
            ->route('dashboard.docente', [
                'course' => $classSession->course_id,
                'date' => $classSession->scheduled_at->toDateString(),
            ])
            ->with('success', "Estado de {$student->nombre} {$student->apellido} actualizado.");
    }

    public function storeRoster(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'class_session_id' => ['required', 'integer', 'exists:class_sessions,id'],
            'attendance' => ['present', 'array'],
            'attendance.*' => ['required', Rule::in(['present', 'late', 'absent'])],
        ]);

        $teacher = $this->teacher($request);
        $classSession = ClassSession::query()->with('course')->findOrFail($validated['class_session_id']);
        abort_unless($classSession->teacher_id === $teacher->id, 403);
        abort_if($classSession->status === 'closed', 422, 'La sesión de clase ya está cerrada.');

        $attendance = collect($validated['attendance'] ?? []);
        $students = Student::query()
            ->whereIn('id', $attendance->keys())
            ->where(function ($query) use ($classSession): void {
                $query->where('course_id', $classSession->course_id)
                    ->orWhere(function ($legacyQuery) use ($classSession): void {
                        $legacyQuery->whereNull('course_id')->where('curso', $classSession->course->name);
                    });
            })
            ->get()
            ->keyBy('id');

        abort_unless($students->count() === $attendance->count(), 403);

        $campusRecords = Attendance::query()
            ->whereIn('student_id', $students->keys())
            ->whereDate('fecha_hora', $classSession->scheduled_at)
            ->where('is_ignored', false)
            ->oldest('fecha_hora')
            ->get()
            ->groupBy('student_id')
            ->map(fn ($records): Attendance => $records->last());

        DB::transaction(function () use ($attendance, $campusRecords, $classSession, $teacher): void {
            foreach ($attendance as $studentId => $status) {
                $isOnCampus = $campusRecords->get((int) $studentId)?->tipo === 'Entrada';
                $storedStatus = $status === 'absent'
                    ? ($isOnCampus
                        ? ClassAttendanceVerification::StatusCampusAbsentClass
                        : ClassAttendanceVerification::StatusAbsentCampus)
                    : $status;

                ClassAttendanceVerification::query()->updateOrCreate(
                    [
                        'class_session_id' => $classSession->id,
                        'student_id' => (int) $studentId,
                    ],
                    [
                        'teacher_id' => $teacher->id,
                        'status' => $storedStatus,
                        'was_on_campus' => $isOnCampus,
                        'verified_at' => now(),
                    ],
                );
            }

            $classSession->update([
                'status' => 'closed',
                'ended_at' => now(),
            ]);
        });

        $pending = max(0, $this->courseStudentCount($classSession) - $attendance->count());
        $message = $pending > 0
            ? "Asistencia guardada. Quedaron {$pending} estudiantes pendientes."
            : 'Asistencia completa guardada correctamente.';

        return redirect()
            ->route('dashboard.docente', [
                'course' => $classSession->course_id,
                'date' => $classSession->scheduled_at->toDateString(),
            ])
            ->with('success', $message);
    }

    public function close(Request $request, ClassSession $classSession): RedirectResponse
    {
        $teacher = $this->teacher($request);
        abort_unless($classSession->teacher_id === $teacher->id, 403);

        $classSession->update([
            'status' => 'closed',
            'ended_at' => now(),
        ]);

        return redirect()
            ->route('dashboard.docente', [
                'course' => $classSession->course_id,
                'date' => $classSession->scheduled_at->toDateString(),
            ])
            ->with('success', 'Sesión cerrada y reporte de asistencia guardado.');
    }

    private function teacher(Request $request): User
    {
        return User::query()
            ->where('role', 'teacher')
            ->where('is_active', true)
            ->findOrFail((int) $request->session()->get('user.id'));
    }

    private function assignedCourse(User $teacher, int $courseId): Course
    {
        return $teacher->courses()->whereKey($courseId)->where('is_active', true)->firstOrFail();
    }

    private function courseStudentCount(ClassSession $classSession): int
    {
        return Student::query()
            ->where('is_active', true)
            ->where(function ($query) use ($classSession): void {
                $query->where('course_id', $classSession->course_id)
                    ->orWhere(function ($legacyQuery) use ($classSession): void {
                        $legacyQuery->whereNull('course_id')->where('curso', $classSession->course->name);
                    });
            })
            ->count();
    }
}
