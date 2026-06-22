<?php

namespace Database\Factories;

use App\Enums\AnnouncementAudience;
use App\Enums\AnnouncementStatus;
use App\Models\Announcement;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    public function definition(): array
    {
        $title = $this->faker->unique()->sentence(4);

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.$this->faker->unique()->numberBetween(1, 99999),
            'summary' => $this->faker->sentence(),
            'content' => $this->faker->paragraphs(3, true),
            'status' => AnnouncementStatus::Published,
            'audience' => AnnouncementAudience::Public,
            'is_pinned' => false,
            'published_at' => now()->subDay(),
            'expires_at' => null,
            'created_by_id' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => AnnouncementStatus::Draft, 'published_at' => null]);
    }

    public function archived(): static
    {
        return $this->state(fn () => ['status' => AnnouncementStatus::Archived]);
    }

    public function pinned(): static
    {
        return $this->state(fn () => ['is_pinned' => true]);
    }

    public function expired(): static
    {
        return $this->state(fn () => ['published_at' => now()->subDays(10), 'expires_at' => now()->subDay()]);
    }

    public function forFamilies(): static
    {
        return $this->state(fn () => ['audience' => AnnouncementAudience::Families]);
    }

    public function both(): static
    {
        return $this->state(fn () => ['audience' => AnnouncementAudience::Both]);
    }
}
