<?php

namespace App\Actions\Enrollments;

use App\Enums\EnrollmentStatus;
use App\Models\AuditLog;
use App\Models\Enrollment;
use App\Services\AuditLogger;
use App\Support\EnrollmentAuditData;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelEnrollmentAction
{
    public function __construct(private SyncGroupStatusAction $syncGroupStatus) {}

    /**
     * @throws ValidationException
     */
    public function execute(Enrollment $enrollment, ?string $internalNotes = null): Enrollment
    {
        return DB::transaction(function () use ($enrollment, $internalNotes) {
            $cancellableStatuses = [
                EnrollmentStatus::Pending,
                EnrollmentStatus::Waitlist,
            ];

            if (! in_array($enrollment->status, $cancellableStatuses)) {
                throw ValidationException::withMessages([
                    'status' => __('La inscripción no puede cancelarse desde el estado actual.'),
                ]);
            }

            $statusBefore = $enrollment->status;

            $enrollment->update([
                'status' => EnrollmentStatus::Cancelled,
                'ended_at' => now(),
                'waitlist_position' => null,
                'internal_notes' => $internalNotes ?? $enrollment->internal_notes,
            ]);

            $this->syncGroupStatus->execute($enrollment->activityGroup);

            app(AuditLogger::class)->log(
                AuditLog::ENROLLMENT_CANCELLED,
                $enrollment,
                'Inscripción cancelada',
                EnrollmentAuditData::properties($enrollment, $statusBefore),
                subjectLabel: EnrollmentAuditData::label($enrollment),
            );

            return $enrollment->fresh();
        });
    }
}
