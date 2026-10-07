<?php

namespace App\Console\Commands;

use App\Mail\DeviceStatusSummaryMail;
use App\Models\School;
use App\Services\SchoolMailerFactory;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('devices:send-status-summary {--to=* : Destinatarios del resumen} ')]
#[Description('Envía un resumen del estado y las caídas registradas de los lectores')]
class SendDeviceStatusSummary extends Command
{
    public function handle(SchoolMailerFactory $mailerFactory): int
    {
        $requestedRecipients = collect($this->option('to'))
            ->filter(fn (mixed $email): bool => is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->unique()
            ->values()
            ->all();
        $sent = 0;

        School::query()
            ->with(['notificationSetting', 'devices' => fn ($query) => $query->where('is_active', true)])
            ->whereHas('notificationSetting')
            ->each(function (School $school) use ($mailerFactory, $requestedRecipients, &$sent): void {
                $setting = $school->notificationSetting;
                $recipients = $requestedRecipients ?: preg_split(
                    '/[\s,;]+/',
                    (string) $setting->device_alert_email,
                    -1,
                    PREG_SPLIT_NO_EMPTY,
                );

                if ($recipients === [] || blank($setting->smtp_host) || blank($setting->smtp_port) || blank($setting->from_address)) {
                    return;
                }

                try {
                    $mailerFactory->make($setting)
                        ->to($recipients)
                        ->send(new DeviceStatusSummaryMail($school, $school->devices, $setting));
                    $sent++;
                } catch (Throwable $exception) {
                    report($exception);
                }
            });

        $this->info("Resúmenes enviados: {$sent}.");

        return self::SUCCESS;
    }
}
