<?php

namespace Tests\Feature;

use App\Actions\Forms\SubmitFormResponseAction;
use App\Actions\Forms\UpdateFormResponseAction;
use App\Enums\FormFieldType;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Family;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormResponse;
use App\Models\FormTargetItem;
use App\Models\Grade;
use App\Models\SchoolStage;
use App\Models\Student;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FormResponseTest extends TestCase
{
    use RefreshDatabase;

    private AcademicYear $year;

    private Family $family;

    private Student $student;

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
        $this->family = Family::factory()->create(['is_ampa_member' => true]);
        $this->student = Student::factory()->create(['family_id' => $this->family->id, 'is_active' => true]);
    }

    // ─── SubmitFormResponseAction ─────────────────────────────────────────────

    public function test_family_can_submit_per_family_form(): void
    {
        $form = Form::factory()->create(['academic_year_id' => $this->year->id]);
        $field = FormField::factory()->create(['form_id' => $form->id, 'type' => FormFieldType::TextShort]);

        $response = app(SubmitFormResponseAction::class)->execute(
            form: $form,
            family: $this->family,
            student: null,
            answers: [$field->id => 'Mi respuesta'],
        );

        $this->assertDatabaseHas('form_responses', [
            'form_id' => $form->id,
            'family_id' => $this->family->id,
            'student_id' => null,
            'response_key' => "family:{$this->family->id}",
        ]);
        $this->assertDatabaseHas('form_response_answers', [
            'form_response_id' => $response->id,
            'form_field_id' => $field->id,
            'value' => 'Mi respuesta',
        ]);
    }

    public function test_family_can_submit_per_student_form_with_eligible_student(): void
    {
        $grade = $this->createGrade();
        $classroom = Classroom::create([
            'academic_year_id' => $this->year->id,
            'grade_id' => $grade->id,
            'name' => 'A',
        ]);
        $this->student->classrooms()->attach($classroom->id, ['enrolled_at' => now()]);

        $form = Form::factory()->perStudent()->forGrade()->create(['academic_year_id' => $this->year->id]);
        FormTargetItem::create([
            'form_id' => $form->id,
            'targetable_type' => Grade::class,
            'targetable_id' => $grade->id,
        ]);

        $response = app(SubmitFormResponseAction::class)->execute(
            form: $form,
            family: $this->family,
            student: $this->student,
            answers: [],
        );

        $this->assertDatabaseHas('form_responses', [
            'form_id' => $form->id,
            'family_id' => $this->family->id,
            'student_id' => $this->student->id,
            'response_key' => "student:{$this->student->id}",
        ]);
    }

    public function test_family_cannot_submit_for_student_of_another_family(): void
    {
        $otherFamily = Family::factory()->create();
        $otherStudent = Student::factory()->create(['family_id' => $otherFamily->id]);

        $form = Form::factory()->perStudent()->create(['academic_year_id' => $this->year->id]);

        $this->expectException(ValidationException::class);

        app(SubmitFormResponseAction::class)->execute(
            form: $form,
            family: $this->family,
            student: $otherStudent,
            answers: [],
        );
    }

    public function test_family_cannot_submit_for_ineligible_student(): void
    {
        $targetGrade = $this->createGrade('1º Primaria', 1);
        $otherGrade = $this->createGrade('2º Primaria', 2);
        $classroom = Classroom::create([
            'academic_year_id' => $this->year->id,
            'grade_id' => $otherGrade->id,
            'name' => 'A',
        ]);
        $this->student->classrooms()->attach($classroom->id, ['enrolled_at' => now()]);

        $form = Form::factory()->perStudent()->forGrade()->create(['academic_year_id' => $this->year->id]);
        FormTargetItem::create([
            'form_id' => $form->id,
            'targetable_type' => Grade::class,
            'targetable_id' => $targetGrade->id,
        ]);

        $this->expectException(ValidationException::class);

        app(SubmitFormResponseAction::class)->execute(
            form: $form,
            family: $this->family,
            student: $this->student,
            answers: [],
        );
    }

    public function test_cannot_duplicate_per_family_response(): void
    {
        $form = Form::factory()->create(['academic_year_id' => $this->year->id]);

        app(SubmitFormResponseAction::class)->execute(
            form: $form,
            family: $this->family,
            student: null,
            answers: [],
        );

        $this->expectException(ValidationException::class);

        app(SubmitFormResponseAction::class)->execute(
            form: $form,
            family: $this->family,
            student: null,
            answers: [],
        );
    }

    public function test_cannot_duplicate_per_student_response(): void
    {
        $form = Form::factory()->perStudent()->create(['academic_year_id' => $this->year->id]);

        app(SubmitFormResponseAction::class)->execute(
            form: $form,
            family: $this->family,
            student: $this->student,
            answers: [],
        );

        $this->expectException(ValidationException::class);

        app(SubmitFormResponseAction::class)->execute(
            form: $form,
            family: $this->family,
            student: $this->student,
            answers: [],
        );
    }

    public function test_required_field_empty_throws_validation_exception(): void
    {
        $form = Form::factory()->create(['academic_year_id' => $this->year->id]);
        FormField::factory()->required()->create([
            'form_id' => $form->id,
            'type' => FormFieldType::TextShort,
        ]);

        $this->expectException(ValidationException::class);

        app(SubmitFormResponseAction::class)->execute(
            form: $form,
            family: $this->family,
            student: null,
            answers: [],
        );
    }

    public function test_select_with_invalid_option_throws_validation_exception(): void
    {
        $form = Form::factory()->create(['academic_year_id' => $this->year->id]);
        $field = FormField::factory()->select(['Opción A', 'Opción B'])->create(['form_id' => $form->id]);

        $this->expectException(ValidationException::class);

        app(SubmitFormResponseAction::class)->execute(
            form: $form,
            family: $this->family,
            student: null,
            answers: [$field->id => 'Opción inválida'],
        );
    }

    public function test_checkboxes_with_invalid_option_throws_validation_exception(): void
    {
        $form = Form::factory()->create(['academic_year_id' => $this->year->id]);
        $field = FormField::factory()->checkboxes(['Lunes', 'Martes'])->create(['form_id' => $form->id]);

        $this->expectException(ValidationException::class);

        app(SubmitFormResponseAction::class)->execute(
            form: $form,
            family: $this->family,
            student: null,
            answers: [$field->id => ['Lunes', 'Jueves']],
        );
    }

    public function test_closed_form_cannot_receive_responses(): void
    {
        $form = Form::factory()->closed()->create(['academic_year_id' => $this->year->id]);

        $this->expectException(ValidationException::class);

        app(SubmitFormResponseAction::class)->execute(
            form: $form,
            family: $this->family,
            student: null,
            answers: [],
        );
    }

    public function test_form_not_targeting_family_cannot_receive_response(): void
    {
        $this->family->update(['is_ampa_member' => false]);
        $form = Form::factory()->forAmpaMembers()->create(['academic_year_id' => $this->year->id]);

        $this->expectException(ValidationException::class);

        app(SubmitFormResponseAction::class)->execute(
            form: $form,
            family: $this->family,
            student: null,
            answers: [],
        );
    }

    // ─── UpdateFormResponseAction ─────────────────────────────────────────────

    public function test_family_can_edit_response_when_allow_edit_true(): void
    {
        $form = Form::factory()->allowEdit()->create(['academic_year_id' => $this->year->id]);
        $field = FormField::factory()->create(['form_id' => $form->id, 'type' => FormFieldType::TextShort]);

        $existingResponse = FormResponse::create([
            'form_id' => $form->id,
            'family_id' => $this->family->id,
            'student_id' => null,
            'response_key' => "family:{$this->family->id}",
            'submitted_at' => now(),
        ]);

        $updated = app(UpdateFormResponseAction::class)->execute(
            response: $existingResponse,
            family: $this->family,
            answers: [$field->id => 'Respuesta editada'],
        );

        $this->assertDatabaseHas('form_response_answers', [
            'form_response_id' => $updated->id,
            'form_field_id' => $field->id,
            'value' => 'Respuesta editada',
        ]);
    }

    public function test_family_cannot_edit_response_when_allow_edit_false(): void
    {
        $form = Form::factory()->create(['academic_year_id' => $this->year->id, 'allow_edit' => false]);

        $existingResponse = FormResponse::create([
            'form_id' => $form->id,
            'family_id' => $this->family->id,
            'student_id' => null,
            'response_key' => "family:{$this->family->id}",
            'submitted_at' => now(),
        ]);

        $this->expectException(ValidationException::class);

        app(UpdateFormResponseAction::class)->execute(
            response: $existingResponse,
            family: $this->family,
            answers: [],
        );
    }

    public function test_family_cannot_edit_response_when_form_is_closed(): void
    {
        $form = Form::factory()->closed()->allowEdit()->create(['academic_year_id' => $this->year->id]);

        $existingResponse = FormResponse::create([
            'form_id' => $form->id,
            'family_id' => $this->family->id,
            'student_id' => null,
            'response_key' => "family:{$this->family->id}",
            'submitted_at' => now(),
        ]);

        $this->expectException(ValidationException::class);

        app(UpdateFormResponseAction::class)->execute(
            response: $existingResponse,
            family: $this->family,
            answers: [],
        );
    }

    public function test_family_cannot_edit_another_familys_response(): void
    {
        $form = Form::factory()->allowEdit()->create(['academic_year_id' => $this->year->id]);
        $otherFamily = Family::factory()->create();

        $otherResponse = FormResponse::create([
            'form_id' => $form->id,
            'family_id' => $otherFamily->id,
            'student_id' => null,
            'response_key' => "family:{$otherFamily->id}",
            'submitted_at' => now(),
        ]);

        $this->expectException(ValidationException::class);

        app(UpdateFormResponseAction::class)->execute(
            response: $otherResponse,
            family: $this->family,
            answers: [],
        );
    }
}
