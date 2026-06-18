<?php

namespace App\Actions\Enrollments;

use App\Enums\EnrollmentStatus;
use App\Models\AuditLog;
use App\Models\Enrollment;
use App\Services\AuditLogger;
use App\Support\EnrollmentAuditData;
use Illuminate\Validation\ValidationException;

class VoidPaymentAction
{
    /**
     * @throws ValidationException
     */
    public function execute(Enrollment $enrollment): Enrollment
    {
        if ($enrollment->status !== EnrollmentStatus::Paid) {
            throw ValidationException::withMessages([
                'status' => __('Solo se puede anular el pago de una inscripción con estado Pagado.'),
            ]);
        }

        $voidNote = 'Pago anulado el '.now()->format('d/m/Y H:i');
        $internalNotes = $enrollment->internal_notes
            ? $enrollment->internal_notes."\n".$voidNote
            : $voidNote;

        $statusBefore = $enrollment->status;

        $enrollment->update([
            'status' => EnrollmentStatus::PendingPayment,
            'paid_at' => null,
            'payment_method' => null,
            'internal_notes' => $internalNotes,
        ]);

        app(AuditLogger::class)->log(
            AuditLog::PAYMENT_VOIDED,
            $enrollment,
            'Pago anulado',
            EnrollmentAuditData::properties($enrollment, $statusBefore),
            subjectLabel: EnrollmentAuditData::label($enrollment),
        );

        return $enrollment->fresh();
    }
}
