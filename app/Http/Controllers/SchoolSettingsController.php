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
        ]);

        $school->update($validated);

        return to_route('dashboard.admin')->withFragment('settings')
            ->with('success', 'Tiempo entre ponches actualizado.');
    }
}
