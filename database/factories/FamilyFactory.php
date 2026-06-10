<?php

namespace Database\Factories;

use App\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Family>
 */
class FamilyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isAmpaMember = fake()->boolean(40);

        return [
            'name' => fake()->lastName().' '.fake()->lastName(),
            'is_ampa_member' => $isAmpaMember,
            'ampa_member_since' => $isAmpaMember ? fake()->dateTimeBetween('-4 years', 'now') : null,
            'notes' => fake()->optional(0.2)->sentence(),
        ];
    }

    public function ampaMember(): static
    {
        return $this->state(fn () => [
            'is_ampa_member' => true,
            'ampa_member_since' => fake()->dateTimeBetween('-4 years', 'now'),
        ]);
    }

    public function notAmpaMember(): static
    {
        return $this->state(fn () => [
            'is_ampa_member' => false,
            'ampa_member_since' => null,
        ]);
    }
}
