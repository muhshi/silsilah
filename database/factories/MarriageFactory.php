<?php

namespace Database\Factories;

use App\Models\Marriage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Marriage>
 */
class MarriageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'marriage_date' => fake()->date(),
            'is_current' => fake()->boolean(90),
        ];
    }
}
