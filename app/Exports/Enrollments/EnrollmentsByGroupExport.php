<?php

namespace App\Exports\Enrollments;

use App\Models\Enrollment;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;

class EnrollmentsByGroupExport extends BaseEnrollmentExport
{
    use Exportable;

    public function __construct(private readonly int $activityGroupId) {}

    public function query(): Builder
    {
        return $this->withRelations(
            Enrollment::query()->where('activity_group_id', $this->activityGroupId)
        );
    }
}
