<?php

namespace App\Exports\Enrollments;

use App\Models\Enrollment;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;

class GlobalEnrollmentsExport extends BaseEnrollmentExport
{
    use Exportable;

    public function __construct(private readonly int $academicYearId) {}

    public function query(): Builder
    {
        return $this->withRelations(
            Enrollment::query()
                ->where('academic_year_id', $this->academicYearId)
                ->orderBy('registered_at')
        );
    }
}
