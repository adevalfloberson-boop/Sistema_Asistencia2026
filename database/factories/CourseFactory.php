<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => fn (): int => School::query()->firstOrCreate(
                ['code' => fake()->unique()->bothify('SCH-###')],
                ['name' => fake()->company()],
            )->id,
            'code' => fake()->unique()->bothify('CUR-###'),
            'name' => fake()->randomElement(['1ro A', '2do B', '3ro A', '4to C']),
            'grade' => fake()->randomElement(['Primero', 'Segundo', 'Tercero', 'Cuarto']),
            'section' => fake()->randomElement(['A', 'B', 'C']),
            'shift' => fake()->randomElement(['Matutina', 'Vespertina']),
            'is_active' => true,
        ];
    }
}
