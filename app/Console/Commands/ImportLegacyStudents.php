<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\School;
use App\Models\Student;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[Signature('app:import-legacy-students')]
#[Description('Importa alumnos desde public.prueba_usuarios sin modificar las tablas heredadas')]
class ImportLegacyStudents extends Command
{
    public function handle(): int
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->error('La importación heredada solo está disponible con PostgreSQL.');

            return self::FAILURE;
        }

        $legacyTable = DB::selectOne("select to_regclass('public.prueba_usuarios') as table_name");

        if ($legacyTable?->table_name === null) {
            $this->warn('No existe public.prueba_usuarios; no se realizó ningún cambio.');

            return self::SUCCESS;
        }

        $legacyStudents = DB::table('public.prueba_usuarios')
            ->select(['matricula', 'nombre', 'apellido', 'curso', 'idlector'])
            ->orderBy('curso')
            ->orderBy('matricula')
            ->get();
        $school = School::query()->firstOrCreate(
            ['code' => config('attendance.default_school_code')],
            ['name' => config('attendance.default_school_name')],
        );
        $listNumbers = [];
        $imported = 0;

        DB::transaction(function () use ($legacyStudents, $school, &$listNumbers, &$imported): void {
            foreach ($legacyStudents as $legacyStudent) {
                $courseName = trim((string) ($legacyStudent->curso ?: 'Curso piloto'));
                $course = Course::query()->firstOrCreate(
                    [
                        'school_id' => $school->id,
                        'code' => Str::upper(Str::slug($courseName, '-')),
                    ],
                    [
                        'name' => $courseName,
                        'section' => Str::contains($courseName, ' ') ? Str::afterLast($courseName, ' ') : null,
                        'shift' => 'Matutina',
                    ],
                );
                $listNumbers[$course->id] = ($listNumbers[$course->id] ?? 0) + 1;

                Student::query()->updateOrCreate(
                    ['matricula' => (string) $legacyStudent->matricula],
                    [
                        'school_id' => $school->id,
                        'course_id' => $course->id,
                        'nombre' => (string) $legacyStudent->nombre,
                        'apellido' => (string) $legacyStudent->apellido,
                        'numero_lista' => $listNumbers[$course->id],
                        'area' => $course->area,
                        'curso' => $course->name,
                        'seccion' => $course->section,
                        'id_lector' => (string) $legacyStudent->idlector,
                        'is_active' => true,
                    ],
                );
                $imported++;
            }
        });

        $this->info("{$imported} estudiantes importados o actualizados. La tabla heredada no fue modificada.");

        return self::SUCCESS;
    }
}
