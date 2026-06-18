<?php

namespace App\Support;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;

/**
 * Builds consistent audit-log payloads (label + before/after properties) for
 * enrollment and payment actions.
 */
class EnrollmentAuditData
{
    public static function label(Enrollment $enrollment): string
    {
        $enrollment->loadMissing(['student', 'activity']);

        return ($enrollment->student?->full_name ?? 'Alumno/a').' — '.($enrollment->activity?->name ?? 'Actividad');
    }

    /**
     * @return array<string, mixed>
     */
    public static function properties(Enrollment $enrollment, ?EnrollmentStatus $statusBefore): array
    {
        $enrollment->loadMissing(['student', 'family', 'activity', 'activityGroup']);

        return [
            'student' => $enrollment->student?->full_name,
            'student_id' => $enrollment->student_id,
            'family' => $enrollment->family?->name,
            'family_id' => $enrollment->family_id,
            'activity' => $enrollment->activity?->name,
            'group' => $enrollment->activityGroup?->name,
            'status_before' => $statusBefore?->value,
            'status_after' => $enrollment->status?->value,
            'amount' => $enrollment->amount,
            'payment_method' => $enrollment->payment_method?->value,
            'paid_at' => $enrollment->paid_at?->toDateTimeString(),
        ];
    }
}
