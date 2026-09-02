<?php

namespace Database\Factories;

use App\Models\ClassSession;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassSession>
 */
class ClassSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'teacher_id' => User::factory()->state(['role' => 'teacher']),
            'subject' => fake()->randomElement(['Matemática', 'Lengua Española', 'Ciencias Sociales']),
            'scheduled_at' => now(),
            'started_at' => now(),
            'ended_at' => null,
            'status' => 'open',
        ];
    }
}
