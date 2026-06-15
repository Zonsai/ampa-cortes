<?php

namespace Tests\Feature;

use App\Enums\ActivityGroupStatus;
use App\Enums\ActivityStatus;
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
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Hardening1Test extends TestCase
{
    use RefreshDatabase;

    private AcademicYear $activeYear;

    protected function setUp(): void
    {
        parent::setUp();
        // Flush rate-limiter state so throttle tests start clean.
        Cache::flush();
        $this->seed(RoleSeeder::class);
        $this->activeYear = AcademicYear::factory()->create(['is_active' => true]);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function createFamilyUser(): array
    {
        $user = User::factory()->create();
        $user->assignRole('familia');

        $family = Family::factory()->create(['is_ampa_member' => true]);
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

    // ─── Seeder security (4 tests) ──────────────────────────────────────────

    public function test_admin_user_seeder_aborts_outside_local_environment(): void
    {
        // APP_ENV = 'testing' in phpunit.xml — not 'local'
        (new AdminUserSeeder)->run();

        $this->assertDatabaseMissing('users', ['email' => 'admin@ampa.test']);
    }

    public function test_database_seeder_does_not_create_admin_user_outside_local(): void
    {
        // Reset DB state first (DatabaseSeeder runs RoleSeeder + AcademicYearSeeder)
        (new DatabaseSeeder)->run();

        $this->assertDatabaseMissing('users', ['email' => 'admin@ampa.test']);
    }

    public function test_role_seeder_creates_expected_roles_and_permissions(): void
    {
        $expectedRoles = ['super_admin', 'junta_ampa', 'admin_extraescolares', 'admin_formularios', 'familia'];

        foreach ($expectedRoles as $role) {
            $this->assertTrue(Role::where('name', $role)->exists(), "Rol '{$role}' debe existir");
        }

        $this->assertTrue(Permission::where('name', 'manage payments')->exists());
        $this->assertTrue(Permission::where('name', 'view enrollments')->exists());
        $this->assertTrue(Permission::where('name', 'manage enrollments')->exists());
    }

    public function test_role_seeder_is_idempotent(): void
    {
        $countBefore = Role::count();

        $this->seed(RoleSeeder::class);

        $this->assertSame($countBefore, Role::count());
    }

    // ─── ampa:create-admin command (7 tests) ────────────────────────────────

    public function test_create_admin_command_creates_super_admin_user(): void
    {
        $this->artisan('ampa:create-admin', [
            '--email' => 'newadmin@ampa.test',
            '--name' => 'Nuevo Admin',
            '--password' => 'securepassword',
        ])->assertExitCode(0);

        $this->assertDatabaseHas('users', ['email' => 'newadmin@ampa.test']);
    }

    public function test_create_admin_command_assigns_super_admin_role(): void
    {
        $this->artisan('ampa:create-admin', [
            '--email' => 'newadmin@ampa.test',
            '--name' => 'Nuevo Admin',
            '--password' => 'securepassword',
        ])->assertExitCode(0);

        $user = User::where('email', 'newadmin@ampa.test')->first();
        $this->assertTrue($user->hasRole('super_admin'));
    }

    public function test_create_admin_command_hashes_password(): void
    {
        $this->artisan('ampa:create-admin', [
            '--email' => 'newadmin@ampa.test',
            '--name' => 'Nuevo Admin',
            '--password' => 'securepassword',
        ])->assertExitCode(0);

        $user = User::where('email', 'newadmin@ampa.test')->first();
        $this->assertNotSame('securepassword', $user->password);
        $this->assertTrue(Hash::check('securepassword', $user->password));
    }

    public function test_create_admin_command_does_not_duplicate_existing_user(): void
    {
        User::factory()->create(['email' => 'existing@ampa.test']);

        $this->artisan('ampa:create-admin', [
            '--email' => 'existing@ampa.test',
            '--name' => 'Admin',
            '--password' => 'securepassword',
        ])->assertExitCode(1);

        $this->assertSame(1, User::where('email', 'existing@ampa.test')->count());
    }

    public function test_create_admin_command_updates_existing_user_with_force(): void
    {
        User::factory()->create(['email' => 'existing@ampa.test', 'name' => 'Nombre Antiguo']);

        $this->artisan('ampa:create-admin', [
            '--email' => 'existing@ampa.test',
            '--name' => 'Nombre Nuevo',
            '--password' => 'nuevapassword123',
            '--force' => true,
        ])->assertExitCode(0);

        $this->assertDatabaseHas('users', ['email' => 'existing@ampa.test', 'name' => 'Nombre Nuevo']);
        $this->assertSame(1, User::where('email', 'existing@ampa.test')->count());
    }

    public function test_create_admin_command_fails_if_super_admin_role_not_seeded(): void
    {
        Role::where('name', 'super_admin')->delete();

        $this->artisan('ampa:create-admin', [
            '--email' => 'admin@ampa.test',
            '--name' => 'Admin',
            '--password' => 'securepassword',
        ])->assertExitCode(1);
    }

    public function test_create_admin_command_validates_password_minimum_length(): void
    {
        $this->artisan('ampa:create-admin', [
            '--email' => 'admin@ampa.test',
            '--name' => 'Admin',
            '--password' => 'short',
        ])->assertExitCode(1);

        $this->assertDatabaseMissing('users', ['email' => 'admin@ampa.test']);
    }

    // ─── Login throttle (2 tests) ────────────────────────────────────────────

    public function test_familia_login_works_normally_before_throttle_kicks_in(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);
        $user->assignRole('familia');
        $family = Family::factory()->create();
        Guardian::factory()->create(['user_id' => $user->id, 'family_id' => $family->id]);

        $response = $this->post(route('familia.login.submit'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('familia.dashboard'));
    }

    public function test_familia_login_is_throttled_after_excess_attempts(): void
    {
        // 5 failed attempts exhaust the throttle:5,1 limit
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('familia.login.submit'), [
                'email' => 'noexiste@example.com',
                'password' => 'wrongpassword',
            ]);
        }

        // 6th attempt must be rejected by the throttle middleware
        $response = $this->post(route('familia.login.submit'), [
            'email' => 'noexiste@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(429);
    }

    // ─── Familia zone — soft-deleted family (1 test) ────────────────────────

    public function test_user_with_soft_deleted_family_is_redirected_with_error(): void
    {
        $user = User::factory()->create();
        $user->assignRole('familia');
        $family = Family::factory()->create();
        Guardian::factory()->create(['user_id' => $user->id, 'family_id' => $family->id]);

        // Soft-delete the family
        $family->delete();

        $response = $this->actingAs($user)->get(route('familia.dashboard'));

        $response->assertRedirect(route('familia.login'));
        $response->assertSessionHas('error');
    }

    // ─── Familia zone — inactive year enrollment (3 tests) ──────────────────

    public function test_family_cannot_enroll_in_activity_from_inactive_year(): void
    {
        ['user' => $user, 'student' => $student] = $this->createFamilyUser();

        $inactiveYear = AcademicYear::factory()->create(['is_active' => false]);
        $activity = ExtracurricularActivity::factory()->create([
            'academic_year_id' => $inactiveYear->id,
            'status' => ActivityStatus::Published,
            'is_visible_for_families' => true,
            'requires_ampa_membership' => false,
        ]);
        $group = ActivityGroup::factory()->create([
            'activity_id' => $activity->id,
            'status' => ActivityGroupStatus::Open,
            'max_spots' => 10,
        ]);

        $response = $this->actingAs($user)
            ->from(route('familia.activities.index'))
            ->post(route('familia.enroll'), [
                'student_id' => $student->id,
                'activity_group_id' => $group->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_enrollment_blocked_by_inactive_year_does_not_create_record(): void
    {
        ['user' => $user, 'student' => $student] = $this->createFamilyUser();

        $inactiveYear = AcademicYear::factory()->create(['is_active' => false]);
        $activity = ExtracurricularActivity::factory()->create([
            'academic_year_id' => $inactiveYear->id,
            'status' => ActivityStatus::Published,
            'is_visible_for_families' => true,
            'requires_ampa_membership' => false,
        ]);
        $group = ActivityGroup::factory()->create([
            'activity_id' => $activity->id,
            'status' => ActivityGroupStatus::Open,
            'max_spots' => 10,
        ]);

        $this->actingAs($user)->post(route('familia.enroll'), [
            'student_id' => $student->id,
            'activity_group_id' => $group->id,
        ]);

        $this->assertDatabaseMissing('enrollments', [
            'student_id' => $student->id,
            'activity_group_id' => $group->id,
        ]);
    }

    public function test_family_can_enroll_in_activity_from_active_year(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();

        $activity = ExtracurricularActivity::factory()->create([
            'academic_year_id' => $this->activeYear->id,
            'status' => ActivityStatus::Published,
            'is_visible_for_families' => true,
            'requires_ampa_membership' => false,
        ]);
        $group = ActivityGroup::factory()->create([
            'activity_id' => $activity->id,
            'status' => ActivityGroupStatus::Open,
            'max_spots' => 10,
        ]);

        $this->actingAs($user)->post(route('familia.enroll'), [
            'student_id' => $student->id,
            'activity_group_id' => $group->id,
        ]);

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->id,
            'activity_group_id' => $group->id,
            'family_id' => $family->id,
        ]);
    }

    // ─── Enrollment policy (2 tests) ────────────────────────────────────────

    public function test_user_with_manage_enrollments_permission_can_update_enrollment(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage enrollments');
        $enrollment = Enrollment::factory()->create();

        $this->assertTrue($user->can('update', $enrollment));
    }

    public function test_user_without_manage_enrollments_cannot_update_enrollment(): void
    {
        $user = User::factory()->create();
        // No permissions assigned
        $enrollment = Enrollment::factory()->create();

        $this->assertFalse($user->can('update', $enrollment));
    }
}
