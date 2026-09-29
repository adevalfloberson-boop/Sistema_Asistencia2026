<?php

namespace App\Http\Controllers;

use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SchoolScheduleExceptionController extends Controller
{
    public function store(Request $request, School $school): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'exit_time' => ['required', 'date_format:H:i'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $school->scheduleExceptions()->updateOrCreate(['date' => $validated['date']], $validated);

        return back()->with('success', 'La salida especial fue guardada para la fecha indicada.');
    }
}
