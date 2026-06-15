<?php

namespace Tests\Feature;

use App\Actions\Consents\RevokeConsentAction;
use App\Enums\ConsentEventType;
use App\Enums\ConsentResponseStatus;
use App\Enums\ConsentScope;
use App\Enums\ConsentTypeStatus;
use App\Exports\Consents\ConsentHistoryExport;
use App\Exports\Consents\ConsentStatusExport;
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
use App\Models\Student;
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

    // ── Fase 5D: ConsentStatusExport ─────────────────────────────────────────

    public function test_consent_status_export_has_expected_headings(): void
    {
        $type = ConsentType::factory()->create();
        $export = new ConsentStatusExport($type);

        $headings = $export->headings();

        $this->assertContains('Tipo de consentimiento', $headings);
        $this->assertContains('Familia', $headings);
        $this->assertContains('Estado', $headings);
        $this->assertContains('Versión', $headings);
        $this->assertContains('Fecha respuesta', $headings);
        $this->assertContains('Fecha revocación', $headings);
        $this->assertContains('Respondido por', $headings);
    }

    public function test_consent_status_export_includes_family_row(): void
    {
        $type = ConsentType::factory()->published()->create(['name' => 'Fotos escolares']);
        $family = Family::factory()->create(['name' => 'García López']);
        $version = ConsentVersion::factory()->for($type)->published()->create(['version_number' => 1]);
        ConsentResponse::factory()->accepted()->create([
            'consent_type_id' => $type->id,
            'consent_version_id' => $version->id,
            'family_id' => $family->id,
            'subject_key' => "family:{$family->id}",
        ]);

        $export = new ConsentStatusExport($type);
        $rows = $export->query()->get();

        $this->assertCount(1, $rows);
        $mapped = $export->map($rows->first());

        $this->assertContains('Fotos escolares', $mapped);
        $this->assertContains('García López', $mapped);
        $this->assertContains('Aceptado', $mapped);
    }

    public function test_consent_status_export_includes_student_when_per_student(): void
    {
        $type = ConsentType::factory()->published()->perStudent()->create();
        $family = Family::factory()->create();
        $student = Student::factory()->create(['family_id' => $family->id, 'is_active' => true, 'first_name' => 'Laura', 'last_name' => 'Sánchez']);
        $version = ConsentVersion::factory()->for($type)->published()->create(['version_number' => 1]);
        ConsentResponse::factory()->accepted()->create([
            'consent_type_id' => $type->id,
            'consent_version_id' => $version->id,
            'family_id' => $family->id,
            'student_id' => $student->id,
            'subject_key' => "student:{$student->id}",
        ]);

        $export = new ConsentStatusExport($type);
        $rows = $export->query()->get();
        $mapped = $export->map($rows->first());

        $this->assertTrue(collect($mapped)->contains(fn ($v) => str_contains((string) ($v ?? ''), 'Sánchez')));
    }

    public function test_consent_status_export_shows_all_statuses(): void
    {
        $type = ConsentType::factory()->published()->create();
        $version = ConsentVersion::factory()->for($type)->published()->create(['version_number' => 1]);

        foreach ([
            ConsentResponseStatus::Pending,
            ConsentResponseStatus::Accepted,
            ConsentResponseStatus::Rejected,
            ConsentResponseStatus::Revoked,
        ] as $status) {
            $family = Family::factory()->create();
            ConsentResponse::factory()->create([
                'consent_type_id' => $type->id,
                'consent_version_id' => $version->id,
                'family_id' => $family->id,
                'subject_key' => "family:{$family->id}",
                'status' => $status,
                'responded_at' => $status !== ConsentResponseStatus::Pending ? now() : null,
                'revoked_at' => $status === ConsentResponseStatus::Revoked ? now() : null,
            ]);
        }

        $export = new ConsentStatusExport($type);
        $rows = $export->query()->get();

        $this->assertCount(4, $rows);
        $statuses = $rows->map(fn ($r) => $export->map($r)[8])->values()->toArray();
        $this->assertContains('Pendiente', $statuses);
        $this->assertContains('Aceptado', $statuses);
        $this->assertContains('Rechazado', $statuses);
        $this->assertContains('Revocado', $statuses);
    }

    // ── Fase 5D: ConsentHistoryExport ────────────────────────────────────────

    public function test_consent_history_export_has_expected_headings(): void
    {
        $type = ConsentType::factory()->create();
        $export = new ConsentHistoryExport($type);

        $headings = $export->headings();

        $this->assertContains('Tipo de consentimiento', $headings);
        $this->assertContains('Familia', $headings);
        $this->assertContains('Evento', $headings);
        $this->assertContains('IP', $headings);
        $this->assertContains('User Agent', $headings);
        $this->assertContains('Notas', $headings);
        $this->assertContains('Fecha evento', $headings);
    }

    public function test_consent_history_export_includes_events(): void
    {
        $type = ConsentType::factory()->published()->create(['name' => 'Actividades']);
        $family = Family::factory()->create(['name' => 'Martínez Ruiz']);
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
            'event_type' => ConsentEventType::Accepted,
            'performed_by_id' => null,
            'ip_address' => '10.0.0.1',
            'user_agent' => 'Mozilla/5.0',
        ]);

        $export = new ConsentHistoryExport($type);
        $rows = $export->query()->get();

        $this->assertCount(1, $rows);
        $mapped = $export->map($rows->first());

        $this->assertContains('Actividades', $mapped);
        $this->assertContains('Martínez Ruiz', $mapped);
        $this->assertContains('Aceptado', $mapped);
    }

    public function test_consent_history_export_includes_ip_and_user_agent(): void
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

        ConsentHistory::create([
            'consent_response_id' => $response->id,
            'consent_version_id' => $version->id,
            'consent_type_id' => $type->id,
            'family_id' => $family->id,
            'student_id' => null,
            'event_type' => ConsentEventType::PendingCreated,
            'performed_by_id' => null,
            'ip_address' => '192.168.1.50',
            'user_agent' => 'Chrome/120',
            'notes' => null,
        ]);

        $export = new ConsentHistoryExport($type);
        $mapped = $export->map($export->query()->first());

        $this->assertContains('192.168.1.50', $mapped);
        $this->assertContains('Chrome/120', $mapped);
    }

    // ── Fase 5D: Acciones de export en Filament ───────────────────────────────

    public function test_export_estado_action_exists_in_consent_types_table(): void
    {
        ConsentType::factory()->published()->create();
        $this->actingAs($this->superAdmin);

        Livewire::test(ListConsentTypes::class)
            ->assertTableActionExists('exportar_estado');
    }

    public function test_export_historico_action_exists_in_consent_types_table(): void
    {
        ConsentType::factory()->published()->create();
        $this->actingAs($this->superAdmin);

        Livewire::test(ListConsentTypes::class)
            ->assertTableActionExists('exportar_historico');
    }

    // ── Fase 5D: requires_image_review ───────────────────────────────────────

    public function test_requires_image_review_can_be_saved_on_consent_type(): void
    {
        $type = ConsentType::factory()->create(['requires_image_review' => true]);

        $this->assertDatabaseHas('consent_types', [
            'id' => $type->id,
            'requires_image_review' => true,
        ]);
        $this->assertTrue($type->fresh()->requires_image_review);
    }

    public function test_requires_image_review_defaults_to_false(): void
    {
        $type = ConsentType::factory()->create();

        $this->assertFalse($type->fresh()->requires_image_review);
    }

    public function test_revoking_consent_with_image_review_adds_note_to_history(): void
    {
        $type = ConsentType::factory()->create(['is_revocable' => true, 'requires_image_review' => true]);
        $version = ConsentVersion::factory()->for($type)->published()->create(['version_number' => 1]);
        $family = Family::factory()->create();

        $response = ConsentResponse::factory()->accepted()->create([
            'consent_type_id' => $type->id,
            'consent_version_id' => $version->id,
            'family_id' => $family->id,
            'subject_key' => "family:{$family->id}",
        ]);

        app(RevokeConsentAction::class)->execute($response, $family);

        $this->assertDatabaseHas('consent_histories', [
            'consent_response_id' => $response->id,
            'event_type' => ConsentEventType::Revoked->value,
            'notes' => RevokeConsentAction::IMAGE_REVIEW_NOTE,
        ]);
    }

    public function test_revoking_consent_without_image_review_does_not_add_note(): void
    {
        $type = ConsentType::factory()->create(['is_revocable' => true, 'requires_image_review' => false]);
        $version = ConsentVersion::factory()->for($type)->published()->create(['version_number' => 1]);
        $family = Family::factory()->create();

        $response = ConsentResponse::factory()->accepted()->create([
            'consent_type_id' => $type->id,
            'consent_version_id' => $version->id,
            'family_id' => $family->id,
            'subject_key' => "family:{$family->id}",
        ]);

        app(RevokeConsentAction::class)->execute($response, $family);

        $history = ConsentHistory::where('consent_response_id', $response->id)
            ->where('event_type', ConsentEventType::Revoked->value)
            ->first();

        $this->assertNull($history->notes);
    }

    public function test_image_review_flag_column_exists_in_responses_relation_manager(): void
    {
        $type = ConsentType::factory()->published()->create(['requires_image_review' => true]);
        $this->actingAs($this->superAdmin);

        Livewire::test(ConsentResponsesRelationManager::class, [
            'ownerRecord' => $type,
            'pageClass' => EditConsentType::class,
        ])
            ->assertOk();
    }
}
