<?php

namespace App\Actions\Enrollments;

use App\Enums\EnrollmentStatus;
use App\Models\AuditLog;
use App\Models\Enrollment;
use App\Services\AuditLogger;
use App\Support\EnrollmentAuditData;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DropEnrollmentAction
{
    public function __construct(private SyncGroupStatusAction $syncGroupStatus) {}

    /**
     * @throws ValidationException
     */
    public function execute(Enrollment $enrollment, ?string $internalNotes = null): Enrollment
    {
        return DB::transaction(function () use ($enrollment, $internalNotes) {
            $terminableStatuses = [
                EnrollmentStatus::Enrolled,
                EnrollmentStatus::PendingPayment,
                EnrollmentStatus::Paid,
            ];

            if (! in_array($enrollment->status, $terminableStatuses)) {
                throw ValidationException::withMessages([
                    'status' => __('La inscripción no puede darse de baja desde el estado actual.'),
                ]);
            }

            $statusBefore = $enrollment->status;

            $enrollment->update([
                'status' => EnrollmentStatus::Dropped,
                'ended_at' => now(),
                'waitlist_position' => null,
                'internal_notes' => $internalNotes ?? $enrollment->internal_notes,
            ]);

            $this->syncGroupStatus->execute($enrollment->activityGroup);

            app(AuditLogger::class)->log(
                AuditLog::ENROLLMENT_DROPPED,
                $enrollment,
                'Inscripción dada de baja',
                EnrollmentAuditData::properties($enrollment, $statusBefore),
                subjectLabel: EnrollmentAuditData::label($enrollment),
            );

            return $enrollment->fresh();
        });
    }
}
