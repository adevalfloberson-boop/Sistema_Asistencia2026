<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CourseController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        Course::query()->create($this->validated($request));

        return to_route('dashboard.admin.page', 'courses')
            ->with('success', 'Curso creado correctamente.');
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $validated = $this->validated($request, $course);
        $course->update($validated);
        $course->students()->update([
            'curso' => $course->name,
            'area' => $course->area,
            'seccion' => $course->section,
        ]);

        return to_route('dashboard.admin.page', 'courses')
            ->with('success', 'Curso y alumnos asociados actualizados.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        $course->update(['is_active' => false]);

        return to_route('dashboard.admin.page', 'courses')
            ->with('success', 'Curso desactivado sin borrar estudiantes ni historial.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Course $course = null): array
    {
        return $request->validate([
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('courses')->where(
                    fn ($query) => $query->where('school_id', $request->integer('school_id')),
                )->ignore($course),
            ],
            'name' => ['required', 'string', 'max:255'],
            'grade' => ['nullable', 'string', 'max:100'],
            'area' => ['nullable', 'string', 'max:100'],
            'section' => ['nullable', 'string', 'max:50'],
            'shift' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
