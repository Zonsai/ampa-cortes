<?php

namespace Database\Factories;

use App\Enums\ExceptionType;
use App\Models\ActivityGroup;
use App\Models\ActivityGroupException;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityGroupException>
 */
class ActivityGroupExceptionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'activity_group_id' => ActivityGroup::factory(),
            'original_date' => fake()->date(),
            'type' => ExceptionType::Cancelled,
            'reason' => fake()->boolean(50) ? fake()->sentence() : null,
        ];
    }

    public function cancelled(): static
    {
        return $this->state([
            'type' => ExceptionType::Cancelled,
            'new_date' => null,
            'new_starts_at' => null,
            'new_ends_at' => null,
            'new_location' => null,
        ]);
    }

    public function modified(): static
    {
        return $this->state([
            'type' => ExceptionType::Modified,
            'new_starts_at' => '17:00:00',
            'new_ends_at' => '18:30:00',
        ]);
    }

    public function extra(): static
    {
        return $this->state([
            'type' => ExceptionType::Extra,
            'original_date' => null,
            'new_date' => fake()->date(),
            'new_starts_at' => '10:00:00',
            'new_ends_at' => '11:30:00',
        ]);
    }
}
