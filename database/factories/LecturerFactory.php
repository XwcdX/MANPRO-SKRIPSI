<?php

namespace Database\Factories;

use App\Models\Lecturer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class LecturerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Lecturer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->userName() . '@peter.petra.ac.id',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'title' => $this->faker->numberBetween(0, 3),
            'division_id' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the lecturer's email address should be unverified.
     *
     * @return static
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the lecturer is a Head of Division (title 2) and assign a dummy division_id.
     * (You might need to create a DivisionFactory or handle this differently if `division_id` is crucial)
     *
     * @return static
     */
    public function headOfDivision(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => 2,
            'division_id' => \App\Models\Division::factory(),
        ]);
    }
}