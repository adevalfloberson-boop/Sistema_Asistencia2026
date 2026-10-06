<?php

namespace App\Services;

use App\Mail\AttendanceRecordedMail;
use App\Models\Attendance;
use App\Models\AttendanceNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class AttendanceNotificationService
{
    public function __construct(private readonly SchoolMailerFactory $mailerFactory) {}

    /**
     * @param  array<int, string>|null  $recipientFilter
     */
    public function sendFor(Attendance $attendance, ?array $recipientFilter = null): void
    {
        $attendance->loadMissing(['school.notificationSetting', 'student']);
        $setting = $attendance->school?->notificationSetting;

        if ($setting === null || ! $setting->email_enabled) {
            $this->record($attendance, null, 'Omitido', 'Las notificaciones están desactivadas.');

            return;
        }

        if (! $this->eventIsEnabled($attendance, $setting)) {
            $this->record($attendance, null, 'Omitido', 'Este tipo de evento está desactivado.');

            return;
        }

        if ($attendance->student === null) {
            $this->record($attendance, null, 'Omitido', 'No se encontró el estudiante asociado.');

            return;
        }

        $recipients = collect([
            $setting->send_to_father ? $attendance->student->father_email : null,
            $setting->send_to_mother ? $attendance->student->mother_email : null,
        ])->filter()->map(fn (string $email): string => Str::lower(trim($email)))->unique()->values();

        if ($recipientFilter !== null) {
            $allowedRecipients = collect($recipientFilter)
                ->map(fn (string $email): string => Str::lower(trim($email)));
            $recipients = $recipients->intersect($allowedRecipients)->values();
        }

        if ($recipients->isEmpty()) {
            $this->record($attendance, null, 'Omitido', 'El estudiante no tiene destinatarios configurados.');

            return;
        }

        foreach ($recipients as $recipient) {
            try {
                $this->mailerFactory->make($setting)
                    ->to($recipient)
                    ->send((new AttendanceRecordedMail($attendance))->from($setting->from_address, $setting->from_name));
                $this->record($attendance, $recipient, 'Enviado');
            } catch (Throwable $exception) {
                Log::warning('No se pudo enviar una notificación de asistencia.', [
                    'school_id' => $attendance->school_id,
                    'attendance_id' => $attendance->id,
                    'reason' => $this->safeFailureReason($exception),
                ]);
                $this->record($attendance, $recipient, 'Fallido', $this->safeFailureReason($exception));
            }
        }
    }

    private function eventIsEnabled(Attendance $attendance, object $setting): bool
    {
        return match (true) {
            $attendance->is_early_departure => $setting->notify_early_departure,
            $attendance->tipo === 'Entrada' => $setting->notify_entry,
            $attendance->tipo === 'Salida' => $setting->notify_exit,
            default => false,
        };
    }

    private function record(Attendance $attendance, ?string $recipient, string $status, ?string $reason = null): void
    {
        AttendanceNotification::query()->create([
            'school_id' => $attendance->school_id,
            'student_id' => $attendance->student_id,
            'attendance_id' => $attendance->id,
            'event_type' => $attendance->is_early_departure ? 'Salida anticipada' : $attendance->tipo,
            'recipient' => $recipient,
            'status' => $status,
            'failure_reason' => $reason,
            'processed_at' => now(),
        ]);
    }

    private function safeFailureReason(Throwable $exception): string
    {
        return Str::limit(preg_replace('/(?:password|auth|credential)[^\s]*/i', '[dato protegido]', $exception->getMessage()), 500);
    }
}
