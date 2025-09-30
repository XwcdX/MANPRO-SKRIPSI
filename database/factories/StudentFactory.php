<?php

namespace Database\Factories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class StudentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Student::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dueDate = $this->faker->boolean(80)
            ? $this->faker->dateTimeBetween('+1 month', '+1 year')
            : null;

        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->userName() . '@john.petra.ac.id',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'thesis_title' => $this->faker->optional(0.7)->sentence(rand(4, 8)),
            'status' => $this->faker->numberBetween(0, 8),
            'head_division_comment' => $this->faker->optional(0.5)->sentence(),
            'revision_notes' => $this->faker->optional(0.3)->paragraph(1),
            'final_thesis_path' => $this->faker->optional(0.1)->url(),
            'due_date' => $dueDate ? $dueDate->format('Y-m-d') : null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the student's email address should be unverified.
     *
     * @return static
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the student has no thesis details yet.
     *
     * @return static
     */
    public function noThesis(): static
    {
        return $this->state(fn(array $attributes) => [
            'thesis_title' => null,
            'status' => 0,
            'head_division_comment' => null,
            'revision_notes' => null,
            'final_thesis_path' => null,
            'due_date' => null,
        ]);
    }
}