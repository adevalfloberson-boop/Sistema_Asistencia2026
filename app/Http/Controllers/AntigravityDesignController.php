<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\BiometricDevice;
use App\Models\BiometricEnrollment;
use App\Models\ClassSession;
use App\Models\Course;
use App\Models\School;
use App\Models\Student;
use App\Models\SystemAuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AntigravityDesignController extends Controller
{
    /**
     * Hub Central / Presentación Comercial del Sistema
     */
    public function hub(): View
    {
        $school = School::query()->where('code', 'SANPATRICIO')->first() ?? School::query()->first();
        $studentCount = Student::query()->count() ?: 35;
        $deviceCount = BiometricDevice::query()->count() ?: 3;
        $attendanceCount = Attendance::query()->whereDate('fecha_hora', today())->count() ?: 28;

        return view('antigravity.hub', compact('school', 'studentCount', 'deviceCount', 'attendanceCount'));
    }

    /**
     * Login Corporativo Split-Screen de Alta Conversión
     */
    public function login(): View
    {
        return view('antigravity.login');
    }

    /**
     * Acceso Rápido de Demostración con 1 Clic
     */
    public function quickLogin(Request $request, string $role): RedirectResponse
    {
        $username = match ($role) {
            'superadmin' => 'superadmin',
            'director', 'admin' => 'director',
            'docente', 'teacher' => 'docente01',
            'recepcion', 'viewer' => 'recepcion',
            default => 'director',
        };

        $user = User::query()->where('username', $username)->first();

        if ($user) {
            $school = $user->school_id ? School::query()->find($user->school_id) : null;
            $request->session()->regenerate();
            $request->session()->put('user', [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'role' => $user->role,
                'school_id' => $user->school_id,
                'institution_code' => $school?->code,
            ]);
        }

        return match ($role) {
            'superadmin' => redirect()->route('antigravity.superadmin'),
            'docente', 'teacher' => redirect()->route('antigravity.teacher'),
            'recepcion', 'viewer' => redirect()->route('antigravity.kiosk'),
            default => redirect()->route('antigravity.admin'),
        };
    }

    /**
     * Dashboard del Director / Administrador
     */
    public function admin(Request $request, string $tab = 'overview'): View
    {
        $school = School::query()->where('code', 'SANPATRICIO')->first() ?? School::query()->first();
        $courses = Course::query()->where('school_id', $school?->id)->orderBy('code')->get();
        $devices = BiometricDevice::query()->where('school_id', $school?->id)->get();
        $students = Student::query()->where('school_id', $school?->id)->orderBy('apellido')->get();
        $todayAttendances = Attendance::query()
            ->with(['student', 'device'])
            ->where('school_id', $school?->id)
            ->whereDate('fecha_hora', today())
            ->orderByDesc('fecha_hora')
            ->get();

        $totalStudents = $students->count() ?: 35;
        $presentToday = $todayAttendances->where('tipo', 'Entrada')->unique('student_id')->count() ?: 28;
        $absentToday = max(0, $totalStudents - $presentToday);
        $tardyToday = $todayAttendances->where('estado', 'Tarde')->unique('student_id')->count() ?: 3;
        $attendanceRate = $totalStudents > 0 ? round(($presentToday / $totalStudents) * 100, 1) : 91.4;

        $firstEntryByStudent = $todayAttendances
            ->where('tipo', 'Entrada')
            ->sortBy('fecha_hora')
            ->groupBy('student_id')
            ->map(fn ($entries) => $entries->first());
        $trafficLightRoster = $students->map(function (Student $student) use ($firstEntryByStudent): array {
            $entry = $firstEntryByStudent->get($student->id);
            $isLate = $entry !== null && ($entry->is_late || $entry->estado === 'Tarde');

            return [
                'student' => $student,
                'entry' => $entry,
                'status' => $entry === null ? 'absent' : ($isLate ? 'late' : 'present'),
            ];
        });
        $trafficLightCounts = [
            'present' => $trafficLightRoster->where('status', 'present')->count(),
            'late' => $trafficLightRoster->where('status', 'late')->count(),
            'absent' => $trafficLightRoster->where('status', 'absent')->count(),
        ];

        // Distribución por curso
        $courseStats = $courses->map(function ($course) use ($todayAttendances) {
            $courseStudents = Student::query()->where('course_id', $course->id)->count();
            $coursePresent = $todayAttendances->where('curso', $course->name)->where('tipo', 'Entrada')->unique('student_id')->count();
            $percentage = $courseStudents > 0 ? round(($coursePresent / $courseStudents) * 100) : 88;

            return [
                'name' => $course->name,
                'code' => $course->code,
                'total' => $courseStudents,
                'present' => $coursePresent,
                'percentage' => $percentage,
            ];
        });

        // Distribución horaria de llegada (histograma / curva de flujo)
        $hourlyFlow = [
            ['label' => '07:15 - 07:30', 'count' => 4, 'height' => 35, 'time' => '7:15 AM'],
            ['label' => '07:30 - 07:45', 'count' => 16, 'height' => 100, 'time' => '7:30 AM (Pico)'],
            ['label' => '07:45 - 08:00', 'count' => 8, 'height' => 55, 'time' => '7:45 AM'],
            ['label' => '08:00+', 'count' => 3, 'height' => 22, 'time' => 'Tardanzas'],
        ];

        // Tendencia semanal comparativa
        $weeklyTrend = [
            ['day' => 'Lun', 'date' => '01 Sep', 'rate' => 93.8, 'present' => 33],
            ['day' => 'Mar', 'date' => '02 Sep', 'rate' => 91.4, 'present' => 32],
            ['day' => 'Mié', 'date' => '03 Sep', 'rate' => 94.2, 'present' => 33],
            ['day' => 'Jue', 'date' => '04 Sep', 'rate' => 96.0, 'present' => 34],
            ['day' => 'Hoy', 'date' => '08 Sep', 'rate' => 91.4, 'present' => 28, 'is_today' => true],
        ];

        // Incidencias inteligentes y alertas de conciliación
        $incidents = [
            [
                'id' => 1,
                'type' => 'campus_missing_class',
                'severity' => 'warning',
                'title' => 'En el plantel, pero ausente en aula',
                'student' => 'Mateo Peña Almonte (1.º Sec A)',
                'description' => 'Ponchó a las 07:34 AM en Torniquetes pero el Prof. Carlos Ramírez reportó ausencia en Matemáticas.',
                'time' => '08:12 AM',
                'badge' => 'Requiere Atención',
            ],
            [
                'id' => 2,
                'type' => 'late_bus',
                'severity' => 'info',
                'title' => 'Tardanza colectiva justificada',
                'student' => 'Ruta Escolar #4 (3 estudiantes)',
                'description' => 'Demora de 12 minutos por congestión en Av. Enriquillo. Llegada registrada a las 07:48 AM.',
                'time' => '07:48 AM',
                'badge' => 'Auto-Justificado',
            ],
        ];

        return view('antigravity.admin.overview', compact(
            'school',
            'tab',
            'courses',
            'devices',
            'students',
            'todayAttendances',
            'totalStudents',
            'presentToday',
            'absentToday',
            'tardyToday',
            'attendanceRate',
            'trafficLightRoster',
            'trafficLightCounts',
            'courseStats',
            'hourlyFlow',
            'weeklyTrend',
            'incidents'
        ));
    }

    /**
     * Directorio de Estudiantes
     */
    public function students(): View
    {
        $school = School::query()->where('code', 'SANPATRICIO')->first() ?? School::query()->first();
        $courses = Course::query()->where('school_id', $school?->id)->orderBy('code')->get();
        $students = Student::query()
            ->with(['course', 'enrollments'])
            ->where('school_id', $school?->id)
            ->orderBy('apellido')
            ->get();

        return view('antigravity.admin.students', compact('school', 'courses', 'students'));
    }

    /**
     * Estación de Enrolamiento Biométrico (Mapa de 10 dedos)
     */
    public function enrollment(): View
    {
        $school = School::query()->where('code', 'SANPATRICIO')->first() ?? School::query()->first();
        $students = Student::query()->where('school_id', $school?->id)->orderBy('apellido')->get();
        $devices = BiometricDevice::query()->where('school_id', $school?->id)->get();
        $enrollments = BiometricEnrollment::query()->with('student')->latest()->take(10)->get();

        return view('antigravity.admin.enrollment', compact('school', 'students', 'devices', 'enrollments'));
    }

    /**
     * Consola de Dispositivos y Lectores Biométricos
     */
    public function devices(): View
    {
        $school = School::query()->where('code', 'SANPATRICIO')->first() ?? School::query()->first();
        $devices = BiometricDevice::query()->where('school_id', $school?->id)->get();

        return view('antigravity.admin.devices', compact('school', 'devices'));
    }

    /**
     * Panel "Aula Activa" para Docentes (Tablet / Móvil)
     */
    public function teacher(): View
    {
        $school = School::query()->where('code', 'SANPATRICIO')->first() ?? School::query()->first();
        $docente = User::query()->where('username', 'docente01')->first();
        $courses = Course::query()->where('school_id', $school?->id)->get();
        $selectedCourse = $courses->first();

        $students = Student::query()
            ->where('course_id', $selectedCourse?->id)
            ->orderBy('numero_lista')
            ->get();

        $session = ClassSession::query()
            ->with('verifications')
            ->where('course_id', $selectedCourse?->id)
            ->latest()
            ->first();

        $todayPunches = Attendance::query()
            ->where('school_id', $school?->id)
            ->whereDate('fecha_hora', today())
            ->where('tipo', 'Entrada')
            ->get()
            ->keyBy('student_id');

        return view('antigravity.teacher', compact(
            'school',
            'docente',
            'courses',
            'selectedCourse',
            'students',
            'session',
            'todayPunches'
        ));
    }

    /**
     * Consola SaaS Multi-Tenant para SuperAdmin
     */
    public function superadmin(): View
    {
        $schools = School::query()->withCount(['students', 'devices', 'users'])->get();
        $auditLogs = SystemAuditLog::query()->with(['actor', 'school'])->latest()->take(15)->get();

        $totalSchools = $schools->count();
        $totalStudents = Student::query()->count();
        $totalDevices = BiometricDevice::query()->count();
        $activeSchools = $schools->where('is_active', true)->count();

        return view('antigravity.superadmin', compact(
            'schools',
            'auditLogs',
            'totalSchools',
            'totalStudents',
            'totalDevices',
            'activeSchools'
        ));
    }

    /**
     * Modo Kiosco / Pantalla Gigante para Recepción
     */
    public function kiosk(): View
    {
        $school = School::query()->where('code', 'SANPATRICIO')->first() ?? School::query()->first();
        $recentPunches = Attendance::query()
            ->with('student')
            ->where('school_id', $school?->id)
            ->whereDate('fecha_hora', today())
            ->latest('fecha_hora')
            ->take(12)
            ->get();

        $todayTotal = Attendance::query()
            ->where('school_id', $school?->id)
            ->whereDate('fecha_hora', today())
            ->where('tipo', 'Entrada')
            ->distinct('student_id')
            ->count('student_id');

        return view('antigravity.kiosk', compact('school', 'recentPunches', 'todayTotal'));
    }
}
