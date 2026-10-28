<?php

namespace Database\Factories;

use App\Models\FamilyTree;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FamilyTree>
 */
class FamilyTreeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->lastName().' Family',
            'description' => fake()->sentence(),
            'is_public' => fake()->boolean(80),
            'view_password' => null,
            'is_premium' => false,
        ];
    }

    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_premium' => true,
        ]);
    }
}
