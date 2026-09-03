<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ViewerDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $viewer = User::query()->with('school')->findOrFail((int) $request->session()->get('user.id'));
        abort_unless($viewer->school_id !== null, 403);

        $date = $request->filled('date') ? Carbon::parse($request->string('date')->toString()) : today();
        $courseId = $request->integer('course_id') ?: null;
        $search = trim($request->string('student')->toString());
        $courses = Course::query()->where('school_id', $viewer->school_id)->where('is_active', true)->orderBy('name')->get();

        $students = Student::query()
            ->where('school_id', $viewer->school_id)
            ->where('is_active', true)
            ->when($courseId, fn ($query, int $id) => $query->where('course_id', $id))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($studentQuery) use ($search): void {
                    $studentQuery->where('nombre', 'like', "%{$search}%")
                        ->orWhere('apellido', 'like', "%{$search}%")
                        ->orWhere('matricula', 'like', "%{$search}%")
                        ->orWhere('id_lector', 'like', "%{$search}%");
                });
            })
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->get();

        $events = Attendance::query()
            ->where('school_id', $viewer->school_id)
            ->whereDate('fecha_hora', $date)
            ->where('is_ignored', false)
            ->when($courseId, fn ($query, int $id) => $query->whereHas('student', fn ($studentQuery) => $studentQuery->where('course_id', $id)))
            ->latest('fecha_hora')
            ->with('student:id,nombre,apellido,matricula,curso')
            ->take(100)
            ->get();

        $presentStudentIds = $events->where('tipo', 'Entrada')->pluck('student_id')->filter()->unique();

        return view('dashboards.viewer', [
            'viewer' => $viewer,
            'courses' => $courses,
            'students' => $students,
            'events' => $events,
            'date' => $date,
            'courseId' => $courseId,
            'search' => $search,
            'summary' => [
                'students' => $students->count(),
                'present' => $presentStudentIds->count(),
                'absent' => max(0, $students->count() - $presentStudentIds->count()),
                'events' => $events->count(),
            ],
        ]);
    }
}
