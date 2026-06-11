<?php

namespace Database\Factories;

use App\Enums\FormFieldType;
use App\Models\Form;
use App\Models\FormField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormField>
 */
class FormFieldFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'form_id' => Form::factory(),
            'type' => FormFieldType::TextShort,
            'label' => ucfirst(fake()->words(3, true)).'?',
            'description' => null,
            'is_required' => false,
            'sort_order' => 0,
            'options' => null,
            'validation_rules' => null,
        ];
    }

    public function required(): static
    {
        return $this->state(['is_required' => true]);
    }

    public function select(array $options = ['Opción A', 'Opción B', 'Opción C']): static
    {
        return $this->state(['type' => FormFieldType::Select, 'options' => $options]);
    }

    public function radio(array $options = ['Sí', 'No', 'Tal vez']): static
    {
        return $this->state(['type' => FormFieldType::Radio, 'options' => $options]);
    }

    public function checkboxes(array $options = ['Lunes', 'Martes', 'Miércoles']): static
    {
        return $this->state(['type' => FormFieldType::Checkboxes, 'options' => $options]);
    }

    public function yesNo(): static
    {
        return $this->state(['type' => FormFieldType::YesNo]);
    }

    public function email(): static
    {
        return $this->state(['type' => FormFieldType::Email]);
    }

    public function infoText(): static
    {
        return $this->state(['type' => FormFieldType::InfoText]);
    }
}
