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

test('adms reader inventory updates counts from its registration payload', function () {
    [, $device] = admsDevice();
    $payload = 'DeviceName=M2F PRO-LR,FWVersion=1.2.3,Platform=ZMM220,MACAddress=00-17-61-11-18-E3,UserCount=31,FPCount=34,AttLogCount=150,MaxUserCount=3000,MaxFingerCount=6000';

    $this->call('POST', '/iclock/registry?SN=M2F123456', [], [], [], [], $payload)
        ->assertOk();

    expect($device->fresh()->model)->toBe('M2F PRO-LR')
        ->and($device->fresh()->firmware_version)->toBe('1.2.3')
        ->and($device->fresh()->platform)->toBe('ZMM220')
        ->and($device->fresh()->mac_address)->toBe('00:17:61:11:18:e3')
        ->and($device->fresh()->user_count)->toBe(31)
        ->and($device->fresh()->fingerprint_count)->toBe(34)
        ->and($device->fresh()->attendance_count)->toBe(150)
        ->and($device->fresh()->capacity)->toMatchArray(['users' => 3000, 'fingerprints' => 6000]);
});

test('a new adms serial is discovered and waits for school assignment', function () {
    $this->get('/iclock/cdata?SN=UNKNOWN&options=all')
        ->assertOk()
        ->assertSeeText('GET OPTION FROM: UNKNOWN');

    $device = BiometricDevice::query()->where('serial_number', 'UNKNOWN')->firstOrFail();

    expect($device->school_id)->toBeNull()
        ->and($device->is_active)->toBeFalse()
        ->and($device->status)->toBe('pending_assignment')
        ->and($device->last_seen_at)->not->toBeNull();
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

test('adms registers the second punch as exit after the configured minimum time', function () {
    [$school] = admsDevice();
    Student::query()->create([
        'school_id' => $school->id,
        'matricula' => 'MAT-002',
        'nombre' => 'Luis',
        'apellido' => 'Gómez',
        'curso' => '6to B DAAI 2026-2027',
        'numero_lista' => 1,
        'id_lector' => '1002',
    ]);

    $this->travelTo('2026-09-02 07:30:00');
    $this->call('POST', '/iclock/cdata?SN=M2F123456&table=ATTLOG', [], [], [], [], "1002\t2026-09-02 07:30:00\t0\t1\t0\t0\t0\n")
        ->assertOk();
    $this->travel(10)->minutes();
    $this->call('POST', '/iclock/cdata?SN=M2F123456&table=ATTLOG', [], [], [], [], "1002\t2026-09-02 07:40:00\t0\t1\t0\t0\t0\n")
        ->assertOk();
    $this->travelBack();

    expect(Attendance::query()->orderBy('fecha_hora')->pluck('tipo')->all())
        ->toBe(['Entrada', 'Salida'])
        ->and(Attendance::query()->where('is_ignored', true)->count())->toBe(0);
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
