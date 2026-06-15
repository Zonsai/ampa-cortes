<?php

namespace Tests\Feature;

use App\Actions\Enrollments\ConfirmEnrollmentAction;
use App\Actions\Enrollments\MarkPendingPaymentAction;
use App\Actions\Enrollments\MoveToWaitlistAction;
use App\Actions\Enrollments\RegisterPaymentAction;
use App\Actions\Enrollments\RequestFamilyEnrollmentAction;
use App\Enums\ActivityGroupStatus;
use App\Enums\ActivityStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\PaymentMethod;
use App\Models\AcademicYear;
use App\Models\ActivityGroup;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\ExtracurricularActivity;
use App\Models\Family;
use App\Models\Grade;
use App\Models\Guardian;
use App\Models\SchoolStage;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class Enrollments2Test extends TestCase
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

    /** @return array{user: User, family: Family, student: Student} */
    private function createFamilyUser(bool $isAmpaMember = true): array
    {
        $user = User::factory()->create();
        $user->assignRole('familia');
        $family = Family::factory()->create(['is_ampa_member' => $isAmpaMember]);
        Guardian::factory()->create(['user_id' => $user->id, 'family_id' => $family->id]);
        $student = Student::factory()->create(['family_id' => $family->id, 'is_active' => true]);

        $stage = SchoolStage::firstOrCreate(['name' => 'General', 'sort_order' => 99]);
        $grade = Grade::firstOrCreate(['school_stage_id' => $stage->id, 'name' => 'General'], ['sort_order' => 99]);
        $classroom = Classroom::firstOrCreate([
            'academic_year_id' => $this->activeYear->id,
            'grade_id' => $grade->id,
            'name' => 'General',
        ]);
        $student->classrooms()->attach($classroom->id, ['enrolled_at' => now()]);

        return compact('user', 'family', 'student');
    }

    /** @return array{activity: ExtracurricularActivity, group: ActivityGroup} */
    private function createOpenActivity(int $maxSpots = 10): array
    {
        $activity = ExtracurricularActivity::factory()->create([
            'academic_year_id' => $this->activeYear->id,
            'status' => ActivityStatus::Published,
            'is_visible_for_families' => true,
            'requires_ampa_membership' => false,
        ]);

        $group = ActivityGroup::factory()->create([
            'activity_id' => $activity->id,
            'max_spots' => $maxSpots,
            'status' => ActivityGroupStatus::Open,
        ]);

        return compact('activity', 'group');
    }

    private function makeEnrolled(Student $student, Family $family, ActivityGroup $group): Enrollment
    {
        return Enrollment::factory()->create([
            'student_id' => $student->id,
            'family_id' => $family->id,
            'activity_id' => $group->activity_id,
            'activity_group_id' => $group->id,
            'academic_year_id' => $this->activeYear->id,
            'status' => EnrollmentStatus::Enrolled,
            'enrolled_at' => now(),
        ]);
    }

    private function makePending(Student $student, Family $family, ActivityGroup $group): Enrollment
    {
        return Enrollment::factory()->create([
            'student_id' => $student->id,
            'family_id' => $family->id,
            'activity_id' => $group->activity_id,
            'activity_group_id' => $group->id,
            'academic_year_id' => $this->activeYear->id,
            'status' => EnrollmentStatus::Pending,
        ]);
    }

    // ─── Familia: flujo solicitud (10 tests) ─────────────────────────────────

    public function test_family_request_with_spots_creates_pending(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();
        ['group' => $group] = $this->createOpenActivity(maxSpots: 5);

        $this->actingAs($user)->post(route('familia.enroll'), [
            'student_id' => $student->id,
            'activity_group_id' => $group->id,
        ]);

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->id,
            'family_id' => $family->id,
            'activity_group_id' => $group->id,
            'status' => EnrollmentStatus::Pending->value,
        ]);
    }

    public function test_family_request_when_full_creates_waitlist(): void
    {
        ['user' => $userA, 'family' => $familyA, 'student' => $studentA] = $this->createFamilyUser();
        ['user' => $userB, 'family' => $familyB, 'student' => $studentB] = $this->createFamilyUser();
        ['group' => $group] = $this->createOpenActivity(maxSpots: 1);

        // Occupy the single spot via a direct Enrolled enrollment (admin path)
        $this->makeEnrolled($studentA, $familyA, $group);

        $this->actingAs($userB)->post(route('familia.enroll'), [
            'student_id' => $studentB->id,
            'activity_group_id' => $group->id,
        ]);

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $studentB->id,
            'status' => EnrollmentStatus::Waitlist->value,
        ]);
    }

    public function test_pending_does_not_occupy_a_spot(): void
    {
        ['family' => $family, 'student' => $student] = $this->createFamilyUser();
        ['group' => $group] = $this->createOpenActivity(maxSpots: 1);

        $this->makePending($student, $family, $group);

        $this->assertSame(0, $group->spotsOccupied());
        $this->assertTrue($group->hasAvailableSpots());
    }

    public function test_two_families_can_both_be_pending_for_the_same_spot(): void
    {
        ['user' => $userA, 'family' => $familyA, 'student' => $studentA] = $this->createFamilyUser();
        ['user' => $userB, 'family' => $familyB, 'student' => $studentB] = $this->createFamilyUser();
        ['group' => $group] = $this->createOpenActivity(maxSpots: 1);

        $this->actingAs($userA)->post(route('familia.enroll'), [
            'student_id' => $studentA->id,
            'activity_group_id' => $group->id,
        ]);
        $this->actingAs($userB)->post(route('familia.enroll'), [
            'student_id' => $studentB->id,
            'activity_group_id' => $group->id,
        ]);

        $this->assertDatabaseHas('enrollments', ['student_id' => $studentA->id, 'status' => EnrollmentStatus::Pending->value]);
        $this->assertDatabaseHas('enrollments', ['student_id' => $studentB->id, 'status' => EnrollmentStatus::Pending->value]);
    }

    public function test_success_message_for_pending_request(): void
    {
        ['user' => $user, 'student' => $student] = $this->createFamilyUser();
        ['group' => $group] = $this->createOpenActivity();

        $response = $this->actingAs($user)->post(route('familia.enroll'), [
            'student_id' => $student->id,
            'activity_group_id' => $group->id,
        ]);

        $response->assertSessionHas('success', 'Solicitud enviada. El AMPA revisará la inscripción.');
    }

    public function test_success_message_for_waitlist_request(): void
    {
        ['user' => $userA, 'family' => $familyA, 'student' => $studentA] = $this->createFamilyUser();
        ['user' => $userB, 'student' => $studentB] = $this->createFamilyUser();
        ['group' => $group] = $this->createOpenActivity(maxSpots: 1);

        $this->makeEnrolled($studentA, $familyA, $group);

        $response = $this->actingAs($userB)->post(route('familia.enroll'), [
            'student_id' => $studentB->id,
            'activity_group_id' => $group->id,
        ]);

        $response->assertSessionHas('success', 'Te has apuntado a la lista de espera.');
    }

    public function test_show_view_displays_solicitud_enviada_for_pending(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();
        ['activity' => $activity, 'group' => $group] = $this->createOpenActivity();

        $this->makePending($student, $family, $group);

        $this->actingAs($user)
            ->get(route('familia.activities.show', $activity))
            ->assertOk()
            ->assertSee('Solicitud enviada');
    }

    public function test_show_view_button_with_spots_says_solicitar_plaza(): void
    {
        ['user' => $user] = $this->createFamilyUser();
        ['activity' => $activity] = $this->createOpenActivity(maxSpots: 5);

        $this->actingAs($user)
            ->get(route('familia.activities.show', $activity))
            ->assertOk()
            ->assertSee('Solicitar plaza');
    }

    public function test_show_view_button_when_full_says_apuntarse_a_lista_de_espera(): void
    {
        ['user' => $userA, 'family' => $familyA, 'student' => $studentA] = $this->createFamilyUser();
        ['user' => $userB] = $this->createFamilyUser();
        ['activity' => $activity, 'group' => $group] = $this->createOpenActivity(maxSpots: 1);

        $this->makeEnrolled($studentA, $familyA, $group);
        $group->update(['status' => ActivityGroupStatus::Full]);

        $this->actingAs($userB)
            ->get(route('familia.activities.show', $activity))
            ->assertOk()
            ->assertSee('Apuntarse a lista de espera');
    }

    public function test_index_view_displays_solicitud_enviada_for_pending(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();
        ['activity' => $activity, 'group' => $group] = $this->createOpenActivity();

        $this->makePending($student, $family, $group);

        $this->actingAs($user)
            ->get(route('familia.activities.index'))
            ->assertOk()
            ->assertSee('Solicitud enviada');
    }

    // ─── Admin: ConfirmEnrollmentAction (6 tests) ────────────────────────────

    public function test_admin_can_confirm_pending_to_enrolled(): void
    {
        ['family' => $family, 'student' => $student] = $this->createFamilyUser();
        ['group' => $group] = $this->createOpenActivity(maxSpots: 5);

        $pending = $this->makePending($student, $family, $group);

        app(ConfirmEnrollmentAction::class)->execute($pending);

        $this->assertDatabaseHas('enrollments', [
            'id' => $pending->id,
            'status' => EnrollmentStatus::Enrolled->value,
        ]);
        $this->assertNotNull($pending->fresh()->enrolled_at);
    }

    public function test_confirm_fails_when_no_spots_available(): void
    {
        ['family' => $familyA, 'student' => $studentA] = $this->createFamilyUser();
        ['family' => $familyB, 'student' => $studentB] = $this->createFamilyUser();
        ['group' => $group] = $this->createOpenActivity(maxSpots: 1);

        // Occupy the only spot
        $this->makeEnrolled($studentA, $familyA, $group);
        $pending = $this->makePending($studentB, $familyB, $group);

        $this->expectException(ValidationException::class);
        app(ConfirmEnrollmentAction::class)->execute($pending);
    }

    public function test_confirm_does_not_change_status_when_no_spots(): void
    {
        ['family' => $familyA, 'student' => $studentA] = $this->createFamilyUser();
        ['family' => $familyB, 'student' => $studentB] = $this->createFamilyUser();
        ['group' => $group] = $this->createOpenActivity(maxSpots: 1);

        $this->makeEnrolled($studentA, $familyA, $group);
        $pending = $this->makePending($studentB, $familyB, $group);

        try {
            app(ConfirmEnrollmentAction::class)->execute($pending);
        } catch (ValidationException) {
        }

        $this->assertDatabaseHas('enrollments', [
            'id' => $pending->id,
            'status' => EnrollmentStatus::Pending->value,
        ]);
    }

    public function test_confirm_requires_manage_enrollments_permission(): void
    {
        ['family' => $family, 'student' => $student] = $this->createFamilyUser();
        ['group' => $group] = $this->createOpenActivity();
        $pending = $this->makePending($student, $family, $group);

        $userWithPermission = User::factory()->create();
        $userWithPermission->givePermissionTo('manage enrollments');

        $userWithout = User::factory()->create();

        $this->assertTrue($userWithPermission->can('update', $pending));
        $this->assertFalse($userWithout->can('update', $pending));
    }

    public function test_admin_extraescolares_can_confirm_if_has_manage_enrollments(): void
    {
        ['family' => $family, 'student' => $student] = $this->createFamilyUser();
        ['group' => $group] = $this->createOpenActivity();
        $pending = $this->makePending($student, $family, $group);

        $adminExtraescolares = User::factory()->create();
        $adminExtraescolares->assignRole('admin_extraescolares');

        $this->assertTrue($adminExtraescolares->can('update', $pending));
    }

    public function test_admin_formularios_cannot_confirm(): void
    {
        ['family' => $family, 'student' => $student] = $this->createFamilyUser();
        ['group' => $group] = $this->createOpenActivity();
        $pending = $this->makePending($student, $family, $group);

        $adminFormularios = User::factory()->create();
        $adminFormularios->assignRole('admin_formularios');

        $this->assertFalse($adminFormularios->can('update', $pending));
    }

    // ─── Admin: MoveToWaitlistAction (2 tests) ───────────────────────────────

    public function test_admin_can_move_pending_to_waitlist(): void
    {
        ['family' => $family, 'student' => $student] = $this->createFamilyUser();
        ['group' => $group] = $this->createOpenActivity();
        $pending = $this->makePending($student, $family, $group);

        app(MoveToWaitlistAction::class)->execute($pending);

        $this->assertDatabaseHas('enrollments', [
            'id' => $pending->id,
            'status' => EnrollmentStatus::Waitlist->value,
        ]);
    }

    public function test_move_to_waitlist_assigns_next_position(): void
    {
        ['family' => $familyA, 'student' => $studentA] = $this->createFamilyUser();
        ['family' => $familyB, 'student' => $studentB] = $this->createFamilyUser();
        ['group' => $group] = $this->createOpenActivity();

        // There's already one person at position 1 in the waitlist
        Enrollment::factory()->create([
            'student_id' => $studentA->id,
            'family_id' => $familyA->id,
            'activity_id' => $group->activity_id,
            'activity_group_id' => $group->id,
            'academic_year_id' => $this->activeYear->id,
            'status' => EnrollmentStatus::Waitlist,
            'waitlist_position' => 1,
        ]);

        $pending = $this->makePending($studentB, $familyB, $group);
        app(MoveToWaitlistAction::class)->execute($pending);

        $this->assertDatabaseHas('enrollments', [
            'id' => $pending->id,
            'status' => EnrollmentStatus::Waitlist->value,
            'waitlist_position' => 2,
        ]);
    }

    // ─── Concurrencia / capacidad (3 tests) ──────────────────────────────────

    public function test_two_pending_requests_do_not_occupy_spots(): void
    {
        ['family' => $familyA, 'student' => $studentA] = $this->createFamilyUser();
        ['family' => $familyB, 'student' => $studentB] = $this->createFamilyUser();
        ['group' => $group] = $this->createOpenActivity(maxSpots: 1);

        $this->makePending($studentA, $familyA, $group);
        $this->makePending($studentB, $familyB, $group);

        $this->assertSame(0, $group->fresh()->spotsOccupied());
        $this->assertTrue($group->fresh()->hasAvailableSpots());
    }

    public function test_confirming_first_pending_occupies_the_spot(): void
    {
        ['family' => $family, 'student' => $student] = $this->createFamilyUser();
        ['group' => $group] = $this->createOpenActivity(maxSpots: 1);

        $pending = $this->makePending($student, $family, $group);
        app(ConfirmEnrollmentAction::class)->execute($pending);

        $this->assertSame(1, $group->fresh()->spotsOccupied());
        $this->assertFalse($group->fresh()->hasAvailableSpots());
    }

    public function test_cannot_confirm_more_pending_requests_than_available_spots(): void
    {
        ['family' => $familyA, 'student' => $studentA] = $this->createFamilyUser();
        ['family' => $familyB, 'student' => $studentB] = $this->createFamilyUser();
        ['group' => $group] = $this->createOpenActivity(maxSpots: 1);

        $pendingA = $this->makePending($studentA, $familyA, $group);
        $pendingB = $this->makePending($studentB, $familyB, $group);

        // Confirm first request — succeeds
        app(ConfirmEnrollmentAction::class)->execute($pendingA);

        // Second confirmation must fail — spot is now taken
        $this->expectException(ValidationException::class);
        app(ConfirmEnrollmentAction::class)->execute($pendingB);
    }

    // ─── Flujo de pagos post-confirmación (2 tests) ──────────────────────────

    public function test_confirmed_enrollment_can_be_moved_to_pending_payment(): void
    {
        ['family' => $family, 'student' => $student] = $this->createFamilyUser();
        ['group' => $group] = $this->createOpenActivity();

        $pending = $this->makePending($student, $family, $group);
        $enrolled = app(ConfirmEnrollmentAction::class)->execute($pending);

        app(MarkPendingPaymentAction::class)->execute($enrolled);

        $this->assertDatabaseHas('enrollments', [
            'id' => $enrolled->id,
            'status' => EnrollmentStatus::PendingPayment->value,
        ]);
    }

    public function test_pending_payment_can_be_registered_as_paid(): void
    {
        ['family' => $family, 'student' => $student] = $this->createFamilyUser();
        ['group' => $group] = $this->createOpenActivity();

        $pending = $this->makePending($student, $family, $group);
        $enrolled = app(ConfirmEnrollmentAction::class)->execute($pending);
        app(MarkPendingPaymentAction::class)->execute($enrolled);

        $pendingPayment = $enrolled->fresh();
        app(RegisterPaymentAction::class)->execute(
            $pendingPayment,
            now()->toDateTimeString(),
            PaymentMethod::Cash,
        );

        $this->assertDatabaseHas('enrollments', [
            'id' => $enrolled->id,
            'status' => EnrollmentStatus::Paid->value,
        ]);
    }

    // ─── RequestFamilyEnrollmentAction directo (1 test adicional) ───────────

    public function test_request_action_validates_inactive_academic_year(): void
    {
        ['family' => $family, 'student' => $student] = $this->createFamilyUser();

        $inactiveYear = AcademicYear::factory()->create(['is_active' => false]);
        $activity = ExtracurricularActivity::factory()->create([
            'academic_year_id' => $inactiveYear->id,
            'status' => ActivityStatus::Published,
            'is_visible_for_families' => true,
        ]);
        $group = ActivityGroup::factory()->create([
            'activity_id' => $activity->id,
            'max_spots' => 10,
            'status' => ActivityGroupStatus::Open,
        ]);

        $this->expectException(ValidationException::class);
        app(RequestFamilyEnrollmentAction::class)->execute(
            student: $student,
            group: $group,
            family: $family,
            academicYear: $this->activeYear,
        );
    }
}
