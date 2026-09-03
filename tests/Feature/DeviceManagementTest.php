<?php

use App\Models\BiometricDevice;
use App\Models\BiometricEnrollment;
use App\Models\DeviceCommand;
use App\Models\School;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function administratorSession(): array
{
    return [
        'user' => [
            'id' => 1,
            'username' => 'admin',
            'role' => 'superadmin',
            'institution_code' => 'INST001',
        ],
    ];
}

test('administrator can add a biometric device using its name and mac address', function () {
    $school = School::query()->create([
        'code' => 'INST001',
        'name' => 'Escuela Primaria Central',
    ]);

    $response = $this->withSession(administratorSession())->post(route('devices.store'), [
        'school_id' => $school->id,
        'name' => 'Entrada principal',
        'mac_address' => '00:17:61:11:18:e3',
        'model' => 'UA760',
        'location' => 'Recepción',
        'connection_mode' => 'sdk',
        'network' => '192.168.100',
        'ip_address' => '',
        'port' => 4370,
    ]);

    $response->assertRedirect(route('dashboard.admin.page', 'devices'));
    $this->assertDatabaseHas('biometric_devices', [
        'school_id' => $school->id,
        'name' => 'Entrada principal',
        'mac_address' => '00:17:61:11:18:e3',
        'model' => 'UA760',
    ]);
    $this->assertDatabaseHas('device_commands', [
        'type' => 'inspect',
        'status' => 'pending',
    ]);
});

test('administrator can add an adms device using its serial number without local network data', function () {
    $school = School::query()->create([
        'code' => 'INST001',
        'name' => 'Escuela Primaria Central',
    ]);

    $response = $this->withSession(administratorSession())->post(route('devices.store'), [
        'school_id' => $school->id,
        'name' => 'Entrada ADMS',
        'serial_number' => 'M2F123456',
        'model' => 'M2F PRO-LR',
        'location' => 'Recepción',
        'connection_mode' => 'adms',
        'port' => 4370,
    ]);

    $response->assertRedirect(route('dashboard.admin.page', 'devices'));
    $this->assertDatabaseHas('biometric_devices', [
        'school_id' => $school->id,
        'serial_number' => 'M2F123456',
        'connection_mode' => 'adms',
        'mac_address' => null,
    ]);
});

test('administrator can queue fingerprint enrollment without interrupting attendance', function () {
    $school = School::query()->create([
        'code' => 'INST001',
        'name' => 'Escuela Primaria Central',
    ]);
    $device = BiometricDevice::query()->create([
        'school_id' => $school->id,
        'key' => 'entrada-principal',
        'name' => 'Entrada principal',
        'mac_address' => '00:17:61:11:18:e3',
        'network' => '192.168.100',
    ]);
    $student = Student::query()->create([
        'school_id' => $school->id,
        'matricula' => 'MAT-001',
        'nombre' => 'Ana',
        'apellido' => 'Pérez',
        'curso' => '1º A',
        'id_lector' => '1001',
    ]);

    $response = $this->withSession(administratorSession())->post(route('devices.enroll'), [
        'student_id' => $student->id,
        'biometric_device_id' => $device->id,
        'finger_index' => 1,
    ]);

    $response->assertRedirect(route('dashboard.admin.page', 'enrollment'));
    expect(DeviceCommand::query()->where('type', 'enroll')->where('status', 'pending')->exists())->toBeTrue();
    expect(BiometricEnrollment::query()->where([
        'student_id' => $student->id,
        'biometric_device_id' => $device->id,
        'finger_index' => 1,
        'status' => 'pending',
    ])->exists())->toBeTrue();
});

test('non administrator cannot change biometric devices', function () {
    $school = School::query()->create([
        'code' => 'INST001',
        'name' => 'Escuela Primaria Central',
    ]);

    $this->withSession(['user' => ['id' => 2, 'username' => 'docente', 'role' => 'teacher']])
        ->post(route('devices.store'), [
            'school_id' => $school->id,
            'name' => 'Entrada principal',
            'mac_address' => '00:17:61:11:18:e3',
            'connection_mode' => 'sdk',
            'network' => '192.168.100',
            'port' => 4370,
        ])
        ->assertForbidden();
});

test('administrator can read lightweight device connection statuses', function () {
    $school = School::query()->create([
        'code' => 'STATUS001',
        'name' => 'Escuela de estado',
    ]);
    $device = BiometricDevice::query()->create([
        'school_id' => $school->id,
        'key' => 'estado-adms',
        'name' => 'Lector ADMS',
        'serial_number' => 'STATUS-ADMS-001',
        'connection_mode' => 'adms',
        'last_seen_at' => now(),
    ]);

    $this->withSession(administratorSession())
        ->getJson(route('devices.statuses'))
        ->assertOk()
        ->assertJsonPath('summary.online', 1)
        ->assertJsonPath('devices.0.id', $device->id)
        ->assertJsonPath('devices.0.status', 'online');
});
