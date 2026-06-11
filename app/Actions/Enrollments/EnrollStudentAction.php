<?php

namespace App\Actions\Enrollments;

use App\Enums\EnrollmentStatus;
use App\Enums\PriceType;
use App\Models\AcademicYear;
use App\Models\ActivityGroup;
use App\Models\Enrollment;
use App\Models\Family;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EnrollStudentAction
{
    public function __construct(private SyncGroupStatusAction $syncGroupStatus) {}

    /**
     * @throws ValidationException
     */
    public function execute(
        Student $student,
        ActivityGroup $group,
        Family $family,
        AcademicYear $academicYear,
        ?string $familyNotes = null,
        ?string $internalNotes = null,
    ): Enrollment {
        return DB::transaction(function () use ($student, $group, $family, $academicYear, $familyNotes, $internalNotes) {
            $group = ActivityGroup::lockForUpdate()->findOrFail($group->id);
            $group->load('activity');

            $activity = $group->activity;

            if ($activity->requires_ampa_membership && ! $family->is_ampa_member) {
                throw ValidationException::withMessages([
                    'student_id' => __('Esta actividad requiere ser socio/a de la AMPA.'),
                ]);
            }

            $activeStatuses = EnrollmentStatus::activeStatuses();

            $existingEnrollment = Enrollment::where('student_id', $student->id)
                ->where('activity_group_id', $group->id)
                ->whereIn('status', $activeStatuses)
                ->first();

            if ($existingEnrollment) {
                throw ValidationException::withMessages([
                    'student_id' => __('El/la alumno/a ya tiene una inscripción activa en este grupo.'),
                ]);
            }

            $spotStatuses = EnrollmentStatus::spotOccupyingStatuses();
            $occupiedSpots = Enrollment::where('activity_group_id', $group->id)
                ->whereIn('status', $spotStatuses)
                ->count();

            $hasAvailableSpots = $occupiedSpots < $group->max_spots;

            $isMember = $family->is_ampa_member;
            $priceType = $isMember ? PriceType::Member : PriceType::NonMember;
            $amount = $isMember ? $group->price_member : $group->price_non_member;

            if ($hasAvailableSpots) {
                $status = EnrollmentStatus::Enrolled;
                $enrolledAt = now();
                $waitlistPosition = null;
            } else {
                $status = EnrollmentStatus::Waitlist;
                $enrolledAt = null;
                $waitlistPosition = $group->nextWaitlistPosition();
            }

            $enrollment = Enrollment::create([
                'student_id' => $student->id,
                'family_id' => $family->id,
                'activity_id' => $activity->id,
                'activity_group_id' => $group->id,
                'academic_year_id' => $academicYear->id,
                'status' => $status,
                'registered_at' => now(),
                'enrolled_at' => $enrolledAt,
                'ended_at' => null,
                'amount' => $amount,
                'price_type' => $priceType,
                'waitlist_position' => $waitlistPosition,
                'family_notes' => $familyNotes,
                'internal_notes' => $internalNotes,
            ]);

            $this->syncGroupStatus->execute($group->fresh());

            return $enrollment;
        });
    }
}
