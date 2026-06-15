<?php

namespace App\Exports\Consents;

use App\Models\ConsentHistory;
use App\Models\ConsentType;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ConsentHistoryExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(private readonly ConsentType $consentType) {}

    public function query(): Builder
    {
        return ConsentHistory::query()
            ->where('consent_type_id', $this->consentType->id)
            ->with(['family', 'student', 'performedBy', 'consentVersion'])
            ->orderBy('created_at');
    }

    public function headings(): array
    {
        return [
            'Tipo de consentimiento',
            'Familia',
            'Alumno/a',
            'Evento',
            'Versión',
            'Usuario',
            'IP',
            'User Agent',
            'Notas',
            'Fecha evento',
        ];
    }

    /** @param ConsentHistory $history */
    public function map($history): array
    {
        return [
            $this->consentType->name,
            $history->family->name,
            $history->student
                ? $history->student->last_name.', '.$history->student->first_name
                : null,
            $history->event_type->getLabel(),
            $history->consentVersion?->version_number,
            $history->performedBy?->name,
            $history->ip_address,
            $history->user_agent,
            $history->notes,
            $history->created_at->format('d/m/Y H:i:s'),
        ];
    }
}
