<?php

namespace App\Actions\Enrollments;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
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

            $enrollment->update([
                'status' => EnrollmentStatus::Waitlist,
                'waitlist_position' => $position,
            ]);

            return $enrollment->fresh();
        });
    }
}
