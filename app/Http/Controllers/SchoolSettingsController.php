<?php

namespace App\Http\Controllers;

use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SchoolSettingsController extends Controller
{
    public function __invoke(Request $request, School $school): RedirectResponse
    {
        $validated = $request->validate([
            'attendance_cooldown_minutes' => ['required', 'integer', 'min:1', 'max:120'],
            'attendance_entry_time' => ['required', 'date_format:H:i'],
            'attendance_exit_time' => ['required', 'date_format:H:i', 'after:attendance_entry_time'],
            'attendance_late_grace_minutes' => ['required', 'integer', 'min:0', 'max:180'],
        ]);

        $school->update($validated);

        return to_route('dashboard.admin.page', 'settings')
            ->with('success', 'Horario y reglas de asistencia actualizados.');
    }
}
