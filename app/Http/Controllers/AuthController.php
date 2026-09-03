<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'institution_code' => ['nullable', 'string', 'max:50'],
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->where('username', $validated['username'])
            ->where('is_active', true)
            ->first();

        $school = null;

        if ($user?->role !== 'superadmin') {
            $school = School::query()
                ->where('code', strtoupper((string) ($validated['institution_code'] ?? '')))
                ->where('is_active', true)
                ->first();
        }

        if ($user === null
            || ! Hash::check($validated['password'], $user->password)
            || ($user->role !== 'superadmin' && ($school === null || $user->school_id !== $school->id))) {
            return back()
                ->withErrors(['login' => 'Credenciales incorrectas o institución inválida.'])
                ->withInput($request->only('institution_code', 'username'));
        }

        $request->session()->regenerate();
        $request->session()->put('user', [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'role' => $user->role,
            'school_id' => $user->school_id,
            'institution_code' => $school?->code,
        ]);

        return to_route(match ($user->role) {
            'superadmin' => 'dashboard.superadmin',
            'admin' => 'dashboard.admin',
            'teacher' => 'dashboard.docente',
            'viewer' => 'dashboard.viewer',
            default => 'login',
        });
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
