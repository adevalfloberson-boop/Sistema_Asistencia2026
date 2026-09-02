<?php

namespace App\Services;

use App\Models\AdmsEvent;
use App\Models\BiometricDevice;
use App\Models\Student;
use Carbon\Carbon;

class AdmsAttendanceProcessor
{
    public function __construct(private readonly BiometricAttendanceRecorder $attendanceRecorder) {}

    /**
     * @param  array{user_id: string, event_at: Carbon, status_code: int|null, verify_mode: int|null, raw_payload: string}  $event
     */
    public function process(BiometricDevice $device, array $event): AdmsEvent
    {
        $eventKey = hash('sha256', implode('|', [
            'adms', $device->id, $event['user_id'], $event['event_at']->format('Y-m-d H:i:s'),
            $event['status_code'] ?? '', $event['verify_mode'] ?? '',
        ]));

        $admsEvent = AdmsEvent::query()->firstOrCreate(
            ['device_event_key' => $eventKey],
            [
                'biometric_device_id' => $device->id,
                'user_id' => $event['user_id'],
                'event_at' => $event['event_at'],
                'status_code' => $event['status_code'],
                'verify_mode' => $event['verify_mode'],
                'raw_payload' => $event['raw_payload'],
            ],
        );

        if ($admsEvent->processing_status === 'processed') {
            return $admsEvent;
        }

        $student = Student::query()
            ->where('school_id', $device->school_id)
            ->where('id_lector', $event['user_id'])
            ->where('is_active', true)
            ->first();

        if ($student === null) {
            $admsEvent->update([
                'processing_status' => 'unmatched',
                'error' => "ID biométrico {$event['user_id']} no registrado en esta escuela.",
            ]);

            return $admsEvent->fresh();
        }

        $record = $this->attendanceRecorder->record($student, $device, [
            'event_key' => $eventKey,
            'event_timestamp' => $event['event_at']->toDateTimeString(),
            'event_source' => 'adms',
            'device_status' => $event['status_code'],
            'device_punch' => $event['verify_mode'],
            'reader_ip' => $device->ip_address,
        ]);

        $admsEvent->update([
            'student_id' => $student->id,
            'attendance_id' => $record['attendance']?->id,
            'processing_status' => 'processed',
            'error' => null,
        ]);

        return $admsEvent->fresh();
    }

    public function retryUnmatched(BiometricDevice $device, int $limit = 100): void
    {
        $device->admsEvents()
            ->where('processing_status', 'unmatched')
            ->oldest('id')
            ->limit($limit)
            ->get()
            ->each(function (AdmsEvent $event) use ($device): void {
                $this->process($device, [
                    'user_id' => (string) $event->user_id,
                    'event_at' => $event->event_at,
                    'status_code' => $event->status_code,
                    'verify_mode' => $event->verify_mode,
                    'raw_payload' => $event->raw_payload,
                ]);
            });
    }
}
