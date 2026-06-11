<?php

namespace App\Actions\Forms;

use App\Enums\FormFieldType;
use App\Enums\FormResponseScope;
use App\Models\Family;
use App\Models\Form;
use App\Models\FormResponse;
use App\Models\FormResponseAnswer;
use App\Models\Student;
use App\Services\FormEligibleStudentsService;
use App\Services\FormVisibilityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmitFormResponseAction
{
    public function __construct(
        private readonly FormVisibilityService $visibilityService,
        private readonly FormEligibleStudentsService $eligibleStudentsService,
    ) {}

    /**
     * Validates and submits a form response for the given family.
     *
     * @param  array<int|string, mixed>  $answers  Keyed by form_field_id
     *
     * @throws ValidationException
     */
    public function execute(
        Form $form,
        Family $family,
        ?Student $student,
        array $answers,
    ): FormResponse {
        // 1. Verify the form is currently open for this family.
        if (! $this->visibilityService->isOpenForFamily($form, $family)) {
            throw ValidationException::withMessages([
                'form' => 'Este formulario no está disponible o no está dirigido a tu familia.',
            ]);
        }

        // 2. Per-student scope: validate student eligibility.
        if ($form->response_scope === FormResponseScope::PerStudent) {
            $this->validateStudent($form, $family, $student);
        }

        // 3. Build response_key and check for duplicates.
        $responseKey = FormResponse::buildResponseKey($family, $student);

        $existing = FormResponse::where('form_id', $form->id)
            ->where('response_key', $responseKey)
            ->whereNull('deleted_at')
            ->first();

        if ($existing !== null) {
            throw ValidationException::withMessages([
                'form' => 'Ya has enviado una respuesta para este formulario.',
            ]);
        }

        // 4. Load fields and validate answers.
        $form->load('formFields');
        $this->validateAnswers($form, $answers);

        // 5. Persist response + answers in a transaction.
        return DB::transaction(function () use ($form, $family, $student, $answers, $responseKey) {
            $response = FormResponse::create([
                'form_id' => $form->id,
                'family_id' => $family->id,
                'student_id' => $student?->id,
                'response_key' => $responseKey,
                'submitted_at' => now(),
            ]);

            foreach ($form->formFields as $field) {
                if (! $field->type->storesAnswer()) {
                    continue;
                }

                $value = $answers[$field->id] ?? null;

                FormResponseAnswer::create([
                    'form_response_id' => $response->id,
                    'form_field_id' => $field->id,
                    'value' => is_array($value) ? json_encode($value) : $value,
                ]);
            }

            return $response->fresh(['answers']);
        });
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    /**
     * @throws ValidationException
     */
    private function validateStudent(Form $form, Family $family, ?Student $student): void
    {
        if ($student === null) {
            throw ValidationException::withMessages([
                'student_id' => 'Debes seleccionar un alumno/a.',
            ]);
        }

        if ($student->family_id !== $family->id) {
            throw ValidationException::withMessages([
                'student_id' => 'Este alumno/a no pertenece a tu familia.',
            ]);
        }

        $eligible = $this->eligibleStudentsService->getEligibleStudents($form, $family);

        if (! $eligible->contains('id', $student->id)) {
            throw ValidationException::withMessages([
                'student_id' => 'Este alumno/a no está incluido en el público objetivo de este formulario.',
            ]);
        }
    }

    /**
     * Validates all field answers according to type and is_required.
     *
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

                continue;
            }

            if ($isEmpty) {
                continue;
            }

            $typeError = $this->validateFieldValue($field->type, $value, $field->options ?? []);

            if ($typeError !== null) {
                $errors["field_{$field->id}"] = "Campo «{$field->label}»: {$typeError}";
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function validateFieldValue(FormFieldType $type, mixed $value, array $options): ?string
    {
        return match ($type) {
            FormFieldType::Email => filter_var($value, FILTER_VALIDATE_EMAIL) === false
                ? 'Introduce un email válido.'
                : null,

            FormFieldType::Number => ! is_numeric($value)
                ? 'Introduce un número válido.'
                : null,

            FormFieldType::Date => $this->isValidDate($value)
                ? null
                : 'Introduce una fecha válida (YYYY-MM-DD).',

            FormFieldType::Select,
            FormFieldType::Radio => (! empty($options) && ! in_array($value, $options, true))
                ? 'Selecciona una opción válida.'
                : null,

            FormFieldType::Checkbox,
            FormFieldType::YesNo => ! in_array($value, ['1', '0', 'true', 'false', true, false, 1, 0], true)
                ? 'El valor debe ser verdadero o falso.'
                : null,

            FormFieldType::Checkboxes => $this->validateCheckboxes($value, $options),

            default => null,
        };
    }

    private function isValidDate(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $d = \DateTime::createFromFormat('Y-m-d', $value);

        return $d !== false && $d->format('Y-m-d') === $value;
    }

    private function validateCheckboxes(mixed $value, array $options): ?string
    {
        if (! is_array($value)) {
            return 'Selecciona una o más opciones.';
        }

        if (! empty($options)) {
            foreach ($value as $item) {
                if (! in_array($item, $options, true)) {
                    return 'Contiene opciones no válidas.';
                }
            }
        }

        return null;
    }
}
