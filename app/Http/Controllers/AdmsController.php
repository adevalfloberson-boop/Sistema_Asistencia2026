<?php

namespace App\Http\Controllers;

use App\Models\BiometricDevice;
use App\Models\DeviceCommand;
use App\Services\AdmsAttendanceProcessor;
use App\Services\AdmsPayloadParser;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdmsController extends Controller
{
    public function cdata(
        Request $request,
        AdmsPayloadParser $parser,
        AdmsAttendanceProcessor $processor,
    ): Response {
        $device = $this->device($request);
        $this->markOnline($device, $request);
        $this->captureDeviceInfo($device, $request);

        if ($request->isMethod('get')) {
            $processor->retryUnmatched($device);

            return $this->plain(implode("\n", [
                "GET OPTION FROM: {$device->serial_number}",
                'ATTLOGStamp=0',
                'OPERLOGStamp=0',
                'ATTPHOTOStamp=0',
                'ErrorDelay=60',
                'Delay=30',
                'TransTimes=00:00',
                'TransInterval=1',
                'TransFlag=1111000000',
                'Realtime=1',
                'Encrypt=0',
            ]));
        }

        $table = strtoupper((string) $request->query('table', 'ATTLOG'));
        $payload = $request->getContent();
        $processed = 0;

        if ($table === 'ATTLOG') {
            foreach ($parser->attendanceLines($payload) as $event) {
                $processor->process($device, $event);
                $processed++;
            }
        }

        return $this->plain("OK: {$processed}");
    }

    public function registry(Request $request): Response
    {
        $device = $this->device($request);
        $this->markOnline($device, $request);
        $this->captureDeviceInfo($device, $request);

        return $this->plain('OK');
    }

    public function getRequest(Request $request, AdmsAttendanceProcessor $processor): Response
    {
        $device = $this->device($request);
        $this->markOnline($device, $request);
        $processor->retryUnmatched($device);

        $command = DB::transaction(function () use ($device): ?DeviceCommand {
            $command = $device->commands()
                ->where('status', 'pending')
                ->oldest('id')
                ->lockForUpdate()
                ->first();

            $command?->update([
                'status' => 'processing',
                'started_at' => now(),
            ]);

            return $command;
        });

        if ($command === null) {
            return $this->plain('OK');
        }

        $instruction = match ($command->type) {
            'inspect' => 'INFO',
            'sync_time' => 'SET OPTIONS DateTime='.now()->format('Y-m-d H:i:s'),
            default => null,
        };

        if ($instruction === null) {
            $command->update([
                'status' => 'failed',
                'error' => "La orden {$command->type} todavía requiere el agente SDK.",
                'completed_at' => now(),
            ]);

            return $this->plain('OK');
        }

        return $this->plain("C:{$command->id}:{$instruction}");
    }

    public function deviceCommand(Request $request): Response
    {
        $device = $this->device($request);
        $this->markOnline($device, $request);
        $payload = $request->getContent();
        parse_str($payload, $result);
        $this->captureDeviceInfo($device, $request, $result);

        $commandId = $result['ID'] ?? $request->input('ID');
        $returnCode = (int) ($result['Return'] ?? $request->input('Return', -1));
        $command = $device->commands()->find($commandId);

        if ($command !== null) {
            $command->update([
                'status' => $returnCode === 0 ? 'completed' : 'failed',
                'result' => ['return_code' => $returnCode, 'payload' => $payload],
                'error' => $returnCode === 0 ? null : "El lector devolvió el código {$returnCode}.",
                'completed_at' => now(),
            ]);
        }

        return $this->plain('OK');
    }

    private function device(Request $request): BiometricDevice
    {
        $serialNumber = trim((string) $request->query('SN'));

        abort_if($serialNumber === '', 422, 'Falta el número de serie SN.');

        return BiometricDevice::query()->firstOrCreate(
            ['serial_number' => $serialNumber],
            [
                'key' => 'adms-'.Str::limit(Str::slug($serialNumber), 48, '').'-'.substr(sha1($serialNumber), 0, 8),
                'name' => "Nuevo lector {$serialNumber}",
                'connection_mode' => 'adms',
                'port' => 8000,
                'status' => 'pending_assignment',
                'is_active' => false,
            ],
        );
    }

    private function markOnline(BiometricDevice $device, Request $request): void
    {
        $wasConnected = $device->status === 'connected';
        $device->update([
            'status' => $device->is_active ? 'connected' : 'pending_assignment',
            'ip_address' => $request->ip(),
            'last_seen_at' => now(),
            'last_connected_at' => $wasConnected ? ($device->last_connected_at ?? now()) : now(),
            'last_error' => null,
        ]);
    }

    /**
     * Stores the inventory values pushed by the reader after an INFO command
     * or during its ADMS registration handshake.
     *
     * @param  array<string, mixed>  $extra
     */
    private function captureDeviceInfo(BiometricDevice $device, Request $request, array $extra = []): void
    {
        $info = $this->deviceInfoFromPayload($request->getContent());

        foreach ([$request->query(), $extra] as $values) {
            foreach ($values as $key => $value) {
                if (is_scalar($value)) {
                    $info[strtolower(ltrim((string) $key, '~'))] = trim((string) $value);
                }
            }
        }

        $updates = [];
        $textFields = [
            'devicename' => 'model',
            'model' => 'model',
            'fwversion' => 'firmware_version',
            'firmver' => 'firmware_version',
            'firmwareversion' => 'firmware_version',
            'platform' => 'platform',
        ];

        foreach ($textFields as $source => $destination) {
            if (($info[$source] ?? '') !== '') {
                $updates[$destination] = $info[$source];
            }
        }

        $macAddress = $this->normaliseMac($info['macaddress'] ?? $info['mac'] ?? null);
        if ($macAddress !== null && ($device->mac_address === null || $device->mac_address === $macAddress)) {
            $updates['mac_address'] = $macAddress;
        }

        foreach ([
            'usercount' => 'user_count',
            'fpcount' => 'fingerprint_count',
            'attlogcount' => 'attendance_count',
            'transactioncount' => 'attendance_count',
        ] as $source => $destination) {
            if (isset($info[$source]) && ctype_digit($info[$source])) {
                $updates[$destination] = (int) $info[$source];
            }
        }

        $capacity = is_array($device->capacity) ? $device->capacity : [];
        foreach ([
            'maxusercount' => 'users',
            'maxfingercount' => 'fingerprints',
            'maxattlogcount' => 'attendance_logs',
            'maxfacecount' => 'faces',
        ] as $source => $destination) {
            if (isset($info[$source]) && ctype_digit($info[$source])) {
                $capacity[$destination] = (int) $info[$source];
            }
        }
        if ($capacity !== []) {
            $updates['capacity'] = $capacity;
        }

        if ($updates !== []) {
            $device->update($updates);
        }
    }

    /**
     * @return array<string, string>
     */
    private function deviceInfoFromPayload(string $payload): array
    {
        $info = [];

        foreach (preg_split('/[,\r\n&]+/', $payload) ?: [] as $entry) {
            [$key, $value] = array_pad(explode('=', $entry, 2), 2, null);
            $key = strtolower(ltrim(trim($key), '~'));

            if ($key !== '' && $value !== null) {
                $info[$key] = trim($value);
            }
        }

        return $info;
    }

    private function normaliseMac(?string $value): ?string
    {
        $characters = preg_replace('/[^0-9a-f]/i', '', (string) $value);

        if ($characters === null || strlen($characters) !== 12) {
            return null;
        }

        return strtolower(implode(':', str_split($characters, 2)));
    }

    private function plain(string $content, int $status = 200): Response
    {
        return response($content."\n", $status)->header('Content-Type', 'text/plain; charset=utf-8');
    }
}
