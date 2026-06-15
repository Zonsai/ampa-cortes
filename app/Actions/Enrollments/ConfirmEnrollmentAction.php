<?php

namespace App\Actions\Enrollments;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmEnrollmentAction
{
    public function __construct(private SyncGroupStatusAction $syncGroupStatus) {}

    /**
     * Confirms a Pending enrollment request, moving it to Enrolled if a spot is available.
     *
     * Uses lockForUpdate to prevent race conditions when multiple Pending
     * enrollments exist for the same group.
     *
     * @throws ValidationException
     */
    public function execute(Enrollment $enrollment): Enrollment
    {
        return DB::transaction(function () use ($enrollment) {
            if ($enrollment->status !== EnrollmentStatus::Pending) {
                throw ValidationException::withMessages([
                    'status' => __('Solo se pueden confirmar inscripciones en estado pendiente.'),
                ]);
            }

            $group = $enrollment->activityGroup()->lockForUpdate()->first();

            if (! $group->hasAvailableSpots()) {
                throw ValidationException::withMessages([
                    'status' => __('No quedan plazas disponibles en este grupo.'),
                ]);
            }

            $enrollment->update([
                'status' => EnrollmentStatus::Enrolled,
                'enrolled_at' => now(),
                'waitlist_position' => null,
            ]);

            $this->syncGroupStatus->execute($group->fresh());

            return $enrollment->fresh();
        });
    }
}
