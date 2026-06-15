<?php

namespace App\Actions\Enrollments;

use App\Models\AcademicYear;
use App\Models\ActivityGroup;
use App\Models\Enrollment;
use App\Models\Family;
use App\Models\Student;
use Illuminate\Validation\ValidationException;

class EnrollFamiliaStudentAction
{
    public function __construct(private EnrollStudentAction $enrollStudent) {}

    /**
     * Enrolls a student from the family portal, enforcing classroom and grade restrictions
     * on top of the base enrollment rules (AMPA membership, duplicates, spot availability).
     *
     * @throws ValidationException
     */
    public function execute(
        Student $student,
        ActivityGroup $group,
        Family $family,
        AcademicYear $academicYear,
        ?string $familyNotes = null,
    ): Enrollment {
        $group->loadMissing(['grades', 'activity']);

        if ($group->activity->academic_year_id !== $academicYear->id) {
            throw ValidationException::withMessages([
                'activity_group_id' => __('Esta actividad no pertenece al curso académico activo.'),
            ]);
        }

        $classroom = $student->classrooms()
            ->where('academic_year_id', $academicYear->id)
            ->first();

        if (! $classroom) {
            throw ValidationException::withMessages([
                'student_id' => __('El/la alumno/a no tiene clase asignada en este curso académico.'),
            ]);
        }

        if ($group->grades->isNotEmpty() && ! $group->grades->contains('id', $classroom->grade_id)) {
            throw ValidationException::withMessages([
                'student_id' => __('El/la alumno/a no pertenece a un curso permitido para este grupo.'),
            ]);
        }

        return $this->enrollStudent->execute(
            student: $student,
            group: $group,
            family: $family,
            academicYear: $academicYear,
            familyNotes: $familyNotes,
        );
    }
}
