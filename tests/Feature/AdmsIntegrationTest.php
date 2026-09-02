<?php

use App\Models\AdmsEvent;
use App\Models\Attendance;
use App\Models\BiometricDevice;
use App\Models\DeviceCommand;
use App\Models\School;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function admsDevice(): array
{
    $school = School::query()->create([
        'code' => 'ADMS001',
        'name' => 'Escuela ADMS',
    ]);

    $device = BiometricDevice::query()->create([
        'school_id' => $school->id,
        'key' => 'entrada-adms',
        'name' => 'Entrada ADMS',
        'serial_number' => 'M2F123456',
        'model' => 'M2F PRO-LR',
        'connection_mode' => 'adms',
        'network' => null,
    ]);

    return [$school, $device];
}

test('registered adms reader can initialize and reports online', function () {
    [, $device] = admsDevice();

    $response = $this->get('/iclock/cdata?SN=M2F123456&options=all');

    $response->assertOk()->assertSeeText('GET OPTION FROM: M2F123456');
    expect($device->fresh()->status)->toBe('connected')
        ->and($device->fresh()->last_seen_at)->not->toBeNull();
});

test('unregistered serial number cannot send adms data', function () {
    $this->get('/iclock/cdata?SN=UNKNOWN&options=all')->assertNotFound();
});

test('adms attendance creates one deduplicated attendance using existing rules', function () {
    [$school, $device] = admsDevice();
    Student::query()->create([
        'school_id' => $school->id,
        'matricula' => 'MAT-001',
        'nombre' => 'Ana',
        'apellido' => 'Pérez',
        'curso' => '1º A',
        'id_lector' => '1001',
    ]);
    $payload = "1001\t2026-08-29 07:30:00\t0\t1\t0\t0\t0\n";

    $this->call('POST', '/iclock/cdata?SN=M2F123456&table=ATTLOG', [], [], [], [], $payload)
        ->assertOk()
        ->assertSeeText('OK: 1');
    $this->call('POST', '/iclock/cdata?SN=M2F123456&table=ATTLOG', [], [], [], [], $payload)
        ->assertOk();

    expect(Attendance::query()->count())->toBe(1)
        ->and(AdmsEvent::query()->count())->toBe(1)
        ->and(Attendance::query()->first()->sync_source)->toBe('adms')
        ->and(Attendance::query()->first()->biometric_device_id)->toBe($device->id);
});

test('unknown biometric user remains available for a later retry', function () {
    admsDevice();
    $payload = "9999\t2026-08-29 07:35:00\t0\t1\t0\t0\t0\n";

    $this->call('POST', '/iclock/cdata?SN=M2F123456&table=ATTLOG', [], [], [], [], $payload)
        ->assertOk();

    expect(Attendance::query()->count())->toBe(0)
        ->and(AdmsEvent::query()->first()->processing_status)->toBe('unmatched');
});

test('adms reader can poll and complete a supported command', function () {
    [, $device] = admsDevice();
    $command = DeviceCommand::query()->create([
        'biometric_device_id' => $device->id,
        'type' => 'sync_time',
        'status' => 'pending',
    ]);

    $this->get('/iclock/getrequest?SN=M2F123456')
        ->assertOk()
        ->assertSeeText("C:{$command->id}:SET OPTIONS DateTime=");

    $this->call('POST', '/iclock/devicecmd?SN=M2F123456', [], [], [], [], "ID={$command->id}&Return=0&CMD=SET OPTIONS")
        ->assertOk();

    expect($command->fresh()->status)->toBe('completed');
});
