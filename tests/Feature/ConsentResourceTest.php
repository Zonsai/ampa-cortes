<?php

namespace Tests\Feature;

use App\Enums\ConsentEventType;
use App\Enums\ConsentResponseStatus;
use App\Enums\ConsentScope;
use App\Enums\ConsentTypeStatus;
use App\Filament\Resources\ConsentTypes\Pages\CreateConsentType;
use App\Filament\Resources\ConsentTypes\Pages\EditConsentType;
use App\Filament\Resources\ConsentTypes\Pages\ListConsentTypes;
use App\Filament\Resources\ConsentTypes\RelationManagers\ConsentResponsesRelationManager;
use App\Filament\Resources\ConsentTypes\RelationManagers\ConsentVersionsRelationManager;
use App\Models\ConsentHistory;
use App\Models\ConsentResponse;
use App\Models\ConsentType;
use App\Models\ConsentVersion;
use App\Models\Family;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ConsentResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $juntaAmpa;

    private User $adminFormularios;

    private User $adminExtraescolares;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->superAdmin = User::factory()->create(['email_verified_at' => now()]);
        $this->superAdmin->assignRole('super_admin');

        $this->juntaAmpa = User::factory()->create(['email_verified_at' => now()]);
        $this->juntaAmpa->assignRole('junta_ampa');

        $this->adminFormularios = User::factory()->create(['email_verified_at' => now()]);
        $this->adminFormularios->assignRole('admin_formularios');

        $this->adminExtraescolares = User::factory()->create(['email_verified_at' => now()]);
        $this->adminExtraescolares->assignRole('admin_extraescolares');
    }

    // ── Acceso a páginas ──────────────────────────────────────────────────────

    public function test_super_admin_can_list_consent_types(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/consent-types')
            ->assertOk();
    }

    public function test_super_admin_can_access_create_consent_type(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/consent-types/create')
            ->assertOk();
    }

    public function test_super_admin_can_access_edit_consent_type(): void
    {
        $type = ConsentType::factory()->create();

        $this->actingAs($this->superAdmin)
            ->get("/admin/consent-types/{$type->id}/edit")
            ->assertOk();
    }

    // ── Permisos ──────────────────────────────────────────────────────────────

    public function test_junta_ampa_can_access_consent_types(): void
    {
        $this->actingAs($this->juntaAmpa)
            ->get('/admin/consent-types')
            ->assertOk();
    }

    public function test_admin_formularios_can_access_consent_types(): void
    {
        $this->actingAs($this->adminFormularios)
            ->get('/admin/consent-types')
            ->assertOk();
    }

    public function test_admin_extraescolares_cannot_access_consent_types(): void
    {
        $this->actingAs($this->adminExtraescolares)
            ->get('/admin/consent-types')
            ->assertForbidden();
    }

    // ── Creación ──────────────────────────────────────────────────────────────

    public function test_consent_type_can_be_created(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(CreateConsentType::class)
            ->fillForm([
                'name' => 'Autorización excursión',
                'scope' => ConsentScope::PerFamily->value,
                'is_rejectable' => true,
                'is_revocable' => true,
                'sort_order' => 1,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('consent_types', [
            'name' => 'Autorización excursión',
            'scope' => ConsentScope::PerFamily->value,
            'status' => ConsentTypeStatus::Draft->value,
        ]);
    }

    // ── Relation managers ─────────────────────────────────────────────────────

    public function test_versions_relation_manager_loads(): void
    {
        $type = ConsentType::factory()->published()->create();
        $version = ConsentVersion::factory()->for($type)->published()->create(['version_number' => 1]);

        $this->actingAs($this->superAdmin);

        Livewire::test(ConsentVersionsRelationManager::class, [
            'ownerRecord' => $type,
            'pageClass' => EditConsentType::class,
        ])
            ->assertOk()
            ->assertCanSeeTableRecords([$version]);
    }

    public function test_responses_relation_manager_loads(): void
    {
        $type = ConsentType::factory()->published()->create();

        $this->actingAs($this->superAdmin);

        Livewire::test(ConsentResponsesRelationManager::class, [
            'ownerRecord' => $type,
            'pageClass' => EditConsentType::class,
        ])
            ->assertOk();
    }

    // ── Acción Publicar ───────────────────────────────────────────────────────

    public function test_publish_action_publishes_draft_type(): void
    {
        $type = ConsentType::factory()->create();
        $this->actingAs($this->superAdmin);

        Livewire::test(ListConsentTypes::class)
            ->callTableAction('publicar', $type, data: [
                'legal_text' => 'Este es el texto legal de prueba para el consentimiento.',
                'summary' => 'Resumen de prueba',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('consent_types', [
            'id' => $type->id,
            'status' => ConsentTypeStatus::Published->value,
        ]);

        $this->assertDatabaseHas('consent_versions', [
            'consent_type_id' => $type->id,
            'version_number' => 1,
            'legal_text' => 'Este es el texto legal de prueba para el consentimiento.',
        ]);
    }

    public function test_publish_action_not_visible_for_published_type(): void
    {
        $publishedType = ConsentType::factory()->published()->create();
        $this->actingAs($this->superAdmin);

        Livewire::test(ListConsentTypes::class)
            ->assertTableActionHidden('publicar', $publishedType);
    }

    // ── Acción Publicar nueva versión ─────────────────────────────────────────

    public function test_publish_new_version_action_creates_new_version(): void
    {
        $type = ConsentType::factory()->published()->create();
        ConsentVersion::factory()->for($type)->published()->create(['version_number' => 1]);
        $this->actingAs($this->superAdmin);

        Livewire::test(ListConsentTypes::class)
            ->callTableAction('publicar_nueva_version', $type, data: [
                'legal_text' => 'Texto legal actualizado para la versión 2.',
                'summary' => 'Cambios en la versión 2',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('consent_versions', [
            'consent_type_id' => $type->id,
            'version_number' => 2,
            'legal_text' => 'Texto legal actualizado para la versión 2.',
        ]);
    }

    public function test_publish_new_version_resets_accepted_responses_to_pending(): void
    {
        $type = ConsentType::factory()->published()->create();
        $family = Family::factory()->create();
        $v1 = ConsentVersion::factory()->for($type)->published()->create(['version_number' => 1]);

        $response = ConsentResponse::factory()->accepted()->create([
            'consent_type_id' => $type->id,
            'consent_version_id' => $v1->id,
            'family_id' => $family->id,
            'subject_key' => "family:{$family->id}",
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(ListConsentTypes::class)
            ->callTableAction('publicar_nueva_version', $type, data: [
                'legal_text' => 'Texto legal v2.',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('consent_responses', [
            'id' => $response->id,
            'status' => ConsentResponseStatus::Pending->value,
        ]);
    }

    // ── Respuestas en relation manager ────────────────────────────────────────

    public function test_responses_visible_in_relation_manager(): void
    {
        $type = ConsentType::factory()->published()->create();
        $family = Family::factory()->create();
        $version = ConsentVersion::factory()->for($type)->published()->create(['version_number' => 1]);

        $response = ConsentResponse::factory()->create([
            'consent_type_id' => $type->id,
            'consent_version_id' => $version->id,
            'family_id' => $family->id,
            'subject_key' => "family:{$family->id}",
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(ConsentResponsesRelationManager::class, [
            'ownerRecord' => $type,
            'pageClass' => EditConsentType::class,
        ])
            ->assertOk()
            ->assertCanSeeTableRecords([$response]);
    }

    public function test_response_history_action_exists_in_relation_manager(): void
    {
        $type = ConsentType::factory()->published()->create();
        $family = Family::factory()->create();
        $version = ConsentVersion::factory()->for($type)->published()->create(['version_number' => 1]);

        $response = ConsentResponse::factory()->accepted()->create([
            'consent_type_id' => $type->id,
            'consent_version_id' => $version->id,
            'family_id' => $family->id,
            'subject_key' => "family:{$family->id}",
        ]);

        ConsentHistory::create([
            'consent_response_id' => $response->id,
            'consent_version_id' => $version->id,
            'consent_type_id' => $type->id,
            'family_id' => $family->id,
            'student_id' => null,
            'event_type' => ConsentEventType::PendingCreated,
            'performed_by_id' => null,
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(ConsentResponsesRelationManager::class, [
            'ownerRecord' => $type,
            'pageClass' => EditConsentType::class,
        ])
            ->assertTableActionExists('ver_historial');
    }

    // ── Soft delete ───────────────────────────────────────────────────────────

    public function test_consent_type_can_be_soft_deleted(): void
    {
        $type = ConsentType::factory()->create();
        $this->actingAs($this->superAdmin);

        Livewire::test(ListConsentTypes::class)
            ->callTableBulkAction('delete', [$type]);

        $this->assertSoftDeleted('consent_types', ['id' => $type->id]);
    }
}
