<?php

namespace Tests\Feature;

use App\Enums\ActivityGroupStatus;
use App\Enums\ActivityStatus;
use App\Enums\EnrollmentStatus;
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
use Tests\TestCase;

class FamilyActivitiesTest extends TestCase
{
    use RefreshDatabase;

    private AcademicYear $activeYear;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->activeYear = AcademicYear::factory()->create(['is_active' => true]);
    }

    /** @return array{user: User, family: Family, student: Student} */
    private function createFamilyUser(bool $isAmpaMember = true): array
    {
        $user = User::factory()->create();
        $user->assignRole('familia');
        $family = Family::factory()->create(['is_ampa_member' => $isAmpaMember]);
        Guardian::factory()->create(['user_id' => $user->id, 'family_id' => $family->id]);
        $student = Student::factory()->create(['family_id' => $family->id, 'is_active' => true]);

        return compact('user', 'family', 'student');
    }

    /** @return array{activity: ExtracurricularActivity, group: ActivityGroup} */
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

    // ─── Index view ──────────────────────────────────────────────────────────

    public function test_activities_index_shows_activity_name_and_group_count(): void
    {
        ['user' => $user] = $this->createFamilyUser();
        ['activity' => $activity, 'group' => $group] = $this->createPublishedActivity();
        ActivityGroup::factory()->create(['activity_id' => $activity->id]);

        $this->actingAs($user)
            ->get(route('familia.activities.index'))
            ->assertOk()
            ->assertSee($activity->name)
            ->assertSee('2 grupos');
    }

    public function test_activities_index_shows_short_description(): void
    {
        ['user' => $user] = $this->createFamilyUser();
        ExtracurricularActivity::factory()->create([
            'academic_year_id' => $this->activeYear->id,
            'status' => ActivityStatus::Published,
            'is_visible_for_families' => true,
            'short_description' => 'Descripción breve de la actividad extraescolar',
        ]);

        $this->actingAs($user)
            ->get(route('familia.activities.index'))
            ->assertOk()
            ->assertSee('Descripción breve de la actividad extraescolar');
    }

    public function test_activities_index_shows_ampa_only_badge(): void
    {
        ['user' => $user] = $this->createFamilyUser();
        $this->createPublishedActivity(['requires_ampa_membership' => true]);

        $this->actingAs($user)
            ->get(route('familia.activities.index'))
            ->assertOk()
            ->assertSee('Solo socios/as AMPA');
    }

    public function test_activities_index_shows_price_from_groups(): void
    {
        ['user' => $user] = $this->createFamilyUser();
        ['activity' => $activity] = $this->createPublishedActivity();
        ActivityGroup::factory()->create([
            'activity_id' => $activity->id,
            'price_member' => '25.00',
            'price_non_member' => '35.00',
        ]);

        $this->actingAs($user)
            ->get(route('familia.activities.index'))
            ->assertOk()
            ->assertSee('25');
    }

    public function test_activities_index_shows_cta_link_to_detail(): void
    {
        ['user' => $user] = $this->createFamilyUser();
        ['activity' => $activity] = $this->createPublishedActivity();

        $this->actingAs($user)
            ->get(route('familia.activities.index'))
            ->assertOk()
            ->assertSee('Ver grupos y apuntarse')
            ->assertSee(route('familia.activities.show', $activity), false);
    }

    public function test_activities_index_shows_enrolled_student_status(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();
        ['activity' => $activity, 'group' => $group] = $this->createPublishedActivity();

        Enrollment::factory()->create([
            'student_id' => $student->id,
            'family_id' => $family->id,
            'activity_id' => $activity->id,
            'activity_group_id' => $group->id,
            'academic_year_id' => $this->activeYear->id,
            'status' => EnrollmentStatus::Enrolled,
        ]);

        $this->actingAs($user)
            ->get(route('familia.activities.index'))
            ->assertOk()
            ->assertSee($student->first_name)
            ->assertSee('Inscrito/a');
    }

    public function test_activities_index_shows_waitlist_student_status(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();
        ['activity' => $activity, 'group' => $group] = $this->createPublishedActivity();

        Enrollment::factory()->create([
            'student_id' => $student->id,
            'family_id' => $family->id,
            'activity_id' => $activity->id,
            'activity_group_id' => $group->id,
            'academic_year_id' => $this->activeYear->id,
            'status' => EnrollmentStatus::Waitlist,
            'waitlist_position' => 1,
            'enrolled_at' => null,
        ]);

        $this->actingAs($user)
            ->get(route('familia.activities.index'))
            ->assertOk()
            ->assertSee('Lista de espera');
    }

    // ─── Show view (field correctness) ───────────────────────────────────────

    public function test_activities_show_displays_weekdays_and_times(): void
    {
        ['user' => $user] = $this->createFamilyUser();
        ['activity' => $activity] = $this->createPublishedActivity([], [
            'weekdays' => [1, 3],
            'starts_at' => '16:00:00',
            'ends_at' => '17:00:00',
        ]);

        $this->actingAs($user)
            ->get(route('familia.activities.show', $activity))
            ->assertOk()
            ->assertSee('Lun')
            ->assertSee('Mié')
            ->assertSee('16:00')
            ->assertSee('17:00');
    }

    public function test_activities_show_displays_member_and_non_member_prices(): void
    {
        ['user' => $user] = $this->createFamilyUser();
        ['activity' => $activity] = $this->createPublishedActivity([], [
            'price_member' => '20.00',
            'price_non_member' => '30.00',
        ]);

        $this->actingAs($user)
            ->get(route('familia.activities.show', $activity))
            ->assertOk()
            ->assertSee('20')
            ->assertSee('30');
    }

    public function test_activities_show_displays_spot_count(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();
        ['activity' => $activity, 'group' => $group] = $this->createPublishedActivity([], ['max_spots' => 5]);

        Enrollment::factory()->create([
            'student_id' => $student->id,
            'family_id' => $family->id,
            'activity_id' => $activity->id,
            'activity_group_id' => $group->id,
            'academic_year_id' => $this->activeYear->id,
            'status' => EnrollmentStatus::Enrolled,
        ]);

        $this->actingAs($user)
            ->get(route('familia.activities.show', $activity))
            ->assertOk()
            ->assertSee('1/5');
    }

    public function test_activities_show_returns_404_for_hidden_activity(): void
    {
        ['user' => $user] = $this->createFamilyUser();
        $activity = ExtracurricularActivity::factory()->create([
            'academic_year_id' => $this->activeYear->id,
            'status' => ActivityStatus::Published,
            'is_visible_for_families' => false,
        ]);

        $this->actingAs($user)
            ->get(route('familia.activities.show', $activity))
            ->assertNotFound();
    }

    // ─── Per-student enrollment status display ─────────────────────────────

    public function test_activities_show_displays_enrolled_status_per_student(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();
        ['activity' => $activity, 'group' => $group] = $this->createPublishedActivity();

        Enrollment::factory()->create([
            'student_id' => $student->id,
            'family_id' => $family->id,
            'activity_id' => $activity->id,
            'activity_group_id' => $group->id,
            'academic_year_id' => $this->activeYear->id,
            'status' => EnrollmentStatus::Enrolled,
        ]);

        $this->actingAs($user)
            ->get(route('familia.activities.show', $activity))
            ->assertOk()
            ->assertSee($student->first_name)
            ->assertSee('Inscrito/a');
    }

    public function test_activities_show_displays_waitlist_status_per_student(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();
        ['activity' => $activity, 'group' => $group] = $this->createPublishedActivity();

        Enrollment::factory()->create([
            'student_id' => $student->id,
            'family_id' => $family->id,
            'activity_id' => $activity->id,
            'activity_group_id' => $group->id,
            'academic_year_id' => $this->activeYear->id,
            'status' => EnrollmentStatus::Waitlist,
            'waitlist_position' => 1,
            'enrolled_at' => null,
        ]);

        $this->actingAs($user)
            ->get(route('familia.activities.show', $activity))
            ->assertOk()
            ->assertSee('Lista de espera');
    }

    public function test_activities_show_displays_pending_payment_status_per_student(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();
        ['activity' => $activity, 'group' => $group] = $this->createPublishedActivity();

        Enrollment::factory()->create([
            'student_id' => $student->id,
            'family_id' => $family->id,
            'activity_id' => $activity->id,
            'activity_group_id' => $group->id,
            'academic_year_id' => $this->activeYear->id,
            'status' => EnrollmentStatus::PendingPayment,
        ]);

        $this->actingAs($user)
            ->get(route('familia.activities.show', $activity))
            ->assertOk()
            ->assertSee('Pendiente de pago');
    }

    public function test_activities_show_displays_paid_status_per_student(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();
        ['activity' => $activity, 'group' => $group] = $this->createPublishedActivity();

        Enrollment::factory()->create([
            'student_id' => $student->id,
            'family_id' => $family->id,
            'activity_id' => $activity->id,
            'activity_group_id' => $group->id,
            'academic_year_id' => $this->activeYear->id,
            'status' => EnrollmentStatus::Paid,
            'paid_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('familia.activities.show', $activity))
            ->assertOk()
            ->assertSee('Pagado');
    }

    // ─── Eligibility display ─────────────────────────────────────────────────

    public function test_activities_show_shows_no_classroom_message_for_student_without_class(): void
    {
        ['user' => $user] = $this->createFamilyUser();
        // Student has no classroom attached for the active year
        ['activity' => $activity] = $this->createPublishedActivity();

        $this->actingAs($user)
            ->get(route('familia.activities.show', $activity))
            ->assertOk()
            ->assertSee('Sin clase asignada este curso');
    }

    public function test_activities_show_shows_grade_restriction_message_for_wrong_grade(): void
    {
        ['user' => $user, 'student' => $student] = $this->createFamilyUser();
        ['activity' => $activity, 'group' => $group] = $this->createPublishedActivity();

        $stage = SchoolStage::create(['name' => 'Primaria', 'sort_order' => 1]);
        $allowedGrade = Grade::create(['school_stage_id' => $stage->id, 'name' => '1º Primaria', 'sort_order' => 1]);
        $studentGrade = Grade::create(['school_stage_id' => $stage->id, 'name' => '2º Primaria', 'sort_order' => 2]);

        $classroom = Classroom::create([
            'academic_year_id' => $this->activeYear->id,
            'grade_id' => $studentGrade->id,
            'name' => 'A',
        ]);
        $student->classrooms()->attach($classroom->id, ['enrolled_at' => now()]);
        $group->grades()->attach($allowedGrade->id);

        $this->actingAs($user)
            ->get(route('familia.activities.show', $activity))
            ->assertOk()
            ->assertSee('No disponible para su curso');
    }

    public function test_activities_show_shows_ampa_warning_for_non_member_family(): void
    {
        ['user' => $user] = $this->createFamilyUser(false);
        ['activity' => $activity] = $this->createPublishedActivity(['requires_ampa_membership' => true]);

        $this->actingAs($user)
            ->get(route('familia.activities.show', $activity))
            ->assertOk()
            ->assertSee('exclusiva para socios/as del AMPA');
    }

    // ─── Backend enrollment validation (POST) ────────────────────────────────

    private function createGrade(string $name, int $sortOrder = 1): Grade
    {
        $stage = SchoolStage::firstOrCreate(['name' => 'Primaria', 'sort_order' => 1]);

        return Grade::firstOrCreate(
            ['school_stage_id' => $stage->id, 'name' => $name],
            ['sort_order' => $sortOrder]
        );
    }

    private function attachClassroomToStudent(Student $student, Grade $grade): Classroom
    {
        $classroom = Classroom::create([
            'academic_year_id' => $this->activeYear->id,
            'grade_id' => $grade->id,
            'name' => 'A',
        ]);
        $student->classrooms()->attach($classroom->id, ['enrolled_at' => now()]);

        return $classroom;
    }

    public function test_enroll_post_blocked_and_no_enrollment_created_when_student_has_no_classroom(): void
    {
        ['user' => $user, 'student' => $student] = $this->createFamilyUser();
        // createFamilyUser() does NOT attach a classroom — by design for view tests
        ['group' => $group] = $this->createPublishedActivity();

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

    public function test_enroll_post_blocked_and_no_enrollment_created_when_grade_not_allowed(): void
    {
        ['user' => $user, 'student' => $student] = $this->createFamilyUser();
        ['group' => $group] = $this->createPublishedActivity();

        $allowedGrade = $this->createGrade('1º Primaria', 1);
        $studentGrade = $this->createGrade('2º Primaria', 2);

        $this->attachClassroomToStudent($student, $studentGrade);
        $group->grades()->attach($allowedGrade->id);

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

    public function test_enroll_post_succeeds_when_student_has_classroom_and_grade_is_permitted(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();
        ['group' => $group] = $this->createPublishedActivity();

        $grade = $this->createGrade('1º Primaria');
        $this->attachClassroomToStudent($student, $grade);
        $group->grades()->attach($grade->id);

        $response = $this->actingAs($user)
            ->post(route('familia.enroll'), [
                'student_id' => $student->id,
                'activity_group_id' => $group->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionMissing('error');
        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->id,
            'family_id' => $family->id,
            'activity_group_id' => $group->id,
        ]);
    }

    public function test_enroll_post_succeeds_when_student_has_classroom_and_group_has_no_grade_restriction(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();
        ['group' => $group] = $this->createPublishedActivity();
        // Group has no grades attached — open to all

        $grade = $this->createGrade('1º Primaria');
        $this->attachClassroomToStudent($student, $grade);

        $response = $this->actingAs($user)
            ->post(route('familia.enroll'), [
                'student_id' => $student->id,
                'activity_group_id' => $group->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionMissing('error');
        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->id,
            'family_id' => $family->id,
            'activity_group_id' => $group->id,
        ]);
    }
}
