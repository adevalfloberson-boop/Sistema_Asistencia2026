<?php

namespace App\Services;

use Carbon\Carbon;

class AdmsPayloadParser
{
    /**
     * @return array<int, array{user_id: string, event_at: Carbon, status_code: int|null, verify_mode: int|null, raw_payload: string}>
     */
    public function attendanceLines(string $payload): array
    {
        $events = [];

        foreach (preg_split('/\r\n|\r|\n/', trim($payload)) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $parts = str_contains($line, "\t")
                ? array_map('trim', explode("\t", $line))
                : preg_split('/\s+/', $line, 4);

            if (! is_array($parts) || count($parts) < 3) {
                continue;
            }

            $timestamp = str_contains($line, "\t")
                ? ($parts[1] ?? '')
                : (($parts[1] ?? '').' '.($parts[2] ?? ''));
            $offset = str_contains($line, "\t") ? 0 : 1;

            try {
                $eventAt = Carbon::createFromFormat('Y-m-d H:i:s', $timestamp);
            } catch (\Throwable) {
                continue;
            }

            $events[] = [
                'user_id' => (string) $parts[0],
                'event_at' => $eventAt,
                'status_code' => isset($parts[2 + $offset]) && is_numeric($parts[2 + $offset]) ? (int) $parts[2 + $offset] : null,
                'verify_mode' => isset($parts[3 + $offset]) && is_numeric($parts[3 + $offset]) ? (int) $parts[3 + $offset] : null,
                'raw_payload' => $line,
            ];
        }

        return $events;
    }
}
