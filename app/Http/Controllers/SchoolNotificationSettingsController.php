<?php

namespace App\Http\Controllers;

use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SchoolNotificationSettingsController extends Controller
{
    public function update(Request $request, School $school): RedirectResponse
    {
        $validated = $request->validate([
            'email_enabled' => ['nullable', 'boolean'],
            'notify_entry' => ['nullable', 'boolean'],
            'notify_exit' => ['nullable', 'boolean'],
            'notify_early_departure' => ['nullable', 'boolean'],
            'send_to_father' => ['nullable', 'boolean'],
            'send_to_mother' => ['nullable', 'boolean'],
        ]);

        $school->notificationSetting()->updateOrCreate([], [
            'email_enabled' => (bool) ($validated['email_enabled'] ?? false),
            'notify_entry' => (bool) ($validated['notify_entry'] ?? false),
            'notify_exit' => (bool) ($validated['notify_exit'] ?? false),
            'notify_early_departure' => (bool) ($validated['notify_early_departure'] ?? false),
            'send_to_father' => (bool) ($validated['send_to_father'] ?? false),
            'send_to_mother' => (bool) ($validated['send_to_mother'] ?? false),
        ]);

        return to_route('dashboard.admin.page', 'settings')
            ->with('success', 'Preferencias de notificación actualizadas.');
    }
}
