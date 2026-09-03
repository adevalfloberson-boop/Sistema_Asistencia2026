<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\SystemAuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SuperAdminController extends Controller
{
    public function index(Request $request): View
    {
        $schools = School::query()
            ->withCount(['users', 'students', 'devices'])
            ->with(['users' => fn ($query) => $query->where('role', 'admin')->where('is_active', true)->orderBy('name')])
            ->orderBy('name')
            ->get();

        $logs = SystemAuditLog::query()
            ->with(['actor:id,name,username', 'school:id,name,code'])
            ->latest()
            ->take(25)
            ->get();

        return view('dashboards.superadmin', [
            'schools' => $schools,
            'logs' => $logs,
            'summary' => [
                'schools' => $schools->count(),
                'activeSchools' => $schools->where('is_active', true)->count(),
                'suspendedSchools' => $schools->where('is_active', false)->count(),
                'administrators' => $schools->sum(fn (School $school): int => $school->users->count()),
            ],
        ]);
    }

    public function storeSchool(Request $request): RedirectResponse
    {
        $validated = $this->schoolValidated($request);
        $school = School::query()->create($validated);
        $this->audit($request, 'school.created', "Institución {$school->name} creada.", $school, ['code' => $school->code]);

        return to_route('dashboard.superadmin')->with('success', 'Institución creada. Ahora crea su administrador maestro.');
    }

    public function updateSchool(Request $request, School $school): RedirectResponse
    {
        $validated = $this->schoolValidated($request, $school);
        $school->update($validated);
        $this->audit($request, 'school.updated', "Perfil de {$school->name} actualizado.", $school);

        return to_route('dashboard.superadmin')->with('success', 'Perfil institucional actualizado.');
    }

    public function changeSchoolStatus(Request $request, School $school): RedirectResponse
    {
        $validated = $request->validate(['is_active' => ['required', 'boolean']]);
        $school->update(['is_active' => $validated['is_active']]);
        $action = $school->is_active ? 'activada' : 'suspendida';
        $this->audit($request, 'school.status_changed', "Institución {$school->name} {$action}.", $school, ['is_active' => $school->is_active]);

        return to_route('dashboard.superadmin')->with('success', "Institución {$action}.");
    }

    public function storeAdministrator(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', Rule::unique('users')->where(fn ($query) => $query->where('school_id', $request->integer('school_id')))],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:10', 'confirmed'],
        ]);

        $administrator = User::query()->create([
            'school_id' => $validated['school_id'],
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'admin',
            'is_active' => true,
        ]);
        $this->audit($request, 'administrator.created', "Administrador {$administrator->username} creado.", $administrator->school, ['administrator_id' => $administrator->id]);

        return to_route('dashboard.superadmin')->with('success', 'Administrador maestro creado.');
    }

    public function resetAdministratorPassword(Request $request, User $administrator): RedirectResponse
    {
        abort_unless($administrator->role === 'admin' && $administrator->school_id !== null, 422);
        $validated = $request->validate(['password' => ['required', 'string', 'min:10', 'confirmed']]);
        $administrator->update(['password' => $validated['password']]);
        $this->audit($request, 'administrator.password_reset', "Contraseña de {$administrator->username} restablecida.", $administrator->school, ['administrator_id' => $administrator->id]);

        return to_route('dashboard.superadmin')->with('success', 'Contraseña administrativa restablecida.');
    }

    public function downloadBackup(Request $request, School $school): Response
    {
        $payload = [
            'generated_at' => now()->toIso8601String(),
            'school' => $school->toArray(),
            'users' => $school->users()->get()->toArray(),
            'courses' => $school->courses()->get()->toArray(),
            'students' => $school->students()->get()->toArray(),
            'devices' => $school->devices()->get()->toArray(),
            'attendances' => $school->attendances()->get()->toArray(),
        ];
        $this->audit($request, 'backup.downloaded', "Respaldo cifrado descargado para {$school->name}.", $school);

        return response(Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR)), 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="respaldo-cifrado-'.$school->code.'-'.now()->format('Ymd-His').'.backup"',
        ]);
    }

    /** @return array<string, mixed> */
    private function schoolValidated(Request $request, ?School $school = null): array
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('schools', 'code')->ignore($school)],
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:100'],
            'tax_id' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'logo_path' => ['nullable', 'url', 'max:2048'],
            'active_modules' => ['nullable', 'array'],
            'active_modules.*' => ['in:attendance'],
        ]);

        $validated['code'] = strtoupper($validated['code']);
        $validated['active_modules'] = $validated['active_modules'] ?? [];

        return $validated;
    }

    /** @param array<string, mixed> $context */
    private function audit(Request $request, string $event, string $description, ?School $school = null, array $context = []): void
    {
        SystemAuditLog::query()->create([
            'actor_id' => $request->session()->get('user.id'),
            'school_id' => $school?->id,
            'event' => $event,
            'description' => $description,
            'ip_address' => $request->ip(),
            'context' => $context,
        ]);
    }
}
