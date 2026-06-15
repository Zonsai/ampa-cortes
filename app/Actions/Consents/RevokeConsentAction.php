<?php

namespace App\Actions\Consents;

use App\Enums\ConsentEventType;
use App\Enums\ConsentResponseStatus;
use App\Models\ConsentHistory;
use App\Models\ConsentResponse;
use App\Models\Family;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RevokeConsentAction
{
    /** Note written to history when the revoked consent type requires image review. */
    public const IMAGE_REVIEW_NOTE = 'Revocación que requiere revisión de uso de imagen/materiales.';

    /**
     * Revokes a previously accepted consent response.
     * History is preserved — this only changes the current status.
     *
     * @throws ValidationException
     */
    public function execute(
        ConsentResponse $response,
        Family $family,
        ?User $performedBy = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): ConsentResponse {
        $this->validate($response, $family);

        return DB::transaction(function () use ($response, $performedBy, $ipAddress, $userAgent) {
            $response->update([
                'status' => ConsentResponseStatus::Revoked,
                'revoked_at' => now(),
            ]);

            $response->loadMissing('consentType');
            $notes = $response->consentType->requires_image_review
                ? self::IMAGE_REVIEW_NOTE
                : null;

            ConsentHistory::create([
                'consent_response_id' => $response->id,
                'consent_version_id' => $response->consent_version_id,
                'consent_type_id' => $response->consent_type_id,
                'family_id' => $response->family_id,
                'student_id' => $response->student_id,
                'event_type' => ConsentEventType::Revoked,
                'performed_by_id' => $performedBy?->id,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'notes' => $notes,
            ]);

            return $response->fresh();
        });
    }

    /**
     * @throws ValidationException
     */
    private function validate(ConsentResponse $response, Family $family): void
    {
        if ($response->family_id !== $family->id) {
            throw ValidationException::withMessages([
                'consent' => 'Este consentimiento no pertenece a tu familia.',
            ]);
        }

        $response->loadMissing('consentType');

        if (! $response->consentType->is_revocable) {
            throw ValidationException::withMessages([
                'consent' => 'Este consentimiento no puede ser revocado.',
            ]);
        }

        if ($response->status !== ConsentResponseStatus::Accepted) {
            throw ValidationException::withMessages([
                'consent' => 'Solo puedes revocar consentimientos aceptados.',
            ]);
        }
    }
}
