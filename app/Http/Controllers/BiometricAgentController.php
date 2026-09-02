<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\BiometricDevice;
use App\Models\BiometricEnrollment;
use App\Models\DeviceCommand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class BiometricAgentController extends Controller
{
    public function devices(Request $request): JsonResponse
    {
        $this->ensureAuthorized($request);

        $devices = BiometricDevice::query()
            ->with('school:id,code,name')
            ->where('is_active', true)
            ->whereIn('connection_mode', ['sdk', 'hybrid'])
            ->orderBy('key')
            ->get()
            ->map(fn (BiometricDevice $device): array => [
                'key' => $device->key,
                'name' => $device->name,
                'school' => $device->school?->name,
                'mac' => $device->mac_address,
                'network' => $device->network,
                'ip' => $device->ip_address,
                'port' => $device->port,
                'password' => $device->device_password ?? '0',
                'timeout' => 5,
            ]);

        return response()->json(['devices' => $devices]);
    }

    public function heartbeat(Request $request, string $key): JsonResponse
    {
        $this->ensureAuthorized($request);

        $validated = $request->validate([
            'status' => ['required', 'in:connected,disconnected'],
            'ip_address' => ['nullable', 'ip'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'firmware_version' => ['nullable', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'max:255'],
            'user_count' => ['nullable', 'integer', 'min:0'],
            'fingerprint_count' => ['nullable', 'integer', 'min:0'],
            'attendance_count' => ['nullable', 'integer', 'min:0'],
            'device_time' => ['nullable', 'date'],
            'error' => ['nullable', 'string', 'max:2000'],
        ]);

        $device = BiometricDevice::query()->where('key', $key)->firstOrFail();
        $isConnected = $validated['status'] === 'connected';
        $wasConnected = $device->status === 'connected';

        $device->fill([
            'status' => $validated['status'],
            'ip_address' => $validated['ip_address'] ?? $device->ip_address,
            'serial_number' => $validated['serial_number'] ?? $device->serial_number,
            'model' => $validated['model'] ?? $device->model,
            'firmware_version' => $validated['firmware_version'] ?? $device->firmware_version,
            'platform' => $validated['platform'] ?? $device->platform,
            'user_count' => $validated['user_count'] ?? $device->user_count,
            'fingerprint_count' => $validated['fingerprint_count'] ?? $device->fingerprint_count,
            'attendance_count' => $validated['attendance_count'] ?? $device->attendance_count,
            'last_error' => $isConnected ? null : ($validated['error'] ?? 'No se pudo conectar con el lector.'),
        ]);

        if ($isConnected) {
            $device->last_seen_at = now();
            $device->last_connected_at = $wasConnected
                ? ($device->last_connected_at ?? now())
                : now();
        } else {
            $device->last_disconnected_at = now();
        }

        $device->save();

        return response()->json(['success' => true]);
    }

    public function attendanceCheckpoint(Request $request, string $key): JsonResponse
    {
        $this->ensureAuthorized($request);

        $device = BiometricDevice::query()->where('key', $key)->firstOrFail();
        $lastAttendance = Attendance::query()
            ->where(function ($query) use ($device): void {
                $query->where('biometric_device_id', $device->id)
                    ->orWhere('reader_key', $device->key);
            })
            ->latest('fecha_hora')
            ->latest('id')
            ->first();

        return response()->json([
            'last_event_at' => $lastAttendance?->fecha_hora?->format('Y-m-d H:i:s'),
            'last_event_key' => $lastAttendance?->device_event_key,
            'server_time' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function nextCommand(Request $request, string $key): JsonResponse|Response
    {
        $this->ensureAuthorized($request);

        $command = DB::transaction(function () use ($key): ?DeviceCommand {
            $device = BiometricDevice::query()->where('key', $key)->firstOrFail();
            $command = $device->commands()
                ->where('status', 'pending')
                ->oldest()
                ->lockForUpdate()
                ->first();

            if ($command !== null) {
                $command->update([
                    'status' => 'processing',
                    'started_at' => now(),
                ]);
            }

            return $command;
        });

        if ($command === null) {
            return response()->noContent();
        }

        return response()->json([
            'id' => $command->id,
            'type' => $command->type,
            'payload' => $command->payload ?? [],
        ]);
    }

    public function completeCommand(Request $request, DeviceCommand $command): JsonResponse
    {
        $this->ensureAuthorized($request);

        $validated = $request->validate([
            'success' => ['required', 'boolean'],
            'result' => ['nullable', 'array'],
            'error' => ['nullable', 'string', 'max:2000'],
        ]);

        $command->update([
            'status' => $validated['success'] ? 'completed' : 'failed',
            'result' => $validated['result'] ?? null,
            'error' => $validated['success'] ? null : ($validated['error'] ?? 'La orden no pudo completarse.'),
            'completed_at' => now(),
        ]);

        if (in_array($command->type, ['enroll', 'verify_enrollment'], true)) {
            BiometricEnrollment::query()
                ->where('device_command_id', $command->id)
                ->update([
                    'status' => $validated['success'] ? 'enrolled' : 'failed',
                    'enrolled_at' => $validated['success'] ? now() : null,
                    'error' => $validated['success'] ? null : ($validated['error'] ?? 'No se encontró la huella en el lector.'),
                ]);
        }

        if ($validated['success'] && $command->type === 'inspect') {
            $result = $validated['result'] ?? [];
            $command->device->update([
                'serial_number' => $result['serial_number'] ?? $command->device->serial_number,
                'model' => $result['model'] ?? $command->device->model,
                'firmware_version' => $result['firmware_version'] ?? $command->device->firmware_version,
                'platform' => $result['platform'] ?? $command->device->platform,
                'user_count' => $result['user_count'] ?? $command->device->user_count,
                'fingerprint_count' => $result['fingerprint_count'] ?? $command->device->fingerprint_count,
                'attendance_count' => $result['attendance_count'] ?? $command->device->attendance_count,
            ]);
        }

        return response()->json(['success' => true]);
    }

    private function ensureAuthorized(Request $request): void
    {
        $expectedToken = config('attendance.api_token');
        $providedToken = (string) $request->header('X-Biometric-Token');

        abort_unless(
            is_string($expectedToken)
                && $expectedToken !== ''
                && hash_equals($expectedToken, $providedToken),
            401,
            'Token biométrico inválido.',
        );
    }
}
