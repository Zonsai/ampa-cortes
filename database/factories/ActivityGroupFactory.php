<?php

namespace Database\Factories;

use App\Enums\ActivityGroupStatus;
use App\Models\ActivityGroup;
use App\Models\ExtracurricularActivity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityGroup>
 */
class ActivityGroupFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $weekdays = fake()->randomElements([1, 2, 3, 4, 5], fake()->numberBetween(1, 3));
        sort($weekdays);

        return [
            'activity_id' => ExtracurricularActivity::factory(),
            'name' => 'Grupo '.fake()->randomLetter(),
            'weekdays' => $weekdays,
            'starts_at' => fake()->time('H:i:s', '18:00:00'),
            'ends_at' => fake()->time('H:i:s', '20:00:00'),
            'max_spots' => fake()->numberBetween(8, 20),
            'price_member' => fake()->randomFloat(2, 20, 60),
            'price_non_member' => fake()->randomFloat(2, 30, 80),
            'provider' => fake()->boolean(50) ? fake()->company() : null,
            'location' => fake()->boolean(50) ? fake()->word() : null,
            'status' => ActivityGroupStatus::Open,
        ];
    }

    public function full(): static
    {
        return $this->state(['status' => ActivityGroupStatus::Full]);
    }

    public function closed(): static
    {
        return $this->state(['status' => ActivityGroupStatus::Closed]);
    }

    public function withMaxSpots(int $spots): static
    {
        return $this->state(['max_spots' => $spots]);
    }
}
