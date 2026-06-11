<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Enums\FormStatus;
use App\Enums\FormTargetType;
use App\Models\AcademicYear;
use App\Models\ActivityGroup;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\ExtracurricularActivity;
use App\Models\Family;
use App\Models\Form;
use App\Models\Grade;
use App\Models\SchoolStage;
use Illuminate\Support\Collection as SupportCollection;

class FormVisibilityService
{
    /**
     * Returns true when the form is currently open and targeted at this family.
     * This is the gate for submitting responses.
     */
    public function isOpenForFamily(Form $form, Family $family): bool
    {
        if (! $form->isOpenNow()) {
            return false;
        }

        return $this->isTargetedAt($form, $family);
    }

    /**
     * Returns true when this family is within the form's target audience,
     * regardless of status or dates.
     */
    public function isTargetedAt(Form $form, Family $family): bool
    {
        return match ($form->target_type) {
            FormTargetType::AllFamilies => true,
            FormTargetType::AmpaMembers => (bool) $family->is_ampa_member,
            FormTargetType::ByStage => $this->familyHasStudentInStage($form, $family),
            FormTargetType::ByGrade => $this->familyHasStudentInGrade($form, $family),
            FormTargetType::ByClassroom => $this->familyHasStudentInClassroom($form, $family),
            FormTargetType::ByActivity => $this->familyHasEnrollmentInActivity($form, $family),
            FormTargetType::ByGroup => $this->familyHasEnrollmentInGroup($form, $family),
        };
    }

    /**
     * Returns all published+open forms targeted at the given family for a given academic year.
     * If no year is provided, uses the currently active year (is_active = true).
     * Returns an empty collection when no active year exists and none is provided.
     *
     * @return SupportCollection<int, Form>
     */
    public function getOpenFormsForFamily(Family $family, ?AcademicYear $academicYear = null): SupportCollection
    {
        $year = $academicYear ?? AcademicYear::where('is_active', true)->first();

        if ($year === null) {
            return collect();
        }

        return Form::query()
            ->with('formTargetItems')
            ->where('status', FormStatus::Published)
            ->where('academic_year_id', $year->id)
            ->get()
            ->filter(fn (Form $form) => $this->isOpenForFamily($form, $family))
            ->values();
    }

    // ─── Private target helpers ─────────────────────────────────────────────

    private function familyHasStudentInStage(Form $form, Family $family): bool
    {
        $stageIds = $this->targetIds($form, SchoolStage::class);

        if ($stageIds->isEmpty()) {
            return false;
        }

        return $family->students()
            ->where('is_active', true)
            ->whereHas('classrooms', function ($q) use ($stageIds, $form) {
                $q->where('academic_year_id', $form->academic_year_id)
                    ->whereHas('grade', fn ($q) => $q->whereIn('school_stage_id', $stageIds));
            })
            ->exists();
    }

    private function familyHasStudentInGrade(Form $form, Family $family): bool
    {
        $gradeIds = $this->targetIds($form, Grade::class);

        if ($gradeIds->isEmpty()) {
            return false;
        }

        return $family->students()
            ->where('is_active', true)
            ->whereHas('classrooms', function ($q) use ($gradeIds, $form) {
                $q->where('academic_year_id', $form->academic_year_id)
                    ->whereIn('grade_id', $gradeIds);
            })
            ->exists();
    }

    private function familyHasStudentInClassroom(Form $form, Family $family): bool
    {
        $classroomIds = $this->targetIds($form, Classroom::class);

        if ($classroomIds->isEmpty()) {
            return false;
        }

        return $family->students()
            ->where('is_active', true)
            ->whereHas('classrooms', function ($q) use ($classroomIds, $form) {
                $q->where('academic_year_id', $form->academic_year_id)
                    ->whereIn('classrooms.id', $classroomIds);
            })
            ->exists();
    }

    private function familyHasEnrollmentInActivity(Form $form, Family $family): bool
    {
        $activityIds = $this->targetIds($form, ExtracurricularActivity::class);

        if ($activityIds->isEmpty()) {
            return false;
        }

        $activeStatuses = array_map(
            fn ($s) => $s->value,
            EnrollmentStatus::activeStatuses()
        );

        return Enrollment::where('family_id', $family->id)
            ->where('academic_year_id', $form->academic_year_id)
            ->whereIn('activity_id', $activityIds)
            ->whereIn('status', $activeStatuses)
            ->exists();
    }

    private function familyHasEnrollmentInGroup(Form $form, Family $family): bool
    {
        $groupIds = $this->targetIds($form, ActivityGroup::class);

        if ($groupIds->isEmpty()) {
            return false;
        }

        $activeStatuses = array_map(
            fn ($s) => $s->value,
            EnrollmentStatus::activeStatuses()
        );

        return Enrollment::where('family_id', $family->id)
            ->where('academic_year_id', $form->academic_year_id)
            ->whereIn('activity_group_id', $groupIds)
            ->whereIn('status', $activeStatuses)
            ->exists();
    }

    /**
     * Returns the target IDs for a given morph type from the form's target items.
     *
     * @return SupportCollection<int, int>
     */
    private function targetIds(Form $form, string $morphType): SupportCollection
    {
        return $form->formTargetItems
            ->where('targetable_type', $morphType)
            ->pluck('targetable_id');
    }
}
