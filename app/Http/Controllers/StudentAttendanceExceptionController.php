<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StudentAttendanceExceptionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $student = Student::query()->findOrFail($validated['student_id']);
        $student->attendanceExceptions()->updateOrCreate(
            ['date' => $validated['date']],
            ['reason' => $validated['reason'] ?: 'Pasantía'],
        );

        return back()->with('success', 'La excepción individual fue guardada.');
    }
}
