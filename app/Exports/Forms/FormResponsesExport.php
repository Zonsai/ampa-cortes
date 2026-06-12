<?php

namespace App\Exports\Forms;

use App\Models\Form;
use App\Models\FormField;
use App\Models\FormResponse;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class FormResponsesExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(private readonly Form $form)
    {
        $this->form->loadMissing('formFields');
    }

    public function query(): Builder
    {
        return FormResponse::query()
            ->where('form_id', $this->form->id)
            ->with([
                'family.guardians' => fn ($q) => $q->orderBy('id'),
                'student.classrooms' => fn ($q) => $q->with('grade'),
                'answers',
            ]);
    }

    public function headings(): array
    {
        $fixed = ['Familia', 'Tutor principal', 'Email', 'Teléfono', 'Alumno/a', 'Clase', 'Fecha respuesta'];

        $dynamic = $this->answerableFields()
            ->pluck('label')
            ->values()
            ->toArray();

        return array_merge($fixed, $dynamic);
    }

    /** @param FormResponse $response */
    public function map($response): array
    {
        $guardian = $response->family->guardians->first();
        $classroom = $response->student?->classrooms
            ->firstWhere('academic_year_id', $this->form->academic_year_id);

        $fixed = [
            $response->family->name,
            $guardian?->full_name,
            $guardian?->email,
            $guardian?->phone,
            $response->student
                ? $response->student->last_name.', '.$response->student->first_name
                : null,
            $classroom?->full_name,
            $response->submitted_at->format('d/m/Y H:i'),
        ];

        $dynamic = $this->answerableFields()
            ->map(fn (FormField $field) => $this->decodeAnswer(
                $response->answers->firstWhere('form_field_id', $field->id)?->value
            ))
            ->values()
            ->toArray();

        return array_merge($fixed, $dynamic);
    }

    private function answerableFields()
    {
        return $this->form->formFields
            ->filter(fn (FormField $field) => $field->type->storesAnswer())
            ->sortBy('sort_order');
    }

    private function decodeAnswer(?string $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE && is_array($decoded)
            ? implode(', ', $decoded)
            : $value;
    }
}
