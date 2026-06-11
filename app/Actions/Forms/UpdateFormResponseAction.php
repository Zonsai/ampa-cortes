<?php

namespace App\Actions\Forms;

use App\Models\Family;
use App\Models\Form;
use App\Models\FormResponse;
use App\Models\FormResponseAnswer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateFormResponseAction
{
    /**
     * Updates an existing form response's answers.
     *
     * @param  array<int|string, mixed>  $answers  Keyed by form_field_id
     *
     * @throws ValidationException
     */
    public function execute(
        FormResponse $response,
        Family $family,
        array $answers,
    ): FormResponse {
        // 1. Verify ownership.
        if ($response->family_id !== $family->id) {
            throw ValidationException::withMessages([
                'form' => 'No tienes permiso para editar esta respuesta.',
            ]);
        }

        $form = $response->form;

        // 2. Verify allow_edit is enabled.
        if (! $form->allow_edit) {
            throw ValidationException::withMessages([
                'form' => 'Este formulario no permite editar la respuesta una vez enviada.',
            ]);
        }

        // 3. Verify the form is still open.
        if (! $form->isOpenNow()) {
            throw ValidationException::withMessages([
                'form' => 'El formulario ya está cerrado y no se pueden editar las respuestas.',
            ]);
        }

        // 4. Load fields and validate new answers.
        $form->load('formFields');
        $this->validateAnswers($form, $answers);

        // 5. Update answers in a transaction (delete + recreate is simplest for a form with variable fields).
        return DB::transaction(function () use ($response, $form, $answers) {
            foreach ($form->formFields as $field) {
                if (! $field->type->storesAnswer()) {
                    continue;
                }

                $value = $answers[$field->id] ?? null;

                FormResponseAnswer::updateOrCreate(
                    [
                        'form_response_id' => $response->id,
                        'form_field_id' => $field->id,
                    ],
                    [
                        'value' => is_array($value) ? json_encode($value) : $value,
                    ]
                );
            }

            $response->touch();

            return $response->fresh(['answers']);
        });
    }

    /**
     * @param  array<int|string, mixed>  $answers
     *
     * @throws ValidationException
     */
    private function validateAnswers(Form $form, array $answers): void
    {
        $errors = [];

        foreach ($form->formFields as $field) {
            if (! $field->type->storesAnswer()) {
                continue;
            }

            $value = $answers[$field->id] ?? null;
            $isEmpty = $value === null || $value === '' || $value === [];

            if ($field->is_required && $isEmpty) {
                $errors["field_{$field->id}"] = "El campo «{$field->label}» es obligatorio.";
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }
}
