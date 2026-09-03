<?php

namespace App\Http\Controllers;

use App\Models\AdmsEvent;
use App\Models\Attendance;
use App\Models\BiometricDevice;
use App\Models\BiometricEnrollment;
use App\Models\Course;
use App\Models\DeviceCommand;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\BiometricAttendanceRecorder;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function superadmin(Request $request): View
    {
        return $this->renderDashboard(
            $request->session()->get('user'),
            null,
            'Superadministración técnica',
            'overview',
            $request,
            null,
            'dashboard.superadmin',
            'dashboard.superadmin.page',
        );
    }

    public function superadminPage(Request $request, string $page): View
    {
        abort_unless(in_array($page, ['overview', 'devices', 'enrollment', 'students', 'courses', 'teachers', 'settings', 'attendance'], true), 404);

        return $this->renderDashboard(
            $request->session()->get('user'),
            null,
            'Superadministración técnica',
            $page,
            $request,
            null,
            'dashboard.superadmin',
            'dashboard.superadmin.page',
        );
    }

    public function admin(Request $request, string $page = 'overview'): View
    {
        abort_unless(in_array($page, ['overview', 'devices', 'enrollment', 'students', 'courses', 'teachers', 'settings', 'attendance'], true), 404);

        $usuario = session('user');
        $isSchoolAdmin = ($usuario['role'] ?? null) === 'admin';
        $schoolId = $isSchoolAdmin ? (int) ($usuario['school_id'] ?? 0) : null;

        abort_unless(! $isSchoolAdmin || $schoolId > 0, 403);

        return $this->renderDashboard(
            $usuario,
            null,
            $isSchoolAdmin ? 'Administración del centro' : 'Superadministración técnica',
            $page,
            $request,
            $schoolId,
        );
    }

    public function registrarAsistencia(Request $request, BiometricAttendanceRecorder $attendanceRecorder): JsonResponse
    {
        $expectedToken = config('attendance.api_token');

        if ($expectedToken === null || ! hash_equals($expectedToken, (string) $request->header('X-Biometric-Token'))) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado para registrar asistencia.',
            ], 401);
        }

        $validated = $request->validate([
            'id_lector' => ['required', 'string'],
            'reader_key' => ['nullable', 'string', 'max:255'],
            'reader_name' => ['nullable', 'string', 'max:255'],
            'reader_school' => ['nullable', 'string', 'max:255'],
            'reader_mac' => ['nullable', 'mac_address'],
            'reader_ip' => ['nullable', 'ip'],
            'event_key' => ['nullable', 'string', 'size:64'],
            'event_timestamp' => ['nullable', 'date'],
            'event_source' => ['nullable', 'in:live,history'],
            'device_status' => ['nullable', 'integer', 'min:0'],
            'device_punch' => ['nullable', 'integer', 'min:0'],
        ]);

        $device = BiometricDevice::query()
            ->when(
                $validated['reader_key'] ?? null,
                fn ($query, string $key) => $query->where('key', $key),
            )
            ->when(
                ! isset($validated['reader_key']) && isset($validated['reader_mac']),
                fn ($query) => $query->where('mac_address', strtolower($validated['reader_mac'])),
            )
            ->first();

        $student = Student::query()
            ->where('id_lector', $validated['id_lector'])
            ->when($device?->school_id, fn ($query, int $schoolId) => $query->where('school_id', $schoolId))
            ->first();

        if ($student === null) {
            return response()->json([
                'success' => false,
                'message' => "ID {$validated['id_lector']} no registrado en la base de datos.",
            ], 404);
        }

        $record = $attendanceRecorder->record($student, $device, $validated);

        if ($record['debounced']) {
            $cooldownMinutes = $student->school?->attendance_cooldown_minutes ?? 10;

            return response()->json([
                'success' => false,
                'message' => "Debes esperar {$cooldownMinutes} minutos entre ponches.",
                'remaining_seconds' => $record['remaining_seconds'],
            ], 422);
        }

        $attendance = $record['attendance'];

        if ($device !== null) {
            $wasConnected = $device->status === 'connected';
            $device->update([
                'status' => 'connected',
                'ip_address' => $validated['reader_ip'] ?? $device->ip_address,
                'last_seen_at' => now(),
                'last_connected_at' => $wasConnected ? ($device->last_connected_at ?? now()) : now(),
                'last_error' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'duplicate' => ! $record['created'],
            'ignored' => $record['ignored'],
            'message' => match (true) {
                $record['ignored'] => 'Ponche recibido, pero ignorado por estar dentro del tiempo de espera.',
                $record['created'] => 'Asistencia registrada correctamente.',
                default => 'El ponche ya estaba sincronizado.',
            },
            'student' => "{$student->nombre} {$student->apellido}",
            'matricula' => $student->matricula,
            'curso' => $student->curso,
            'tipo' => $attendance->tipo,
            'hora' => $attendance->fecha_hora->format('H:i:s'),
            'fecha_hora' => $attendance->fecha_hora->format('Y-m-d H:i:s'),
            'source' => $attendance->sync_source,
            'reader' => [
                'key' => $validated['reader_key'] ?? null,
                'name' => $validated['reader_name'] ?? null,
                'school' => $validated['reader_school'] ?? null,
                'ip' => $validated['reader_ip'] ?? null,
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $usuario
     * @param  array<int, string>|null  $cursosPermitidos
     */
    private function renderDashboard(?array $usuario, ?array $cursosPermitidos, string $roleLabel, string $activePage = 'overview', ?Request $request = null, ?int $lockedSchoolId = null, string $dashboardBaseRoute = 'dashboard.admin', string $dashboardPageRoute = 'dashboard.admin.page'): View
    {
        $schools = School::query()->where('is_active', true)->when($lockedSchoolId, fn ($query, int $schoolId) => $query->whereKey($schoolId))->orderBy('name')->get();
        $studentQuery = Student::query();
        $attendanceQuery = Attendance::query()->where('is_ignored', false);
        $analysisDate = $request?->filled('date') ? Carbon::parse($request->string('date')->toString()) : today();
        $selectedSchoolId = $lockedSchoolId ?? ($request?->integer('school_id') ?: null);
        $selectedCourse = $request?->string('course')->toString() ?: null;

        $studentQuery->when($selectedSchoolId, fn ($query, int $schoolId) => $query->where('school_id', $schoolId));
        $attendanceQuery->when($selectedSchoolId, fn ($query, int $schoolId) => $query->where('school_id', $schoolId));
        $studentQuery->when($selectedCourse, fn ($query, string $course) => $query->where('curso', $course));
        $attendanceQuery->when($selectedCourse, fn ($query, string $course) => $query->where('curso', $course));

        if ($cursosPermitidos !== null) {
            $studentQuery->whereIn('curso', $cursosPermitidos);
            $attendanceQuery->whereIn('curso', $cursosPermitidos);
        }

        $studentQuery->where('is_active', true);
        $studentsTotal = 0;
        $presentToday = 0;
        $departuresToday = 0;
        $absentToday = 0;
        $attendanceByCourse = collect();
        $weeklyTrend = collect();

        if ($activePage === 'overview') {
            $studentsTotal = (clone $studentQuery)->count();
            $todayAttendances = (clone $attendanceQuery)->whereDate('fecha_hora', $analysisDate);
            $presentToday = (clone $todayAttendances)->where('tipo', 'Entrada')->distinct()->count('student_id');
            $departuresToday = (clone $todayAttendances)->where('tipo', 'Salida')->distinct()->count('student_id');
            $absentToday = max(0, $studentsTotal - $presentToday);

            $courses = (clone $studentQuery)
                ->whereNotNull('curso')
                ->distinct()
                ->orderBy('curso')
                ->pluck('curso');

            $attendanceByCourse = $courses->map(function (string $course) use ($studentQuery, $attendanceQuery, $analysisDate): array {
                $students = (clone $studentQuery)->where('curso', $course)->count();
                $present = (clone $attendanceQuery)
                    ->whereDate('fecha_hora', $analysisDate)
                    ->where('curso', $course)
                    ->where('tipo', 'Entrada')
                    ->distinct()
                    ->count('student_id');

                return [
                    'curso' => $course,
                    'estudiantes' => $students,
                    'presentes' => $present,
                    'porcentaje' => $students === 0 ? 0 : round(($present / $students) * 100, 1),
                ];
            })->sortByDesc('porcentaje')->values();

            $weeklyTrend = collect(range(4, 0))->map(function (int $daysAgo) use ($attendanceQuery, $studentsTotal): array {
                $date = today()->subDays($daysAgo);
                $present = (clone $attendanceQuery)
                    ->whereDate('fecha_hora', $date)
                    ->where('tipo', 'Entrada')
                    ->distinct()
                    ->count('student_id');

                return [
                    'fecha' => $date->format('d/m'),
                    'dia' => $date->locale('es')->isoFormat('ddd'),
                    'porcentaje' => $studentsTotal === 0 ? 0 : round(($present / $studentsTotal) * 100, 1),
                ];
            });
        }

        $recentRecords = in_array($activePage, ['overview', 'attendance'], true)
            ? (clone $attendanceQuery)
                ->whereDate('fecha_hora', $analysisDate)
                ->with('student')
                ->latest('fecha_hora')
                ->take(12)
                ->get()
                ->map(function (Attendance $attendance): array {
                    return [
                        'nombre' => $attendance->student === null
                            ? "ID lector: {$attendance->id_lector}"
                            : "{$attendance->student->nombre} {$attendance->student->apellido}",
                        'matricula' => $attendance->matricula,
                        'curso' => $attendance->curso,
                        'hora' => Carbon::parse($attendance->fecha_hora)->format('H:i:s'),
                        'tipo' => $attendance->tipo,
                        'id' => $attendance->id,
                        'is_late' => $attendance->is_late,
                        'is_early_departure' => $attendance->is_early_departure,
                        'excuse_type' => $attendance->excuse_type,
                        'excuse_note' => $attendance->excuse_note,
                        'lector' => $attendance->reader_name ?? $attendance->reader_key ?? 'No identificado',
                        'escuela' => $attendance->reader_school,
                    ];
                })
            : collect();

        $devices = in_array($activePage, ['overview', 'devices', 'enrollment', 'attendance'], true)
            ? BiometricDevice::query()
                ->with('school')
                ->withCount('enrollments')
                ->withMax('attendances', 'fecha_hora')
                ->when($lockedSchoolId, fn ($query, int $schoolId) => $query->where('school_id', $schoolId))
                ->orderBy('school_id')
                ->orderBy('name')
                ->get()
            : collect();
        $deviceSummary = [
            'total' => $devices->count(),
            'online' => $devices->filter(fn (BiometricDevice $device): bool => $device->connectionStatus() === 'online')->count(),
            'delayed' => $devices->filter(fn (BiometricDevice $device): bool => $device->connectionStatus() === 'delayed')->count(),
            'offline' => $devices->filter(
                fn (BiometricDevice $device): bool => in_array($device->connectionStatus(), ['offline', 'never_connected'], true),
            )->count(),
        ];
        $schoolNetwork = $schools->map(function (School $school) use ($devices): array {
            $schoolDevices = $devices->where('school_id', $school->id);

            return [
                'school' => $school,
                'total' => $schoolDevices->count(),
                'online' => $schoolDevices->filter(
                    fn (BiometricDevice $device): bool => $device->connectionStatus() === 'online',
                )->count(),
                'alerts' => $schoolDevices->filter(
                    fn (BiometricDevice $device): bool => in_array($device->connectionStatus(), ['offline', 'delayed', 'never_connected'], true),
                )->count(),
                'last_seen_at' => $schoolDevices->max('last_seen_at'),
            ];
        });
        $students = in_array($activePage, ['students', 'enrollment'], true)
            ? (clone $studentQuery)->with('school')->orderBy('nombre')->orderBy('apellido')->get()
            : collect();
        $allCourses = in_array($activePage, ['overview', 'students', 'courses', 'teachers'], true)
            ? Course::query()->with('school')->withCount('students')->when($lockedSchoolId, fn ($query, int $schoolId) => $query->where('school_id', $schoolId))->orderBy('school_id')->orderBy('name')->get()
            : collect();
        $teachers = $activePage === 'teachers' ? User::query()
            ->whereIn('role', ['admin', 'teacher', 'viewer'])
            ->with(['school', 'courses'])
            ->when($lockedSchoolId, fn ($query, int $schoolId) => $query->where('school_id', $schoolId))
            ->orderBy('name')
            ->get() : collect();
        $enrollments = $activePage === 'enrollment' ? BiometricEnrollment::query()
            ->with(['student', 'device'])
            ->when($lockedSchoolId, fn ($query, int $schoolId) => $query->whereHas('device', fn ($deviceQuery) => $deviceQuery->where('school_id', $schoolId)))
            ->latest()
            ->take(12)
            ->get() : collect();
        $recentCommands = in_array($activePage, ['devices', 'enrollment'], true) ? DeviceCommand::query()
            ->with(['device', 'student'])
            ->when($lockedSchoolId, fn ($query, int $schoolId) => $query->whereHas('device', fn ($deviceQuery) => $deviceQuery->where('school_id', $schoolId)))
            ->latest()
            ->take(12)
            ->get() : collect();
        $recentAdmsEvents = $activePage === 'attendance' ? AdmsEvent::query()
            ->select([
                'id',
                'biometric_device_id',
                'student_id',
                'user_id',
                'event_at',
                'processing_status',
                'raw_payload',
                'error',
                'created_at',
            ])
            ->with([
                'device:id,school_id,name,serial_number,ip_address',
                'device.school:id,name,short_name',
                'student:id,nombre,apellido,matricula,curso',
            ])
            ->when($lockedSchoolId, fn ($query, int $schoolId) => $query->whereHas('device', fn ($deviceQuery) => $deviceQuery->where('school_id', $schoolId)))
            ->latest()
            ->take(12)
            ->get() : collect();

        return view('layouts.dashboard', [
            'usuario' => $usuario,
            'roleLabel' => $roleLabel,
            'canManageSchool' => in_array($usuario['role'] ?? null, ['superadmin', 'admin'], true),
            'dashboardBaseRoute' => $dashboardBaseRoute,
            'dashboardPageRoute' => $dashboardPageRoute,
            'activePage' => $activePage,
            'analysisDate' => $analysisDate,
            'selectedSchoolId' => $selectedSchoolId,
            'selectedCourse' => $selectedCourse,
            'resumen' => [
                'total_estudiantes' => $studentsTotal,
                'presentes_hoy' => $presentToday,
                'ausentes_hoy' => $absentToday,
                'salidas_hoy' => $departuresToday,
                'porcentaje_hoy' => $studentsTotal === 0 ? 0 : round(($presentToday / $studentsTotal) * 100, 1),
            ],
            'asistenciaPorCurso' => $attendanceByCourse,
            'tendenciaSemanal' => $weeklyTrend,
            'ultimosRegistros' => $recentRecords,
            'schools' => $schools,
            'devices' => $devices,
            'deviceSummary' => $deviceSummary,
            'schoolNetwork' => $schoolNetwork,
            'students' => $students,
            'allCourses' => $allCourses,
            'teachers' => $teachers,
            'enrollments' => $enrollments,
            'recentCommands' => $recentCommands,
            'recentAdmsEvents' => $recentAdmsEvents,
        ]);
    }
}
