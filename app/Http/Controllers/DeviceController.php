<?php

namespace App\Http\Controllers;

use App\Models\BiometricDevice;
use App\Models\BiometricEnrollment;
use App\Models\DeviceCommand;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DeviceController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $this->ensureAdministrator();

        $validated = $request->validate([
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'name' => ['required', 'string', 'max:255'],
            'mac_address' => [Rule::requiredIf(fn (): bool => in_array($request->input('connection_mode'), ['sdk', 'hybrid'], true)), 'nullable', 'mac_address', 'unique:biometric_devices,mac_address'],
            'serial_number' => [Rule::requiredIf(fn (): bool => in_array($request->input('connection_mode'), ['adms', 'hybrid'], true)), 'nullable', 'string', 'max:100', 'unique:biometric_devices,serial_number'],
            'model' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:255'],
            'connection_mode' => ['required', 'in:sdk,adms,hybrid'],
            'network' => [Rule::requiredIf(fn (): bool => in_array($request->input('connection_mode'), ['sdk', 'hybrid'], true)), 'nullable', 'regex:/^\d{1,3}\.\d{1,3}\.\d{1,3}$/'],
            'ip_address' => ['nullable', 'ip'],
            'port' => ['nullable', 'integer', 'between:1,65535'],
            'device_password' => ['nullable', 'string', 'max:50'],
        ]);

        $validated['key'] = $this->uniqueKey($validated['name']);
        $validated['mac_address'] = isset($validated['mac_address']) ? strtolower($validated['mac_address']) : null;
        $validated['serial_number'] = isset($validated['serial_number']) ? trim($validated['serial_number']) : null;
        $validated['port'] ??= 4370;
        $validated['device_password'] ??= '0';

        $device = BiometricDevice::query()->create($validated);
        $this->queueCommand($device, 'inspect');

        return redirect()
            ->route('dashboard.admin', ['section' => 'devices'])
            ->with('success', $device->connection_mode === 'adms'
                ? "Lector {$device->name} añadido. Configura este servidor ADMS en el equipo."
                : "Lector {$device->name} añadido. El agente intentará conectarlo automáticamente.");
    }

    public function update(Request $request, BiometricDevice $device): RedirectResponse
    {
        $this->ensureAdministrator();

        $validated = $request->validate([
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'name' => ['required', 'string', 'max:255'],
            'mac_address' => [Rule::requiredIf(fn (): bool => in_array($request->input('connection_mode'), ['sdk', 'hybrid'], true)), 'nullable', 'mac_address', 'unique:biometric_devices,mac_address,'.$device->id],
            'serial_number' => [Rule::requiredIf(fn (): bool => in_array($request->input('connection_mode'), ['adms', 'hybrid'], true)), 'nullable', 'string', 'max:100', 'unique:biometric_devices,serial_number,'.$device->id],
            'model' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:255'],
            'connection_mode' => ['required', 'in:sdk,adms,hybrid'],
            'network' => [Rule::requiredIf(fn (): bool => in_array($request->input('connection_mode'), ['sdk', 'hybrid'], true)), 'nullable', 'regex:/^\d{1,3}\.\d{1,3}\.\d{1,3}$/'],
            'ip_address' => ['nullable', 'ip'],
            'port' => ['nullable', 'integer', 'between:1,65535'],
            'device_password' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['mac_address'] = isset($validated['mac_address']) ? strtolower($validated['mac_address']) : null;
        $validated['serial_number'] = isset($validated['serial_number']) ? trim($validated['serial_number']) : null;
        $validated['port'] ??= 4370;
        $validated['is_active'] = $request->boolean('is_active');
        if (($validated['device_password'] ?? '') === '') {
            unset($validated['device_password']);
        }

        $device->update($validated);

        return redirect()
            ->route('dashboard.admin', ['section' => 'devices'])
            ->with('success', "Configuración de {$device->name} actualizada.");
    }

    public function inspect(BiometricDevice $device): RedirectResponse
    {
        $this->ensureAdministrator();
        $this->queueCommand($device, 'inspect');

        return back()->with('success', "Consulta enviada a {$device->name}. Se actualizará al responder.");
    }

    public function synchronizeTime(BiometricDevice $device): RedirectResponse
    {
        $this->ensureAdministrator();
        $this->queueCommand($device, 'sync_time');

        return back()->with('success', "Sincronización de hora enviada a {$device->name}.");
    }

    public function enroll(Request $request): RedirectResponse
    {
        $this->ensureAdministrator();

        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'biometric_device_id' => ['required', 'integer', 'exists:biometric_devices,id'],
            'finger_index' => ['required', 'integer', 'between:0,9'],
        ]);

        $student = Student::query()->findOrFail($validated['student_id']);
        $device = BiometricDevice::query()->findOrFail($validated['biometric_device_id']);

        if ($student->school_id !== $device->school_id) {
            return back()->withErrors(['enrollment' => 'El estudiante y el lector deben pertenecer a la misma escuela.']);
        }

        if ($student->id_lector === null || $student->id_lector === '') {
            return back()->withErrors(['enrollment' => 'El estudiante necesita un ID biométrico antes de registrar la huella.']);
        }

        $command = $this->queueCommand($device, 'enroll', $student, [
            'uid' => $student->id,
            'user_id' => $student->id_lector,
            'name' => trim("{$student->nombre} {$student->apellido}"),
            'finger_index' => (int) $validated['finger_index'],
        ]);

        BiometricEnrollment::query()->updateOrCreate(
            [
                'biometric_device_id' => $device->id,
                'student_id' => $student->id,
                'finger_index' => $validated['finger_index'],
            ],
            [
                'device_command_id' => $command->id,
                'user_id' => $student->id_lector,
                'status' => 'pending',
                'enrolled_at' => null,
                'error' => null,
            ],
        );

        return redirect()
            ->route('dashboard.admin', ['section' => 'enrollment'])
            ->with('success', "Solicitud enviada. {$student->nombre} debe colocar el dedo en {$device->name}.");
    }

    public function verifyEnrollment(BiometricEnrollment $enrollment): RedirectResponse
    {
        $this->ensureAdministrator();
        $enrollment->loadMissing(['device', 'student']);

        $command = $this->queueCommand($enrollment->device, 'verify_enrollment', $enrollment->student, [
            'uid' => $enrollment->student_id,
            'user_id' => $enrollment->user_id,
            'finger_index' => $enrollment->finger_index,
        ]);

        $enrollment->update([
            'device_command_id' => $command->id,
            'status' => 'pending',
            'error' => null,
        ]);

        return back()->with('success', 'Verificación de plantilla enviada al lector.');
    }

    public function commandStatus(DeviceCommand $command): JsonResponse
    {
        $this->ensureAdministrator();

        return response()->json([
            'id' => $command->id,
            'status' => $command->status,
            'result' => $command->result,
            'error' => $command->error,
            'completed_at' => $command->completed_at?->toIso8601String(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function queueCommand(
        BiometricDevice $device,
        string $type,
        ?Student $student = null,
        array $payload = [],
    ): DeviceCommand {
        return $device->commands()->create([
            'student_id' => $student?->id,
            'type' => $type,
            'payload' => $payload,
            'status' => 'pending',
            'requested_by' => session('user.username'),
        ]);
    }

    private function uniqueKey(string $name): string
    {
        $base = Str::slug($name) ?: 'lector';
        $key = $base;
        $suffix = 2;

        while (BiometricDevice::query()->where('key', $key)->exists()) {
            $key = "{$base}-{$suffix}";
            $suffix++;
        }

        return $key;
    }

    private function ensureAdministrator(): void
    {
        abort_unless(session('user.role') === 'superadmin', 403);
    }
}
