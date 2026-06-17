<?php

namespace Tests\Feature;

use App\Enums\ActivityGroupStatus;
use App\Enums\ActivityStatus;
use App\Enums\ConsentResponseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\FormStatus;
use App\Models\AcademicYear;
use App\Models\ActivityGroup;
use App\Models\Classroom;
use App\Models\ConsentResponse;
use App\Models\ConsentType;
use App\Models\ConsentVersion;
use App\Models\Enrollment;
use App\Models\ExtracurricularActivity;
use App\Models\Family;
use App\Models\Form;
use App\Models\FormResponse;
use App\Models\Grade;
use App\Models\Guardian;
use App\Models\SchoolStage;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyDashboardTest extends TestCase
{
    use RefreshDatabase;

    private AcademicYear $activeYear;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->activeYear = AcademicYear::factory()->create(['is_active' => true]);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * @return array{user: User, family: Family, student: Student}
     */
    private function createFamilyUser(): array
    {
        $user = User::factory()->create();
        $user->assignRole('familia');

        $family = Family::factory()->create(['is_ampa_member' => true]);
        Guardian::factory()->create(['user_id' => $user->id, 'family_id' => $family->id]);
        $student = Student::factory()->create(['family_id' => $family->id, 'is_active' => true]);

        $stage = SchoolStage::firstOrCreate(['name' => 'General', 'sort_order' => 99]);
        $grade = Grade::firstOrCreate(['school_stage_id' => $stage->id, 'name' => 'General'], ['sort_order' => 99]);
        $classroom = Classroom::firstOrCreate(['academic_year_id' => $this->activeYear->id, 'grade_id' => $grade->id, 'name' => 'General']);
        $student->classrooms()->attach($classroom->id, ['enrolled_at' => now()]);

        return compact('user', 'family', 'student');
    }

    /**
     * @return array{activity: ExtracurricularActivity, group: ActivityGroup}
     */
    private function createPublishedActivity(): array
    {
        $activity = ExtracurricularActivity::factory()->create([
            'academic_year_id' => $this->activeYear->id,
            'status' => ActivityStatus::Published,
            'is_visible_for_families' => true,
            'requires_ampa_membership' => false,
        ]);

        $group = ActivityGroup::factory()->create([
            'activity_id' => $activity->id,
            'max_spots' => 10,
            'status' => ActivityGroupStatus::Open,
        ]);

        return compact('activity', 'group');
    }

    private function createEnrollment(Family $family, Student $student, ActivityGroup $group, EnrollmentStatus $status): Enrollment
    {
        return Enrollment::factory()->create([
            'student_id' => $student->id,
            'family_id' => $family->id,
            'activity_id' => $group->activity_id,
            'activity_group_id' => $group->id,
            'academic_year_id' => $this->activeYear->id,
            'status' => $status,
        ]);
    }

    // ─── Empty / all-clear states ────────────────────────────────────────────

    public function test_dashboard_renders_with_all_clear_message_when_nothing_pending(): void
    {
        ['user' => $user] = $this->createFamilyUser();

        $this->actingAs($user)
            ->get(route('familia.dashboard'))
            ->assertOk()
            ->assertSee('Todo al día');
    }

    public function test_dashboard_shows_empty_enrollments_state_when_none(): void
    {
        ['user' => $user] = $this->createFamilyUser();

        $this->actingAs($user)
            ->get(route('familia.dashboard'))
            ->assertOk()
            ->assertSee('Todavía no tienes inscripciones');
    }

    public function test_dashboard_renders_for_family_without_students(): void
    {
        $user = User::factory()->create();
        $user->assignRole('familia');
        $family = Family::factory()->create(['is_ampa_member' => false]);
        Guardian::factory()->create(['user_id' => $user->id, 'family_id' => $family->id]);

        $this->actingAs($user)
            ->get(route('familia.dashboard'))
            ->assertOk();
    }

    // ─── Enrollment counts & states ──────────────────────────────────────────

    public function test_dashboard_shows_pending_request_notice_for_pending_enrollment(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();
        ['group' => $group] = $this->createPublishedActivity();
        $this->createEnrollment($family, $student, $group, EnrollmentStatus::Pending);

        $this->actingAs($user)
            ->get(route('familia.dashboard'))
            ->assertOk()
            ->assertSee('Solicitudes enviadas')
            ->assertSee('Solicitud enviada');
    }

    public function test_dashboard_shows_enrolled_and_waitlist_counts(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();
        ['group' => $group] = $this->createPublishedActivity();
        $this->createEnrollment($family, $student, $group, EnrollmentStatus::Enrolled);
        $this->createEnrollment($family, $student, $group, EnrollmentStatus::Waitlist);

        $this->actingAs($user)
            ->get(route('familia.dashboard'))
            ->assertOk()
            ->assertSee('Inscrito')
            ->assertSee('lista de espera');
    }

    public function test_dashboard_shows_pending_payment_state(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();
        ['group' => $group] = $this->createPublishedActivity();
        $this->createEnrollment($family, $student, $group, EnrollmentStatus::PendingPayment);

        $this->actingAs($user)
            ->get(route('familia.dashboard'))
            ->assertOk()
            ->assertSee('Pendiente de pago');
    }

    public function test_dashboard_shows_paid_as_pago_registrado(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();
        ['group' => $group] = $this->createPublishedActivity();
        $this->createEnrollment($family, $student, $group, EnrollmentStatus::Paid);

        $this->actingAs($user)
            ->get(route('familia.dashboard'))
            ->assertOk()
            ->assertSee('Pago registrado');
    }

    // ─── Pending forms counter ───────────────────────────────────────────────

    public function test_dashboard_shows_pending_forms_notice_when_unanswered(): void
    {
        ['user' => $user] = $this->createFamilyUser();

        Form::factory()->create([
            'academic_year_id' => $this->activeYear->id,
            'status' => FormStatus::Published,
        ]);

        $this->actingAs($user)
            ->get(route('familia.dashboard'))
            ->assertOk()
            ->assertSee('Formularios pendientes');
    }

    public function test_dashboard_hides_pending_forms_notice_when_answered(): void
    {
        ['user' => $user, 'family' => $family] = $this->createFamilyUser();

        $form = Form::factory()->create([
            'academic_year_id' => $this->activeYear->id,
            'status' => FormStatus::Published,
        ]);

        FormResponse::create([
            'form_id' => $form->id,
            'family_id' => $family->id,
            'student_id' => null,
            'response_key' => 'family:'.$family->id,
            'submitted_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('familia.dashboard'))
            ->assertOk()
            ->assertDontSee('Formularios pendientes');
    }

    // ─── Pending consents counter ────────────────────────────────────────────

    public function test_dashboard_shows_pending_consents_notice(): void
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

    // ─── Activities flow explanation ─────────────────────────────────────────

    public function test_activities_index_shows_flow_explanation(): void
    {
        ['user' => $user] = $this->createFamilyUser();
        $this->createPublishedActivity();

        $this->actingAs($user)
            ->get(route('familia.activities.index'))
            ->assertOk()
            ->assertSee('no reserva plaza hasta que el AMPA la confirme');
    }
}
