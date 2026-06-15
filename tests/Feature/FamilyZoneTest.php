<?php

namespace Tests\Feature;

use App\Actions\Enrollments\EnrollStudentAction;
use App\Enums\ActivityGroupStatus;
use App\Enums\ActivityStatus;
use App\Enums\ConsentResponseStatus;
use App\Enums\EnrollmentStatus;
use App\Models\AcademicYear;
use App\Models\ActivityGroup;
use App\Models\Classroom;
use App\Models\ConsentResponse;
use App\Models\ConsentType;
use App\Models\ConsentVersion;
use App\Models\Enrollment;
use App\Models\ExtracurricularActivity;
use App\Models\Family;
use App\Models\Grade;
use App\Models\Guardian;
use App\Models\SchoolStage;
use App\Models\Student;
use App\Models\User;
use App\Services\ConsentStatusService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyZoneTest extends TestCase
{
    use RefreshDatabase;

    private AcademicYear $activeYear;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->activeYear = AcademicYear::factory()->create(['is_active' => true]);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    /**
     * Creates a user with the 'familia' role linked to a Guardian/Family.
     *
     * @return array{user: User, family: Family, student: Student}
     */
    private function createFamilyUser(): array
    {
        $user = User::factory()->create();
        $user->assignRole('familia');

        $family = Family::factory()->create(['is_ampa_member' => true]);
        Guardian::factory()->create(['user_id' => $user->id, 'family_id' => $family->id]);
        $student = Student::factory()->create(['family_id' => $family->id, 'is_active' => true]);

        // The family portal now requires students to have a classroom in the active year.
        $stage = SchoolStage::firstOrCreate(['name' => 'General', 'sort_order' => 99]);
        $grade = Grade::firstOrCreate(['school_stage_id' => $stage->id, 'name' => 'General'], ['sort_order' => 99]);
        $classroom = Classroom::firstOrCreate(['academic_year_id' => $this->activeYear->id, 'grade_id' => $grade->id, 'name' => 'General']);
        $student->classrooms()->attach($classroom->id, ['enrolled_at' => now()]);

        return compact('user', 'family', 'student');
    }

    /**
     * Creates a published visible activity with an open group for the active year.
     *
     * @return array{activity: ExtracurricularActivity, group: ActivityGroup}
     */
    private function createPublishedActivity(array $activityOverrides = [], array $groupOverrides = []): array
    {
        $activity = ExtracurricularActivity::factory()->create(array_merge([
            'academic_year_id' => $this->activeYear->id,
            'status' => ActivityStatus::Published,
            'is_visible_for_families' => true,
            'requires_ampa_membership' => false,
        ], $activityOverrides));

        $group = ActivityGroup::factory()->create(array_merge([
            'activity_id' => $activity->id,
            'max_spots' => 10,
            'status' => ActivityGroupStatus::Open,
        ], $groupOverrides));

        return compact('activity', 'group');
    }

    // ─── Access control (5 tests) ────────────────────────────────────────────

    public function test_unauthenticated_user_is_redirected_to_familia_login(): void
    {
        $response = $this->get(route('familia.dashboard'));

        $response->assertRedirect(route('familia.login'));
    }

    public function test_user_without_familia_role_gets_403_on_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('familia.dashboard'));

        $response->assertForbidden();
    }

    public function test_user_with_familia_role_but_no_guardian_is_redirected_to_login_with_error(): void
    {
        $user = User::factory()->create();
        $user->assignRole('familia');
        // No guardian created — user has no family link

        $response = $this->actingAs($user)->get(route('familia.dashboard'));

        $response->assertRedirect(route('familia.login'));
        $response->assertSessionHas('error');
    }

    public function test_authenticated_familia_user_can_access_dashboard(): void
    {
        ['user' => $user] = $this->createFamilyUser();

        $response = $this->actingAs($user)->get(route('familia.dashboard'));

        $response->assertOk();
    }

    public function test_login_page_redirects_if_already_logged_in_as_familia(): void
    {
        ['user' => $user] = $this->createFamilyUser();

        $response = $this->actingAs($user)->get(route('familia.login'));

        $response->assertRedirect(route('familia.dashboard'));
    }

    // ─── Data isolation (3 tests) ────────────────────────────────────────────

    public function test_family_cannot_see_another_familys_students_on_children_page(): void
    {
        ['user' => $userA, 'student' => $studentA] = $this->createFamilyUser();
        ['student' => $studentB] = $this->createFamilyUser();

        $response = $this->actingAs($userA)->get(route('familia.children'));

        $response->assertOk();
        $response->assertSee($studentA->first_name);
        $response->assertDontSee($studentB->first_name);
    }

    public function test_family_cannot_cancel_another_familys_enrollment(): void
    {
        ['user' => $userA] = $this->createFamilyUser();
        ['family' => $familyB, 'student' => $studentB] = $this->createFamilyUser();
        ['group' => $group] = $this->createPublishedActivity();

        $enrollmentB = app(EnrollStudentAction::class)->execute(
            student: $studentB,
            group: $group,
            family: $familyB,
            academicYear: $this->activeYear,
        );
        // Force to Pending so it would normally be cancellable by its own family
        $enrollmentB->update(['status' => EnrollmentStatus::Pending->value]);

        $response = $this->actingAs($userA)
            ->post(route('familia.enrollment.cancel', $enrollmentB));

        $response->assertForbidden();
        $this->assertDatabaseHas('enrollments', [
            'id' => $enrollmentB->id,
            'status' => EnrollmentStatus::Pending->value,
        ]);
    }

    public function test_family_cannot_enroll_another_familys_student(): void
    {
        ['user' => $userA] = $this->createFamilyUser();
        ['student' => $studentB] = $this->createFamilyUser();
        ['group' => $group] = $this->createPublishedActivity();

        $response = $this->actingAs($userA)
            ->post(route('familia.enroll'), [
                'student_id' => $studentB->id,
                'activity_group_id' => $group->id,
            ]);

        $response->assertForbidden();
    }

    // ─── Activities visibility (4 tests) ─────────────────────────────────────

    public function test_draft_activity_is_not_visible_in_activities_list(): void
    {
        ['user' => $user] = $this->createFamilyUser();

        $draft = ExtracurricularActivity::factory()->create([
            'academic_year_id' => $this->activeYear->id,
            'status' => ActivityStatus::Draft,
            'is_visible_for_families' => true,
        ]);

        $response = $this->actingAs($user)->get(route('familia.activities.index'));

        $response->assertOk();
        $response->assertDontSee($draft->name);
    }

    public function test_activity_hidden_for_families_is_not_visible(): void
    {
        ['user' => $user] = $this->createFamilyUser();

        $hidden = ExtracurricularActivity::factory()->create([
            'academic_year_id' => $this->activeYear->id,
            'status' => ActivityStatus::Published,
            'is_visible_for_families' => false,
        ]);

        $response = $this->actingAs($user)->get(route('familia.activities.index'));

        $response->assertOk();
        $response->assertDontSee($hidden->name);
    }

    public function test_activity_from_another_academic_year_is_not_visible(): void
    {
        ['user' => $user] = $this->createFamilyUser();

        $otherYear = AcademicYear::factory()->create(['is_active' => false]);
        $otherActivity = ExtracurricularActivity::factory()->create([
            'academic_year_id' => $otherYear->id,
            'status' => ActivityStatus::Published,
            'is_visible_for_families' => true,
        ]);

        $response = $this->actingAs($user)->get(route('familia.activities.index'));

        $response->assertOk();
        $response->assertDontSee($otherActivity->name);
    }

    public function test_no_active_year_shows_informative_message(): void
    {
        ['user' => $user] = $this->createFamilyUser();
        $this->activeYear->update(['is_active' => false]);

        $response = $this->actingAs($user)->get(route('familia.activities.index'));

        $response->assertOk();
        $response->assertSeeText('No hay curso académico activo');
    }

    // ─── Enrollment operations (9 tests) ─────────────────────────────────────

    public function test_family_can_enroll_own_student_successfully(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();
        ['group' => $group] = $this->createPublishedActivity();

        $response = $this->actingAs($user)
            ->post(route('familia.enroll'), [
                'student_id' => $student->id,
                'activity_group_id' => $group->id,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->id,
            'family_id' => $family->id,
            'activity_group_id' => $group->id,
            'status' => EnrollmentStatus::Enrolled->value,
        ]);
    }

    public function test_enrollment_lands_on_waitlist_when_group_is_full(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();
        ['group' => $group] = $this->createPublishedActivity([], ['max_spots' => 1]);

        // Fill the spot with another family
        ['family' => $otherFamily, 'student' => $otherStudent] = $this->createFamilyUser();
        app(EnrollStudentAction::class)->execute(
            student: $otherStudent,
            group: $group,
            family: $otherFamily,
            academicYear: $this->activeYear,
        );

        $response = $this->actingAs($user)
            ->post(route('familia.enroll'), [
                'student_id' => $student->id,
                'activity_group_id' => $group->id,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->id,
            'family_id' => $family->id,
            'status' => EnrollmentStatus::Waitlist->value,
        ]);
    }

    public function test_duplicate_enrollment_redirects_back_with_error(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();
        ['activity' => $activity, 'group' => $group] = $this->createPublishedActivity();

        app(EnrollStudentAction::class)->execute(
            student: $student,
            group: $group,
            family: $family,
            academicYear: $this->activeYear,
        );

        $response = $this->actingAs($user)
            ->from(route('familia.activities.show', $activity))
            ->post(route('familia.enroll'), [
                'student_id' => $student->id,
                'activity_group_id' => $group->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_family_can_cancel_pending_enrollment(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();
        ['group' => $group] = $this->createPublishedActivity();

        $enrollment = Enrollment::factory()->create([
            'student_id' => $student->id,
            'family_id' => $family->id,
            'activity_id' => $group->activity_id,
            'activity_group_id' => $group->id,
            'academic_year_id' => $this->activeYear->id,
            'status' => EnrollmentStatus::Pending,
        ]);

        $response = $this->actingAs($user)
            ->post(route('familia.enrollment.cancel', $enrollment));

        $response->assertRedirect(route('familia.children'));
        $this->assertDatabaseHas('enrollments', [
            'id' => $enrollment->id,
            'status' => EnrollmentStatus::Cancelled->value,
        ]);
    }

    public function test_family_can_cancel_waitlist_enrollment(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();
        ['group' => $group] = $this->createPublishedActivity();

        $enrollment = Enrollment::factory()->create([
            'student_id' => $student->id,
            'family_id' => $family->id,
            'activity_id' => $group->activity_id,
            'activity_group_id' => $group->id,
            'academic_year_id' => $this->activeYear->id,
            'status' => EnrollmentStatus::Waitlist,
            'waitlist_position' => 1,
            'enrolled_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->post(route('familia.enrollment.cancel', $enrollment));

        $response->assertRedirect(route('familia.children'));
        $this->assertDatabaseHas('enrollments', [
            'id' => $enrollment->id,
            'status' => EnrollmentStatus::Cancelled->value,
        ]);
    }

    public function test_family_cannot_cancel_enrolled_enrollment_from_portal(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();
        ['group' => $group] = $this->createPublishedActivity();

        $enrollment = Enrollment::factory()->create([
            'student_id' => $student->id,
            'family_id' => $family->id,
            'activity_id' => $group->activity_id,
            'activity_group_id' => $group->id,
            'academic_year_id' => $this->activeYear->id,
            'status' => EnrollmentStatus::Enrolled,
        ]);

        $response = $this->actingAs($user)
            ->post(route('familia.enrollment.cancel', $enrollment));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('enrollments', [
            'id' => $enrollment->id,
            'status' => EnrollmentStatus::Enrolled->value,
        ]);
    }

    public function test_family_cannot_cancel_dropped_enrollment(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();
        ['group' => $group] = $this->createPublishedActivity();

        $enrollment = Enrollment::factory()->create([
            'student_id' => $student->id,
            'family_id' => $family->id,
            'activity_id' => $group->activity_id,
            'activity_group_id' => $group->id,
            'academic_year_id' => $this->activeYear->id,
            'status' => EnrollmentStatus::Dropped,
            'ended_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->post(route('familia.enrollment.cancel', $enrollment));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('enrollments', [
            'id' => $enrollment->id,
            'status' => EnrollmentStatus::Dropped->value,
        ]);
    }

    public function test_non_ampa_member_family_cannot_enroll_in_ampa_only_activity(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();
        $family->update(['is_ampa_member' => false]);

        ['group' => $group] = $this->createPublishedActivity(['requires_ampa_membership' => true]);

        $response = $this->actingAs($user)
            ->from(route('familia.activities.show', $group->activity_id))
            ->post(route('familia.enroll'), [
                'student_id' => $student->id,
                'activity_group_id' => $group->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('enrollments', [
            'student_id' => $student->id,
            'activity_group_id' => $group->id,
        ]);
    }

    public function test_familia_user_cannot_access_filament_admin_panel(): void
    {
        ['user' => $user] = $this->createFamilyUser();

        $response = $this->actingAs($user)->get('/admin');

        $response->assertForbidden();
    }

    // ─── Dashboard consent notice (3 tests) ──────────────────────────────────

    public function test_dashboard_shows_pending_consents_notice_when_pending_exist(): void
    {
        ['user' => $user, 'family' => $family] = $this->createFamilyUser();

        $type = ConsentType::factory()->published()->create();
        $version = ConsentVersion::factory()->published()->create(['consent_type_id' => $type->id]);
        ConsentResponse::create([
            'consent_type_id' => $type->id,
            'consent_version_id' => $version->id,
            'family_id' => $family->id,
            'student_id' => null,
            'subject_key' => "family:{$family->id}",
            'status' => ConsentResponseStatus::Pending,
        ]);

        $this->actingAs($user)
            ->get(route('familia.dashboard'))
            ->assertOk()
            ->assertSee('Consentimientos pendientes');
    }

    public function test_dashboard_does_not_show_pending_consents_notice_when_none(): void
    {
        ['user' => $user] = $this->createFamilyUser();

        $this->actingAs($user)
            ->get(route('familia.dashboard'))
            ->assertOk()
            ->assertDontSee('Consentimientos pendientes');
    }

    public function test_count_pending_for_family_returns_correct_count(): void
    {
        ['user' => $user, 'family' => $family] = $this->createFamilyUser();

        $type = ConsentType::factory()->published()->create();
        $version = ConsentVersion::factory()->published()->create(['consent_type_id' => $type->id]);

        ConsentResponse::create([
            'consent_type_id' => $type->id,
            'consent_version_id' => $version->id,
            'family_id' => $family->id,
            'student_id' => null,
            'subject_key' => "family:{$family->id}",
            'status' => ConsentResponseStatus::Pending,
        ]);

        $service = app(ConsentStatusService::class);

        $this->assertSame(1, $service->countPendingForFamily($family));
    }

    // ─── Dashboard quick access links (2 tests) ───────────────────────────────

    public function test_dashboard_shows_quick_access_link_to_forms(): void
    {
        ['user' => $user] = $this->createFamilyUser();

        $this->actingAs($user)
            ->get(route('familia.dashboard'))
            ->assertOk()
            ->assertSee(route('familia.forms.index'), false);
    }

    public function test_dashboard_shows_quick_access_link_to_consents(): void
    {
        ['user' => $user] = $this->createFamilyUser();

        $this->actingAs($user)
            ->get(route('familia.dashboard'))
            ->assertOk()
            ->assertSee(route('familia.consents.index'), false);
    }
}
