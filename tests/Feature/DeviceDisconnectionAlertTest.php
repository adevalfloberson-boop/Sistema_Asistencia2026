<?php

use App\Mail\DeviceDisconnectedMail;
use App\Mail\DeviceStatusSummaryMail;
use App\Models\BiometricDevice;
use App\Models\School;
use App\Services\SchoolMailerFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailer;
use Illuminate\Mail\PendingMail;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

function createDisconnectedAlertDevice(array $settingOverrides = []): BiometricDevice
{
    $school = School::query()->create([
        'code' => 'ALERT-'.(School::query()->count() + 1),
        'name' => 'Centro de Alertas',
    ]);

    $school->notificationSetting()->create(array_merge([
        'device_alerts_enabled' => true,
        'device_alert_email' => 'superflober516@gmail.com, honattanreyes58@gmail.com',
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => 587,
        'smtp_security' => 'starttls',
        'from_address' => 'sistema@gmail.com',
        'from_name' => 'Asistencia Escolar',
    ], $settingOverrides));

    return BiometricDevice::query()->create([
        'school_id' => $school->id,
        'key' => 'alert-reader-'.$school->id,
        'name' => 'Entrada principal',
        'mac_address' => sprintf('00:17:61:11:18:%02x', $school->id),
        'network' => '192.168.100',
        'status' => 'connected',
        'last_seen_at' => now()->subMinutes(20),
    ]);
}

test('monitor sends only one email while a reader remains disconnected', function () {
    Carbon::setTestNow('2026-10-06 12:00:00');
    config()->set('attendance.device_offline_after_minutes', 10);
    $device = createDisconnectedAlertDevice();

    $pendingMail = Mockery::mock(PendingMail::class);
    $pendingMail->shouldReceive('send')
        ->once()
        ->with(Mockery::on(fn (object $mail): bool => $mail instanceof DeviceDisconnectedMail && $mail->device->is($device)));
    $mailer = Mockery::mock(Mailer::class);
    $mailer->shouldReceive('to')->once()->with([
        'superflober516@gmail.com',
        'honattanreyes58@gmail.com',
    ])->andReturn($pendingMail);
    $factory = Mockery::mock(SchoolMailerFactory::class);
    $factory->shouldReceive('make')->once()->andReturn($mailer);
    $this->app->instance(SchoolMailerFactory::class, $factory);

    $this->artisan('devices:monitor')->assertSuccessful();
    $this->artisan('devices:monitor')->assertSuccessful();

    $device->refresh();
    expect($device->status)->toBe('disconnected')
        ->and($device->last_disconnected_at)->not->toBeNull()
        ->and($device->disconnection_count)->toBe(1)
        ->and($device->disconnect_alert_sent_at?->toDateTimeString())->toBe('2026-10-06 12:00:00');
});

test('monitor records a disconnection without emailing when alerts are disabled', function () {
    config()->set('attendance.device_offline_after_minutes', 10);
    $device = createDisconnectedAlertDevice(['device_alerts_enabled' => false]);
    $factory = Mockery::mock(SchoolMailerFactory::class);
    $factory->shouldNotReceive('make');
    $this->app->instance(SchoolMailerFactory::class, $factory);

    $this->artisan('devices:monitor')->assertSuccessful();

    expect($device->fresh()->status)->toBe('disconnected')
        ->and($device->fresh()->disconnection_count)->toBe(1)
        ->and($device->fresh()->disconnect_alert_sent_at)->toBeNull();
});

test('deployment summary command sends current status and accumulated outage count', function () {
    $device = createDisconnectedAlertDevice();
    $device->update(['disconnection_count' => 3]);

    $pendingMail = Mockery::mock(PendingMail::class);
    $pendingMail->shouldReceive('send')
        ->once()
        ->with(Mockery::on(fn (object $mail): bool => $mail instanceof DeviceStatusSummaryMail
            && $mail->devices->first()->disconnection_count === 3));
    $mailer = Mockery::mock(Mailer::class);
    $mailer->shouldReceive('to')->once()->with([
        'superflober516@gmail.com',
        'honattanreyes58@gmail.com',
    ])->andReturn($pendingMail);
    $factory = Mockery::mock(SchoolMailerFactory::class);
    $factory->shouldReceive('make')->once()->andReturn($mailer);
    $this->app->instance(SchoolMailerFactory::class, $factory);

    $this->artisan('devices:send-status-summary', [
        '--to' => ['superflober516@gmail.com', 'honattanreyes58@gmail.com'],
    ])->assertSuccessful();
});

test('a successful heartbeat enables a future disconnection alert', function () {
    config()->set('attendance.api_token', 'agent-secret');
    $device = createDisconnectedAlertDevice();
    $device->update([
        'status' => 'disconnected',
        'disconnect_alert_sent_at' => now(),
    ]);

    $this->withHeader('X-Biometric-Token', 'agent-secret')
        ->postJson(route('api.biometric.heartbeat', $device->key), ['status' => 'connected'])
        ->assertOk();

    expect($device->fresh()->status)->toBe('connected')
        ->and($device->fresh()->disconnect_alert_sent_at)->toBeNull();
});
