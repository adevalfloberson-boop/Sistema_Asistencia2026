<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\School;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('attendance:close-missing-exits {--date= : Date to close in Y-m-d format}')]
#[Description('Creates an automatic exit for students who entered but did not register an exit.')]
class CloseMissingStudentExits extends Command
{
    public function handle(): int
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date'))->startOfDay() : today();
        $created = 0;

        School::query()->where('is_active', true)->each(function (School $school) use ($date, &$created): void {
            $exceptionExitTime = $school->scheduleExceptions()
                ->whereDate('date', $date)
                ->value('exit_time');
            $exitTime = $exceptionExitTime === null
                ? ($school->attendance_exit_time?->format('H:i:s') ?? '14:00:00')
                : Carbon::parse($exceptionExitTime)->format('H:i:s');
            $automaticExitAt = Carbon::parse($date->toDateString().' '.$exitTime);
            $entries = Attendance::query()
                ->where('school_id', $school->id)
                ->whereDate('fecha_hora', $date)
                ->where('tipo', 'Entrada')
                ->where('is_ignored', false)
                ->with('student')
                ->get()
                ->unique('student_id');

            foreach ($entries as $entry) {
                $hasExit = Attendance::query()
                    ->where('school_id', $school->id)
                    ->where('student_id', $entry->student_id)
                    ->whereDate('fecha_hora', $date)
                    ->where('tipo', 'Salida')
                    ->where('is_ignored', false)
                    ->exists();

                if ($hasExit || $entry->student === null) {
                    continue;
                }

                Attendance::query()->create([
                    'school_id' => $school->id,
                    'student_id' => $entry->student_id,
                    'matricula' => $entry->matricula,
                    'id_lector' => $entry->id_lector,
                    'reader_key' => 'automatic-closure',
                    'reader_name' => 'Cierre automático',
                    'reader_school' => $school->name,
                    'sync_source' => 'automatic_closure',
                    'is_ignored' => false,
                    'is_late' => false,
                    'is_early_departure' => false,
                    'fecha_hora' => $automaticExitAt,
                    'received_at' => now(),
                    'curso' => $entry->curso,
                    'estado' => 'Salida automática',
                    'tipo' => 'Salida',
                ]);
                $created++;
            }
        });

        $this->info("Salidas automáticas creadas: {$created}");

        return self::SUCCESS;
    }
}
