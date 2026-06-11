<?php

namespace Database\Factories;

use App\Enums\FormResponseScope;
use App\Enums\FormStatus;
use App\Enums\FormTargetType;
use App\Models\AcademicYear;
use App\Models\Form;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Form>
 */
class FormFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'academic_year_id' => AcademicYear::factory(),
            'title' => ucfirst(fake()->sentence(4, false)),
            'description' => fake()->optional(0.5)->paragraph(),
            'status' => FormStatus::Published,
            'response_scope' => FormResponseScope::PerFamily,
            'target_type' => FormTargetType::AllFamilies,
            'allow_edit' => false,
            'opens_at' => null,
            'closes_at' => null,
            'internal_notes' => null,
            'created_by' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => FormStatus::Draft]);
    }

    public function closed(): static
    {
        return $this->state(['status' => FormStatus::Closed]);
    }

    public function archived(): static
    {
        return $this->state(['status' => FormStatus::Archived]);
    }

    public function perStudent(): static
    {
        return $this->state(['response_scope' => FormResponseScope::PerStudent]);
    }

    public function allowEdit(): static
    {
        return $this->state(['allow_edit' => true]);
    }

    public function forAmpaMembers(): static
    {
        return $this->state(['target_type' => FormTargetType::AmpaMembers]);
    }

    public function forStage(): static
    {
        return $this->state(['target_type' => FormTargetType::ByStage]);
    }

    public function forGrade(): static
    {
        return $this->state(['target_type' => FormTargetType::ByGrade]);
    }

    public function forClassroom(): static
    {
        return $this->state(['target_type' => FormTargetType::ByClassroom]);
    }

    public function forActivity(): static
    {
        return $this->state(['target_type' => FormTargetType::ByActivity]);
    }

    public function forGroup(): static
    {
        return $this->state(['target_type' => FormTargetType::ByGroup]);
    }

    public function openSince(string $date): static
    {
        return $this->state(['opens_at' => $date]);
    }

    public function closedAt(string $date): static
    {
        return $this->state(['closes_at' => $date]);
    }

    public function notYetOpen(): static
    {
        return $this->state(['opens_at' => now()->addDay()]);
    }

    public function alreadyClosed(): static
    {
        return $this->state(['closes_at' => now()->subDay()]);
    }
}
