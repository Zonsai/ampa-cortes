<?php

namespace App\Actions\Enrollments;

use App\Enums\EnrollmentStatus;
use App\Models\AuditLog;
use App\Models\Enrollment;
use App\Services\AuditLogger;
use App\Support\EnrollmentAuditData;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PromoteFromWaitlistAction
{
    public function __construct(private SyncGroupStatusAction $syncGroupStatus) {}

    /**
     * @throws ValidationException
     */
    public function execute(Enrollment $enrollment): Enrollment
    {
        return DB::transaction(function () use ($enrollment) {
            if ($enrollment->status !== EnrollmentStatus::Waitlist) {
                throw ValidationException::withMessages([
                    'status' => __('Solo se pueden promover inscripciones en lista de espera.'),
                ]);
            }

            $group = $enrollment->activityGroup()->lockForUpdate()->first();

            if (! $group->hasAvailableSpots()) {
                throw ValidationException::withMessages([
                    'status' => __('No hay plazas disponibles para promover esta inscripción.'),
                ]);
            }

            $statusBefore = $enrollment->status;

            $enrollment->update([
                'status' => EnrollmentStatus::Enrolled,
                'enrolled_at' => now(),
                'waitlist_position' => null,
            ]);

            $this->syncGroupStatus->execute($group->fresh());

            app(AuditLogger::class)->log(
                AuditLog::ENROLLMENT_PROMOTED,
                $enrollment,
                'Pasado de lista de espera a inscrito/a',
                EnrollmentAuditData::properties($enrollment, $statusBefore),
                subjectLabel: EnrollmentAuditData::label($enrollment),
            );

            return $enrollment->fresh();
        });
    }
}
