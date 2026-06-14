<?php

namespace Tests\Feature;

use App\Actions\Consents\AcceptConsentAction;
use App\Actions\Consents\PublishConsentTypeAction;
use App\Actions\Consents\PublishNewConsentVersionAction;
use App\Actions\Consents\RejectConsentAction;
use App\Actions\Consents\RevokeConsentAction;
use App\Enums\ConsentEventType;
use App\Enums\ConsentResponseStatus;
use App\Enums\ConsentScope;
use App\Models\ConsentResponse;
use App\Models\ConsentType;
use App\Models\ConsentVersion;
use App\Models\Family;
use App\Models\Student;
use App\Models\User;
use App\Services\ConsentStatusService;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ConsentTest extends TestCase
{
    use RefreshDatabase;

    private Family $family;

    private Family $otherFamily;

    private Student $student;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->family = Family::factory()->create();
        $this->otherFamily = Family::factory()->create();
        $this->student = Student::factory()->create([
            'family_id' => $this->family->id,
            'is_active' => true,
        ]);
        $this->user = User::factory()->create();
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function createPublishedTypeWithVersion(array $typeOverrides = []): array
    {
        $type = ConsentType::factory()->create($typeOverrides);
        $version = ConsentVersion::factory()->create([
            'consent_type_id' => $type->id,
            'version_number' => 1,
        ]);

        return [$type, $version];
    }

    // ─── PublishConsentTypeAction ─────────────────────────────────────────────

    public function test_publish_consent_type_creates_pending_responses_for_all_families(): void
    {
        [$type, $version] = $this->createPublishedTypeWithVersion(['scope' => ConsentScope::PerFamily]);

        app(PublishConsentTypeAction::class)->execute($type, $version);

        // Both $this->family and $this->otherFamily should have pending responses
        $this->assertDatabaseCount('consent_responses', 2);
        $this->assertDatabaseHas('consent_responses', [
            'consent_type_id' => $type->id,
            'family_id' => $this->family->id,
            'status' => ConsentResponseStatus::Pending->value,
        ]);
        $this->assertDatabaseHas('consent_responses', [
            'consent_type_id' => $type->id,
            'family_id' => $this->otherFamily->id,
            'status' => ConsentResponseStatus::Pending->value,
        ]);
    }

    public function test_publish_consent_type_creates_pending_responses_for_active_students(): void
    {
        $inactiveStudent = Student::factory()->create([
            'family_id' => $this->otherFamily->id,
            'is_active' => false,
        ]);

        [$type, $version] = $this->createPublishedTypeWithVersion(['scope' => ConsentScope::PerStudent]);

        app(PublishConsentTypeAction::class)->execute($type, $version);

        // Only $this->student (active) should have a response
        $this->assertDatabaseCount('consent_responses', 1);
        $this->assertDatabaseHas('consent_responses', [
            'consent_type_id' => $type->id,
            'student_id' => $this->student->id,
            'status' => ConsentResponseStatus::Pending->value,
        ]);
        $this->assertDatabaseMissing('consent_responses', [
            'student_id' => $inactiveStudent->id,
        ]);
    }

    public function test_publish_does_not_create_responses_for_inactive_students(): void
    {
        Student::factory()->count(3)->create([
            'family_id' => $this->otherFamily->id,
            'is_active' => false,
        ]);

        [$type, $version] = $this->createPublishedTypeWithVersion(['scope' => ConsentScope::PerStudent]);

        app(PublishConsentTypeAction::class)->execute($type, $version);

        $this->assertDatabaseCount('consent_responses', 1); // only $this->student
    }

    public function test_subject_key_per_family_uses_family_prefix(): void
    {
        [$type, $version] = $this->createPublishedTypeWithVersion(['scope' => ConsentScope::PerFamily]);

        app(PublishConsentTypeAction::class)->execute($type, $version);

        $this->assertDatabaseHas('consent_responses', [
            'family_id' => $this->family->id,
            'subject_key' => "family:{$this->family->id}",
        ]);
    }

    public function test_subject_key_per_student_uses_student_prefix(): void
    {
        [$type, $version] = $this->createPublishedTypeWithVersion(['scope' => ConsentScope::PerStudent]);

        app(PublishConsentTypeAction::class)->execute($type, $version);

        $this->assertDatabaseHas('consent_responses', [
            'student_id' => $this->student->id,
            'subject_key' => "student:{$this->student->id}",
        ]);
    }

    public function test_unique_constraint_prevents_duplicate_consent_responses(): void
    {
        [$type, $version] = $this->createPublishedTypeWithVersion(['scope' => ConsentScope::PerFamily]);

        ConsentResponse::create([
            'consent_type_id' => $type->id,
            'consent_version_id' => $version->id,
            'family_id' => $this->family->id,
            'student_id' => null,
            'subject_key' => "family:{$this->family->id}",
            'status' => ConsentResponseStatus::Pending,
        ]);

        $this->expectException(QueryException::class);

        ConsentResponse::create([
            'consent_type_id' => $type->id,
            'consent_version_id' => $version->id,
            'family_id' => $this->family->id,
            'student_id' => null,
            'subject_key' => "family:{$this->family->id}",
            'status' => ConsentResponseStatus::Pending,
        ]);
    }

    // ─── AcceptConsentAction ──────────────────────────────────────────────────

    public function test_accept_consent_changes_status_to_accepted(): void
    {
        [$type, $version] = $this->createPublishedTypeWithVersion();

        app(PublishConsentTypeAction::class)->execute($type, $version);

        $response = ConsentResponse::where('family_id', $this->family->id)->first();

        app(AcceptConsentAction::class)->execute($response, $this->family);

        $this->assertEquals(ConsentResponseStatus::Accepted, $response->fresh()->status);
    }

    public function test_accept_records_responded_by_user(): void
    {
        [$type, $version] = $this->createPublishedTypeWithVersion();

        app(PublishConsentTypeAction::class)->execute($type, $version);

        $response = ConsentResponse::where('family_id', $this->family->id)->first();

        app(AcceptConsentAction::class)->execute($response, $this->family, performedBy: $this->user);

        $this->assertEquals($this->user->id, $response->fresh()->responded_by_id);
    }

    public function test_accept_records_ip_and_user_agent(): void
    {
        [$type, $version] = $this->createPublishedTypeWithVersion();

        app(PublishConsentTypeAction::class)->execute($type, $version);

        $response = ConsentResponse::where('family_id', $this->family->id)->first();

        app(AcceptConsentAction::class)->execute(
            response: $response,
            family: $this->family,
            ipAddress: '192.168.1.100',
            userAgent: 'Mozilla/5.0 Test',
        );

        $fresh = $response->fresh();
        $this->assertEquals('192.168.1.100', $fresh->ip_address);
        $this->assertEquals('Mozilla/5.0 Test', $fresh->user_agent);
    }

    public function test_family_cannot_accept_another_familys_consent(): void
    {
        [$type, $version] = $this->createPublishedTypeWithVersion();

        app(PublishConsentTypeAction::class)->execute($type, $version);

        $otherResponse = ConsentResponse::where('family_id', $this->otherFamily->id)->first();

        $this->expectException(ValidationException::class);

        app(AcceptConsentAction::class)->execute($otherResponse, $this->family);
    }

    public function test_family_cannot_accept_consent_for_student_of_another_family(): void
    {
        $otherStudent = Student::factory()->create([
            'family_id' => $this->otherFamily->id,
            'is_active' => true,
        ]);

        [$type, $version] = $this->createPublishedTypeWithVersion(['scope' => ConsentScope::PerStudent]);

        app(PublishConsentTypeAction::class)->execute($type, $version);

        $otherStudentResponse = ConsentResponse::where('student_id', $otherStudent->id)->first();

        // Tamper: force family_id to match but student still belongs to otherFamily
        $otherStudentResponse->update(['family_id' => $this->family->id]);

        $this->expectException(ValidationException::class);

        app(AcceptConsentAction::class)->execute($otherStudentResponse, $this->family);
    }

    // ─── RejectConsentAction ─────────────────────────────────────────────────

    public function test_reject_consent_works_when_rejectable(): void
    {
        [$type, $version] = $this->createPublishedTypeWithVersion(['is_rejectable' => true]);

        app(PublishConsentTypeAction::class)->execute($type, $version);

        $response = ConsentResponse::where('family_id', $this->family->id)->first();

        app(RejectConsentAction::class)->execute($response, $this->family);

        $this->assertEquals(ConsentResponseStatus::Rejected, $response->fresh()->status);
    }

    public function test_reject_consent_fails_when_not_rejectable(): void
    {
        [$type, $version] = $this->createPublishedTypeWithVersion(['is_rejectable' => false]);

        app(PublishConsentTypeAction::class)->execute($type, $version);

        $response = ConsentResponse::where('family_id', $this->family->id)->first();

        $this->expectException(ValidationException::class);

        app(RejectConsentAction::class)->execute($response, $this->family);
    }

    // ─── RevokeConsentAction ─────────────────────────────────────────────────

    public function test_revoke_accepted_consent_works_when_revocable(): void
    {
        [$type, $version] = $this->createPublishedTypeWithVersion(['is_revocable' => true]);

        app(PublishConsentTypeAction::class)->execute($type, $version);

        $response = ConsentResponse::where('family_id', $this->family->id)->first();
        app(AcceptConsentAction::class)->execute($response, $this->family);

        app(RevokeConsentAction::class)->execute($response->fresh(), $this->family);

        $this->assertEquals(ConsentResponseStatus::Revoked, $response->fresh()->status);
        $this->assertNotNull($response->fresh()->revoked_at);
    }

    public function test_revoke_fails_when_not_revocable(): void
    {
        [$type, $version] = $this->createPublishedTypeWithVersion(['is_revocable' => false]);

        app(PublishConsentTypeAction::class)->execute($type, $version);

        $response = ConsentResponse::where('family_id', $this->family->id)->first();

        // Manually set to accepted to reach the is_revocable check
        $response->update(['status' => ConsentResponseStatus::Accepted]);

        $this->expectException(ValidationException::class);

        app(RevokeConsentAction::class)->execute($response, $this->family);
    }

    public function test_revoke_fails_when_response_is_not_accepted(): void
    {
        [$type, $version] = $this->createPublishedTypeWithVersion();

        app(PublishConsentTypeAction::class)->execute($type, $version);

        $response = ConsentResponse::where('family_id', $this->family->id)->first();
        // response is still pending

        $this->expectException(ValidationException::class);

        app(RevokeConsentAction::class)->execute($response, $this->family);
    }

    public function test_revocation_does_not_delete_history(): void
    {
        [$type, $version] = $this->createPublishedTypeWithVersion();

        app(PublishConsentTypeAction::class)->execute($type, $version);

        $response = ConsentResponse::where('family_id', $this->family->id)->first();
        app(AcceptConsentAction::class)->execute($response, $this->family);
        app(RevokeConsentAction::class)->execute($response->fresh(), $this->family);

        // History must have: pending_created + accepted + revoked
        $this->assertEquals(3, $response->histories()->count());
        $this->assertDatabaseHas('consent_histories', [
            'consent_response_id' => $response->id,
            'event_type' => ConsentEventType::Accepted->value,
        ]);
        $this->assertDatabaseHas('consent_histories', [
            'consent_response_id' => $response->id,
            'event_type' => ConsentEventType::Revoked->value,
        ]);
    }

    public function test_history_records_events_in_order(): void
    {
        [$type, $version] = $this->createPublishedTypeWithVersion();

        app(PublishConsentTypeAction::class)->execute($type, $version);

        $response = ConsentResponse::where('family_id', $this->family->id)->first();
        app(AcceptConsentAction::class)->execute($response, $this->family);
        app(RevokeConsentAction::class)->execute($response->fresh(), $this->family);

        $events = $response->histories()->pluck('event_type');

        $this->assertEquals(
            [ConsentEventType::PendingCreated, ConsentEventType::Accepted, ConsentEventType::Revoked],
            $events->all(),
        );
    }

    // ─── PublishNewConsentVersionAction ───────────────────────────────────────

    public function test_new_version_moves_accepted_response_to_pending(): void
    {
        [$type, $version] = $this->createPublishedTypeWithVersion();

        app(PublishConsentTypeAction::class)->execute($type, $version);

        $response = ConsentResponse::where('family_id', $this->family->id)->first();
        app(AcceptConsentAction::class)->execute($response, $this->family);

        app(PublishNewConsentVersionAction::class)->execute($type, 'Nuevo texto legal v2');

        $this->assertEquals(ConsentResponseStatus::Pending, $response->fresh()->status);
    }

    public function test_new_version_moves_rejected_response_to_pending(): void
    {
        [$type, $version] = $this->createPublishedTypeWithVersion(['is_rejectable' => true]);

        app(PublishConsentTypeAction::class)->execute($type, $version);

        $response = ConsentResponse::where('family_id', $this->family->id)->first();
        app(RejectConsentAction::class)->execute($response, $this->family);

        app(PublishNewConsentVersionAction::class)->execute($type, 'Nuevo texto legal v2');

        $this->assertEquals(ConsentResponseStatus::Pending, $response->fresh()->status);
    }

    public function test_new_version_leaves_revoked_response_unchanged(): void
    {
        [$type, $version] = $this->createPublishedTypeWithVersion();

        app(PublishConsentTypeAction::class)->execute($type, $version);

        $response = ConsentResponse::where('family_id', $this->family->id)->first();
        app(AcceptConsentAction::class)->execute($response, $this->family);
        app(RevokeConsentAction::class)->execute($response->fresh(), $this->family);

        app(PublishNewConsentVersionAction::class)->execute($type, 'Nuevo texto legal v2');

        $this->assertEquals(ConsentResponseStatus::Revoked, $response->fresh()->status);
    }

    public function test_publish_new_version_updates_pending_responses_to_new_version(): void
    {
        [$type, $version1] = $this->createPublishedTypeWithVersion();

        app(PublishConsentTypeAction::class)->execute($type, $version1);

        $response = ConsentResponse::where('family_id', $this->family->id)->first();
        // response is pending — do NOT accept or reject it

        $version2 = app(PublishNewConsentVersionAction::class)->execute($type, 'Nuevo texto legal v2');

        $fresh = $response->fresh();
        $this->assertSame(ConsentResponseStatus::Pending, $fresh->status);
        $this->assertSame($version2->id, $fresh->consent_version_id);

        $this->assertDatabaseHas('consent_histories', [
            'consent_response_id' => $response->id,
            'consent_version_id' => $version2->id,
            'event_type' => ConsentEventType::NewVersionRequired->value,
        ]);
    }

    public function test_publish_new_version_sets_accepted_responses_to_pending_new_version(): void
    {
        [$type, $version1] = $this->createPublishedTypeWithVersion();

        app(PublishConsentTypeAction::class)->execute($type, $version1);

        $response = ConsentResponse::where('family_id', $this->family->id)->first();
        app(AcceptConsentAction::class)->execute($response, $this->family);

        $version2 = app(PublishNewConsentVersionAction::class)->execute($type, 'Nuevo texto legal v2');

        $fresh = $response->fresh();
        $this->assertSame(ConsentResponseStatus::Pending, $fresh->status);
        $this->assertSame($version2->id, $fresh->consent_version_id);
        $this->assertNull($fresh->responded_at);
    }

    public function test_publish_new_version_sets_rejected_responses_to_pending_new_version(): void
    {
        [$type, $version1] = $this->createPublishedTypeWithVersion(['is_rejectable' => true]);

        app(PublishConsentTypeAction::class)->execute($type, $version1);

        $response = ConsentResponse::where('family_id', $this->family->id)->first();
        app(RejectConsentAction::class)->execute($response, $this->family);

        $version2 = app(PublishNewConsentVersionAction::class)->execute($type, 'Nuevo texto legal v2');

        $fresh = $response->fresh();
        $this->assertSame(ConsentResponseStatus::Pending, $fresh->status);
        $this->assertSame($version2->id, $fresh->consent_version_id);
        $this->assertNull($fresh->responded_at);
    }

    public function test_publish_new_version_does_not_update_revoked_responses(): void
    {
        [$type, $version1] = $this->createPublishedTypeWithVersion();

        app(PublishConsentTypeAction::class)->execute($type, $version1);

        $response = ConsentResponse::where('family_id', $this->family->id)->first();
        app(AcceptConsentAction::class)->execute($response, $this->family);
        app(RevokeConsentAction::class)->execute($response->fresh(), $this->family);

        app(PublishNewConsentVersionAction::class)->execute($type, 'Nuevo texto legal v2');

        $fresh = $response->fresh();
        $this->assertSame(ConsentResponseStatus::Revoked, $fresh->status);
        $this->assertSame($version1->id, $fresh->consent_version_id);

        $this->assertDatabaseMissing('consent_histories', [
            'consent_response_id' => $response->id,
            'event_type' => ConsentEventType::NewVersionRequired->value,
        ]);
    }

    public function test_accepting_outdated_version_fails(): void
    {
        [$type, $version1] = $this->createPublishedTypeWithVersion();

        app(PublishConsentTypeAction::class)->execute($type, $version1);

        $response = ConsentResponse::where('family_id', $this->family->id)->first();

        // Publish version 2 — pending response gets updated to v2
        app(PublishNewConsentVersionAction::class)->execute($type, 'Texto versión 2');

        // Refresh the model so Eloquent sees the current DB state (v2), then
        // simulate a race condition by forcing the version pointer back to v1.
        $response->refresh();
        $response->update(['consent_version_id' => $version1->id]);

        $this->expectException(ValidationException::class);

        app(AcceptConsentAction::class)->execute($response->fresh(), $this->family);
    }

    // ─── ConsentStatusService ─────────────────────────────────────────────────

    public function test_consent_status_service_returns_pending_for_family(): void
    {
        [$type, $version] = $this->createPublishedTypeWithVersion();

        app(PublishConsentTypeAction::class)->execute($type, $version);

        $pending = app(ConsentStatusService::class)->getPendingForFamily($this->family);

        $this->assertCount(1, $pending);
        $this->assertEquals($this->family->id, $pending->first()->family_id);
        $this->assertEquals(ConsentResponseStatus::Pending, $pending->first()->status);
    }

    public function test_consent_status_service_does_not_return_other_familys_responses(): void
    {
        [$type, $version] = $this->createPublishedTypeWithVersion();

        app(PublishConsentTypeAction::class)->execute($type, $version);

        // Get only responses for $this->otherFamily — from $this->family's perspective
        $responses = app(ConsentStatusService::class)->getResponsesForFamily($this->family);

        $this->assertTrue($responses->every(fn ($r) => $r->family_id === $this->family->id));
        $this->assertFalse($responses->contains('family_id', $this->otherFamily->id));
    }
}
