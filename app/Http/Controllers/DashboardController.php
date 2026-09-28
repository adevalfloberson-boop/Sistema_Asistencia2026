<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceNotification;
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
        abort_unless(in_array($page, ['overview', 'devices', 'enrollment', 'students', 'courses', 'teachers', 'settings', 'attendance', 'reports'], true), 404);

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
        abort_unless(in_array($page, ['overview', 'devices', 'enrollment', 'students', 'courses', 'teachers', 'settings', 'attendance', 'reports'], true), 404);

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
                $record['ignored'] => 'Ponche recibido, pero la salida anticipada no está autorizada.',
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
        $attendanceStatus = $request?->string('attendance_status')->toString() ?: 'all';

        if (! in_array($attendanceStatus, ['all', 'present', 'late', 'absent'], true)) {
            $attendanceStatus = 'all';
        }

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
        $attendanceRoster = collect();
        $attendanceRosterSummary = ['total' => 0, 'present' => 0, 'late' => 0, 'absent' => 0];
        $attendanceCourses = collect();
        $reportEndDate = $request?->filled('report_end') ? Carbon::parse($request->string('report_end')->toString()) : today();
        $reportStartDate = $request?->filled('report_start') ? Carbon::parse($request->string('report_start')->toString()) : $reportEndDate->copy()->subDays(29);
        $reportSummary = ['students' => 0, 'entries' => 0, 'late' => 0, 'excused' => 0];
        $reportByCourse = collect();
        $reportByDay = collect();
        $reportRecords = collect();
        $reportCourses = collect();
        $reportGenderByCourse = collect();
        $reportGenderTotals = ['female' => 0, 'male' => 0, 'unspecified' => 0, 'total' => 0];
        $reportStudents = collect();
        $selectedReportStudent = null;
        $reportStudentSummary = ['attendance_days' => 0, 'late_days' => 0, 'days_without_entry' => 0];
        $enrollmentSummary = ['active_readers' => 0, 'total_readers' => 0, 'today' => 0, 'pending' => 0];

        if ($reportStartDate->greaterThan($reportEndDate)) {
            [$reportStartDate, $reportEndDate] = [$reportEndDate, $reportStartDate];
        }

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

        if ($activePage === 'attendance') {
            $attendanceCourses = Student::query()
                ->where('is_active', true)
                ->when($selectedSchoolId, fn ($query, int $schoolId) => $query->where('school_id', $schoolId))
                ->when($cursosPermitidos !== null, fn ($query) => $query->whereIn('curso', $cursosPermitidos))
                ->whereNotNull('curso')
                ->where('curso', '!=', '')
                ->distinct()
                ->orderBy('curso')
                ->pluck('curso');

            $attendanceRoster = (clone $studentQuery)
                ->with(['attendances' => fn ($query) => $query
                    ->whereDate('fecha_hora', $analysisDate)
                    ->where('tipo', 'Entrada')
                    ->where('is_ignored', false)
                    ->oldest('fecha_hora')])
                ->orderBy('curso')
                ->orderBy('nombre')
                ->orderBy('apellido')
                ->get()
                ->map(function (Student $student): array {
                    $entry = $student->attendances->first();
                    $status = match (true) {
                        $entry === null => 'absent',
                        $entry->is_late => 'late',
                        default => 'present',
                    };

                    return [
                        'nombre' => "{$student->nombre} {$student->apellido}",
                        'matricula' => $student->matricula,
                        'curso' => $student->curso,
                        'status' => $status,
                        'hora' => $entry?->fecha_hora?->format('H:i'),
                    ];
                });

            $attendanceRosterSummary = [
                'total' => $attendanceRoster->count(),
                'present' => $attendanceRoster->where('status', 'present')->count(),
                'late' => $attendanceRoster->where('status', 'late')->count(),
                'absent' => $attendanceRoster->where('status', 'absent')->count(),
            ];

            if ($attendanceStatus !== 'all') {
                $attendanceRoster = $attendanceRoster->where('status', $attendanceStatus)->values();
            }
        }

        if ($activePage === 'reports') {
            $reportStudents = (clone $studentQuery)
                ->orderBy('nombre')
                ->orderBy('apellido')
                ->get(['id', 'nombre', 'apellido', 'matricula', 'curso']);
            $selectedReportStudent = $reportStudents->firstWhere('id', $request?->integer('report_student_id'));

            if ($selectedReportStudent !== null) {
                $studentQuery->whereKey($selectedReportStudent->id);
                $attendanceQuery->where('student_id', $selectedReportStudent->id);
            }

            $reportCourses = Student::query()
                ->where('is_active', true)
                ->when($selectedSchoolId, fn ($query, int $schoolId) => $query->where('school_id', $schoolId))
                ->when($cursosPermitidos !== null, fn ($query) => $query->whereIn('curso', $cursosPermitidos))
                ->whereNotNull('curso')
                ->where('curso', '!=', '')
                ->distinct()
                ->orderBy('curso')
                ->pluck('curso');

            $reportRecords = (clone $attendanceQuery)
                ->whereBetween('fecha_hora', [
                    $reportStartDate->copy()->startOfDay(),
                    $reportEndDate->copy()->endOfDay(),
                ])
                ->with('student')
                ->oldest('fecha_hora')
                ->get();

            $reportEntries = $reportRecords
                ->where('tipo', 'Entrada')
                ->unique(fn (Attendance $attendance): string => "{$attendance->student_id}|{$attendance->fecha_hora->toDateString()}")
                ->values();

            $reportSummary = [
                'students' => (clone $studentQuery)->count(),
                'entries' => $reportEntries->count(),
                'late' => $reportEntries->where('is_late', true)->count(),
                'excused' => $reportRecords->whereNotNull('excuse_type')->count(),
            ];

            if ($selectedReportStudent !== null) {
                $expectedSchoolDays = collect(range(0, (int) $reportStartDate->diffInDays($reportEndDate)))
                    ->map(fn (int $days): Carbon => $reportStartDate->copy()->addDays($days))
                    ->filter(fn (Carbon $date): bool => $date->isWeekday())
                    ->count();
                $attendanceDays = $reportEntries->unique(fn (Attendance $attendance): string => $attendance->fecha_hora->toDateString())->count();

                $reportStudentSummary = [
                    'attendance_days' => $attendanceDays,
                    'late_days' => $reportEntries->where('is_late', true)->count(),
                    'days_without_entry' => max(0, $expectedSchoolDays - $attendanceDays),
                ];
            }

            $reportGenderByCourse = (clone $studentQuery)
                ->whereNotNull('curso')
                ->where('curso', '!=', '')
                ->get(['curso', 'sexo'])
                ->groupBy('curso')
                ->map(function ($students, string $course): array {
                    return [
                        'course' => $course,
                        'female' => $students->where('sexo', 'Femenino')->count(),
                        'male' => $students->where('sexo', 'Masculino')->count(),
                        'unspecified' => $students->whereNull('sexo')->count(),
                        'total' => $students->count(),
                    ];
                })
                ->sortBy('course')
                ->values();
            $reportGenderTotals = [
                'female' => $reportGenderByCourse->sum('female'),
                'male' => $reportGenderByCourse->sum('male'),
                'unspecified' => $reportGenderByCourse->sum('unspecified'),
                'total' => $reportGenderByCourse->sum('total'),
            ];

            $reportByCourse = (clone $studentQuery)
                ->whereNotNull('curso')
                ->where('curso', '!=', '')
                ->get()
                ->groupBy('curso')
                ->map(function ($students, string $course) use ($reportEntries): array {
                    $courseEntries = $reportEntries->where('curso', $course);

                    return [
                        'course' => $course,
                        'students' => $students->count(),
                        'entries' => $courseEntries->count(),
                        'late' => $courseEntries->where('is_late', true)->count(),
                        'on_time_percentage' => $courseEntries->isEmpty()
                            ? 0
                            : round(($courseEntries->where('is_late', false)->count() / $courseEntries->count()) * 100, 1),
                    ];
                })
                ->sortBy('course')
                ->values();

            $reportByDay = $reportEntries
                ->groupBy(fn (Attendance $attendance): string => $attendance->fecha_hora->toDateString())
                ->map(function ($entries, string $date): array {
                    return [
                        'date' => Carbon::parse($date),
                        'entries' => $entries->count(),
                        'late' => $entries->where('is_late', true)->count(),
                    ];
                })
                ->sortByDesc('date')
                ->values();

            $reportRecords = $reportRecords->sortByDesc('fecha_hora')->take(100)->values();
        }

        $recentRecords = in_array($activePage, ['overview', 'attendance'], true)
            ? (clone $attendanceQuery)
                ->whereDate('fecha_hora', $analysisDate)
                ->with('student')
                ->latest('fecha_hora')
                ->when($activePage === 'overview', fn ($query) => $query->limit(12))
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
        $studentSummary = ['total' => 0, 'active' => 0, 'biometric' => 0, 'pending' => 0];
        if ($activePage === 'students' && ($usuario['role'] ?? null) === 'admin') {
            $managementStudentsQuery = Student::query()->where('school_id', $lockedSchoolId);
            $studentSummary = [
                'total' => (clone $managementStudentsQuery)->count(),
                'active' => (clone $managementStudentsQuery)->where('is_active', true)->count(),
                'biometric' => (clone $managementStudentsQuery)->whereNotNull('id_lector')->where('id_lector', '!=', '')->count(),
                'pending' => (clone $managementStudentsQuery)->where(fn ($query) => $query->whereNull('id_lector')->orWhere('id_lector', ''))->count(),
            ];
            $students = $managementStudentsQuery
                ->with(['school', 'course'])
                ->withMax(['attendances as last_entry_at' => fn ($query) => $query->where('tipo', 'Entrada')], 'fecha_hora')
                ->withMax(['attendances as last_exit_at' => fn ($query) => $query->where('tipo', 'Salida')], 'fecha_hora')
                ->when($request?->filled('student_search'), function ($query) use ($request): void {
                    $search = $request->string('student_search')->toString();
                    $query->where(function ($studentQuery) use ($search): void {
                        $studentQuery->where('nombre', 'like', "%{$search}%")
                            ->orWhere('apellido', 'like', "%{$search}%")
                            ->orWhere('matricula', 'like', "%{$search}%")
                            ->orWhere('id_lector', 'like', "%{$search}%");
                    });
                })
                ->when($request?->integer('student_course_id'), fn ($query, int $courseId) => $query->where('course_id', $courseId))
                ->when($request?->string('student_status')->toString() === 'active', fn ($query) => $query->where('is_active', true))
                ->when($request?->string('student_status')->toString() === 'inactive', fn ($query) => $query->where('is_active', false))
                ->when($request?->string('student_biometric')->toString() === 'registered', fn ($query) => $query->whereNotNull('id_lector')->where('id_lector', '!=', ''))
                ->when($request?->string('student_biometric')->toString() === 'pending', fn ($query) => $query->where(fn ($studentQuery) => $studentQuery->whereNull('id_lector')->orWhere('id_lector', '')))
                ->orderBy('nombre')
                ->orderBy('apellido')
                ->paginate(25)
                ->withQueryString();
        } else {
            $students = in_array($activePage, ['students', 'enrollment'], true)
                ? (clone $studentQuery)
                    ->with(['school', 'enrollments'])
                    ->orderBy('nombre')
                    ->orderBy('apellido')
                    ->get()
                : collect();
        }
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
        if ($activePage === 'enrollment') {
            $enrollmentSummary = [
                'active_readers' => $devices->filter(fn (BiometricDevice $device): bool => $device->connectionStatus() === 'online')->count(),
                'total_readers' => $devices->where('is_active', true)->count(),
                'today' => BiometricEnrollment::query()
                    ->when($selectedSchoolId, fn ($query, int $schoolId) => $query->whereHas('device', fn ($deviceQuery) => $deviceQuery->where('school_id', $schoolId)))
                    ->whereDate('created_at', today())
                    ->count(),
                'pending' => BiometricEnrollment::query()
                    ->when($selectedSchoolId, fn ($query, int $schoolId) => $query->whereHas('device', fn ($deviceQuery) => $deviceQuery->where('school_id', $schoolId)))
                    ->whereIn('status', ['pending', 'processing'])
                    ->count(),
            ];
        }
        $shareSchool = $activePage === 'overview' && $selectedSchoolId
            ? $schools->firstWhere('id', $selectedSchoolId)
            : null;
        $settingsSchool = $activePage === 'settings' && $lockedSchoolId
            ? School::query()->with('notificationSetting')->findOrFail($lockedSchoolId)
            : null;
        $notificationHistory = $settingsSchool === null
            ? collect()
            : AttendanceNotification::query()
                ->with('student')
                ->where('school_id', $settingsSchool->id)
                ->latest('processed_at')
                ->take(20)
                ->get();

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
            'attendanceStatus' => $attendanceStatus,
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
            'attendanceRoster' => $attendanceRoster,
            'attendanceRosterSummary' => $attendanceRosterSummary,
            'attendanceCourses' => $attendanceCourses,
            'reportStartDate' => $reportStartDate,
            'reportEndDate' => $reportEndDate,
            'reportSummary' => $reportSummary,
            'reportByCourse' => $reportByCourse,
            'reportByDay' => $reportByDay,
            'reportRecords' => $reportRecords,
            'reportCourses' => $reportCourses,
            'reportGenderByCourse' => $reportGenderByCourse,
            'reportGenderTotals' => $reportGenderTotals,
            'reportStudents' => $reportStudents,
            'selectedReportStudent' => $selectedReportStudent,
            'reportStudentSummary' => $reportStudentSummary,
            'enrollmentSummary' => $enrollmentSummary,
            'schools' => $schools,
            'devices' => $devices,
            'deviceSummary' => $deviceSummary,
            'schoolNetwork' => $schoolNetwork,
            'students' => $students,
            'studentSummary' => $studentSummary,
            'allCourses' => $allCourses,
            'teachers' => $teachers,
            'enrollments' => $enrollments,
            'recentCommands' => $recentCommands,
            'shareSchool' => $shareSchool,
            'settingsSchool' => $settingsSchool,
            'notificationHistory' => $notificationHistory,
        ]);
    }
}
