<?php

namespace Database\Factories;

use App\Models\Form;
use App\Models\FormTargetItem;
use App\Models\Grade;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormTargetItem>
 */
class FormTargetItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $grade = Grade::factory()->create();

        return [
            'form_id' => Form::factory(),
            'targetable_type' => Grade::class,
            'targetable_id' => $grade->id,
        ];
    }
}
