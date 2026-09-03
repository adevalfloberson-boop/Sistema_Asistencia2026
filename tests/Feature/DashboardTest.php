<?php

use App\Models\AdmsEvent;
use App\Models\BiometricDevice;
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
        ->assertSee('Red global de lectores');
});

test('superadministrator dashboard shows unmatched raw adms events', function () {
    $school = School::query()->create(['code' => 'ADMS001', 'name' => 'Escuela ADMS']);
    $admin = User::factory()->create(['role' => 'superadmin', 'username' => 'admin-adms']);
    $device = BiometricDevice::query()->create([
        'school_id' => $school->id,
        'key' => 'lector-adms-prueba',
        'name' => 'Lector ADMS prueba',
        'serial_number' => 'UFS2255200490',
        'connection_mode' => 'adms',
        'network' => null,
        'ip_address' => '10.0.3.33',
    ]);
    AdmsEvent::query()->create([
        'biometric_device_id' => $device->id,
        'device_event_key' => hash('sha256', 'adms-event-test'),
        'user_id' => '2',
        'event_at' => now(),
        'processing_status' => 'unmatched',
        'raw_payload' => "2\t2026-08-31 10:46:39\t0\t1\t0\t0\t0",
        'error' => 'ID biométrico 2 no registrado en esta escuela.',
    ]);

    $this->withSession(['user' => [
        'id' => $admin->id,
        'username' => 'admin-adms',
        'role' => 'superadmin',
        'institution_code' => $school->code,
    ]])->get('/dashboard/admin/attendance')
        ->assertOk()
        ->assertSee('Eventos ADMS sin filtrar')
        ->assertSee('Lector ADMS prueba')
        ->assertSee('Sin coincidencia')
        ->assertSee('ID biométrico 2 no registrado en esta escuela.');
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

test('biometric register records an exit after ten minutes', function () {
    Carbon::setTestNow(now());

    Student::query()->create([
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
        ->assertStatus(422)
        ->assertJsonPath('remaining_seconds', 600);

    Carbon::setTestNow(now()->addMinutes(10));

    $this->withHeader('X-Biometric-Token', 'test-biometric-token')
        ->postJson('/api/asistencia', ['id_lector' => '998'])
        ->assertOk()
        ->assertJsonPath('tipo', 'Salida');

    $this->assertDatabaseCount('attendances', 2);
    $this->assertDatabaseHas('attendances', ['id_lector' => '998', 'tipo' => 'Salida']);
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
