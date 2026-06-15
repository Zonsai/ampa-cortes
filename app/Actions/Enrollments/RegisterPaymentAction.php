<?php

namespace App\Actions\Enrollments;

use App\Enums\EnrollmentStatus;
use App\Enums\PaymentMethod;
use App\Models\Enrollment;
use Illuminate\Validation\ValidationException;

class RegisterPaymentAction
{
    /**
     * @throws ValidationException
     */
    public function execute(
        Enrollment $enrollment,
        \DateTimeInterface|string $paidAt,
        PaymentMethod $paymentMethod,
        ?string $notesAddition = null,
    ): Enrollment {
        $payableStatuses = [
            EnrollmentStatus::Enrolled,
            EnrollmentStatus::PendingPayment,
        ];

        if (! in_array($enrollment->status, $payableStatuses)) {
            throw ValidationException::withMessages([
                'status' => __('No se puede registrar pago para una inscripción en estado actual.'),
            ]);
        }

        $internalNotes = $enrollment->internal_notes;

        if ($notesAddition !== null && $notesAddition !== '') {
            $internalNotes = $internalNotes
                ? $internalNotes."\n".$notesAddition
                : $notesAddition;
        }

        $enrollment->update([
            'status' => EnrollmentStatus::Paid,
            'paid_at' => $paidAt,
            'payment_method' => $paymentMethod,
            'internal_notes' => $internalNotes,
        ]);

        return $enrollment->fresh();
    }
}
