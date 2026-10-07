<?php

namespace App\Http\Controllers;

use App\Mail\AttendanceRecordedMail;
use App\Models\Attendance;
use App\Models\School;
use App\Models\Student;
use Illuminate\Http\Response;

class SchoolMailPreviewController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(School $school): Response
    {
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

        return response((new AttendanceRecordedMail($attendance, true))->render());
    }
}
