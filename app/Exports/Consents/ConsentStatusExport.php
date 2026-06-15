<?php

namespace App\Exports\Consents;

use App\Models\ConsentResponse;
use App\Models\ConsentType;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ConsentStatusExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(private readonly ConsentType $consentType) {}

    public function query(): Builder
    {
        return ConsentResponse::query()
            ->where('consent_type_id', $this->consentType->id)
            ->with(['family.guardians', 'student', 'consentVersion', 'respondedBy'])
            ->orderBy('family_id')
            ->orderBy('student_id');
    }

    public function headings(): array
    {
        return [
            'Tipo de consentimiento',
            'Finalidad',
            'Alcance',
            'Familia',
            'Tutor principal',
            'Email tutor',
            'Teléfono tutor',
            'Alumno/a',
            'Estado',
            'Versión',
            'Fecha respuesta',
            'Fecha revocación',
            'Respondido por',
            'Última actualización',
        ];
    }

    /** @param ConsentResponse $response */
    public function map($response): array
    {
        $guardian = $response->family->guardians->sortBy('id')->first();

        return [
            $this->consentType->name,
            $this->consentType->purpose,
            $this->consentType->scope->getLabel(),
            $response->family->name,
            $guardian?->full_name,
            $guardian?->email,
            $guardian?->phone,
            $response->student
                ? $response->student->last_name.', '.$response->student->first_name
                : null,
            $response->status->getLabel(),
            $response->consentVersion?->version_number,
            $response->responded_at?->format('d/m/Y H:i'),
            $response->revoked_at?->format('d/m/Y H:i'),
            $response->respondedBy?->name,
            $response->updated_at->format('d/m/Y H:i'),
        ];
    }
}
