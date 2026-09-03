<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AttendanceExcuseController extends Controller
{
    public function store(Request $request, Attendance $attendance): RedirectResponse
    {
        $validated = $request->validate([
            'excuse_type' => ['required', 'in:late,early_departure'],
            'excuse_note' => ['required', 'string', 'max:1000'],
        ]);

        abort_unless(
            ($validated['excuse_type'] === 'late' && $attendance->tipo === 'Entrada')
            || ($validated['excuse_type'] === 'early_departure' && $attendance->tipo === 'Salida'),
            422,
        );

        $attendance->update([
            'excuse_type' => $validated['excuse_type'],
            'excuse_note' => $validated['excuse_note'],
            'excused_by' => $request->session()->get('user.id'),
            'excused_at' => now(),
        ]);

        return back()->with('success', 'Excusa registrada en el ponche.');
    }
}
