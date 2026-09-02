<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\BiometricDevice;
use App\Models\Course;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $school = School::query()->firstOrCreate(
            ['code' => config('attendance.default_school_code')],
            [
                'name' => config('attendance.default_school_name'),
                'short_name' => config('attendance.default_school_code'),
            ],
        );

        $reader = config('attendance.reader');

        BiometricDevice::query()->firstOrCreate(
            ['mac_address' => strtolower($reader['mac'])],
            [
                'school_id' => $school->id,
                'key' => $reader['key'],
                'name' => $reader['name'],
                'location' => 'Entrada principal',
                'connection_mode' => 'sdk',
                'network' => $reader['network'],
                'ip_address' => $reader['ip'],
                'port' => $reader['port'],
                'device_password' => $reader['password'],
            ],
        );

        Student::query()->whereNull('school_id')->update(['school_id' => $school->id]);
        Attendance::query()->whereNull('school_id')->update(['school_id' => $school->id]);

        $courseNames = Student::query()
            ->where('school_id', $school->id)
            ->whereNotNull('curso')
            ->distinct()
            ->pluck('curso');

        if ($courseNames->isEmpty()) {
            $courseNames = collect(['5to A']);
        }

        $courses = $courseNames->map(function (string $courseName) use ($school): Course {
            $code = Str::upper(Str::slug($courseName, '-'));

            return Course::query()->firstOrCreate(
                ['school_id' => $school->id, 'code' => $code],
                ['name' => $courseName, 'section' => Str::afterLast($courseName, ' '), 'shift' => 'Matutina'],
            );
        });

        foreach ($courses as $course) {
            Student::query()
                ->where('school_id', $school->id)
                ->where('curso', $course->name)
                ->whereNull('course_id')
                ->update(['course_id' => $course->id]);
        }

        $adminPassword = env('INITIAL_ADMIN_PASSWORD');
        if (is_string($adminPassword) && $adminPassword !== '') {
            User::query()->firstOrCreate(
                ['email' => env('INITIAL_ADMIN_EMAIL', 'admin@asistencia.test')],
                [
                    'school_id' => null,
                    'name' => 'Superadministrador',
                    'username' => env('INITIAL_ADMIN_USERNAME', 'admin'),
                    'role' => 'superadmin',
                    'is_active' => true,
                    'password' => $adminPassword,
                ],
            );
        }

        $teacherPassword = env('INITIAL_TEACHER_PASSWORD');
        if (is_string($teacherPassword) && $teacherPassword !== '') {
            $teacher = User::query()->firstOrCreate(
                ['email' => env('INITIAL_TEACHER_EMAIL', 'docente@asistencia.test')],
                [
                    'school_id' => $school->id,
                    'name' => 'Docente de prueba',
                    'username' => env('INITIAL_TEACHER_USERNAME', 'docente'),
                    'role' => 'teacher',
                    'is_active' => true,
                    'password' => $teacherPassword,
                ],
            );

            $teacher->courses()->syncWithoutDetaching($courses->pluck('id'));
        }
    }
}
