<?php

namespace Database\Factories;

use App\Models\ConsentType;
use App\Models\ConsentVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConsentVersion>
 */
class ConsentVersionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'consent_type_id' => ConsentType::factory(),
            'version_number' => 1,
            'legal_text' => fake()->paragraphs(3, true),
            'summary' => fake()->sentence(10, false),
            'effective_from' => null,
            'published_at' => null,
            'created_by_id' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(['published_at' => now()]);
    }
}
