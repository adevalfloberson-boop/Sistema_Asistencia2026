<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $course = Course::query()->findOrFail($validated['course_id']);

        Student::query()->create($this->studentAttributes($validated, $course));

        return to_route('dashboard.admin.page', 'students')
            ->with('success', 'Estudiante registrado y vinculado al lector correctamente.');
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $validated = $this->validated($request, $student);
        $course = Course::query()->findOrFail($validated['course_id']);
        $student->update($this->studentAttributes($validated, $course));

        return to_route('dashboard.admin.page', 'students')
            ->with('success', 'Datos del estudiante actualizados.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        $student->update(['is_active' => false]);

        return to_route('dashboard.admin.page', 'students')
            ->with('success', 'Estudiante desactivado sin borrar su historial.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Student $student = null): array
    {
        return $request->validate([
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'course_id' => [
                'required',
                'integer',
                Rule::exists('courses', 'id')->where(
                    fn ($query) => $query->where('school_id', $request->integer('school_id'))->where('is_active', true),
                ),
            ],
            'matricula' => ['required', 'string', 'max:255', Rule::unique('students')->ignore($student)],
            'nombre' => ['required', 'string', 'max:255'],
            'apellido' => ['required', 'string', 'max:255'],
            'numero_lista' => [
                'nullable',
                'integer',
                'min:1',
                'max:999',
                Rule::unique('students')->where(
                    fn ($query) => $query->where('course_id', $request->integer('course_id')),
                )->ignore($student),
            ],
            'id_lector' => ['required', 'string', 'max:255', Rule::unique('students')->ignore($student)],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function studentAttributes(array $validated, Course $course): array
    {
        return [
            ...$validated,
            'curso' => $course->name,
            'area' => $course->area,
            'seccion' => $course->section,
            'is_active' => true,
        ];
    }
}
