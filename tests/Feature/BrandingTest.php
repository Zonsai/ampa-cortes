<?php

namespace Tests\Feature;

use App\Filament\Pages\BrandingSettings;
use App\Models\AppSetting;
use App\Models\Family;
use App\Models\Guardian;
use App\Models\User;
use App\Services\AppSettings;
use Database\Seeders\AppSettingsSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class BrandingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Cache::flush();
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function createAdminUser(string $role): User
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

    // ─── Service: defaults ────────────────────────────────────────────────────

    public function test_defaults_returned_when_no_records_exist(): void
    {
        $settings = AppSettings::all();

        $this->assertSame('AMPA Cortés de Aragón', $settings['ampa_name']);
        $this->assertSame('CEIP Cortés de Aragón', $settings['school_name']);
        $this->assertSame('#4f46e5', $settings['primary_color']);
        $this->assertSame('#0f766e', $settings['accent_color']);
        $this->assertNull($settings['ampa_logo_path']);
        $this->assertNull($settings['school_logo_path']);
    }

    public function test_settings_read_from_database_when_set(): void
    {
        AppSetting::create(['key' => 'ampa_name', 'value' => 'AMPA Test', 'type' => 'text']);

        AppSettings::clearCache();

        $this->assertSame('AMPA Test', AppSettings::get('ampa_name'));
    }

    public function test_set_creates_new_setting(): void
    {
        AppSettings::set('ampa_name', 'Nuevo AMPA');

        $this->assertDatabaseHas('app_settings', ['key' => 'ampa_name', 'value' => 'Nuevo AMPA']);
    }

    public function test_set_updates_existing_setting(): void
    {
        AppSetting::create(['key' => 'ampa_name', 'value' => 'Primero', 'type' => 'text']);
        AppSettings::set('ampa_name', 'Actualizado');

        $this->assertDatabaseHas('app_settings', ['key' => 'ampa_name', 'value' => 'Actualizado']);
        $this->assertDatabaseCount('app_settings', 1);
    }

    public function test_set_invalidates_cache(): void
    {
        // Warm the cache
        AppSettings::all();
        AppSettings::set('ampa_name', 'Nombre nuevo');

        // After set, cache is cleared; next call reads fresh from DB
        $this->assertSame('Nombre nuevo', AppSettings::get('ampa_name'));
    }

    // ─── Seeder ───────────────────────────────────────────────────────────────

    public function test_seeder_creates_default_settings(): void
    {
        $this->seed(AppSettingsSeeder::class);

        $this->assertDatabaseHas('app_settings', ['key' => 'ampa_name', 'value' => 'AMPA Cortés de Aragón']);
        $this->assertDatabaseHas('app_settings', ['key' => 'school_name', 'value' => 'CEIP Cortés de Aragón']);
        $this->assertDatabaseHas('app_settings', ['key' => 'primary_color', 'value' => '#4f46e5']);
        $this->assertDatabaseHas('app_settings', ['key' => 'accent_color', 'value' => '#0f766e']);
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(AppSettingsSeeder::class);
        $this->seed(AppSettingsSeeder::class);

        $this->assertDatabaseCount('app_settings', 6);
    }

    public function test_seeder_does_not_overwrite_existing_custom_values(): void
    {
        AppSetting::create(['key' => 'ampa_name', 'value' => 'Mi AMPA personalizado', 'type' => 'text']);

        $this->seed(AppSettingsSeeder::class);

        // firstOrCreate: existing row must not be overwritten
        $this->assertDatabaseHas('app_settings', ['key' => 'ampa_name', 'value' => 'Mi AMPA personalizado']);
    }

    // ─── Admin page: access control ──────────────────────────────────────────

    public function test_super_admin_can_access_branding_settings(): void
    {
        $user = $this->createAdminUser('super_admin');

        $this->actingAs($user)->get('/admin/branding-settings')->assertOk();
    }

    public function test_junta_ampa_can_access_branding_settings(): void
    {
        $user = $this->createAdminUser('junta_ampa');

        $this->actingAs($user)->get('/admin/branding-settings')->assertOk();
    }

    public function test_admin_extraescolares_cannot_access_branding_settings(): void
    {
        $user = $this->createAdminUser('admin_extraescolares');

        $this->actingAs($user)->get('/admin/branding-settings')->assertForbidden();
    }

    public function test_admin_formularios_cannot_access_branding_settings(): void
    {
        $user = $this->createAdminUser('admin_formularios');

        $this->actingAs($user)->get('/admin/branding-settings')->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_branding_settings(): void
    {
        $this->get('/admin/branding-settings')->assertRedirect();
    }

    // ─── Admin page: saving settings ─────────────────────────────────────────

    public function test_super_admin_can_save_names_and_colors(): void
    {
        $user = $this->createAdminUser('super_admin');

        Livewire::actingAs($user)
            ->test(BrandingSettings::class)
            ->fillForm([
                'ampa_name' => 'AMPA Modificado',
                'school_name' => 'Colegio Modificado',
                'primary_color' => '#ff0000',
                'accent_color' => '#00ff00',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('app_settings', ['key' => 'ampa_name', 'value' => 'AMPA Modificado']);
        $this->assertDatabaseHas('app_settings', ['key' => 'school_name', 'value' => 'Colegio Modificado']);
    }

    public function test_invalid_hex_color_fails_validation(): void
    {
        $user = $this->createAdminUser('super_admin');

        Livewire::actingAs($user)
            ->test(BrandingSettings::class)
            ->fillForm([
                'ampa_name' => 'AMPA Test',
                'school_name' => 'Colegio Test',
                'primary_color' => 'not-a-color',
                'accent_color' => '#0f766e',
            ])
            ->call('save')
            ->assertHasFormErrors(['primary_color']);
    }

    // ─── Views: landing ───────────────────────────────────────────────────────

    public function test_landing_shows_ampa_name_from_settings(): void
    {
        AppSetting::create(['key' => 'ampa_name', 'value' => 'AMPA Vista Test', 'type' => 'text']);
        AppSettings::clearCache();

        $this->get('/')->assertOk()->assertSee('AMPA Vista Test');
    }

    public function test_landing_shows_school_name_from_settings(): void
    {
        AppSetting::create(['key' => 'school_name', 'value' => 'Colegio Vista Test', 'type' => 'text']);
        AppSettings::clearCache();

        $this->get('/')->assertOk()->assertSee('Colegio Vista Test');
    }

    public function test_landing_does_not_break_without_logo(): void
    {
        $this->get('/')->assertOk();
    }

    // ─── Views: familia zone ──────────────────────────────────────────────────

    public function test_familia_login_shows_ampa_name_branding(): void
    {
        AppSetting::create(['key' => 'ampa_name', 'value' => 'AMPA Login Test', 'type' => 'text']);
        AppSettings::clearCache();

        $this->get('/familia/login')->assertOk()->assertSee('AMPA Login Test');
    }

    public function test_familia_layout_shows_branding_when_authenticated(): void
    {
        AppSetting::create(['key' => 'ampa_name', 'value' => 'AMPA Layout Test', 'type' => 'text']);
        AppSettings::clearCache();

        $user = $this->createFamilyUser();

        $this->actingAs($user)
            ->get('/familia')
            ->assertOk()
            ->assertSee('AMPA Layout Test');
    }

    public function test_familia_layout_does_not_break_without_logo(): void
    {
        $user = $this->createFamilyUser();

        $this->actingAs($user)->get('/familia')->assertOk();
    }

    public function test_landing_contains_css_variable_for_primary_color(): void
    {
        AppSetting::create(['key' => 'primary_color', 'value' => '#123456', 'type' => 'color']);
        AppSettings::clearCache();

        $this->get('/')->assertOk()->assertSee('#123456', false);
    }
}
