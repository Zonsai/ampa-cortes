<?php

namespace App\Actions\Enrollments;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use Illuminate\Validation\ValidationException;

class MarkPendingPaymentAction
{
    /**
     * @throws ValidationException
     */
    public function execute(Enrollment $enrollment): Enrollment
    {
        if ($enrollment->status !== EnrollmentStatus::Enrolled) {
            throw ValidationException::withMessages([
                'status' => __('Solo se puede marcar como pendiente de pago una inscripción con estado Inscrito/a.'),
            ]);
        }

        $enrollment->update([
            'status' => EnrollmentStatus::PendingPayment,
            'paid_at' => null,
            'payment_method' => null,
        ]);

        return $enrollment->fresh();
    }
}
