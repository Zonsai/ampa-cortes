<?php

namespace Database\Factories;

use App\Models\Guardian;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guardian>
 */
class GuardianFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName().' '.fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional(0.9)->numerify('6## ### ###'),
            'phone_alt' => fake()->optional(0.3)->numerify('9## ### ###'),
            'relationship' => fake()->randomElement(['madre', 'padre', 'tutor/a', 'otro']),
            'notes' => fake()->optional(0.15)->sentence(),
        ];
    }
}
