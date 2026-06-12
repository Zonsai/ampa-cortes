<?php

namespace App\Services;

use App\Enums\ConsentResponseStatus;
use App\Models\ConsentResponse;
use App\Models\Family;
use App\Models\Student;
use Illuminate\Support\Collection;

class ConsentStatusService
{
    /**
     * Returns all consent responses for a family (any status, any scope).
     *
     * @return Collection<int, ConsentResponse>
     */
    public function getResponsesForFamily(Family $family): Collection
    {
        return ConsentResponse::where('family_id', $family->id)
            ->with(['consentType', 'consentVersion'])
            ->get();
    }

    /**
     * Returns only pending responses for a family.
     *
     * @return Collection<int, ConsentResponse>
     */
    public function getPendingForFamily(Family $family): Collection
    {
        return ConsentResponse::where('family_id', $family->id)
            ->where('status', ConsentResponseStatus::Pending)
            ->with(['consentType', 'consentVersion'])
            ->get();
    }

    public function hasPendingConsents(Family $family): bool
    {
        return ConsentResponse::where('family_id', $family->id)
            ->where('status', ConsentResponseStatus::Pending)
            ->exists();
    }

    /**
     * Returns all consent responses for a specific student (per_student consents).
     *
     * @return Collection<int, ConsentResponse>
     */
    public function getResponsesForStudent(Student $student): Collection
    {
        return ConsentResponse::where('student_id', $student->id)
            ->with(['consentType', 'consentVersion'])
            ->get();
    }
}
