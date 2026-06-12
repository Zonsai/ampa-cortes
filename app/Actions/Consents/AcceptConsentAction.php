<?php

namespace App\Actions\Consents;

use App\Enums\ConsentEventType;
use App\Enums\ConsentResponseStatus;
use App\Enums\ConsentTypeStatus;
use App\Models\ConsentHistory;
use App\Models\ConsentResponse;
use App\Models\Family;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcceptConsentAction
{
    /**
     * Accepts a consent response on behalf of a family.
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
                'status' => ConsentResponseStatus::Accepted,
                'responded_at' => now(),
                'responded_by_id' => $performedBy?->id,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);

            ConsentHistory::create([
                'consent_response_id' => $response->id,
                'consent_version_id' => $response->consent_version_id,
                'consent_type_id' => $response->consent_type_id,
                'family_id' => $response->family_id,
                'student_id' => $response->student_id,
                'event_type' => ConsentEventType::Accepted,
                'performed_by_id' => $performedBy?->id,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
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

        if ($response->student_id !== null && $response->student?->family_id !== $family->id) {
            throw ValidationException::withMessages([
                'consent' => 'El alumno/a no pertenece a tu familia.',
            ]);
        }

        $response->loadMissing('consentType');

        if ($response->consentType->status !== ConsentTypeStatus::Published) {
            throw ValidationException::withMessages([
                'consent' => 'Este consentimiento no está disponible.',
            ]);
        }

        if ($response->status !== ConsentResponseStatus::Pending) {
            throw ValidationException::withMessages([
                'consent' => 'Solo puedes aceptar consentimientos pendientes.',
            ]);
        }

        $currentVersion = $response->consentType->currentPublishedVersion;

        if ($currentVersion === null || $response->consent_version_id !== $currentVersion->id) {
            throw ValidationException::withMessages([
                'consent' => 'Esta versión del consentimiento ya no está vigente. Por favor, recarga la página.',
            ]);
        }
    }
}
