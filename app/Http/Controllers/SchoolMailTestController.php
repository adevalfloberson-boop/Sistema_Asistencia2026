<?php

namespace App\Http\Controllers;

use App\Mail\AttendanceRecordedMail;
use App\Models\Attendance;
use App\Models\School;
use App\Models\Student;
use App\Services\SchoolMailerFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class SchoolMailTestController extends Controller
{
    public function __invoke(Request $request, School $school, SchoolMailerFactory $mailerFactory): RedirectResponse
    {
        $validated = $request->validate(['test_email' => ['required', 'email', 'max:255']]);
        $setting = $school->notificationSetting;

        if ($setting === null || blank($setting->smtp_host) || blank($setting->from_address)) {
            return back()->with('error', 'Guarda una configuración SMTP completa antes de realizar la prueba.');
        }

        $attendance = new Attendance([
            'school_id' => $school->id,
            'tipo' => 'Entrada',
            'fecha_hora' => now(),
        ]);
        $student = new Student([
            'nombre' => 'Estudiante',
            'apellido' => 'de Prueba',
        ]);
        $attendance->setRelation('school', $school->load('notificationSetting'));
        $attendance->setRelation('student', $student);

        try {
            $mailerFactory->make($setting)
                ->to($validated['test_email'])
                ->send((new AttendanceRecordedMail($attendance, true))->from($setting->from_address, $setting->from_name));
        } catch (Throwable $exception) {
            Log::warning('Falló una prueba de correo SMTP.', [
                'school_id' => $school->id,
                'exception' => $exception::class,
            ]);

            return back()->with('error', 'No se pudo enviar el correo. Verifica servidor, puerto, seguridad y credenciales.');
        }

        return back()->with('success', 'Correo enviado correctamente.');
    }
}
