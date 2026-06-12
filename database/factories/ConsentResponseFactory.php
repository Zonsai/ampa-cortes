<?php

namespace Database\Factories;

use App\Enums\ConsentResponseStatus;
use App\Models\ConsentResponse;
use App\Models\ConsentType;
use App\Models\ConsentVersion;
use App\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConsentResponse>
 */
class ConsentResponseFactory extends Factory
{
    public function definition(): array
    {
        $family = Family::factory()->create();

        return [
            'consent_type_id' => ConsentType::factory(),
            'consent_version_id' => ConsentVersion::factory(),
            'family_id' => $family->id,
            'student_id' => null,
            'subject_key' => "family:{$family->id}",
            'status' => ConsentResponseStatus::Pending,
            'responded_at' => null,
            'responded_by_id' => null,
            'ip_address' => null,
            'user_agent' => null,
            'revoked_at' => null,
        ];
    }

    public function accepted(): static
    {
        return $this->state([
            'status' => ConsentResponseStatus::Accepted,
            'responded_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state([
            'status' => ConsentResponseStatus::Rejected,
            'responded_at' => now(),
        ]);
    }

    public function revoked(): static
    {
        return $this->state([
            'status' => ConsentResponseStatus::Revoked,
            'responded_at' => now(),
            'revoked_at' => now(),
        ]);
    }
}
