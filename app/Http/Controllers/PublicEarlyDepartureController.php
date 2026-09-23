<?php

namespace App\Http\Controllers;

use App\Models\EarlyDepartureAuthorization;
use App\Models\School;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PublicEarlyDepartureController extends Controller
{
    public function show(Request $request, string $token): View
    {
        $school = $this->schoolFromToken($token);
        $search = trim($request->string('search')->toString());
        $students = Student::query()
            ->with('course')
            ->where('school_id', $school->id)
            ->where('is_active', true)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($studentQuery) use ($search): void {
                    $studentQuery->where('nombre', 'like', "%{$search}%")
                        ->orWhere('apellido', 'like', "%{$search}%")
                        ->orWhere('matricula', 'like', "%{$search}%")
                        ->orWhere('id_lector', 'like', "%{$search}%");
                });
            })
            ->orderBy('nombre')
            ->orderBy('apellido')
            ->paginate(40)
            ->withQueryString();
        $authorizations = EarlyDepartureAuthorization::query()
            ->with('student')
            ->where('school_id', $school->id)
            ->whereDate('authorized_for', today())
            ->get()
            ->keyBy('student_id');

        return view('public.early-departures', compact('school', 'students', 'authorizations', 'token', 'search'));
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $school = $this->schoolFromToken($token);
        $validated = $request->validate([
            'student_ids' => ['required', 'array', 'min:1', 'max:100'],
            'student_ids.*' => ['required', 'integer'],
            'authorized_by_name' => ['required', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);
        $studentIds = Student::query()
            ->where('school_id', $school->id)
            ->where('is_active', true)
            ->whereIn('id', array_unique($validated['student_ids']))
            ->pluck('id');
        abort_unless($studentIds->count() === count(array_unique($validated['student_ids'])), 422);

        DB::transaction(function () use ($school, $studentIds, $validated): void {
            foreach ($studentIds as $studentId) {
                EarlyDepartureAuthorization::query()->updateOrCreate(
                    ['school_id' => $school->id, 'student_id' => $studentId, 'authorized_for' => today()],
                    [
                        'authorized_by_name' => $validated['authorized_by_name'],
                        'reason' => $validated['reason'] ?? null,
                        'attendance_id' => null,
                        'used_at' => null,
                    ],
                );
            }
        });

        return back()->with('success', $studentIds->count().' estudiante(s) autorizado(s) para salida anticipada.');
    }

    public function destroy(string $token, EarlyDepartureAuthorization $authorization): RedirectResponse
    {
        $school = $this->schoolFromToken($token);
        abort_unless($authorization->school_id === $school->id && $authorization->authorized_for->isToday(), 404);
        abort_if($authorization->used_at !== null, 422, 'La autorización ya fue utilizada.');
        $authorization->delete();

        return back()->with('success', 'Autorización retirada.');
    }

    private function schoolFromToken(string $token): School
    {
        return School::query()->where('early_departure_token', $token)->where('is_active', true)->firstOrFail();
    }
}
