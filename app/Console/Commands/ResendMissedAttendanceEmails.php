<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\AttendanceNotification;
use App\Services\AttendanceNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

#[Signature('attendance:resend-missed-emails {--date= : Fecha en formato AAAA-MM-DD; por defecto hoy} {--school= : ID de la escuela} {--limit=500 : Máximo de registros} {--send : Envía los correos; sin esta opción solo muestra la vista previa}')]
#[Description('Reenvía alertas de asistencia no entregadas únicamente a destinatarios de Gmail.')]
class ResendMissedAttendanceEmails extends Command
{
    public function handle(AttendanceNotificationService $notificationService): int
    {
        $date = $this->date();

        if ($date === null) {
            $this->error('La fecha debe utilizar el formato AAAA-MM-DD.');

            return self::INVALID;
        }

        $schoolId = filter_var($this->option('school'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($this->option('school') !== null && $schoolId === false) {
            $this->error('El identificador de la escuela debe ser un número entero válido.');

            return self::INVALID;
        }

        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5000]]);
        if ($limit === false) {
            $this->error('El límite debe estar entre 1 y 5000.');

            return self::INVALID;
        }

        $pending = $this->pendingAttendances($date, $schoolId ?: null, $limit);

        if ($pending->isEmpty()) {
            $this->info('No se encontraron alertas pendientes para destinatarios de Gmail.');

            return self::SUCCESS;
        }

        $this->table(
            ['Fecha y hora', 'Escuela', 'Estudiante', 'Evento', 'Destinatarios Gmail'],
            $pending->map(fn (array $item): array => [
                $item['attendance']->fecha_hora->format('d/m/Y H:i'),
                $item['attendance']->school->name,
                $item['attendance']->student->nombre.' '.$item['attendance']->student->apellido,
                $item['attendance']->is_early_departure ? 'Salida anticipada' : $item['attendance']->tipo,
                implode(', ', $item['recipients']),
            ]),
        );

        if (! $this->option('send')) {
            $this->warn("Vista previa: {$pending->count()} registros. Agrega --send para realizar el envío.");

            return self::SUCCESS;
        }

        $pending->each(function (array $item) use ($notificationService): void {
            $notificationService->sendFor($item['attendance'], $item['recipients']);
        });

        $this->info("Proceso finalizado para {$pending->count()} registros de asistencia.");

        return self::SUCCESS;
    }

    private function date(): ?Carbon
    {
        $value = $this->option('date') ?: now()->toDateString();

        if (! is_string($value) || ! Carbon::hasFormatWithModifiers($value, 'Y-m-d')) {
            return null;
        }

        return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
    }

    /**
     * @return Collection<int, array{attendance: Attendance, recipients: array<int, string>}>
     */
    private function pendingAttendances(Carbon $date, ?int $schoolId, int $limit): Collection
    {
        return Attendance::query()
            ->with(['school.notificationSetting', 'student'])
            ->whereDate('fecha_hora', $date)
            ->where('is_ignored', false)
            ->where('sync_source', '!=', 'history')
            ->whereIn('tipo', ['Entrada', 'Salida'])
            ->when($schoolId, fn ($query, int $id) => $query->where('school_id', $id))
            ->oldest('fecha_hora')
            ->limit($limit)
            ->get()
            ->map(function (Attendance $attendance): ?array {
                $setting = $attendance->school?->notificationSetting;

                if (
                    $attendance->student === null
                    || $setting === null
                    || ! $setting->email_enabled
                    || ($attendance->is_early_departure && ! $setting->notify_early_departure)
                    || (! $attendance->is_early_departure && $attendance->tipo === 'Entrada' && ! $setting->notify_entry)
                    || (! $attendance->is_early_departure && $attendance->tipo === 'Salida' && ! $setting->notify_exit)
                ) {
                    return null;
                }

                $recipients = collect([
                    $setting->send_to_father ? $attendance->student->father_email : null,
                    $setting->send_to_mother ? $attendance->student->mother_email : null,
                ])->filter()
                    ->map(fn (string $email): string => Str::lower(trim($email)))
                    ->filter(fn (string $email): bool => Str::endsWith($email, ['@gmail.com', '@googlemail.com']))
                    ->unique()
                    ->values();

                $sentRecipients = AttendanceNotification::query()
                    ->where('attendance_id', $attendance->id)
                    ->where('status', 'Enviado')
                    ->whereIn('recipient', $recipients)
                    ->pluck('recipient')
                    ->map(fn (string $email): string => Str::lower(trim($email)));

                $pendingRecipients = $recipients->diff($sentRecipients)->values()->all();

                return $pendingRecipients === [] ? null : [
                    'attendance' => $attendance,
                    'recipients' => $pendingRecipients,
                ];
            })
            ->filter()
            ->values();
    }
}
