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
            'note' => ['nullable', 'required_if:status,campus_absent_class', 'string', 'max:1000'],
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
}
