<?php

namespace App\Actions\Enrollments;

use App\Enums\EnrollmentStatus;
use App\Models\AuditLog;
use App\Models\Enrollment;
use App\Services\AuditLogger;
use App\Support\EnrollmentAuditData;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MoveToWaitlistAction
{
    /**
     * Moves a Pending enrollment request to Waitlist, assigning the next available position.
     *
     * @throws ValidationException
     */
    public function execute(Enrollment $enrollment): Enrollment
    {
        return DB::transaction(function () use ($enrollment) {
            if ($enrollment->status !== EnrollmentStatus::Pending) {
                throw ValidationException::withMessages([
                    'status' => __('Solo se pueden enviar a lista de espera las inscripciones en estado pendiente.'),
                ]);
            }

            $group = $enrollment->activityGroup()->lockForUpdate()->first();
            $position = $group->nextWaitlistPosition();

            $statusBefore = $enrollment->status;

            $enrollment->update([
                'status' => EnrollmentStatus::Waitlist,
                'waitlist_position' => $position,
            ]);

            app(AuditLogger::class)->log(
                AuditLog::ENROLLMENT_WAITLISTED,
                $enrollment,
                'Enviado a lista de espera',
                EnrollmentAuditData::properties($enrollment, $statusBefore),
                subjectLabel: EnrollmentAuditData::label($enrollment),
            );

            return $enrollment->fresh();
        });
    }
}
