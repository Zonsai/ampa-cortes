<?php

namespace Database\Factories;

use App\Enums\ConsentScope;
use App\Enums\ConsentTypeStatus;
use App\Models\ConsentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConsentType>
 */
class ConsentTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->words(3, true)),
            'purpose' => fake()->sentence(6, false),
            'scope' => ConsentScope::PerFamily,
            'is_rejectable' => true,
            'is_revocable' => true,
            'requires_image_review' => false,
            'status' => ConsentTypeStatus::Draft,
            'sort_order' => 0,
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => ConsentTypeStatus::Published]);
    }

    public function archived(): static
    {
        return $this->state(['status' => ConsentTypeStatus::Archived]);
    }

    public function perStudent(): static
    {
        return $this->state(['scope' => ConsentScope::PerStudent]);
    }

    public function notRejectable(): static
    {
        return $this->state(['is_rejectable' => false]);
    }

    public function notRevocable(): static
    {
        return $this->state(['is_revocable' => false]);
    }
}
