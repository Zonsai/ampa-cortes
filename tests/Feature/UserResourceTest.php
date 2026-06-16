<?php

namespace Tests\Feature;

use App\Filament\Resources\Guardians\Pages\ListGuardians;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Family;
use App\Models\Guardian;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super_admin');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function createUser(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function createFamilyUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('familia');
        $family = Family::factory()->create();
        Guardian::factory()->create(['user_id' => $user->id, 'family_id' => $family->id]);

        return $user;
    }

    // ─── Acceso al recurso ────────────────────────────────────────────────────

    public function test_super_admin_can_list_users(): void
    {
        $this->actingAs($this->superAdmin)->get('/admin/users')->assertOk();
    }

    public function test_junta_ampa_can_list_users(): void
    {
        $user = $this->createUser('junta_ampa');

        $this->actingAs($user)->get('/admin/users')->assertOk();
    }

    public function test_admin_extraescolares_cannot_access_users(): void
    {
        $user = $this->createUser('admin_extraescolares');

        $this->actingAs($user)->get('/admin/users')->assertForbidden();
    }

    public function test_admin_formularios_cannot_access_users(): void
    {
        $user = $this->createUser('admin_formularios');

        $this->actingAs($user)->get('/admin/users')->assertForbidden();
    }

    public function test_familia_cannot_access_filament_panel(): void
    {
        $user = $this->createFamilyUser();

        // Filament v4 returns 403 for authenticated users who fail canAccessPanel()
        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    // ─── Crear usuario ────────────────────────────────────────────────────────

    public function test_super_admin_can_create_admin_user(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test(CreateUser::class)
            ->fillForm([
                'name' => 'Nuevo Admin',
                'email' => 'nuevo@ampa.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'roles_input' => ['junta_ampa'],
                'is_active' => true,
                'is_email_verified' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', ['email' => 'nuevo@ampa.test']);
    }

    public function test_password_is_hashed_on_create(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test(CreateUser::class)
            ->fillForm([
                'name' => 'Test User',
                'email' => 'hashtest@ampa.test',
                'password' => 'plainpassword',
                'password_confirmation' => 'plainpassword',
                'roles_input' => ['junta_ampa'],
                'is_active' => true,
                'is_email_verified' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'hashtest@ampa.test')->first();
        $this->assertNotSame('plainpassword', $user->password);
        $this->assertTrue(Hash::check('plainpassword', $user->password));
    }

    public function test_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'existing@ampa.test']);

        Livewire::actingAs($this->superAdmin)
            ->test(CreateUser::class)
            ->fillForm([
                'name' => 'Duplicado',
                'email' => 'existing@ampa.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'roles_input' => ['junta_ampa'],
                'is_active' => true,
                'is_email_verified' => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['email']);
    }

    public function test_password_required_on_create(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test(CreateUser::class)
            ->fillForm([
                'name' => 'Sin Contraseña',
                'email' => 'nopass@ampa.test',
                'roles_input' => ['junta_ampa'],
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['password']);
    }

    public function test_password_optional_on_edit(): void
    {
        $user = User::factory()->create();
        $user->assignRole('junta_ampa');
        $originalHash = $user->password;

        Livewire::actingAs($this->superAdmin)
            ->test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm([
                'name' => 'Nombre Cambiado',
                'email' => $user->email,
                'roles_input' => ['junta_ampa'],
                'is_active' => true,
                'is_email_verified' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        // Password unchanged when field left empty
        $this->assertSame($originalHash, $user->fresh()->password);
        $this->assertSame('Nombre Cambiado', $user->fresh()->name);
    }

    public function test_roles_assigned_correctly_on_create(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test(CreateUser::class)
            ->fillForm([
                'name' => 'Admin Extra',
                'email' => 'extra@ampa.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'roles_input' => ['admin_extraescolares'],
                'is_active' => true,
                'is_email_verified' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $created = User::where('email', 'extra@ampa.test')->first();
        $this->assertTrue($created->hasRole('admin_extraescolares'));
        $this->assertFalse($created->hasRole('super_admin'));
    }

    // ─── Restricciones de roles ───────────────────────────────────────────────

    public function test_junta_ampa_cannot_assign_super_admin_role(): void
    {
        $junta = $this->createUser('junta_ampa');

        Livewire::actingAs($junta)
            ->test(CreateUser::class)
            ->fillForm([
                'name' => 'Intento Super',
                'email' => 'intento@ampa.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'roles_input' => ['super_admin'],
                'is_active' => true,
                'is_email_verified' => true,
            ])
            ->call('create');
        // super_admin is not in junta's allowed options — form rejects it or server filters it
        $created = User::where('email', 'intento@ampa.test')->first();
        $this->assertFalse($created?->hasRole('super_admin') ?? false);
    }

    public function test_junta_ampa_cannot_edit_super_admin(): void
    {
        $junta = $this->createUser('junta_ampa');

        $this->actingAs($junta)
            ->get("/admin/users/{$this->superAdmin->id}/edit")
            ->assertForbidden();
    }

    public function test_cannot_deactivate_last_super_admin(): void
    {
        // Only one super_admin exists (setUp)
        Livewire::actingAs($this->superAdmin)
            ->test(ListUsers::class)
            ->callTableAction('toggle_active', $this->superAdmin);

        // Should still be active
        $this->assertTrue($this->superAdmin->fresh()->is_active);
    }

    public function test_cannot_deactivate_last_super_admin_via_edit_form(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test(EditUser::class, ['record' => $this->superAdmin->id])
            ->fillForm([
                'is_active' => false,
                'roles_input' => ['super_admin'],
            ])
            ->call('save');

        // Edit is halted: still active
        $this->assertTrue($this->superAdmin->fresh()->is_active);
        $this->assertTrue($this->superAdmin->fresh()->hasRole('super_admin'));
    }

    public function test_cannot_remove_role_from_last_super_admin_via_edit_form(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test(EditUser::class, ['record' => $this->superAdmin->id])
            ->fillForm([
                'is_active' => true,
                'roles_input' => ['junta_ampa'],
            ])
            ->call('save');

        // Edit is halted: still super_admin
        $this->assertTrue($this->superAdmin->fresh()->hasRole('super_admin'));
    }

    public function test_can_deactivate_super_admin_when_another_active_one_exists(): void
    {
        $other = User::factory()->create();
        $other->assignRole('super_admin');

        Livewire::actingAs($this->superAdmin)
            ->test(ListUsers::class)
            ->callTableAction('toggle_active', $other);

        // A second active super_admin can be deactivated
        $this->assertFalse($other->fresh()->is_active);
        $this->assertTrue($this->superAdmin->fresh()->is_active);
    }

    public function test_bulk_delete_cannot_remove_last_active_super_admin(): void
    {
        // Only one super_admin exists (setUp). Selecting it must be blocked.
        Livewire::actingAs($this->superAdmin)
            ->test(ListUsers::class)
            ->callTableBulkAction('delete', [$this->superAdmin]);

        $this->assertDatabaseHas('users', ['id' => $this->superAdmin->id]);
    }

    public function test_bulk_delete_blocks_whole_selection_if_it_includes_last_super_admin(): void
    {
        $regular = $this->createUser('junta_ampa');

        // Selection mixes a regular user with the last active super_admin:
        // the entire action must be halted, deleting nobody.
        Livewire::actingAs($this->superAdmin)
            ->test(ListUsers::class)
            ->callTableBulkAction('delete', [$this->superAdmin, $regular]);

        $this->assertDatabaseHas('users', ['id' => $this->superAdmin->id]);
        $this->assertDatabaseHas('users', ['id' => $regular->id]);
    }

    public function test_bulk_delete_allowed_when_another_active_super_admin_remains(): void
    {
        $other = User::factory()->create();
        $other->assignRole('super_admin');

        // Deleting one super_admin while another active one remains is allowed.
        Livewire::actingAs($this->superAdmin)
            ->test(ListUsers::class)
            ->callTableBulkAction('delete', [$other]);

        $this->assertDatabaseMissing('users', ['id' => $other->id]);
        $this->assertDatabaseHas('users', ['id' => $this->superAdmin->id]);
    }

    // ─── Guardian / Familia ───────────────────────────────────────────────────

    public function test_can_create_familia_user_linked_to_guardian(): void
    {
        $family = Family::factory()->create();
        $guardian = Guardian::factory()->create(['family_id' => $family->id, 'user_id' => null]);

        Livewire::actingAs($this->superAdmin)
            ->test(CreateUser::class)
            ->fillForm([
                'name' => 'Tutor Test',
                'email' => 'tutor@ampa.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'roles_input' => ['familia'],
                'guardian_id' => $guardian->id,
                'is_active' => true,
                'is_email_verified' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'tutor@ampa.test')->first();
        $this->assertNotNull($user);
        $this->assertSame($user->id, $guardian->fresh()->user_id);
    }

    public function test_guardian_user_id_is_set_after_linking(): void
    {
        $family = Family::factory()->create();
        $guardian = Guardian::factory()->create(['family_id' => $family->id, 'user_id' => null]);

        Livewire::actingAs($this->superAdmin)
            ->test(CreateUser::class)
            ->fillForm([
                'name' => 'Tutor Vinculado',
                'email' => 'vinculado@ampa.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'roles_input' => ['familia'],
                'guardian_id' => $guardian->id,
                'is_active' => true,
                'is_email_verified' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertNotNull($guardian->fresh()->user_id);
    }

    public function test_guardian_already_linked_cannot_be_assigned_to_another_user(): void
    {
        $family = Family::factory()->create();
        $existingUser = $this->createFamilyUser();
        $guardian = Guardian::where('user_id', $existingUser->id)->first();

        // Attempt to link the same guardian to a new user via the form
        Livewire::actingAs($this->superAdmin)
            ->test(CreateUser::class)
            ->fillForm([
                'name' => 'Nuevo Tutor',
                'email' => 'nuevo2@ampa.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'roles_input' => ['familia'],
                'guardian_id' => $guardian->id,
                'is_active' => true,
                'is_email_verified' => true,
            ])
            ->call('create');
        // Already-linked guardian is not in options → form rejects it or server guard prevents re-link
        $this->assertSame($existingUser->id, $guardian->fresh()->user_id);
    }

    public function test_familia_user_without_guardian_does_not_break(): void
    {
        $user = User::factory()->create();
        $user->assignRole('familia');

        // Accessing family portal redirects (no guardian linked = middleware catches it)
        $this->actingAs($user)
            ->get('/familia')
            ->assertRedirect(route('familia.login'));
    }

    // ─── is_active ────────────────────────────────────────────────────────────

    public function test_inactive_user_cannot_login_in_familia(): void
    {
        $family = Family::factory()->create();
        $user = User::factory()->inactive()->create();
        $user->assignRole('familia');
        Guardian::factory()->create(['user_id' => $user->id, 'family_id' => $family->id]);

        $this->post(route('familia.login.submit'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect();

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_access_filament_panel(): void
    {
        $user = User::factory()->inactive()->create();
        $user->assignRole('junta_ampa');

        // Filament v4 returns 403 for authenticated users who fail canAccessPanel()
        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    // ─── Reset password ───────────────────────────────────────────────────────

    public function test_super_admin_can_reset_password(): void
    {
        $target = User::factory()->create();
        $target->assignRole('junta_ampa');

        Livewire::actingAs($this->superAdmin)
            ->test(ListUsers::class)
            ->callTableAction('reset_password', $target, data: [
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertTrue(Hash::check('newpassword123', $target->fresh()->password));
    }

    public function test_old_password_no_longer_works_after_reset(): void
    {
        $target = User::factory()->create(['password' => Hash::make('oldpassword')]);
        $target->assignRole('junta_ampa');

        Livewire::actingAs($this->superAdmin)
            ->test(ListUsers::class)
            ->callTableAction('reset_password', $target, data: [
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertFalse(Hash::check('oldpassword', $target->fresh()->password));
        $this->assertTrue(Hash::check('newpassword123', $target->fresh()->password));
    }

    // ─── Acción Guardian: Crear acceso familiar ───────────────────────────────

    public function test_create_family_access_action_creates_user(): void
    {
        $family = Family::factory()->create();
        $guardian = Guardian::factory()->create([
            'family_id' => $family->id,
            'user_id' => null,
            'email' => 'tutor.action@ampa.test',
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(ListGuardians::class)
            ->callTableAction('create_family_access', $guardian, data: [
                'name' => $guardian->full_name,
                'email' => 'tutor.action@ampa.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'is_email_verified' => true,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('users', ['email' => 'tutor.action@ampa.test']);
    }

    public function test_create_family_access_assigns_familia_role(): void
    {
        $family = Family::factory()->create();
        $guardian = Guardian::factory()->create([
            'family_id' => $family->id,
            'user_id' => null,
            'email' => 'role.action@ampa.test',
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(ListGuardians::class)
            ->callTableAction('create_family_access', $guardian, data: [
                'name' => $guardian->full_name,
                'email' => 'role.action@ampa.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'is_email_verified' => true,
            ])
            ->assertHasNoTableActionErrors();

        $user = User::where('email', 'role.action@ampa.test')->first();
        $this->assertTrue($user->hasRole('familia'));
    }

    public function test_create_family_access_links_guardian(): void
    {
        $family = Family::factory()->create();
        $guardian = Guardian::factory()->create([
            'family_id' => $family->id,
            'user_id' => null,
            'email' => 'link.action@ampa.test',
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(ListGuardians::class)
            ->callTableAction('create_family_access', $guardian, data: [
                'name' => $guardian->full_name,
                'email' => 'link.action@ampa.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'is_email_verified' => true,
            ])
            ->assertHasNoTableActionErrors();

        $user = User::where('email', 'link.action@ampa.test')->first();
        $this->assertSame($user->id, $guardian->fresh()->user_id);
    }

    public function test_create_family_access_does_not_duplicate_existing_email(): void
    {
        User::factory()->create(['email' => 'ya.existe@ampa.test']);

        $family = Family::factory()->create();
        $guardian = Guardian::factory()->create([
            'family_id' => $family->id,
            'user_id' => null,
            'email' => 'ya.existe@ampa.test',
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(ListGuardians::class)
            ->callTableAction('create_family_access', $guardian, data: [
                'name' => $guardian->full_name,
                'email' => 'ya.existe@ampa.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'is_email_verified' => true,
            ]);

        // Only one user with that email (no duplicate)
        $this->assertSame(1, User::where('email', 'ya.existe@ampa.test')->count());
        // Guardian still unlinked (action aborted)
        $this->assertNull($guardian->fresh()->user_id);
    }
}
