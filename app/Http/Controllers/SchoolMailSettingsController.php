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
        $deviceAlertRecipients = $request->input('device_alert_recipients', []);
        $request->merge([
            'device_alert_recipients' => is_array($deviceAlertRecipients)
                ? array_values(array_filter($deviceAlertRecipients, fn (mixed $email): bool => filled($email)))
                : $deviceAlertRecipients,
        ]);

        $validated = $request->validate([
            'smtp_host' => ['required', 'string', 'max:255'],
            'smtp_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'smtp_security' => ['required', Rule::in(['none', 'starttls', 'ssl_tls'])],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:2048'],
            'from_address' => ['required', 'email', 'max:255'],
            'from_name' => ['required', 'string', 'max:255'],
            'notification_message' => ['nullable', 'string', 'max:1000'],
            'device_alerts_enabled' => ['nullable', 'boolean'],
            'device_alert_recipients' => ['nullable', 'required_if:device_alerts_enabled,1', 'array', 'max:10'],
            'device_alert_recipients.*' => ['required', 'email', 'distinct', 'max:255'],
            'developer_branding_enabled' => ['nullable', 'boolean'],
            'developer_name' => ['nullable', 'string', 'max:255'],
            'developer_message' => ['nullable', 'string', 'max:1000'],
            'developer_phone' => ['nullable', 'string', 'max:50'],
            'developer_email' => ['nullable', 'email', 'max:255'],
            'developer_website' => ['nullable', 'url:http,https', 'max:255'],
        ]);

        $validated['developer_branding_enabled'] = (bool) ($validated['developer_branding_enabled'] ?? false);
        $validated['device_alerts_enabled'] = (bool) ($validated['device_alerts_enabled'] ?? false);

        $validated['device_alert_email'] = implode(', ', $validated['device_alert_recipients'] ?? []);
        unset($validated['device_alert_recipients']);

        if (blank($validated['smtp_password'] ?? null)) {
            unset($validated['smtp_password']);
        }

        $school->notificationSetting()->updateOrCreate([], $validated);

        return to_route('dashboard.admin.page', 'settings')
            ->with('success', 'Configuración SMTP guardada de forma segura.');
    }
}
