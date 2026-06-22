<?php

namespace Tests\Feature;

use App\Enums\AnnouncementAudience;
use App\Enums\AnnouncementStatus;
use App\Filament\Resources\Announcements\Pages\CreateAnnouncement;
use App\Filament\Resources\Announcements\Pages\ListAnnouncements;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\Family;
use App\Models\Guardian;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    // ── Front público ──────────────────────────────────────────────────────────

    public function test_home_loads_and_is_not_laravel_welcome(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Acceso familias')
            ->assertDontSee('Laravel');
    }

    public function test_about_page_loads(): void
    {
        $this->get('/ampa')->assertOk()->assertSee('El AMPA');
    }

    public function test_extracurriculars_page_loads_without_enrollment(): void
    {
        $this->get('/extraescolares')
            ->assertOk()
            ->assertSee('Acceso familias')
            ->assertDontSee('Solicitar plaza')
            ->assertDontSee(route('familia.enroll'));
    }

    public function test_contact_page_loads(): void
    {
        $this->get('/contacto')->assertOk()->assertSee('Contacto');
    }

    public function test_announcements_index_shows_published_public(): void
    {
        Announcement::factory()->create(['title' => 'Anuncio público visible']);

        $this->get('/anuncios')
            ->assertOk()
            ->assertSee('Anuncio público visible');
    }

    public function test_announcements_index_hides_drafts(): void
    {
        Announcement::factory()->draft()->create(['title' => 'Borrador oculto']);

        $this->get('/anuncios')->assertOk()->assertDontSee('Borrador oculto');
    }

    public function test_announcements_index_hides_expired(): void
    {
        Announcement::factory()->expired()->create(['title' => 'Anuncio caducado']);

        $this->get('/anuncios')->assertOk()->assertDontSee('Anuncio caducado');
    }

    public function test_announcements_index_hides_family_only(): void
    {
        Announcement::factory()->forFamilies()->create(['title' => 'Solo para familias']);

        $this->get('/anuncios')->assertOk()->assertDontSee('Solo para familias');
    }

    public function test_announcement_show_displays_published(): void
    {
        $announcement = Announcement::factory()->create([
            'title' => 'Detalle público',
            'slug' => 'detalle-publico',
            'content' => 'Contenido completo del anuncio.',
        ]);

        $this->get(route('public.announcements.show', $announcement))
            ->assertOk()
            ->assertSee('Detalle público')
            ->assertSee('Contenido completo del anuncio.');
    }

    public function test_announcement_show_returns_404_for_draft(): void
    {
        $announcement = Announcement::factory()->draft()->create(['slug' => 'borrador-404']);

        $this->get('/anuncios/borrador-404')->assertNotFound();
    }

    public function test_announcement_show_returns_404_for_family_only(): void
    {
        $announcement = Announcement::factory()->forFamilies()->create(['slug' => 'familias-404']);

        $this->get('/anuncios/familias-404')->assertNotFound();
    }

    // ── Admin (Filament) ───────────────────────────────────────────────────────

    public function test_super_admin_can_access_announcements_resource(): void
    {
        $this->actingAs($this->userWithRole('super_admin'))->get('/admin/announcements')->assertOk();
    }

    public function test_junta_ampa_can_access_announcements_resource(): void
    {
        $this->actingAs($this->userWithRole('junta_ampa'))->get('/admin/announcements')->assertOk();
    }

    public function test_admin_extraescolares_cannot_access_announcements_resource(): void
    {
        $this->actingAs($this->userWithRole('admin_extraescolares'))->get('/admin/announcements')->assertForbidden();
    }

    public function test_admin_formularios_cannot_access_announcements_resource(): void
    {
        $this->actingAs($this->userWithRole('admin_formularios'))->get('/admin/announcements')->assertForbidden();
    }

    public function test_familia_cannot_access_announcements_resource(): void
    {
        $user = $this->userWithRole('familia');
        $family = Family::factory()->create();
        Guardian::factory()->create(['user_id' => $user->id, 'family_id' => $family->id]);

        $this->actingAs($user)->get('/admin/announcements')->assertForbidden();
    }

    public function test_create_announcement_from_filament_works(): void
    {
        Livewire::actingAs($this->userWithRole('super_admin'))
            ->test(CreateAnnouncement::class)
            ->fillForm([
                'title' => 'Nuevo anuncio admin',
                'slug' => 'nuevo-anuncio-admin',
                'content' => 'Cuerpo del anuncio.',
                'status' => AnnouncementStatus::Draft->value,
                'audience' => AnnouncementAudience::Public->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('announcements', [
            'slug' => 'nuevo-anuncio-admin',
            'title' => 'Nuevo anuncio admin',
        ]);
    }

    public function test_publish_and_archive_actions_work(): void
    {
        $announcement = Announcement::factory()->draft()->create();

        Livewire::actingAs($this->userWithRole('super_admin'))
            ->test(ListAnnouncements::class)
            ->callTableAction('publicar', $announcement);

        $this->assertSame(AnnouncementStatus::Published, $announcement->fresh()->status);
        $this->assertNotNull($announcement->fresh()->published_at);

        Livewire::actingAs($this->userWithRole('super_admin'))
            ->test(ListAnnouncements::class)
            ->callTableAction('archivar', $announcement);

        $this->assertSame(AnnouncementStatus::Archived, $announcement->fresh()->status);
    }

    // ── Audit log ──────────────────────────────────────────────────────────────

    public function test_announcement_actions_create_audit_logs(): void
    {
        $admin = $this->userWithRole('super_admin');

        // Create
        Livewire::actingAs($admin)
            ->test(CreateAnnouncement::class)
            ->fillForm([
                'title' => 'Anuncio auditado',
                'slug' => 'anuncio-auditado',
                'content' => 'Contenido.',
                'status' => AnnouncementStatus::Draft->value,
                'audience' => AnnouncementAudience::Public->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('audit_logs', ['action' => AuditLog::ANNOUNCEMENT_CREATED]);

        $announcement = Announcement::where('slug', 'anuncio-auditado')->firstOrFail();

        // Publish + archive
        Livewire::actingAs($admin)->test(ListAnnouncements::class)->callTableAction('publicar', $announcement);
        Livewire::actingAs($admin)->test(ListAnnouncements::class)->callTableAction('archivar', $announcement->fresh());

        $this->assertDatabaseHas('audit_logs', ['action' => AuditLog::ANNOUNCEMENT_PUBLISHED]);
        $this->assertDatabaseHas('audit_logs', ['action' => AuditLog::ANNOUNCEMENT_ARCHIVED]);

        // Never stores full content.
        $log = AuditLog::where('action', AuditLog::ANNOUNCEMENT_CREATED)->latest('id')->first();
        $this->assertStringNotContainsString('Contenido.', json_encode($log->properties));
    }
}
