<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicYear>
 */
class AcademicYearFactory extends Factory
{
    private static int $sequence = 0;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = 2020 + (self::$sequence % 10);
        self::$sequence++;

        return [
            'name' => "{$year}-".($year + 1),
            'starts_at' => "{$year}-09-01",
            'ends_at' => ($year + 1).'-06-30',
            'is_active' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(['is_active' => true]);
    }
}
