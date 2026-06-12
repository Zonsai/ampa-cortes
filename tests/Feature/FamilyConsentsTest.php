<?php

namespace Tests\Feature;

use App\Enums\ConsentResponseStatus;
use App\Models\ConsentResponse;
use App\Models\ConsentType;
use App\Models\ConsentVersion;
use App\Models\Family;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyConsentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * @return array{user: User, family: Family, student: Student}
     */
    private function createFamilyUser(): array
    {
        $user = User::factory()->create();
        $user->assignRole('familia');

        $family = Family::factory()->create();
        Guardian::factory()->create(['user_id' => $user->id, 'family_id' => $family->id]);
        $student = Student::factory()->create(['family_id' => $family->id, 'is_active' => true]);

        return compact('user', 'family', 'student');
    }

    /**
     * Creates a published ConsentType with one published ConsentVersion.
     *
     * @return array{type: ConsentType, version: ConsentVersion}
     */
    private function createPublishedConsent(array $typeOverrides = []): array
    {
        $type = ConsentType::factory()->published()->create($typeOverrides);
        $version = ConsentVersion::factory()->published()->create(['consent_type_id' => $type->id]);

        return compact('type', 'version');
    }

    private function createPendingResponse(Family $family, ConsentType $type, ConsentVersion $version, ?Student $student = null): ConsentResponse
    {
        return ConsentResponse::create([
            'consent_type_id' => $type->id,
            'consent_version_id' => $version->id,
            'family_id' => $family->id,
            'student_id' => $student?->id,
            'subject_key' => ConsentResponse::buildSubjectKey($family, $student),
            'status' => ConsentResponseStatus::Pending,
        ]);
    }

    // ─── Index (4 tests) ─────────────────────────────────────────────────────

    public function test_index_requires_auth(): void
    {
        $this->get(route('familia.consents.index'))
            ->assertRedirect(route('familia.login'));
    }

    public function test_index_shows_pending_consents(): void
    {
        ['user' => $user, 'family' => $family] = $this->createFamilyUser();
        ['type' => $type, 'version' => $version] = $this->createPublishedConsent(['name' => 'Consentimiento de fotos']);

        $this->createPendingResponse($family, $type, $version);

        $this->actingAs($user)
            ->get(route('familia.consents.index'))
            ->assertOk()
            ->assertSee('Consentimiento de fotos')
            ->assertSee('Pendientes');
    }

    public function test_index_shows_accepted_consents(): void
    {
        ['user' => $user, 'family' => $family] = $this->createFamilyUser();
        ['type' => $type, 'version' => $version] = $this->createPublishedConsent(['name' => 'Autorización salidas']);

        ConsentResponse::create([
            'consent_type_id' => $type->id,
            'consent_version_id' => $version->id,
            'family_id' => $family->id,
            'student_id' => null,
            'subject_key' => "family:{$family->id}",
            'status' => ConsentResponseStatus::Accepted,
            'responded_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('familia.consents.index'))
            ->assertOk()
            ->assertSee('Autorización salidas')
            ->assertSee('Aceptados');
    }

    public function test_index_shows_no_pending_message_when_none(): void
    {
        ['user' => $user] = $this->createFamilyUser();

        $this->actingAs($user)
            ->get(route('familia.consents.index'))
            ->assertOk()
            ->assertSee('No tienes consentimientos pendientes.');
    }

    // ─── Show (6 tests) ──────────────────────────────────────────────────────

    public function test_show_requires_auth(): void
    {
        ['family' => $family] = $this->createFamilyUser();
        ['type' => $type, 'version' => $version] = $this->createPublishedConsent();
        $response = $this->createPendingResponse($family, $type, $version);

        $this->get(route('familia.consents.show', $response))
            ->assertRedirect(route('familia.login'));
    }

    public function test_show_returns_403_for_another_familys_response(): void
    {
        ['user' => $user] = $this->createFamilyUser();

        $otherFamily = Family::factory()->create();
        ['type' => $type, 'version' => $version] = $this->createPublishedConsent();
        $otherResponse = ConsentResponse::create([
            'consent_type_id' => $type->id,
            'consent_version_id' => $version->id,
            'family_id' => $otherFamily->id,
            'student_id' => null,
            'subject_key' => "family:{$otherFamily->id}",
            'status' => ConsentResponseStatus::Pending,
        ]);

        $this->actingAs($user)
            ->get(route('familia.consents.show', $otherResponse))
            ->assertForbidden();
    }

    public function test_show_displays_consent_details(): void
    {
        ['user' => $user, 'family' => $family] = $this->createFamilyUser();
        ['type' => $type, 'version' => $version] = $this->createPublishedConsent([
            'name' => 'Consentimiento LOPD',
            'purpose' => 'Tratamiento de datos personales',
        ]);
        $response = $this->createPendingResponse($family, $type, $version);

        $this->actingAs($user)
            ->get(route('familia.consents.show', $response))
            ->assertOk()
            ->assertSee('Consentimiento LOPD')
            ->assertSee('Tratamiento de datos personales');
    }

    public function test_show_hides_reject_button_when_not_rejectable(): void
    {
        ['user' => $user, 'family' => $family] = $this->createFamilyUser();
        ['type' => $type, 'version' => $version] = $this->createPublishedConsent(['is_rejectable' => false]);
        $response = $this->createPendingResponse($family, $type, $version);

        $this->actingAs($user)
            ->get(route('familia.consents.show', $response))
            ->assertOk()
            ->assertSee('Aceptar')
            ->assertDontSee('Rechazar');
    }

    public function test_show_displays_revoke_button_for_accepted_revocable(): void
    {
        ['user' => $user, 'family' => $family] = $this->createFamilyUser();
        ['type' => $type, 'version' => $version] = $this->createPublishedConsent(['is_revocable' => true]);
        $response = ConsentResponse::create([
            'consent_type_id' => $type->id,
            'consent_version_id' => $version->id,
            'family_id' => $family->id,
            'student_id' => null,
            'subject_key' => "family:{$family->id}",
            'status' => ConsentResponseStatus::Accepted,
            'responded_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('familia.consents.show', $response))
            ->assertOk()
            ->assertSee('Revocar');
    }

    public function test_show_displays_revoked_notice(): void
    {
        ['user' => $user, 'family' => $family] = $this->createFamilyUser();
        ['type' => $type, 'version' => $version] = $this->createPublishedConsent();
        $response = ConsentResponse::create([
            'consent_type_id' => $type->id,
            'consent_version_id' => $version->id,
            'family_id' => $family->id,
            'student_id' => null,
            'subject_key' => "family:{$family->id}",
            'status' => ConsentResponseStatus::Revoked,
            'responded_at' => now(),
            'revoked_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('familia.consents.show', $response))
            ->assertOk()
            ->assertSee('Has revocado este consentimiento');
    }

    // ─── Accept (3 tests) ────────────────────────────────────────────────────

    public function test_accept_requires_auth(): void
    {
        ['family' => $family] = $this->createFamilyUser();
        ['type' => $type, 'version' => $version] = $this->createPublishedConsent();
        $response = $this->createPendingResponse($family, $type, $version);

        $this->post(route('familia.consents.accept', $response))
            ->assertRedirect(route('familia.login'));
    }

    public function test_accept_returns_403_for_another_familys_response(): void
    {
        ['user' => $user] = $this->createFamilyUser();

        $otherFamily = Family::factory()->create();
        ['type' => $type, 'version' => $version] = $this->createPublishedConsent();
        $otherResponse = ConsentResponse::create([
            'consent_type_id' => $type->id,
            'consent_version_id' => $version->id,
            'family_id' => $otherFamily->id,
            'student_id' => null,
            'subject_key' => "family:{$otherFamily->id}",
            'status' => ConsentResponseStatus::Pending,
        ]);

        $this->actingAs($user)
            ->post(route('familia.consents.accept', $otherResponse))
            ->assertForbidden();
    }

    public function test_accept_succeeds_and_redirects_with_success_message(): void
    {
        ['user' => $user, 'family' => $family] = $this->createFamilyUser();
        ['type' => $type, 'version' => $version] = $this->createPublishedConsent();
        $response = $this->createPendingResponse($family, $type, $version);

        $this->actingAs($user)
            ->post(route('familia.consents.accept', $response))
            ->assertRedirect(route('familia.consents.index'))
            ->assertSessionHas('success', 'Consentimiento aceptado correctamente.');

        $this->assertDatabaseHas('consent_responses', [
            'id' => $response->id,
            'status' => ConsentResponseStatus::Accepted->value,
        ]);
    }

    // ─── Reject (2 tests) ────────────────────────────────────────────────────

    public function test_reject_succeeds_and_redirects_with_success_message(): void
    {
        ['user' => $user, 'family' => $family] = $this->createFamilyUser();
        ['type' => $type, 'version' => $version] = $this->createPublishedConsent(['is_rejectable' => true]);
        $response = $this->createPendingResponse($family, $type, $version);

        $this->actingAs($user)
            ->post(route('familia.consents.reject', $response))
            ->assertRedirect(route('familia.consents.index'))
            ->assertSessionHas('success', 'Consentimiento rechazado.');

        $this->assertDatabaseHas('consent_responses', [
            'id' => $response->id,
            'status' => ConsentResponseStatus::Rejected->value,
        ]);
    }

    public function test_reject_fails_when_not_rejectable(): void
    {
        ['user' => $user, 'family' => $family] = $this->createFamilyUser();
        ['type' => $type, 'version' => $version] = $this->createPublishedConsent(['is_rejectable' => false]);
        $response = $this->createPendingResponse($family, $type, $version);

        $this->actingAs($user)
            ->post(route('familia.consents.reject', $response))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('consent_responses', [
            'id' => $response->id,
            'status' => ConsentResponseStatus::Pending->value,
        ]);
    }

    // ─── Revoke (3 tests) ────────────────────────────────────────────────────

    public function test_revoke_succeeds_and_redirects_with_success_message(): void
    {
        ['user' => $user, 'family' => $family] = $this->createFamilyUser();
        ['type' => $type, 'version' => $version] = $this->createPublishedConsent(['is_revocable' => true]);
        $response = ConsentResponse::create([
            'consent_type_id' => $type->id,
            'consent_version_id' => $version->id,
            'family_id' => $family->id,
            'student_id' => null,
            'subject_key' => "family:{$family->id}",
            'status' => ConsentResponseStatus::Accepted,
            'responded_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('familia.consents.revoke', $response))
            ->assertRedirect(route('familia.consents.index'))
            ->assertSessionHas('success', 'Consentimiento revocado. A partir de ahora no se considera autorizado.');

        $this->assertDatabaseHas('consent_responses', [
            'id' => $response->id,
            'status' => ConsentResponseStatus::Revoked->value,
        ]);
    }

    public function test_revoke_fails_when_not_revocable(): void
    {
        ['user' => $user, 'family' => $family] = $this->createFamilyUser();
        ['type' => $type, 'version' => $version] = $this->createPublishedConsent(['is_revocable' => false]);
        $response = ConsentResponse::create([
            'consent_type_id' => $type->id,
            'consent_version_id' => $version->id,
            'family_id' => $family->id,
            'student_id' => null,
            'subject_key' => "family:{$family->id}",
            'status' => ConsentResponseStatus::Accepted,
            'responded_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('familia.consents.revoke', $response))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('consent_responses', [
            'id' => $response->id,
            'status' => ConsentResponseStatus::Accepted->value,
        ]);
    }

    public function test_revoke_returns_403_for_another_familys_response(): void
    {
        ['user' => $user] = $this->createFamilyUser();

        $otherFamily = Family::factory()->create();
        ['type' => $type, 'version' => $version] = $this->createPublishedConsent(['is_revocable' => true]);
        $otherResponse = ConsentResponse::create([
            'consent_type_id' => $type->id,
            'consent_version_id' => $version->id,
            'family_id' => $otherFamily->id,
            'student_id' => null,
            'subject_key' => "family:{$otherFamily->id}",
            'status' => ConsentResponseStatus::Accepted,
            'responded_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('familia.consents.revoke', $otherResponse))
            ->assertForbidden();
    }

    public function test_revoke_fails_when_response_is_not_accepted(): void
    {
        ['user' => $user, 'family' => $family] = $this->createFamilyUser();
        ['type' => $type, 'version' => $version] = $this->createPublishedConsent(['is_revocable' => true]);
        $response = $this->createPendingResponse($family, $type, $version);

        $this->actingAs($user)
            ->post(route('familia.consents.revoke', $response))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('consent_responses', [
            'id' => $response->id,
            'status' => ConsentResponseStatus::Pending->value,
        ]);

        $this->assertDatabaseMissing('consent_histories', [
            'consent_response_id' => $response->id,
            'event_type' => 'revoked',
        ]);
    }

    public function test_accept_fails_when_response_student_belongs_to_another_family(): void
    {
        ['user' => $user, 'family' => $family] = $this->createFamilyUser();

        $otherFamily = Family::factory()->create();
        $alienStudent = Student::factory()->create(['family_id' => $otherFamily->id, 'is_active' => true]);

        ['type' => $type, 'version' => $version] = $this->createPublishedConsent();

        // Inconsistent record: family_id belongs to authenticated family but student_id belongs to another family.
        $response = ConsentResponse::create([
            'consent_type_id' => $type->id,
            'consent_version_id' => $version->id,
            'family_id' => $family->id,
            'student_id' => $alienStudent->id,
            'subject_key' => "student:{$alienStudent->id}",
            'status' => ConsentResponseStatus::Pending,
        ]);

        $this->actingAs($user)
            ->post(route('familia.consents.accept', $response))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('consent_responses', [
            'id' => $response->id,
            'status' => ConsentResponseStatus::Pending->value,
        ]);

        $this->assertDatabaseMissing('consent_histories', [
            'consent_response_id' => $response->id,
            'event_type' => 'accepted',
        ]);
    }
}
