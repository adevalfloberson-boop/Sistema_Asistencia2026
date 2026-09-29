<?php

use App\Models\Attendance;
use App\Models\BiometricDevice;
use App\Models\BiometricEnrollment;
use App\Models\Course;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('attendance.api_token', 'test-biometric-token');
});

test('admin dashboard redirects to login when there is no session', function () {
    $this->get('/dashboard/admin')->assertRedirect('/login');
});

test('superadministrator dashboard is accessible', function () {
    $school = School::query()->create(['code' => 'INST001', 'name' => 'Escuela Central']);
    $admin = User::factory()->create(['role' => 'superadmin', 'username' => 'admin']);

    $this->withSession(['user' => [
        'id' => $admin->id,
        'username' => 'admin',
        'role' => 'superadmin',
        'institution_code' => $school->code,
    ]])->get('/dashboard/admin')
        ->assertOk()
        ->assertSee('Portal escolar')
        ->assertSee('Centro de operaciones')
        ->assertSee('Estado de lectores')
        ->assertSee('Sincronización activa')
        ->assertSee('data-admin-async-panel', escape: false)
        ->assertSee('data-admin-sidebar-summary', escape: false);
});

test('live activity console is rendered only on its dedicated page', function () {
    $school = School::query()->create(['code' => 'LIVE001', 'name' => 'Escuela en Vivo']);
    $admin = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);
    $session = ['user' => [
        'id' => $admin->id,
        'username' => $admin->username,
        'role' => 'admin',
        'school_id' => $school->id,
        'institution_code' => $school->code,
    ]];

    $this->withSession($session)->get(route('dashboard.admin'))
        ->assertOk()
        ->assertDontSee('data-live-attendance-console', escape: false);

    $this->withSession($session)->get(route('dashboard.admin.page', 'attendance'))
        ->assertOk()
        ->assertSee('data-live-attendance-console', escape: false)
        ->assertSee('Operación en tiempo real');
});

test('overview cards open dialogs and list students absent today', function () {
    $school = School::query()->create(['code' => 'ABS001', 'name' => 'Escuela de Ausencias']);
    $admin = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);
    Student::query()->create([
        'school_id' => $school->id,
        'matricula' => 'AUS-001',
        'nombre' => 'María',
        'apellido' => 'Sin Entrada',
        'curso' => '4to A',
        'sexo' => 'Femenino',
        'id_lector' => 'AUS-1',
    ]);
    $presentStudent = Student::query()->create([
        'school_id' => $school->id,
        'matricula' => 'PRE-001',
        'nombre' => 'Juan',
        'apellido' => 'Con Entrada',
        'curso' => '4to A',
        'sexo' => 'Masculino',
        'id_lector' => 'PRE-1',
    ]);
    Attendance::query()->create([
        'school_id' => $school->id,
        'student_id' => $presentStudent->id,
        'matricula' => $presentStudent->matricula,
        'id_lector' => $presentStudent->id_lector,
        'fecha_hora' => now(),
        'curso' => $presentStudent->curso,
        'estado' => 'Tarde',
        'tipo' => 'Entrada',
        'is_late' => true,
    ]);

    $this->withSession(['user' => [
        'id' => $admin->id,
        'username' => $admin->username,
        'role' => 'admin',
        'school_id' => $school->id,
        'institution_code' => $school->code,
    ]])->get(route('dashboard.admin'))
        ->assertOk()
        ->assertSee('data-open-dialog="overview-absent-dialog"', escape: false)
        ->assertSee('data-absent-students-dialog', escape: false)
        ->assertSee('data-late-students-dialog', escape: false)
        ->assertSee('data-roster-search', escape: false)
        ->assertSee('Hembras')
        ->assertSee('Varones')
        ->assertSee('Juan Con Entrada')
        ->assertSee('María Sin Entrada')
        ->assertSee('4to A')
        ->assertViewHas('overviewGenderSummary', fn (array $summary): bool => $summary['female'] === 1
            && $summary['male'] === 1
            && $summary['present_female'] === 0
            && $summary['present_male'] === 1)
        ->assertViewHas('asistenciaPorCurso', fn ($courses): bool => $courses->first()['female'] === 1
            && $courses->first()['male'] === 1
            && $courses->first()['present_male'] === 1);
});

test('biometric enrollment is presented as a guided flow with real person status', function () {
    $school = School::query()->create(['code' => 'BIO001', 'name' => 'Escuela Biométrica']);
    $admin = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);
    $student = Student::query()->create([
        'school_id' => $school->id,
        'matricula' => 'BIO-001',
        'nombre' => 'Adrián',
        'apellido' => 'Peralta Gil',
        'curso' => '2.º Secundaria A',
        'id_lector' => '1023',
        'face_sync_status' => 'pending',
    ]);
    $device = BiometricDevice::query()->create([
        'school_id' => $school->id,
        'key' => 'lector-biometrico',
        'name' => 'Lector principal',
        'connection_mode' => 'adms',
        'serial_number' => 'BIO-DEVICE-001',
        'is_active' => true,
    ]);
    BiometricEnrollment::query()->create([
        'biometric_device_id' => $device->id,
        'student_id' => $student->id,
        'user_id' => $student->id_lector,
        'finger_index' => 1,
        'status' => 'enrolled',
        'enrolled_at' => now(),
    ]);

    $response = $this->withSession(['user' => [
        'id' => $admin->id,
        'username' => $admin->username,
        'role' => 'admin',
        'school_id' => $school->id,
        'institution_code' => $school->code,
    ]])->get(route('dashboard.admin.page', 'enrollment'));

    $response->assertOk()
        ->assertSee('data-biometric-enrollment', escape: false)
        ->assertSee('Registro biométrico')
        ->assertSee('Nombre, matrícula o ID biométrico...')
        ->assertSee('data-student-fingers="1"', escape: false)
        ->assertSee('data-student-face-status="pending"', escape: false)
        ->assertSee(route('devices.enroll'), escape: false)
        ->assertSee(route('devices.enroll-face'), escape: false)
        ->assertSee('Actividad reciente');
});

test('live activity shows every daily record and filters the roster by course and status', function () {
    Carbon::setTestNow('2026-09-28 09:00:00');
    $school = School::query()->create(['code' => 'FILTER001', 'name' => 'Escuela con Filtros']);
    $admin = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);
    $session = ['user' => [
        'id' => $admin->id,
        'username' => $admin->username,
        'role' => 'admin',
        'school_id' => $school->id,
        'institution_code' => $school->code,
    ]];

    foreach (range(1, 14) as $index) {
        $student = Student::query()->create([
            'school_id' => $school->id,
            'matricula' => "CURSO-A-{$index}",
            'nombre' => "Estudiante {$index}",
            'apellido' => 'Curso A',
            'curso' => '1ro A',
            'id_lector' => "A-{$index}",
        ]);

        if ($index < 14) {
            Attendance::query()->create([
                'school_id' => $school->id,
                'student_id' => $student->id,
                'matricula' => $student->matricula,
                'id_lector' => $student->id_lector,
                'fecha_hora' => now()->subMinutes($index),
                'curso' => $student->curso,
                'estado' => $index === 13 ? 'Tarde' : 'A tiempo',
                'tipo' => 'Entrada',
                'is_late' => $index === 13,
            ]);
        }
    }

    Student::query()->create([
        'school_id' => $school->id,
        'matricula' => 'CURSO-B-1',
        'nombre' => 'Estudiante de otro curso',
        'apellido' => 'Curso B',
        'curso' => '2do B',
        'id_lector' => 'B-1',
    ]);

    $response = $this->withSession($session)->get(route('dashboard.admin.page', [
        'page' => 'attendance',
        'course' => '1ro A',
        'attendance_status' => 'absent',
    ]));

    $response->assertOk()
        ->assertSee('13 registros')
        ->assertSee('Estudiante 14 Curso A')
        ->assertSee('data-attendance-roster-status="absent"', escape: false)
        ->assertViewHas('attendanceRosterSummary', fn (array $summary): bool => $summary === [
            'total' => 14,
            'present' => 12,
            'late' => 1,
            'absent' => 1,
        ])
        ->assertViewHas('attendanceRoster', fn ($roster): bool => $roster->count() === 1 && $roster->first()['status'] === 'absent');

    Carbon::setTestNow();
});

test('attendance dashboard does not expose raw adms events', function () {
    $school = School::query()->create(['code' => 'ADMS001', 'name' => 'Escuela ADMS']);
    $admin = User::factory()->create(['role' => 'superadmin', 'username' => 'admin-adms']);

    $this->withSession(['user' => [
        'id' => $admin->id,
        'username' => 'admin-adms',
        'role' => 'superadmin',
        'institution_code' => $school->code,
    ]])->get('/dashboard/admin/attendance')
        ->assertOk()
        ->assertSee('Actividad en vivo')
        ->assertDontSee('Eventos ADMS sin filtrar');
});

test('reports navigation opens a dedicated filterable dashboard', function () {
    Carbon::setTestNow('2026-09-28 10:00:00');
    $school = School::query()->create(['code' => 'REPORT001', 'name' => 'Escuela de Reportes']);
    $admin = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);
    $student = Student::query()->create([
        'school_id' => $school->id,
        'matricula' => 'REP-001',
        'nombre' => 'Ana',
        'apellido' => 'Reporte',
        'sexo' => 'Femenino',
        'curso' => '3ro A',
        'id_lector' => 'REP-1',
    ]);
    Attendance::query()->create([
        'school_id' => $school->id,
        'student_id' => $student->id,
        'matricula' => $student->matricula,
        'id_lector' => $student->id_lector,
        'fecha_hora' => now(),
        'curso' => $student->curso,
        'estado' => 'Tarde',
        'tipo' => 'Entrada',
        'is_late' => true,
    ]);
    Student::query()->create([
        'school_id' => $school->id,
        'matricula' => 'REP-002',
        'nombre' => 'Luis',
        'apellido' => 'Reporte',
        'sexo' => 'Masculino',
        'curso' => '3ro A',
        'id_lector' => 'REP-2',
    ]);
    Student::query()->create([
        'school_id' => $school->id,
        'matricula' => 'REP-003',
        'nombre' => 'Alex',
        'apellido' => 'Sin especificar',
        'curso' => '3ro A',
        'id_lector' => 'REP-3',
    ]);

    $response = $this->withSession(['user' => [
        'id' => $admin->id,
        'username' => $admin->username,
        'role' => 'admin',
        'school_id' => $school->id,
        'institution_code' => $school->code,
    ]])->get(route('dashboard.admin.page', [
        'page' => 'reports',
        'report_start' => '2026-09-01',
        'report_end' => '2026-09-30',
        'course' => '3ro A',
    ]));

    $response->assertOk()
        ->assertSee('data-reports-dashboard', escape: false)
        ->assertSee('Reportes de asistencia')
        ->assertSee('Femenino y masculino por curso')
        ->assertSee('Total general: 3')
        ->assertSee('Ana Reporte')
        ->assertViewHas('activePage', 'reports')
        ->assertViewHas('reportSummary', fn (array $summary): bool => $summary['entries'] === 1 && $summary['late'] === 1)
        ->assertViewHas('reportGenderTotals', fn (array $totals): bool => $totals === [
            'female' => 1,
            'male' => 1,
            'unspecified' => 1,
            'total' => 3,
        ]);

    $individualResponse = $this->withSession(['user' => [
        'id' => $admin->id,
        'username' => $admin->username,
        'role' => 'admin',
        'school_id' => $school->id,
        'institution_code' => $school->code,
    ]])->get(route('dashboard.admin.page', [
        'page' => 'reports',
        'report_start' => '2026-09-01',
        'report_end' => '2026-09-30',
        'report_student_id' => $student->id,
    ]));

    $individualResponse->assertOk()
        ->assertSee('data-report-student-search', escape: false)
        ->assertSee('data-individual-student-report', escape: false)
        ->assertSee('Reporte individual de asistencia')
        ->assertViewHas('selectedReportStudent', fn (?Student $selected): bool => $selected?->is($student) === true)
        ->assertViewHas('reportStudentSummary', fn (array $summary): bool => $summary['attendance_days'] === 1
            && $summary['late_days'] === 1
            && $summary['days_without_entry'] > 0);

    Carbon::setTestNow();
});

test('teacher dashboard redirects to login when there is no session', function () {
    $this->get('/dashboard/docente')->assertRedirect('/login');
});

test('teacher dashboard shows assigned courses', function () {
    $school = School::query()->create(['code' => 'INST001', 'name' => 'Escuela Central']);
    $teacher = User::factory()->create([
        'school_id' => $school->id,
        'role' => 'teacher',
        'username' => 'docente',
    ]);
    $course = Course::query()->create([
        'school_id' => $school->id,
        'code' => '5TO-A',
        'name' => '5to A',
    ]);
    $teacher->courses()->attach($course);

    $this->withSession(['user' => [
        'id' => $teacher->id,
        'username' => 'docente',
        'role' => 'teacher',
        'institution_code' => $school->code,
    ]])->get('/dashboard/docente')
        ->assertOk()
        ->assertSee('¿Quién está realmente en tu clase?')
        ->assertSee('5to A');
});

test('biometric register logs attendance successfully', function () {
    $student = Student::query()->create([
        'matricula' => 'MAT999',
        'nombre' => 'Test',
        'apellido' => 'Student',
        'curso' => '1º A',
        'id_lector' => '999',
    ]);

    $this->withHeader('X-Biometric-Token', 'test-biometric-token')->postJson('/api/asistencia', [
        'id_lector' => '999',
        'reader_key' => 'escuela-1-entrada',
        'reader_name' => 'Entrada principal',
        'reader_school' => 'Escuela 1',
        'reader_mac' => '00:17:61:11:18:e3',
        'reader_ip' => '192.168.100.10',
    ])->assertOk()->assertJson([
        'success' => true,
        'student' => 'Test Student',
        'matricula' => 'MAT999',
        'curso' => '1º A',
        'tipo' => 'Entrada',
    ]);

    $this->assertDatabaseHas('attendances', [
        'student_id' => $student->id,
        'reader_key' => 'escuela-1-entrada',
        'reader_mac' => '00:17:61:11:18:e3',
        'reader_ip' => '192.168.100.10',
    ]);
});

test('biometric register does not use a time cooldown', function () {
    Carbon::setTestNow('2026-09-23 08:00:00');
    $school = School::query()->create([
        'code' => 'NO-COOLDOWN',
        'name' => 'Escuela sin intervalo',
        'attendance_entry_time' => '08:00',
        'attendance_exit_time' => '14:00',
    ]);

    Student::query()->create([
        'school_id' => $school->id,
        'matricula' => 'MAT998',
        'nombre' => 'Registro',
        'apellido' => 'Continuo',
        'curso' => '1º A',
        'id_lector' => '998',
    ]);

    $this->withHeader('X-Biometric-Token', 'test-biometric-token')
        ->postJson('/api/asistencia', ['id_lector' => '998'])
        ->assertOk()
        ->assertJsonPath('tipo', 'Entrada');

    $this->withHeader('X-Biometric-Token', 'test-biometric-token')
        ->postJson('/api/asistencia', ['id_lector' => '998'])
        ->assertOk()
        ->assertJsonPath('ignored', true);

    $this->assertDatabaseCount('attendances', 2);
    $this->assertDatabaseHas('attendances', [
        'id_lector' => '998',
        'tipo' => 'Ignorado',
        'ignored_reason' => 'early_departure_not_authorized',
    ]);
    Carbon::setTestNow();
});

test('superadministrator can register a student linked to a reader', function () {
    $school = School::query()->create(['code' => 'INST001', 'name' => 'Escuela Central']);
    $course = Course::query()->create([
        'school_id' => $school->id,
        'code' => 'PRUEBA-A',
        'name' => 'Prueba A',
    ]);
    $admin = User::factory()->create(['role' => 'superadmin', 'username' => 'admin']);

    $this->withSession(['user' => [
        'id' => $admin->id,
        'username' => 'admin',
        'role' => 'superadmin',
        'institution_code' => $school->code,
    ]])->post('/dashboard/admin/estudiantes', [
        'school_id' => $school->id,
        'course_id' => $course->id,
        'matricula' => 'MAT1000',
        'nombre' => 'Nueva',
        'apellido' => 'Estudiante',
        'numero_lista' => 1,
        'id_lector' => '1000',
    ])->assertRedirect(route('dashboard.admin.page', 'students'));

    $this->assertDatabaseHas('students', ['matricula' => 'MAT1000', 'id_lector' => '1000']);
    $this->assertDatabaseHas('students', ['course_id' => $course->id, 'curso' => 'Prueba A']);
});

test('biometric register fails for unregistered id', function () {
    $this->withHeader('X-Biometric-Token', 'test-biometric-token')
        ->postJson('/api/asistencia', ['id_lector' => '9999'])
        ->assertNotFound()
        ->assertJsonPath('message', 'ID 9999 no registrado en la base de datos.');
});

test('biometric register requires the configured access token', function () {
    $this->postJson('/api/asistencia', ['id_lector' => '1'])
        ->assertUnauthorized()
        ->assertJsonPath('message', 'No autorizado para registrar asistencia.');
});
