<?php

namespace Tests\Feature;

use App\Enums\FormFieldType;
use App\Enums\FormResponseScope;
use App\Enums\FormStatus;
use App\Enums\FormTargetType;
use App\Exports\Forms\FormResponsesExport;
use App\Filament\Resources\Forms\Pages\CreateForm;
use App\Filament\Resources\Forms\Pages\EditForm;
use App\Models\AcademicYear;
use App\Models\Form;
use App\Models\FormField;
use App\Models\Grade;
use App\Models\SchoolStage;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FormResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $adminFormularios;

    private User $adminExtraescolares;

    private AcademicYear $year;

    private function createGrade(string $name = '1º Primaria', int $sortOrder = 1): Grade
    {
        $stage = SchoolStage::firstOrCreate(['name' => 'Primaria'], ['sort_order' => 1]);

        return Grade::create(['school_stage_id' => $stage->id, 'name' => $name, 'sort_order' => $sortOrder]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->year = AcademicYear::factory()->create(['is_active' => true]);

        $this->superAdmin = User::factory()->create(['email_verified_at' => now()]);
        $this->superAdmin->assignRole('super_admin');

        $this->adminFormularios = User::factory()->create(['email_verified_at' => now()]);
        $this->adminFormularios->assignRole('admin_formularios');

        $this->adminExtraescolares = User::factory()->create(['email_verified_at' => now()]);
        $this->adminExtraescolares->assignRole('admin_extraescolares');
    }

    // ── Carga de páginas ──────────────────────────────────────────────────────

    public function test_super_admin_can_list_forms(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/forms')
            ->assertOk();
    }

    public function test_super_admin_can_access_create_form(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/forms/create')
            ->assertOk();
    }

    public function test_super_admin_can_access_edit_form(): void
    {
        $form = Form::factory()->create(['academic_year_id' => $this->year->id]);

        $this->actingAs($this->superAdmin)
            ->get("/admin/forms/{$form->id}/edit")
            ->assertOk();
    }

    // ── Permisos ──────────────────────────────────────────────────────────────

    public function test_admin_formularios_can_access_forms(): void
    {
        $this->actingAs($this->adminFormularios)
            ->get('/admin/forms')
            ->assertOk();
    }

    public function test_admin_extraescolares_cannot_access_forms(): void
    {
        $this->actingAs($this->adminExtraescolares)
            ->get('/admin/forms')
            ->assertForbidden();
    }

    // ── Formulario all_families (sin targets) ─────────────────────────────────

    public function test_all_families_form_can_be_created_without_target_items(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(CreateForm::class)
            ->fillForm([
                'academic_year_id' => $this->year->id,
                'title' => 'Formulario general',
                'status' => FormStatus::Draft->value,
                'response_scope' => FormResponseScope::PerFamily->value,
                'target_type' => FormTargetType::AllFamilies->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $form = Form::where('title', 'Formulario general')->firstOrFail();
        $this->assertCount(0, $form->formTargetItems()->get());
    }

    // ── Formulario by_grade (con targets) ────────────────────────────────────

    public function test_by_grade_form_requires_target_items(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(CreateForm::class)
            ->fillForm([
                'academic_year_id' => $this->year->id,
                'title' => 'Formulario por curso',
                'status' => FormStatus::Draft->value,
                'response_scope' => FormResponseScope::PerFamily->value,
                'target_type' => FormTargetType::ByGrade->value,
                // target_item_ids deliberately absent → should fail validation
            ])
            ->call('create')
            ->assertHasFormErrors(['target_item_ids']);
    }

    public function test_saving_by_grade_form_creates_target_items(): void
    {
        $grade = $this->createGrade();
        $this->actingAs($this->superAdmin);

        Livewire::test(CreateForm::class)
            ->fillForm([
                'academic_year_id' => $this->year->id,
                'title' => 'Formulario por curso',
                'status' => FormStatus::Draft->value,
                'response_scope' => FormResponseScope::PerFamily->value,
                'target_type' => FormTargetType::ByGrade->value,
                'target_item_ids' => [$grade->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $form = Form::where('title', 'Formulario por curso')->firstOrFail();
        $this->assertCount(1, $form->formTargetItems()->get());
        $this->assertEquals(Grade::class, $form->formTargetItems()->first()->targetable_type);
        $this->assertEquals($grade->id, $form->formTargetItems()->first()->targetable_id);
    }

    public function test_changing_to_all_families_clears_target_items(): void
    {
        $grade = $this->createGrade();
        $form = Form::factory()->forGrade()->create(['academic_year_id' => $this->year->id]);
        $form->formTargetItems()->create([
            'targetable_type' => Grade::class,
            'targetable_id' => $grade->id,
        ]);

        $this->assertCount(1, $form->formTargetItems()->get());
        $this->actingAs($this->superAdmin);

        Livewire::test(EditForm::class, ['record' => $form->getRouteKey()])
            ->fillForm([
                'target_type' => FormTargetType::AllFamilies->value,
                'target_item_ids' => [],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertCount(0, $form->formTargetItems()->get());
    }

    // ── Campos (FormField) ────────────────────────────────────────────────────

    public function test_can_create_text_field(): void
    {
        $form = Form::factory()->create(['academic_year_id' => $this->year->id]);

        FormField::create([
            'form_id' => $form->id,
            'type' => FormFieldType::TextShort,
            'label' => 'Nombre completo',
            'is_required' => true,
            'sort_order' => 1,
        ]);

        $this->assertDatabaseHas('form_fields', [
            'form_id' => $form->id,
            'type' => FormFieldType::TextShort->value,
            'label' => 'Nombre completo',
        ]);
    }

    public function test_can_create_select_field_with_options(): void
    {
        $form = Form::factory()->create(['academic_year_id' => $this->year->id]);

        $field = FormField::create([
            'form_id' => $form->id,
            'type' => FormFieldType::Select,
            'label' => 'Talla de camiseta',
            'is_required' => false,
            'sort_order' => 2,
            'options' => ['XS', 'S', 'M', 'L', 'XL'],
        ]);

        $this->assertDatabaseHas('form_fields', [
            'form_id' => $form->id,
            'type' => FormFieldType::Select->value,
        ]);
        $this->assertEquals(['XS', 'S', 'M', 'L', 'XL'], $field->fresh()->options);
    }

    public function test_select_field_type_requires_options(): void
    {
        // Verify the enum contract that drives UI validation
        $this->assertTrue(FormFieldType::Select->requiresOptions());
        $this->assertTrue(FormFieldType::Radio->requiresOptions());
        $this->assertTrue(FormFieldType::Checkboxes->requiresOptions());

        $this->assertFalse(FormFieldType::TextShort->requiresOptions());
        $this->assertFalse(FormFieldType::Email->requiresOptions());
        $this->assertFalse(FormFieldType::InfoText->requiresOptions());
    }

    // ── Export ────────────────────────────────────────────────────────────────

    public function test_export_headings_include_fixed_and_dynamic_columns(): void
    {
        $form = Form::factory()->create(['academic_year_id' => $this->year->id]);
        FormField::factory()->create([
            'form_id' => $form->id,
            'label' => 'Alergias',
            'type' => FormFieldType::TextShort,
            'sort_order' => 1,
        ]);
        FormField::factory()->create([
            'form_id' => $form->id,
            'label' => 'Aviso informativo',
            'type' => FormFieldType::InfoText,
            'sort_order' => 2,
        ]);

        $export = new FormResponsesExport($form->load('formFields'));
        $headings = $export->headings();

        $this->assertContains('Familia', $headings);
        $this->assertContains('Tutor principal', $headings);
        $this->assertContains('Fecha respuesta', $headings);
        $this->assertContains('Alergias', $headings);
        // info_text must be excluded from headings
        $this->assertNotContains('Aviso informativo', $headings);
    }

    public function test_export_works_without_responses(): void
    {
        $form = Form::factory()->create(['academic_year_id' => $this->year->id]);
        FormField::factory()->create([
            'form_id' => $form->id,
            'type' => FormFieldType::TextShort,
            'sort_order' => 1,
        ]);

        $export = new FormResponsesExport($form->load('formFields'));

        $this->assertCount(0, $export->query()->get());
        $this->assertNotEmpty($export->headings());
    }
}
