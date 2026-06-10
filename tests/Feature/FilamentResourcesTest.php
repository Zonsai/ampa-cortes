<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FilamentResourcesTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);

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
