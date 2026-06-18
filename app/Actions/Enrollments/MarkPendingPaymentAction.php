<?php

namespace App\Actions\Enrollments;

use App\Enums\EnrollmentStatus;
use App\Models\AuditLog;
use App\Models\Enrollment;
use App\Services\AuditLogger;
use App\Support\EnrollmentAuditData;
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

        $statusBefore = $enrollment->status;

        $enrollment->update([
            'status' => EnrollmentStatus::PendingPayment,
            'paid_at' => null,
            'payment_method' => null,
        ]);

        app(AuditLogger::class)->log(
            AuditLog::PAYMENT_MARKED_PENDING,
            $enrollment,
            'Marcado como pendiente de pago',
            EnrollmentAuditData::properties($enrollment, $statusBefore),
            subjectLabel: EnrollmentAuditData::label($enrollment),
        );

        return $enrollment->fresh();
    }
}
