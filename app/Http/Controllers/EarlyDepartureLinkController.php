<?php

namespace App\Http\Controllers;

use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EarlyDepartureLinkController extends Controller
{
    public function generate(Request $request, School $school): RedirectResponse
    {
        $school->update(['early_departure_token' => Str::random(64)]);

        return back()->with('success', 'Enlace público de autorizaciones generado.');
    }

    public function revoke(Request $request, School $school): RedirectResponse
    {
        $school->update(['early_departure_token' => null]);

        return back()->with('success', 'Enlace público revocado.');
    }
}
