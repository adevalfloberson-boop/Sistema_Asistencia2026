<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StudentController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $this->applyAdministratorSchool($request);
        $validated = $this->validated($request);
        $course = Course::query()->findOrFail($validated['course_id']);

        $attributes = $this->studentAttributes($validated, $course);
        $attributes['photo_path'] = $this->storePhoto($request, (int) $validated['school_id']);

        Student::query()->create($attributes);

        return to_route('dashboard.admin.page', 'students')
            ->with('success', 'Estudiante registrado y vinculado al lector correctamente.');
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $this->applyAdministratorSchool($request);
        $validated = $this->validated($request, $student);
        $course = Course::query()->findOrFail($validated['course_id']);
        $attributes = $this->studentAttributes($validated, $course);
        $newPhotoPath = $request->hasFile('photo')
            ? $this->storePhoto($request, (int) $validated['school_id'])
            : null;
        $previousPhotoPath = $student->photo_path;

        if ($newPhotoPath !== null) {
            $attributes['photo_path'] = $newPhotoPath;
        }

        $student->update($attributes);

        if ($newPhotoPath !== null && $previousPhotoPath !== null) {
            Storage::disk('public')->delete($previousPhotoPath);
        }

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
            'sexo' => ['nullable', Rule::in(['Femenino', 'Masculino'])],
            'father_email' => ['nullable', 'email', 'max:255'],
            'mother_email' => ['nullable', 'email', 'max:255'],
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
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
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

    private function applyAdministratorSchool(Request $request): void
    {
        if ($request->session()->get('user.role') === 'admin') {
            $request->merge(['school_id' => $request->session()->get('user.school_id')]);
        }
    }

    private function storePhoto(Request $request, int $schoolId): ?string
    {
        if (! $request->hasFile('photo')) {
            return null;
        }

        $path = $request->file('photo')->store("students/photos/{$schoolId}", 'public');

        if ($path === false) {
            throw ValidationException::withMessages([
                'photo' => 'No se pudo guardar la fotografía. Inténtalo nuevamente.',
            ]);
        }

        return $path;
    }
}
