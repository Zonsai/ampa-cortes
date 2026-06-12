<?php

namespace App\Actions\Consents;

use App\Enums\ConsentEventType;
use App\Enums\ConsentResponseStatus;
use App\Enums\ConsentTypeStatus;
use App\Models\ConsentHistory;
use App\Models\ConsentResponse;
use App\Models\ConsentType;
use App\Models\ConsentVersion;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublishNewConsentVersionAction
{
    /**
     * Creates and publishes a new version of a consent type.
     * Moves all accepted/rejected responses back to pending.
     * Revoked responses are left untouched.
     *
     * @throws ValidationException
     */
    public function execute(
        ConsentType $type,
        string $legalText,
        ?string $summary = null,
        ?Carbon $effectiveFrom = null,
        ?User $createdBy = null,
    ): ConsentVersion {
        if ($type->status !== ConsentTypeStatus::Published) {
            throw ValidationException::withMessages([
                'consent_type' => 'El tipo de consentimiento debe estar publicado para añadir una nueva versión.',
            ]);
        }

        return DB::transaction(function () use ($type, $legalText, $summary, $effectiveFrom, $createdBy) {
            $nextVersionNumber = $type->versions()->max('version_number') + 1;

            $newVersion = ConsentVersion::create([
                'consent_type_id' => $type->id,
                'version_number' => $nextVersionNumber,
                'legal_text' => $legalText,
                'summary' => $summary,
                'effective_from' => $effectiveFrom,
                'published_at' => now(),
                'created_by_id' => $createdBy?->id,
            ]);

            $affectedResponses = ConsentResponse::where('consent_type_id', $type->id)
                ->whereIn('status', [
                    ConsentResponseStatus::Accepted->value,
                    ConsentResponseStatus::Rejected->value,
                ])
                ->get();

            foreach ($affectedResponses as $response) {
                $response->update([
                    'consent_version_id' => $newVersion->id,
                    'status' => ConsentResponseStatus::Pending,
                    'responded_at' => null,
                    'responded_by_id' => null,
                ]);

                ConsentHistory::create([
                    'consent_response_id' => $response->id,
                    'consent_version_id' => $newVersion->id,
                    'consent_type_id' => $type->id,
                    'family_id' => $response->family_id,
                    'student_id' => $response->student_id,
                    'event_type' => ConsentEventType::NewVersionRequired,
                    'performed_by_id' => null,
                    'notes' => "Nueva versión {$newVersion->version_number} publicada",
                ]);
            }

            return $newVersion;
        });
    }
}
