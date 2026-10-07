<?php

namespace App\Console\Commands;

use App\Mail\DeviceDisconnectedMail;
use App\Models\BiometricDevice;
use App\Services\SchoolMailerFactory;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('devices:monitor')]
#[Description('Detecta lectores desconectados y envía una alerta por correo')]
class MonitorBiometricDevices extends Command
{
    public function handle(SchoolMailerFactory $mailerFactory): int
    {
        $offlineBefore = now()->subMinutes((int) config('attendance.device_offline_after_minutes'));
        $alerted = 0;

        BiometricDevice::query()
            ->with('school.notificationSetting')
            ->where('is_active', true)
            ->whereNotNull('last_seen_at')
            ->where('last_seen_at', '<', $offlineBefore)
            ->whereNull('disconnect_alert_sent_at')
            ->each(function (BiometricDevice $device) use ($mailerFactory, &$alerted): void {
                $isNewDisconnection = $device->status !== 'disconnected';
                $device->update([
                    'status' => 'disconnected',
                    'last_disconnected_at' => $isNewDisconnection ? now() : $device->last_disconnected_at,
                    'disconnection_count' => $isNewDisconnection
                        ? $device->disconnection_count + 1
                        : $device->disconnection_count,
                ]);

                $setting = $device->school?->notificationSetting;

                if (! $setting?->device_alerts_enabled || blank($setting->device_alert_email)) {
                    return;
                }

                if (blank($setting->smtp_host) || blank($setting->smtp_port) || blank($setting->from_address)) {
                    return;
                }

                try {
                    $recipients = preg_split('/[\s,;]+/', $setting->device_alert_email, -1, PREG_SPLIT_NO_EMPTY);
                    $mailerFactory->make($setting)
                        ->to($recipients)
                        ->send(new DeviceDisconnectedMail($device, $device->school, $setting));

                    $device->update(['disconnect_alert_sent_at' => now()]);
                    $alerted++;
                } catch (Throwable $exception) {
                    report($exception);
                }
            });

        $this->info("Lectores monitoreados. Alertas enviadas: {$alerted}.");

        return self::SUCCESS;
    }
}
