<?php

namespace Tests\Feature;

use App\Enums\FormFieldType;
use App\Enums\FormResponseScope;
use App\Enums\FormStatus;
use App\Enums\FormTargetType;
use App\Models\AcademicYear;
use App\Models\Family;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormResponse;
use App\Models\FormResponseAnswer;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyFormsTest extends TestCase
{
    use RefreshDatabase;

    private AcademicYear $activeYear;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->activeYear = AcademicYear::factory()->create(['is_active' => true]);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * @return array{user: User, family: Family, student: Student}
     */
    private function createFamilyUser(): array
    {
        $user = User::factory()->create();
        $user->assignRole('familia');

        $family = Family::factory()->create(['is_ampa_member' => true]);
        Guardian::factory()->create(['user_id' => $user->id, 'family_id' => $family->id]);
        $student = Student::factory()->create(['family_id' => $family->id, 'is_active' => true]);

        return compact('user', 'family', 'student');
    }

    private function createOpenForm(array $overrides = []): Form
    {
        return Form::factory()->create(array_merge([
            'academic_year_id' => $this->activeYear->id,
            'status' => FormStatus::Published,
        ], $overrides));
    }

    // ─── Index (3 tests) ─────────────────────────────────────────────────────

    public function test_index_requires_auth(): void
    {
        $this->get(route('familia.forms.index'))
            ->assertRedirect(route('familia.login'));
    }

    public function test_index_shows_pending_open_forms(): void
    {
        ['user' => $user] = $this->createFamilyUser();

        $form = $this->createOpenForm(['title' => 'Encuesta de prueba']);

        $this->actingAs($user)
            ->get(route('familia.forms.index'))
            ->assertOk()
            ->assertSee('Encuesta de prueba');
    }

    public function test_index_shows_no_forms_message_when_none_available(): void
    {
        ['user' => $user] = $this->createFamilyUser();

        $this->actingAs($user)
            ->get(route('familia.forms.index'))
            ->assertOk()
            ->assertSee('No tienes formularios pendientes');
    }

    // ─── Show (5 tests) ──────────────────────────────────────────────────────

    public function test_show_requires_auth(): void
    {
        $form = $this->createOpenForm();

        $this->get(route('familia.forms.show', $form))
            ->assertRedirect(route('familia.login'));
    }

    public function test_show_returns_403_when_family_not_targeted(): void
    {
        ['user' => $user, 'family' => $family] = $this->createFamilyUser();

        $otherFamily = Family::factory()->create(['is_ampa_member' => false]);
        $form = $this->createOpenForm(['target_type' => FormTargetType::AmpaMembers]);

        // Family is an ampa member so it IS targeted here — let's use a different family that is not
        $otherUser = User::factory()->create();
        $otherUser->assignRole('familia');
        Guardian::factory()->create(['user_id' => $otherUser->id, 'family_id' => $otherFamily->id]);

        $this->actingAs($otherUser)
            ->get(route('familia.forms.show', $form))
            ->assertForbidden();
    }

    public function test_show_returns_404_for_draft_form(): void
    {
        ['user' => $user] = $this->createFamilyUser();

        $form = Form::factory()->draft()->create(['academic_year_id' => $this->activeYear->id]);

        $this->actingAs($user)
            ->get(route('familia.forms.show', $form))
            ->assertNotFound();
    }

    public function test_show_displays_form_fields(): void
    {
        ['user' => $user] = $this->createFamilyUser();

        $form = $this->createOpenForm(['title' => 'Formulario test']);
        FormField::factory()->create([
            'form_id' => $form->id,
            'label' => 'Nombre del responsable',
            'sort_order' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('familia.forms.show', $form))
            ->assertOk()
            ->assertSee('Nombre del responsable');
    }

    public function test_show_displays_already_responded_notice(): void
    {
        ['user' => $user, 'family' => $family] = $this->createFamilyUser();

        $form = $this->createOpenForm();
        FormResponse::create([
            'form_id' => $form->id,
            'family_id' => $family->id,
            'student_id' => null,
            'response_key' => 'family:'.$family->id,
            'submitted_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('familia.forms.show', $form))
            ->assertOk()
            ->assertSee('Ya has enviado una respuesta');
    }

    public function test_show_displays_student_selector_for_per_student_form(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();

        $form = $this->createOpenForm([
            'response_scope' => FormResponseScope::PerStudent,
        ]);

        $this->actingAs($user)
            ->get(route('familia.forms.show', $form))
            ->assertOk()
            ->assertSee('Alumno/a')
            ->assertSee($student->last_name);
    }

    // ─── Store (5 tests) ─────────────────────────────────────────────────────

    public function test_store_requires_auth(): void
    {
        $form = $this->createOpenForm();

        $this->post(route('familia.forms.submit', $form))
            ->assertRedirect(route('familia.login'));
    }

    public function test_store_submits_per_family_form_successfully(): void
    {
        ['user' => $user, 'family' => $family] = $this->createFamilyUser();

        $form = $this->createOpenForm();
        $field = FormField::factory()->create([
            'form_id' => $form->id,
            'type' => FormFieldType::TextShort,
            'label' => 'Tu opinión',
            'is_required' => false,
            'sort_order' => 1,
        ]);

        $this->actingAs($user)
            ->post(route('familia.forms.submit', $form), [
                'fields' => [$field->id => 'Mi opinión positiva'],
            ])
            ->assertRedirect(route('familia.forms.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('form_responses', [
            'form_id' => $form->id,
            'family_id' => $family->id,
            'response_key' => 'family:'.$family->id,
        ]);
    }

    public function test_store_rejects_duplicate_per_family_response(): void
    {
        ['user' => $user, 'family' => $family] = $this->createFamilyUser();

        $form = $this->createOpenForm();
        FormResponse::create([
            'form_id' => $form->id,
            'family_id' => $family->id,
            'student_id' => null,
            'response_key' => 'family:'.$family->id,
            'submitted_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('familia.forms.submit', $form), ['fields' => []])
            ->assertRedirect()
            ->assertSessionHasErrors('form');
    }

    public function test_store_rejects_missing_required_field(): void
    {
        ['user' => $user] = $this->createFamilyUser();

        $form = $this->createOpenForm();
        $field = FormField::factory()->required()->create([
            'form_id' => $form->id,
            'type' => FormFieldType::TextShort,
            'sort_order' => 1,
        ]);

        $this->actingAs($user)
            ->post(route('familia.forms.submit', $form), [
                'fields' => [$field->id => ''],
            ])
            ->assertRedirect()
            ->assertSessionHasErrors("field_{$field->id}");
    }

    public function test_store_rejects_per_student_form_without_student(): void
    {
        ['user' => $user] = $this->createFamilyUser();

        $form = $this->createOpenForm([
            'response_scope' => FormResponseScope::PerStudent,
        ]);

        $this->actingAs($user)
            ->post(route('familia.forms.submit', $form), ['fields' => []])
            ->assertRedirect()
            ->assertSessionHasErrors('student_id');
    }

    // ─── Edit (4 tests) ──────────────────────────────────────────────────────

    public function test_edit_shows_pre_filled_form(): void
    {
        ['user' => $user, 'family' => $family] = $this->createFamilyUser();

        $form = $this->createOpenForm(['allow_edit' => true]);
        $field = FormField::factory()->create([
            'form_id' => $form->id,
            'type' => FormFieldType::TextShort,
            'sort_order' => 1,
        ]);

        $response = FormResponse::create([
            'form_id' => $form->id,
            'family_id' => $family->id,
            'student_id' => null,
            'response_key' => 'family:'.$family->id,
            'submitted_at' => now(),
        ]);

        FormResponseAnswer::create([
            'form_response_id' => $response->id,
            'form_field_id' => $field->id,
            'value' => 'Respuesta existente',
        ]);

        $this->actingAs($user)
            ->get(route('familia.forms.edit', [$form, $response]))
            ->assertOk()
            ->assertSee('Respuesta existente');
    }

    public function test_edit_returns_403_when_allow_edit_is_false(): void
    {
        ['user' => $user, 'family' => $family] = $this->createFamilyUser();

        $form = $this->createOpenForm(['allow_edit' => false]);
        $response = FormResponse::create([
            'form_id' => $form->id,
            'family_id' => $family->id,
            'student_id' => null,
            'response_key' => 'family:'.$family->id,
            'submitted_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('familia.forms.edit', [$form, $response]))
            ->assertForbidden();
    }

    public function test_edit_returns_403_when_form_is_closed(): void
    {
        ['user' => $user, 'family' => $family] = $this->createFamilyUser();

        $form = Form::factory()->alreadyClosed()->create([
            'academic_year_id' => $this->activeYear->id,
            'allow_edit' => true,
        ]);

        $response = FormResponse::create([
            'form_id' => $form->id,
            'family_id' => $family->id,
            'student_id' => null,
            'response_key' => 'family:'.$family->id,
            'submitted_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('familia.forms.edit', [$form, $response]))
            ->assertForbidden();
    }

    public function test_edit_returns_403_for_another_familys_response(): void
    {
        ['user' => $user] = $this->createFamilyUser();

        $otherFamily = Family::factory()->create();
        $form = $this->createOpenForm(['allow_edit' => true]);
        $response = FormResponse::create([
            'form_id' => $form->id,
            'family_id' => $otherFamily->id,
            'student_id' => null,
            'response_key' => 'family:'.$otherFamily->id,
            'submitted_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('familia.forms.edit', [$form, $response]))
            ->assertForbidden();
    }

    // ─── Update (3 tests) ────────────────────────────────────────────────────

    public function test_update_saves_changes_and_redirects(): void
    {
        ['user' => $user, 'family' => $family] = $this->createFamilyUser();

        $form = $this->createOpenForm(['allow_edit' => true]);
        $field = FormField::factory()->create([
            'form_id' => $form->id,
            'type' => FormFieldType::TextShort,
            'is_required' => false,
            'sort_order' => 1,
        ]);

        $response = FormResponse::create([
            'form_id' => $form->id,
            'family_id' => $family->id,
            'student_id' => null,
            'response_key' => 'family:'.$family->id,
            'submitted_at' => now(),
        ]);

        FormResponseAnswer::create([
            'form_response_id' => $response->id,
            'form_field_id' => $field->id,
            'value' => 'Valor original',
        ]);

        $this->actingAs($user)
            ->put(route('familia.forms.update', [$form, $response]), [
                'fields' => [$field->id => 'Valor actualizado'],
            ])
            ->assertRedirect(route('familia.forms.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('form_response_answers', [
            'form_response_id' => $response->id,
            'form_field_id' => $field->id,
            'value' => 'Valor actualizado',
        ]);
    }

    public function test_update_returns_403_for_another_familys_response(): void
    {
        ['user' => $user] = $this->createFamilyUser();

        $otherFamily = Family::factory()->create();
        $form = $this->createOpenForm(['allow_edit' => true]);
        $response = FormResponse::create([
            'form_id' => $form->id,
            'family_id' => $otherFamily->id,
            'student_id' => null,
            'response_key' => 'family:'.$otherFamily->id,
            'submitted_at' => now(),
        ]);

        $this->actingAs($user)
            ->put(route('familia.forms.update', [$form, $response]), ['fields' => []])
            ->assertForbidden();
    }

    public function test_update_rejects_missing_required_field(): void
    {
        ['user' => $user, 'family' => $family] = $this->createFamilyUser();

        $form = $this->createOpenForm(['allow_edit' => true]);
        $field = FormField::factory()->required()->create([
            'form_id' => $form->id,
            'type' => FormFieldType::TextShort,
            'sort_order' => 1,
        ]);

        $response = FormResponse::create([
            'form_id' => $form->id,
            'family_id' => $family->id,
            'student_id' => null,
            'response_key' => 'family:'.$family->id,
            'submitted_at' => now(),
        ]);

        $this->actingAs($user)
            ->put(route('familia.forms.update', [$form, $response]), [
                'fields' => [$field->id => ''],
            ])
            ->assertRedirect()
            ->assertSessionHasErrors("field_{$field->id}");
    }
}
