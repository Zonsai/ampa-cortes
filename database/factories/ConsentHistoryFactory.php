<?php

namespace Database\Factories;

use App\Enums\ConsentEventType;
use App\Models\ConsentHistory;
use App\Models\ConsentResponse;
use App\Models\ConsentType;
use App\Models\ConsentVersion;
use App\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConsentHistory>
 */
class ConsentHistoryFactory extends Factory
{
    public function definition(): array
    {
        $family = Family::factory()->create();
        $type = ConsentType::factory()->create();
        $version = ConsentVersion::factory()->for($type)->create(['version_number' => 1]);
        $response = ConsentResponse::factory()->create([
            'consent_type_id' => $type->id,
            'consent_version_id' => $version->id,
            'family_id' => $family->id,
            'subject_key' => "family:{$family->id}",
        ]);

        return [
            'consent_response_id' => $response->id,
            'consent_version_id' => $version->id,
            'consent_type_id' => $type->id,
            'family_id' => $family->id,
            'student_id' => null,
            'event_type' => ConsentEventType::PendingCreated,
            'performed_by_id' => null,
            'ip_address' => null,
            'user_agent' => null,
            'notes' => null,
        ];
    }
}
