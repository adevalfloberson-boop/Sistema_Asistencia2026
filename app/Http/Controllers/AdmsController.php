<?php

namespace App\Http\Controllers;

use App\Models\BiometricDevice;
use App\Models\DeviceCommand;
use App\Services\AdmsAttendanceProcessor;
use App\Services\AdmsPayloadParser;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class AdmsController extends Controller
{
    public function cdata(
        Request $request,
        AdmsPayloadParser $parser,
        AdmsAttendanceProcessor $processor,
    ): Response {
        $device = $this->device($request);
        $this->markOnline($device, $request);

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

        return BiometricDevice::query()
            ->where('serial_number', $serialNumber)
            ->whereIn('connection_mode', ['adms', 'hybrid'])
            ->where('is_active', true)
            ->firstOrFail();
    }

    private function markOnline(BiometricDevice $device, Request $request): void
    {
        $wasConnected = $device->status === 'connected';
        $device->update([
            'status' => 'connected',
            'ip_address' => $request->ip(),
            'last_seen_at' => now(),
            'last_connected_at' => $wasConnected ? ($device->last_connected_at ?? now()) : now(),
            'last_error' => null,
        ]);
    }

    private function plain(string $content, int $status = 200): Response
    {
        return response($content."\n", $status)->header('Content-Type', 'text/plain; charset=utf-8');
    }
}
