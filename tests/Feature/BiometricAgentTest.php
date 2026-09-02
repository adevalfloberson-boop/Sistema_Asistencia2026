<?php

use App\Models\BiometricDevice;
use App\Models\DeviceCommand;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('attendance.api_token', 'agent-secret');
});

function createAgentDevice(): BiometricDevice
{
    $school = School::query()->create([
        'code' => 'INST001',
        'name' => 'Escuela Primaria Central',
    ]);

    return BiometricDevice::query()->create([
        'school_id' => $school->id,
        'key' => 'entrada-principal',
        'name' => 'Entrada principal',
        'mac_address' => '00:17:61:11:18:e3',
        'network' => '192.168.100',
        'port' => 4370,
        'device_password' => '0',
    ]);
}

test('agent receives active reader configuration', function () {
    createAgentDevice();

    $this->withHeader('X-Biometric-Token', 'agent-secret')
        ->getJson(route('api.biometric.devices'))
        ->assertOk()
        ->assertJsonPath('devices.0.key', 'entrada-principal')
        ->assertJsonPath('devices.0.mac', '00:17:61:11:18:e3')
        ->assertJsonPath('devices.0.school', 'Escuela Primaria Central');
});

test('agent heartbeat updates device information', function () {
    $device = createAgentDevice();

    $this->withHeader('X-Biometric-Token', 'agent-secret')
        ->postJson(route('api.biometric.heartbeat', $device->key), [
            'status' => 'connected',
            'ip_address' => '192.168.100.10',
            'serial_number' => 'UA760-001',
            'model' => 'UA760',
            'firmware_version' => '6.60',
            'user_count' => 24,
            'fingerprint_count' => 30,
        ])
        ->assertOk();

    $device->refresh();
    expect($device->status)->toBe('connected')
        ->and($device->ip_address)->toBe('192.168.100.10')
        ->and($device->serial_number)->toBe('UA760-001')
        ->and($device->last_seen_at)->not->toBeNull();
});

test('agent claims and completes a queued command', function () {
    $device = createAgentDevice();
    $command = DeviceCommand::query()->create([
        'biometric_device_id' => $device->id,
        'type' => 'inspect',
        'status' => 'pending',
    ]);

    $this->withHeader('X-Biometric-Token', 'agent-secret')
        ->getJson(route('api.biometric.commands.next', $device->key))
        ->assertOk()
        ->assertJsonPath('id', $command->id)
        ->assertJsonPath('type', 'inspect');

    expect($command->fresh()->status)->toBe('processing');

    $this->withHeader('X-Biometric-Token', 'agent-secret')
        ->postJson(route('api.biometric.commands.complete', $command), [
            'success' => true,
            'result' => ['model' => 'UA760', 'user_count' => 10],
        ])
        ->assertOk();

    expect($command->fresh()->status)->toBe('completed')
        ->and($device->fresh()->model)->toBe('UA760')
        ->and($device->fresh()->user_count)->toBe(10);
});

test('agent endpoints reject invalid tokens', function () {
    $this->withHeader('X-Biometric-Token', 'incorrect')
        ->getJson(route('api.biometric.devices'))
        ->assertUnauthorized();
});
