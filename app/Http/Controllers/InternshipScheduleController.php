<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InternshipScheduleController extends Controller
{
    public function update(Request $request, Course $course): RedirectResponse
    {
        $validated = $request->validate([
            'internship_weekday' => ['nullable', 'integer', Rule::in(range(1, 7))],
        ]);

        $course->update(['internship_weekday' => $validated['internship_weekday'] ?? null]);

        return back()->with('success', 'El día de pasantía del curso fue actualizado.');
    }
}
