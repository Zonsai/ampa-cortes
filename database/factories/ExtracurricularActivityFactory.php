<?php

namespace Database\Factories;

use App\Enums\ActivityStatus;
use App\Models\AcademicYear;
use App\Models\ExtracurricularActivity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExtracurricularActivity>
 */
class ExtracurricularActivityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'academic_year_id' => AcademicYear::factory(),
            'name' => ucwords(fake()->words(3, true)),
            'short_description' => fake()->sentence(),
            'long_description' => fake()->boolean(40) ? fake()->paragraph() : null,
            'status' => ActivityStatus::Published,
            'is_visible_for_families' => fake()->boolean(70),
            'requires_ampa_membership' => fake()->boolean(30),
            'internal_notes' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => ActivityStatus::Draft]);
    }

    public function published(): static
    {
        return $this->state(['status' => ActivityStatus::Published]);
    }

    public function archived(): static
    {
        return $this->state(['status' => ActivityStatus::Archived]);
    }

    public function requiresAmpa(): static
    {
        return $this->state(['requires_ampa_membership' => true]);
    }
}
