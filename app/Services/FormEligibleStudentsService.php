<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Enums\FormTargetType;
use App\Models\Enrollment;
use App\Models\Family;
use App\Models\Form;
use App\Models\Student;
use Illuminate\Support\Collection;

class FormEligibleStudentsService
{
    /**
     * Returns the subset of the family's active students that are eligible
     * for the given form, based on its target_type and target items.
     *
     * @return Collection<int, Student>
     */
    public function getEligibleStudents(Form $form, Family $family): Collection
    {
        $students = $family->students()->where('is_active', true)->get();

        // For targets that don't restrict by student, all active students qualify.
        if (! $form->target_type->requiresTargetItems()) {
            return $students;
        }

        return $students->filter(fn (Student $student) => $this->isStudentEligible($form, $student));
    }

    /**
     * Returns true when the given student meets the form's target criteria.
     */
    public function isStudentEligible(Form $form, Student $student): bool
    {
        if (! $form->target_type->requiresTargetItems()) {
            return true;
        }

        $targetIds = $form->formTargetItems->pluck('targetable_id');

        return match ($form->target_type) {
            FormTargetType::ByStage => $this->studentIsInStage($student, $form, $targetIds),
            FormTargetType::ByGrade => $this->studentIsInGrade($student, $form, $targetIds),
            FormTargetType::ByClassroom => $this->studentIsInClassroom($student, $form, $targetIds),
            FormTargetType::ByActivity => $this->studentHasEnrollmentInActivity($student, $form, $targetIds),
            FormTargetType::ByGroup => $this->studentHasEnrollmentInGroup($student, $form, $targetIds),
            default => true,
        };
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    private function studentIsInStage(Student $student, Form $form, Collection $stageIds): bool
    {
        return $student->classrooms()
            ->where('academic_year_id', $form->academic_year_id)
            ->whereHas('grade', fn ($q) => $q->whereIn('school_stage_id', $stageIds))
            ->exists();
    }

    private function studentIsInGrade(Student $student, Form $form, Collection $gradeIds): bool
    {
        return $student->classrooms()
            ->where('academic_year_id', $form->academic_year_id)
            ->whereIn('grade_id', $gradeIds)
            ->exists();
    }

    private function studentIsInClassroom(Student $student, Form $form, Collection $classroomIds): bool
    {
        return $student->classrooms()
            ->where('academic_year_id', $form->academic_year_id)
            ->whereIn('classrooms.id', $classroomIds)
            ->exists();
    }

    private function studentHasEnrollmentInActivity(Student $student, Form $form, Collection $activityIds): bool
    {
        $activeStatuses = array_map(fn ($s) => $s->value, EnrollmentStatus::activeStatuses());

        return Enrollment::where('student_id', $student->id)
            ->where('academic_year_id', $form->academic_year_id)
            ->whereIn('activity_id', $activityIds)
            ->whereIn('status', $activeStatuses)
            ->exists();
    }

    private function studentHasEnrollmentInGroup(Student $student, Form $form, Collection $groupIds): bool
    {
        $activeStatuses = array_map(fn ($s) => $s->value, EnrollmentStatus::activeStatuses());

        return Enrollment::where('student_id', $student->id)
            ->where('academic_year_id', $form->academic_year_id)
            ->whereIn('activity_group_id', $groupIds)
            ->whereIn('status', $activeStatuses)
            ->exists();
    }
}
