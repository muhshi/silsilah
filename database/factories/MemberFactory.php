<?php

namespace Database\Factories;

use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gender = fake()->randomElement(['male', 'female']);
        
        return [
            'first_name' => fake()->firstName($gender),
            'last_name' => fake()->lastName(),
            'gender' => $gender,
            'is_living' => fake()->boolean(85),
            'birth_date' => fake()->dateTimeBetween('-80 years', '-10 years')->format('Y-m-d'),
            'birth_place' => fake()->city(),
            'avatar_id' => fake()->numberBetween(1, 18),
            'photo' => null,
            'profession' => fake()->optional()->jobTitle(),
            'address' => fake()->optional()->address(),
            'bio' => fake()->optional()->paragraph(),
        ];
    }
}
