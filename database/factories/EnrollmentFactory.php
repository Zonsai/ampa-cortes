<?php

namespace Database\Factories;

use App\Enums\EnrollmentStatus;
use App\Enums\PriceType;
use App\Models\AcademicYear;
use App\Models\ActivityGroup;
use App\Models\Enrollment;
use App\Models\Family;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $group = ActivityGroup::factory()->create();

        return [
            'student_id' => Student::factory(),
            'family_id' => Family::factory(),
            'activity_id' => $group->activity_id,
            'activity_group_id' => $group->id,
            'academic_year_id' => AcademicYear::factory(),
            'status' => EnrollmentStatus::Enrolled,
            'registered_at' => now(),
            'enrolled_at' => now(),
            'ended_at' => null,
            'amount' => fake()->randomFloat(2, 20, 60),
            'price_type' => PriceType::Member,
            'waitlist_position' => null,
            'family_notes' => null,
            'internal_notes' => null,
        ];
    }

    public function waitlist(int $position = 1): static
    {
        return $this->state([
            'status' => EnrollmentStatus::Waitlist,
            'enrolled_at' => null,
            'waitlist_position' => $position,
        ]);
    }

    public function dropped(): static
    {
        return $this->state([
            'status' => EnrollmentStatus::Dropped,
            'ended_at' => now(),
            'waitlist_position' => null,
        ]);
    }
}
