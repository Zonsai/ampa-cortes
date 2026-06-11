<?php

namespace Database\Factories;

use App\Models\Family;
use App\Models\Form;
use App\Models\FormResponse;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormResponse>
 */
class FormResponseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $family = Family::factory()->create();

        return [
            'form_id' => Form::factory(),
            'family_id' => $family->id,
            'student_id' => null,
            'response_key' => "family:{$family->id}",
            'submitted_at' => now(),
        ];
    }

    public function forStudent(Student $student): static
    {
        return $this->state([
            'family_id' => $student->family_id,
            'student_id' => $student->id,
            'response_key' => "student:{$student->id}",
        ]);
    }
}
