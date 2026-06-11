<?php

namespace Tests\Feature;

use App\Enums\EnrollmentStatus;
use App\Enums\PriceType;
use App\Models\AcademicYear;
use App\Models\ActivityGroup;
use App\Models\Enrollment;
use App\Models\ExtracurricularActivity;
use App\Models\Family;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentResourcesTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->superAdmin = User::factory()->create([
            'email' => 'test-admin@ampa.test',
            'email_verified_at' => now(),
        ]);
        $this->superAdmin->assignRole('super_admin');
    }

    // ── Listados ──────────────────────────────────────────────────────────────

    public function test_super_admin_can_list_academic_years(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/academic-years')
            ->assertOk();
    }

    public function test_super_admin_can_list_school_stages(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/school-stages')
            ->assertOk();
    }

    public function test_super_admin_can_list_grades(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/grades')
            ->assertOk();
    }

    public function test_super_admin_can_list_classrooms(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/classrooms')
            ->assertOk();
    }

    public function test_super_admin_can_list_families(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/families')
            ->assertOk();
    }

    public function test_super_admin_can_list_guardians(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/guardians')
            ->assertOk();
    }

    public function test_super_admin_can_list_students(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/students')
            ->assertOk();
    }

    // ── Pantallas de creación ─────────────────────────────────────────────────

    public function test_super_admin_can_access_create_academic_year(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/academic-years/create')
            ->assertOk();
    }

    public function test_super_admin_can_access_create_school_stage(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/school-stages/create')
            ->assertOk();
    }

    public function test_super_admin_can_access_create_grade(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/grades/create')
            ->assertOk();
    }

    public function test_super_admin_can_access_create_classroom(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/classrooms/create')
            ->assertOk();
    }

    public function test_super_admin_can_access_create_family(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/families/create')
            ->assertOk();
    }

    public function test_super_admin_can_access_create_guardian(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/guardians/create')
            ->assertOk();
    }

    public function test_super_admin_can_access_create_student(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/students/create')
            ->assertOk();
    }

    // ── Fase 2: Actividades extraescolares ────────────────────────────────────

    public function test_super_admin_can_list_extracurricular_activities(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/extracurricular-activities')
            ->assertOk();
    }

    public function test_super_admin_can_access_create_extracurricular_activity(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/extracurricular-activities/create')
            ->assertOk();
    }

    public function test_super_admin_can_access_edit_extracurricular_activity_with_relation_manager(): void
    {
        $activity = ExtracurricularActivity::factory()->create();

        $this->actingAs($this->superAdmin)
            ->get("/admin/extracurricular-activities/{$activity->id}/edit")
            ->assertOk();
    }

    public function test_edit_activity_page_loads_enrollments_relation_manager_with_data(): void
    {
        $activity = ExtracurricularActivity::factory()->create();
        $group = ActivityGroup::factory()->create([
            'activity_id' => $activity->id,
            'max_spots' => 10,
        ]);
        $family = Family::factory()->create();
        $student = Student::factory()->create(['family_id' => $family->id]);
        $academicYear = AcademicYear::factory()->create();

        Enrollment::create([
            'student_id' => $student->id,
            'family_id' => $family->id,
            'activity_id' => $activity->id,
            'activity_group_id' => $group->id,
            'academic_year_id' => $academicYear->id,
            'status' => EnrollmentStatus::Enrolled,
            'registered_at' => now(),
            'enrolled_at' => now(),
            'amount' => 30.00,
            'price_type' => PriceType::Member,
        ]);

        $this->actingAs($this->superAdmin)
            ->get("/admin/extracurricular-activities/{$activity->id}/edit")
            ->assertOk();
    }

    // ── Fase 2: Inscripciones ─────────────────────────────────────────────────

    public function test_super_admin_can_list_enrollments(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/enrollments')
            ->assertOk();
    }

    public function test_super_admin_can_access_create_enrollment(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/enrollments/create')
            ->assertOk();
    }

    public function test_super_admin_can_access_edit_enrollment(): void
    {
        $family = Family::factory()->create();
        $student = Student::factory()->create(['family_id' => $family->id]);
        $activity = ExtracurricularActivity::factory()->create();
        $group = ActivityGroup::factory()->create(['activity_id' => $activity->id]);
        $academicYear = AcademicYear::factory()->create();

        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'family_id' => $family->id,
            'activity_id' => $activity->id,
            'activity_group_id' => $group->id,
            'academic_year_id' => $academicYear->id,
            'status' => EnrollmentStatus::Enrolled,
            'registered_at' => now(),
            'enrolled_at' => now(),
            'amount' => 30.00,
            'price_type' => PriceType::Member,
        ]);

        $this->actingAs($this->superAdmin)
            ->get("/admin/enrollments/{$enrollment->id}/edit")
            ->assertOk();
    }

    // ── Control de acceso ─────────────────────────────────────────────────────

    public function test_familia_role_cannot_access_filament(): void
    {
        $familiaUser = User::factory()->create(['email_verified_at' => now()]);
        $familiaUser->assignRole('familia');

        $this->actingAs($familiaUser)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_unauthenticated_user_is_redirected_from_admin(): void
    {
        $this->get('/admin/families')
            ->assertRedirect();
    }
}
