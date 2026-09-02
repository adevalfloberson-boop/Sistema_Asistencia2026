<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\BiometricDevice;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BiometricAttendanceRecorder
{
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
        return DB::transaction(function () use ($student, $device, $event): array {
            $eventKey = $event['event_key'] ?? null;
            $recordedAt = isset($event['event_timestamp'])
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

            $cooldownSeconds = ($device?->school?->attendance_cooldown_minutes
                ?? $student->school?->attendance_cooldown_minutes
                ?? 10) * 60;
            $nearbyAttendance = $this->nearbyAcceptedAttendance($student, $recordedAt);
            $elapsedSeconds = $nearbyAttendance === null
                ? null
                : abs($nearbyAttendance->fecha_hora->diffInSeconds($recordedAt));
            $isInsideCooldown = $elapsedSeconds !== null && $elapsedSeconds < $cooldownSeconds;

            if ($eventKey === null && $isInsideCooldown) {
                return [
                    'attendance' => null,
                    'created' => false,
                    'debounced' => true,
                    'ignored' => false,
                    'remaining_seconds' => (int) ceil($cooldownSeconds - $elapsedSeconds),
                ];
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
                'estado' => $isInsideCooldown ? 'Ignorado' : 'Entrada',
                'tipo' => $isInsideCooldown ? 'Ignorado' : 'Entrada',
                'is_ignored' => $isInsideCooldown,
                'ignored_reason' => $isInsideCooldown ? 'cooldown' : null,
            ]);

            if (! $isInsideCooldown) {
                $this->rebuildStudentDay($student, $recordedAt);
            }

            return $this->result($attendance->fresh(), true);
        });
    }

    private function rebuildStudentDay(Student $student, Carbon $date): void
    {
        Attendance::query()
            ->where('student_id', $student->id)
            ->whereDate('fecha_hora', $date)
            ->where('is_ignored', false)
            ->orderBy('fecha_hora')
            ->orderBy('id')
            ->get()
            ->each(function (Attendance $attendance, int $index): void {
                $type = $index % 2 === 0 ? 'Entrada' : 'Salida';

                if ($attendance->tipo !== $type || $attendance->estado !== $type) {
                    $attendance->update([
                        'tipo' => $type,
                        'estado' => $type,
                    ]);
                }
            });
    }

    private function nearbyAcceptedAttendance(Student $student, Carbon $recordedAt): ?Attendance
    {
        $baseQuery = Attendance::query()
            ->where('student_id', $student->id)
            ->whereDate('fecha_hora', $recordedAt)
            ->where('is_ignored', false);

        $previous = (clone $baseQuery)
            ->where('fecha_hora', '<=', $recordedAt)
            ->latest('fecha_hora')
            ->first();
        $next = (clone $baseQuery)
            ->where('fecha_hora', '>', $recordedAt)
            ->oldest('fecha_hora')
            ->first();

        if ($previous === null) {
            return $next;
        }

        if ($next === null) {
            return $previous;
        }

        return abs($previous->fecha_hora->diffInSeconds($recordedAt))
            <= abs($next->fecha_hora->diffInSeconds($recordedAt))
                ? $previous
                : $next;
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
