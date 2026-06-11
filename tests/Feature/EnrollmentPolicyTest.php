<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentPolicyTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $juntaUser;

    private User $adminExtraUser;

    private User $noRoleUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->superAdmin = User::factory()->create(['email_verified_at' => now()]);
        $this->superAdmin->assignRole('super_admin');

        $this->juntaUser = User::factory()->create(['email_verified_at' => now()]);
        $this->juntaUser->assignRole('junta_ampa');

        $this->adminExtraUser = User::factory()->create(['email_verified_at' => now()]);
        $this->adminExtraUser->assignRole('admin_extraescolares');

        $this->noRoleUser = User::factory()->create(['email_verified_at' => now()]);
    }

    public function test_super_admin_can_do_everything(): void
    {
        $enrollment = Enrollment::factory()->create();

        $this->assertTrue($this->superAdmin->can('viewAny', Enrollment::class));
        $this->assertTrue($this->superAdmin->can('create', Enrollment::class));
        $this->assertTrue($this->superAdmin->can('update', $enrollment));
        $this->assertTrue($this->superAdmin->can('delete', $enrollment));
        $this->assertTrue($this->superAdmin->can('forceDelete', $enrollment));
    }

    public function test_junta_ampa_can_manage_and_delete_enrollments(): void
    {
        $enrollment = Enrollment::factory()->create();

        $this->assertTrue($this->juntaUser->can('viewAny', Enrollment::class));
        $this->assertTrue($this->juntaUser->can('create', Enrollment::class));
        $this->assertTrue($this->juntaUser->can('update', $enrollment));
        $this->assertTrue($this->juntaUser->can('delete', $enrollment));
    }

    public function test_admin_extraescolares_can_manage_but_not_delete(): void
    {
        $enrollment = Enrollment::factory()->create();

        $this->assertTrue($this->adminExtraUser->can('viewAny', Enrollment::class));
        $this->assertTrue($this->adminExtraUser->can('create', Enrollment::class));
        $this->assertTrue($this->adminExtraUser->can('update', $enrollment));
        $this->assertFalse($this->adminExtraUser->can('delete', $enrollment));
    }

    public function test_user_without_role_cannot_access_enrollments(): void
    {
        $enrollment = Enrollment::factory()->create();

        $this->assertFalse($this->noRoleUser->can('viewAny', Enrollment::class));
        $this->assertFalse($this->noRoleUser->can('create', Enrollment::class));
        $this->assertFalse($this->noRoleUser->can('update', $enrollment));
        $this->assertFalse($this->noRoleUser->can('delete', $enrollment));
    }

    public function test_junta_ampa_cannot_force_delete_any(): void
    {
        $this->assertFalse($this->juntaUser->can('forceDeleteAny', Enrollment::class));
        $this->assertFalse($this->adminExtraUser->can('forceDeleteAny', Enrollment::class));
    }

    public function test_only_junta_ampa_and_super_admin_can_individually_force_delete(): void
    {
        $enrollment = Enrollment::factory()->create();

        $this->assertTrue($this->juntaUser->can('forceDelete', $enrollment));
        $this->assertFalse($this->adminExtraUser->can('forceDelete', $enrollment));
    }
}
