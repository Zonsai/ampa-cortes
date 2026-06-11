<?php

namespace App\Exports\Enrollments;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;

class WaitlistExport extends BaseEnrollmentExport
{
    use Exportable;

    public function __construct(private readonly int $activityGroupId) {}

    public function query(): Builder
    {
        return $this->withRelations(
            Enrollment::query()
                ->where('activity_group_id', $this->activityGroupId)
                ->where('status', EnrollmentStatus::Waitlist)
                ->orderBy('waitlist_position')
        );
    }
}
