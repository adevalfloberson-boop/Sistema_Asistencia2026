<?php

namespace App\Http\Controllers;

use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SchoolMailSettingsController extends Controller
{
    public function update(Request $request, School $school): RedirectResponse
    {
        $validated = $request->validate([
            'smtp_host' => ['required', 'string', 'max:255'],
            'smtp_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'smtp_security' => ['required', Rule::in(['none', 'starttls', 'ssl_tls'])],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:2048'],
            'from_address' => ['required', 'email', 'max:255'],
            'from_name' => ['required', 'string', 'max:255'],
        ]);

        if (blank($validated['smtp_password'] ?? null)) {
            unset($validated['smtp_password']);
        }

        $school->notificationSetting()->updateOrCreate([], $validated);

        return to_route('dashboard.admin.page', 'settings')
            ->with('success', 'Configuración SMTP guardada de forma segura.');
    }
}
