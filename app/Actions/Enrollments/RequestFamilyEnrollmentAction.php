<?php

namespace App\Actions\Enrollments;

use App\Enums\EnrollmentStatus;
use App\Enums\PriceType;
use App\Models\AcademicYear;
use App\Models\ActivityGroup;
use App\Models\AuditLog;
use App\Models\Enrollment;
use App\Models\Family;
use App\Models\Student;
use App\Services\AuditLogger;
use App\Support\EnrollmentAuditData;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequestFamilyEnrollmentAction
{
    public function __construct(private SyncGroupStatusAction $syncGroupStatus) {}

    /**
     * Creates a family enrollment request.
     *
     * Produces Pending when spots are physically available (spot is NOT reserved;
     * multiple families can be Pending for the same spot until the AMPA confirms).
     * Produces Waitlist when all spots are already occupied.
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

        return DB::transaction(function () use ($student, $group, $family, $academicYear, $familyNotes) {
            $group = ActivityGroup::lockForUpdate()->findOrFail($group->id);
            $group->load('activity');

            $activity = $group->activity;

            if ($activity->requires_ampa_membership && ! $family->is_ampa_member) {
                throw ValidationException::withMessages([
                    'student_id' => __('Esta actividad requiere ser socio/a de la AMPA.'),
                ]);
            }

            $existingEnrollment = Enrollment::where('student_id', $student->id)
                ->where('activity_group_id', $group->id)
                ->whereIn('status', EnrollmentStatus::activeStatuses())
                ->first();

            if ($existingEnrollment) {
                throw ValidationException::withMessages([
                    'student_id' => __('El/la alumno/a ya tiene una inscripción activa en este grupo.'),
                ]);
            }

            // Pending does NOT occupy a spot — only Enrolled / PendingPayment / Paid do.
            $occupiedSpots = Enrollment::where('activity_group_id', $group->id)
                ->whereIn('status', EnrollmentStatus::spotOccupyingStatuses())
                ->count();

            $hasAvailableSpots = $occupiedSpots < $group->max_spots;

            $isMember = $family->is_ampa_member;
            $priceType = $isMember ? PriceType::Member : PriceType::NonMember;
            $amount = $isMember ? $group->price_member : $group->price_non_member;

            if ($hasAvailableSpots) {
                $status = EnrollmentStatus::Pending;
                $waitlistPosition = null;
            } else {
                $status = EnrollmentStatus::Waitlist;
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
                'enrolled_at' => null,
                'ended_at' => null,
                'amount' => $amount,
                'price_type' => $priceType,
                'waitlist_position' => $waitlistPosition,
                'family_notes' => $familyNotes,
            ]);

            $this->syncGroupStatus->execute($group->fresh());

            app(AuditLogger::class)->log(
                $status === EnrollmentStatus::Waitlist
                    ? AuditLog::ENROLLMENT_REQUESTED_WAITLIST
                    : AuditLog::ENROLLMENT_REQUESTED,
                $enrollment,
                $status === EnrollmentStatus::Waitlist
                    ? 'Solicitud familiar enviada a lista de espera'
                    : 'Solicitud familiar de inscripción',
                EnrollmentAuditData::properties($enrollment, null),
                subjectLabel: EnrollmentAuditData::label($enrollment),
            );

            return $enrollment;
        });
    }
}
