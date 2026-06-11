<?php

namespace App\Exports\Enrollments;

use App\Models\Enrollment;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;

class EnrollmentsByActivityExport extends BaseEnrollmentExport
{
    use Exportable;

    public function __construct(private readonly int $activityId) {}

    public function query(): Builder
    {
        return $this->withRelations(
            Enrollment::query()->where('activity_id', $this->activityId)
        );
    }
}
