<?php

namespace App\Exports\Enrollments;

use App\Models\Enrollment;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;

class EnrollmentsByClassroomExport extends BaseEnrollmentExport
{
    use Exportable;

    public function __construct(
        private readonly int $classroomId,
        private readonly int $academicYearId,
    ) {}

    public function query(): Builder
    {
        return $this->withRelations(
            Enrollment::query()
                ->where('academic_year_id', $this->academicYearId)
                ->whereHas(
                    'student.classrooms',
                    fn ($q) => $q->where('classrooms.id', $this->classroomId)
                )
        );
    }
}
