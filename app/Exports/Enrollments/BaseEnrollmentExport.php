<?php

namespace App\Exports\Enrollments;

use App\Models\ActivityGroup;
use App\Models\Enrollment;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

abstract class BaseEnrollmentExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function headings(): array
    {
        return [
            'Curso escolar',
            'Actividad',
            'Grupo',
            'Días',
            'Horario',
            'Alumno/a',
            'Familia',
            'Nivel/Curso',
            'Clase',
            'Tutor/a principal',
            'Email tutor/a',
            'Teléfono tutor/a',
            'Estado',
            'Tipo de precio',
            'Importe (€)',
            'Fecha solicitud',
            'Fecha inscripción',
            'Fecha fin',
            'Pos. lista espera',
            'Observaciones familia',
            'Observaciones internas',
        ];
    }

    /** @param  Enrollment  $enrollment */
    public function map($enrollment): array
    {
        $guardian = $enrollment->family->guardians->first();
        $classroom = $enrollment->student->classrooms
            ->where('academic_year_id', $enrollment->academic_year_id)
            ->first();

        return [
            $enrollment->academicYear->name,
            $enrollment->activity->name,
            $enrollment->activityGroup->name,
            $enrollment->activityGroup->weekdaysLabel(),
            $this->scheduleLabel($enrollment->activityGroup),
            $enrollment->student->last_name.', '.$enrollment->student->first_name,
            $enrollment->family->name,
            $classroom?->grade?->name,
            $classroom?->full_name,
            $guardian?->full_name,
            $guardian?->email,
            $guardian?->phone,
            $enrollment->status->label(),
            $enrollment->price_type?->label(),
            $enrollment->amount,
            $enrollment->registered_at?->format('d/m/Y H:i'),
            $enrollment->enrolled_at?->format('d/m/Y H:i'),
            $enrollment->ended_at?->format('d/m/Y H:i'),
            $enrollment->waitlist_position,
            $enrollment->family_notes,
            $enrollment->internal_notes,
        ];
    }

    protected function withRelations(Builder $query): Builder
    {
        return $query->with([
            'academicYear',
            'activity',
            'activityGroup',
            'student',
            'family.guardians' => fn ($q) => $q->orderBy('id'),
            'student.classrooms' => fn ($q) => $q->with('grade'),
        ]);
    }

    private function scheduleLabel(ActivityGroup $group): string
    {
        return substr((string) $group->starts_at, 0, 5).'–'.substr((string) $group->ends_at, 0, 5);
    }
}
