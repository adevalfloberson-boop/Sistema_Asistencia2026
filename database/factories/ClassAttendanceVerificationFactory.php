<?php

namespace Database\Factories;

use App\Models\ClassAttendanceVerification;
use App\Models\ClassSession;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassAttendanceVerification>
 */
class ClassAttendanceVerificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'class_session_id' => ClassSession::factory(),
            'student_id' => fn (): int => Student::query()->create([
                'school_id' => School::query()->firstOrCreate(
                    ['code' => fake()->unique()->bothify('STU-###')],
                    ['name' => fake()->company()],
                )->id,
                'matricula' => fake()->unique()->numerify('MAT-#####'),
                'nombre' => fake()->firstName(),
                'apellido' => fake()->lastName(),
                'curso' => '1ro A',
                'id_lector' => fake()->unique()->numerify('####'),
                'is_active' => true,
            ])->id,
            'teacher_id' => User::factory()->state(['role' => 'teacher']),
            'status' => ClassAttendanceVerification::StatusPresent,
            'was_on_campus' => true,
            'note' => null,
            'verified_at' => now(),
        ];
    }
}
