<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\BiometricDevice;
use App\Models\EarlyDepartureAuthorization;
use App\Models\SchoolScheduleException;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class BiometricAttendanceRecorder
{
    public function __construct(private readonly AttendanceNotificationService $notificationService) {}

    /**
     * @param  array{
     *     event_key?: string|null,
     *     event_timestamp?: string|null,
     *     event_source?: string|null,
     *     device_status?: int|null,
     *     device_punch?: int|null,
     *     reader_key?: string|null,
     *     reader_name?: string|null,
     *     reader_school?: string|null,
     *     reader_mac?: string|null,
     *     reader_ip?: string|null
     * }  $event
     * @return array{attendance: Attendance|null, created: bool, debounced: bool, ignored: bool, remaining_seconds: int}
     */
    public function record(Student $student, ?BiometricDevice $device, array $event): array
    {
        $result = DB::transaction(function () use ($student, $device, $event): array {
            $eventKey = $event['event_key'] ?? null;
            $recordedAt = in_array(($event['event_source'] ?? 'live'), ['history', 'adms'], true) && isset($event['event_timestamp'])
                ? Carbon::parse($event['event_timestamp'])
                : now();

            if ($eventKey !== null) {
                $existing = Attendance::query()->where('device_event_key', $eventKey)->first();

                if ($existing !== null) {
                    return $this->result($existing, false);
                }

                $legacyMatch = Attendance::query()
                    ->where('student_id', $student->id)
                    ->where('fecha_hora', $recordedAt)
                    ->when(
                        $device !== null,
                        fn ($query) => $query->where(function ($deviceQuery) use ($device): void {
                            $deviceQuery->where('biometric_device_id', $device->id)
                                ->orWhere('reader_key', $device->key);
                        }),
                        fn ($query) => $query->where('reader_key', $event['reader_key'] ?? null),
                    )
                    ->first();

                if ($legacyMatch !== null) {
                    $legacyMatch->update([
                        'device_event_key' => $eventKey,
                        'received_at' => $legacyMatch->received_at ?? now(),
                    ]);

                    return $this->result($legacyMatch->fresh(), false);
                }
            }

            $attendance = Attendance::query()->create([
                'school_id' => $device?->school_id ?? $student->school_id,
                'biometric_device_id' => $device?->id,
                'student_id' => $student->id,
                'matricula' => $student->matricula,
                'id_lector' => $student->id_lector,
                'reader_key' => $event['reader_key'] ?? $device?->key,
                'reader_name' => $event['reader_name'] ?? $device?->name,
                'reader_school' => $event['reader_school'] ?? $device?->school?->name,
                'reader_mac' => $event['reader_mac'] ?? $device?->mac_address,
                'reader_ip' => $event['reader_ip'] ?? $device?->ip_address,
                'device_event_key' => $eventKey,
                'sync_source' => $event['event_source'] ?? 'live',
                'device_status' => $event['device_status'] ?? null,
                'device_punch' => $event['device_punch'] ?? null,
                'fecha_hora' => $recordedAt,
                'received_at' => now(),
                'curso' => $student->curso,
                'estado' => 'Entrada',
                'tipo' => 'Entrada',
                'is_ignored' => false,
                'ignored_reason' => null,
            ]);

            $this->rebuildStudentDay($student, $recordedAt);

            return $this->result($attendance->fresh(), true);
        });

        if ($result['created'] && ! $result['ignored'] && ($event['event_source'] ?? 'live') !== 'history') {
            try {
                $this->notificationService->sendFor($result['attendance']);
            } catch (Throwable $exception) {
                Log::error('Falló el procesamiento de notificación posterior al ponche.', [
                    'attendance_id' => $result['attendance']?->id,
                    'exception' => $exception::class,
                ]);
            }
        }

        return $result;
    }

    private function rebuildStudentDay(Student $student, Carbon $date): void
    {
        $acceptedIndex = 0;

        Attendance::query()
            ->where('student_id', $student->id)
            ->whereDate('fecha_hora', $date)
            ->where('is_ignored', false)
            ->orderBy('fecha_hora')
            ->orderBy('id')
            ->get()
            ->each(function (Attendance $attendance) use ($student, &$acceptedIndex): void {
                $type = $acceptedIndex % 2 === 0 ? 'Entrada' : 'Salida';
                $schedule = $student->school;
                $exitTime = SchoolScheduleException::query()
                    ->where('school_id', $student->school_id)
                    ->whereDate('date', $attendance->fecha_hora)
                    ->value('exit_time') ?? $schedule?->attendance_exit_time;

                if ($type === 'Entrada' && $attendance->sync_source !== 'history' && $this->isAtOrAfterExitTime($attendance->fecha_hora, $exitTime)) {
                    $attendance->update([
                        'tipo' => 'Ignorado',
                        'estado' => 'Ignorado',
                        'is_ignored' => true,
                        'ignored_reason' => 'entry_at_or_after_exit_time',
                        'is_late' => false,
                        'is_early_departure' => false,
                    ]);

                    return;
                }

                $isLate = $type === 'Entrada' && $this->isLate($attendance->fecha_hora, $schedule?->attendance_entry_time, $schedule?->attendance_late_grace_minutes);
                $isEarlyDeparture = $type === 'Salida' && $this->isBeforeExitTime($attendance->fecha_hora, $exitTime);

                if ($isEarlyDeparture && $attendance->sync_source !== 'history') {
                    $authorization = EarlyDepartureAuthorization::query()
                        ->where('school_id', $student->school_id)
                        ->where('student_id', $student->id)
                        ->whereDate('authorized_for', $attendance->fecha_hora)
                        ->where(function ($query) use ($attendance): void {
                            $query->whereNull('used_at')->orWhere('attendance_id', $attendance->id);
                        })
                        ->lockForUpdate()
                        ->first();

                    if ($authorization === null) {
                        $attendance->update([
                            'tipo' => 'Ignorado',
                            'estado' => 'Ignorado',
                            'is_ignored' => true,
                            'ignored_reason' => 'early_departure_not_authorized',
                            'is_late' => false,
                            'is_early_departure' => false,
                        ]);

                        return;
                    }

                    $authorization->update([
                        'attendance_id' => $attendance->id,
                        'used_at' => $attendance->fecha_hora,
                    ]);
                }

                if ($attendance->tipo !== $type || $attendance->estado !== $type || $attendance->is_late !== $isLate || $attendance->is_early_departure !== $isEarlyDeparture || $attendance->is_ignored) {
                    $attendance->update([
                        'tipo' => $type,
                        'estado' => $type,
                        'is_ignored' => false,
                        'ignored_reason' => null,
                        'is_late' => $isLate,
                        'is_early_departure' => $isEarlyDeparture,
                    ]);
                }

                $acceptedIndex++;
            });
    }

    private function isLate(Carbon $recordedAt, mixed $entryTime, mixed $graceMinutes): bool
    {
        if ($entryTime === null) {
            return false;
        }

        $limit = Carbon::parse($recordedAt->toDateString().' '.Carbon::parse($entryTime)->format('H:i:s'))
            ->addMinutes((int) $graceMinutes);

        return $recordedAt->greaterThan($limit);
    }

    private function isBeforeExitTime(Carbon $recordedAt, mixed $exitTime): bool
    {
        if ($exitTime === null) {
            return false;
        }

        $limit = Carbon::parse($recordedAt->toDateString().' '.Carbon::parse($exitTime)->format('H:i:s'));

        return $recordedAt->lessThan($limit);
    }

    private function isAtOrAfterExitTime(Carbon $recordedAt, mixed $exitTime): bool
    {
        if ($exitTime === null) {
            return false;
        }

        $limit = Carbon::parse($recordedAt->toDateString().' '.Carbon::parse($exitTime)->format('H:i:s'));

        return $recordedAt->greaterThanOrEqualTo($limit);
    }

    /**
     * @return array{attendance: Attendance, created: bool, debounced: false, ignored: bool, remaining_seconds: 0}
     */
    private function result(Attendance $attendance, bool $created): array
    {
        return [
            'attendance' => $attendance,
            'created' => $created,
            'debounced' => false,
            'ignored' => $attendance->is_ignored,
            'remaining_seconds' => 0,
        ];
    }
}
