<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TeacherController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $teacher = User::query()->create($this->teacherAttributes($validated));
        $teacher->courses()->sync($validated['course_ids']);

        return to_route('dashboard.admin')->withFragment('teachers')
            ->with('success', 'Docente creado y cursos asignados.');
    }

    public function update(Request $request, User $teacher): RedirectResponse
    {
        abort_unless($teacher->role === 'teacher', 404);
        $validated = $this->validated($request, $teacher);
        $teacher->update($this->teacherAttributes($validated, $teacher));
        $teacher->courses()->sync($validated['course_ids']);

        return to_route('dashboard.admin')->withFragment('teachers')
            ->with('success', 'Docente y asignaciones actualizados.');
    }

    public function destroy(User $teacher): RedirectResponse
    {
        abort_unless($teacher->role === 'teacher', 404);
        $teacher->update(['is_active' => false]);

        return to_route('dashboard.admin')->withFragment('teachers')
            ->with('success', 'Docente desactivado sin borrar sus verificaciones.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?User $teacher = null): array
    {
        return $request->validate([
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:100',
                Rule::unique('users')->where(
                    fn ($query) => $query->where('school_id', $request->integer('school_id')),
                )->ignore($teacher),
            ],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($teacher)],
            'password' => [$teacher === null ? 'required' : 'nullable', 'string', 'min:10', 'confirmed'],
            'course_ids' => ['required', 'array', 'min:1'],
            'course_ids.*' => [
                'integer',
                Rule::exists('courses', 'id')->where(
                    fn ($query) => $query->where('school_id', $request->integer('school_id'))->where('is_active', true),
                ),
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function teacherAttributes(array $validated, ?User $teacher = null): array
    {
        $attributes = [
            'school_id' => $validated['school_id'],
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'role' => 'teacher',
            'is_active' => true,
        ];

        if (! empty($validated['password'])) {
            $attributes['password'] = $validated['password'];
        }

        return $attributes;
    }
}
