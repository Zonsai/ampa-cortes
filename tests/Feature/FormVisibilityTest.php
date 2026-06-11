<?php

namespace Tests\Feature;

use App\Enums\ActivityGroupStatus;
use App\Enums\EnrollmentStatus;
use App\Models\AcademicYear;
use App\Models\ActivityGroup;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\ExtracurricularActivity;
use App\Models\Family;
use App\Models\Form;
use App\Models\FormTargetItem;
use App\Models\Grade;
use App\Models\SchoolStage;
use App\Models\Student;
use App\Services\FormEligibleStudentsService;
use App\Services\FormVisibilityService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private AcademicYear $year;

    private Family $family;

    private Student $student;

    private FormVisibilityService $visibility;

    private FormEligibleStudentsService $eligible;

    private function createGrade(string $name = '1º Primaria', int $sortOrder = 1, ?SchoolStage $stage = null): Grade
    {
        $stage ??= SchoolStage::firstOrCreate(['name' => 'Primaria'], ['sort_order' => 1]);

        return Grade::create(['school_stage_id' => $stage->id, 'name' => $name, 'sort_order' => $sortOrder]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->year = AcademicYear::factory()->create(['is_active' => true]);
        $this->family = Family::factory()->create(['is_ampa_member' => true]);
        $this->student = Student::factory()->create(['family_id' => $this->family->id, 'is_active' => true]);

        $this->visibility = app(FormVisibilityService::class);
        $this->eligible = app(FormEligibleStudentsService::class);
    }

    // ─── FormVisibilityService ───────────────────────────────────────────────

    public function test_family_sees_all_families_form(): void
    {
        $form = Form::factory()->create(['academic_year_id' => $this->year->id]);

        $this->assertTrue($this->visibility->isOpenForFamily($form, $this->family));
    }

    public function test_family_does_not_see_draft_form(): void
    {
        $form = Form::factory()->draft()->create(['academic_year_id' => $this->year->id]);

        $this->assertFalse($this->visibility->isOpenForFamily($form, $this->family));
    }

    public function test_family_does_not_see_closed_form(): void
    {
        $form = Form::factory()->closed()->create(['academic_year_id' => $this->year->id]);

        $this->assertFalse($this->visibility->isOpenForFamily($form, $this->family));
    }

    public function test_family_does_not_see_form_before_opens_at(): void
    {
        $form = Form::factory()->notYetOpen()->create(['academic_year_id' => $this->year->id]);

        $this->assertFalse($this->visibility->isOpenForFamily($form, $this->family));
    }

    public function test_family_does_not_see_form_after_closes_at(): void
    {
        $form = Form::factory()->alreadyClosed()->create(['academic_year_id' => $this->year->id]);

        $this->assertFalse($this->visibility->isOpenForFamily($form, $this->family));
    }

    public function test_ampa_member_family_sees_ampa_members_form(): void
    {
        $this->family->update(['is_ampa_member' => true]);
        $form = Form::factory()->forAmpaMembers()->create(['academic_year_id' => $this->year->id]);

        $this->assertTrue($this->visibility->isOpenForFamily($form, $this->family));
    }

    public function test_non_ampa_member_family_does_not_see_ampa_members_form(): void
    {
        $this->family->update(['is_ampa_member' => false]);
        $form = Form::factory()->forAmpaMembers()->create(['academic_year_id' => $this->year->id]);

        $this->assertFalse($this->visibility->isOpenForFamily($form, $this->family));
    }

    public function test_family_sees_form_directed_at_its_grade(): void
    {
        $grade = $this->createGrade();
        $classroom = Classroom::create([
            'academic_year_id' => $this->year->id,
            'grade_id' => $grade->id,
            'name' => 'A',
        ]);
        $this->student->classrooms()->attach($classroom->id, ['enrolled_at' => now()]);

        $form = Form::factory()->forGrade()->create(['academic_year_id' => $this->year->id]);
        FormTargetItem::create([
            'form_id' => $form->id,
            'targetable_type' => Grade::class,
            'targetable_id' => $grade->id,
        ]);
        $form->load('formTargetItems');

        $this->assertTrue($this->visibility->isOpenForFamily($form, $this->family));
    }

    public function test_family_does_not_see_form_directed_at_different_grade(): void
    {
        $myGrade = $this->createGrade('1º Primaria', 1);
        $otherGrade = $this->createGrade('2º Primaria', 2);

        $classroom = Classroom::create([
            'academic_year_id' => $this->year->id,
            'grade_id' => $myGrade->id,
            'name' => 'A',
        ]);
        $this->student->classrooms()->attach($classroom->id, ['enrolled_at' => now()]);

        $form = Form::factory()->forGrade()->create(['academic_year_id' => $this->year->id]);
        FormTargetItem::create([
            'form_id' => $form->id,
            'targetable_type' => Grade::class,
            'targetable_id' => $otherGrade->id,
        ]);
        $form->load('formTargetItems');

        $this->assertFalse($this->visibility->isOpenForFamily($form, $this->family));
    }

    public function test_family_sees_form_directed_at_its_classroom(): void
    {
        $grade = $this->createGrade();
        $classroom = Classroom::create([
            'academic_year_id' => $this->year->id,
            'grade_id' => $grade->id,
            'name' => 'A',
        ]);
        $this->student->classrooms()->attach($classroom->id, ['enrolled_at' => now()]);

        $form = Form::factory()->forClassroom()->create(['academic_year_id' => $this->year->id]);
        FormTargetItem::create([
            'form_id' => $form->id,
            'targetable_type' => Classroom::class,
            'targetable_id' => $classroom->id,
        ]);
        $form->load('formTargetItems');

        $this->assertTrue($this->visibility->isOpenForFamily($form, $this->family));
    }

    public function test_family_sees_form_directed_at_activity_when_has_active_enrollment(): void
    {
        $activity = ExtracurricularActivity::factory()->create(['academic_year_id' => $this->year->id]);
        $group = ActivityGroup::factory()->create([
            'activity_id' => $activity->id,
            'status' => ActivityGroupStatus::Open,
        ]);
        Enrollment::factory()->create([
            'student_id' => $this->student->id,
            'family_id' => $this->family->id,
            'activity_id' => $activity->id,
            'activity_group_id' => $group->id,
            'academic_year_id' => $this->year->id,
            'status' => EnrollmentStatus::Enrolled,
        ]);

        $form = Form::factory()->forActivity()->create(['academic_year_id' => $this->year->id]);
        FormTargetItem::create([
            'form_id' => $form->id,
            'targetable_type' => ExtracurricularActivity::class,
            'targetable_id' => $activity->id,
        ]);
        $form->load('formTargetItems');

        $this->assertTrue($this->visibility->isOpenForFamily($form, $this->family));
    }

    // ─── FormEligibleStudentsService ─────────────────────────────────────────

    public function test_eligible_students_returns_all_active_students_for_all_families_form(): void
    {
        $student2 = Student::factory()->create(['family_id' => $this->family->id, 'is_active' => true]);
        $form = Form::factory()->create(['academic_year_id' => $this->year->id]);

        $result = $this->eligible->getEligibleStudents($form, $this->family);

        $this->assertCount(2, $result);
    }

    public function test_eligible_students_returns_only_students_in_target_grade(): void
    {
        $grade = $this->createGrade('1º Primaria', 1);
        $otherGrade = $this->createGrade('2º Primaria', 2);

        $classroom = Classroom::create([
            'academic_year_id' => $this->year->id,
            'grade_id' => $grade->id,
            'name' => 'A',
        ]);
        $otherClassroom = Classroom::create([
            'academic_year_id' => $this->year->id,
            'grade_id' => $otherGrade->id,
            'name' => 'A',
        ]);

        // student1 is in target grade, student2 is not
        $student2 = Student::factory()->create(['family_id' => $this->family->id, 'is_active' => true]);
        $this->student->classrooms()->attach($classroom->id, ['enrolled_at' => now()]);
        $student2->classrooms()->attach($otherClassroom->id, ['enrolled_at' => now()]);

        $form = Form::factory()->forGrade()->create(['academic_year_id' => $this->year->id]);
        FormTargetItem::create([
            'form_id' => $form->id,
            'targetable_type' => Grade::class,
            'targetable_id' => $grade->id,
        ]);
        $form->load('formTargetItems');

        $result = $this->eligible->getEligibleStudents($form, $this->family);

        $this->assertCount(1, $result);
        $this->assertEquals($this->student->id, $result->first()->id);
    }

    public function test_eligible_students_returns_only_students_in_target_classroom(): void
    {
        $grade = $this->createGrade();
        $classroom = Classroom::create([
            'academic_year_id' => $this->year->id,
            'grade_id' => $grade->id,
            'name' => 'A',
        ]);
        $otherClassroom = Classroom::create([
            'academic_year_id' => $this->year->id,
            'grade_id' => $grade->id,
            'name' => 'B',
        ]);

        $student2 = Student::factory()->create(['family_id' => $this->family->id, 'is_active' => true]);
        $this->student->classrooms()->attach($classroom->id, ['enrolled_at' => now()]);
        $student2->classrooms()->attach($otherClassroom->id, ['enrolled_at' => now()]);

        $form = Form::factory()->forClassroom()->create(['academic_year_id' => $this->year->id]);
        FormTargetItem::create([
            'form_id' => $form->id,
            'targetable_type' => Classroom::class,
            'targetable_id' => $classroom->id,
        ]);
        $form->load('formTargetItems');

        $result = $this->eligible->getEligibleStudents($form, $this->family);

        $this->assertCount(1, $result);
        $this->assertEquals($this->student->id, $result->first()->id);
    }

    public function test_eligible_students_returns_only_students_with_active_enrollment_in_target_activity(): void
    {
        $activity = ExtracurricularActivity::factory()->create(['academic_year_id' => $this->year->id]);
        $group = ActivityGroup::factory()->create(['activity_id' => $activity->id]);

        $student2 = Student::factory()->create(['family_id' => $this->family->id, 'is_active' => true]);

        // Only student1 has enrollment
        Enrollment::factory()->create([
            'student_id' => $this->student->id,
            'family_id' => $this->family->id,
            'activity_id' => $activity->id,
            'activity_group_id' => $group->id,
            'academic_year_id' => $this->year->id,
            'status' => EnrollmentStatus::Enrolled,
        ]);

        $form = Form::factory()->forActivity()->create(['academic_year_id' => $this->year->id]);
        FormTargetItem::create([
            'form_id' => $form->id,
            'targetable_type' => ExtracurricularActivity::class,
            'targetable_id' => $activity->id,
        ]);
        $form->load('formTargetItems');

        $result = $this->eligible->getEligibleStudents($form, $this->family);

        $this->assertCount(1, $result);
        $this->assertEquals($this->student->id, $result->first()->id);
    }

    public function test_inactive_student_is_not_eligible(): void
    {
        Student::factory()->create(['family_id' => $this->family->id, 'is_active' => false]);
        $form = Form::factory()->create(['academic_year_id' => $this->year->id]);

        $result = $this->eligible->getEligibleStudents($form, $this->family);

        $this->assertCount(1, $result); // only the active student from setUp
    }

    // ─── ByStage targeting ───────────────────────────────────────────────────

    public function test_family_sees_form_directed_at_its_stage(): void
    {
        $stage = SchoolStage::create(['name' => 'Primaria', 'sort_order' => 1]);
        $grade = $this->createGrade('1º Primaria', 1, $stage);
        $classroom = Classroom::create([
            'academic_year_id' => $this->year->id,
            'grade_id' => $grade->id,
            'name' => 'A',
        ]);
        $this->student->classrooms()->attach($classroom->id, ['enrolled_at' => now()]);

        $form = Form::factory()->forStage()->create(['academic_year_id' => $this->year->id]);
        FormTargetItem::create([
            'form_id' => $form->id,
            'targetable_type' => SchoolStage::class,
            'targetable_id' => $stage->id,
        ]);
        $form->load('formTargetItems');

        $this->assertTrue($this->visibility->isOpenForFamily($form, $this->family));
    }

    public function test_family_does_not_see_form_directed_at_different_stage(): void
    {
        $myStage = SchoolStage::create(['name' => 'Infantil', 'sort_order' => 1]);
        $otherStage = SchoolStage::create(['name' => 'Primaria', 'sort_order' => 2]);

        $myGrade = $this->createGrade('1º Infantil', 1, $myStage);
        $classroom = Classroom::create([
            'academic_year_id' => $this->year->id,
            'grade_id' => $myGrade->id,
            'name' => 'A',
        ]);
        $this->student->classrooms()->attach($classroom->id, ['enrolled_at' => now()]);

        $form = Form::factory()->forStage()->create(['academic_year_id' => $this->year->id]);
        FormTargetItem::create([
            'form_id' => $form->id,
            'targetable_type' => SchoolStage::class,
            'targetable_id' => $otherStage->id,
        ]);
        $form->load('formTargetItems');

        $this->assertFalse($this->visibility->isOpenForFamily($form, $this->family));
    }

    public function test_eligible_students_returns_only_students_in_target_stage(): void
    {
        $targetStage = SchoolStage::create(['name' => 'Primaria', 'sort_order' => 1]);
        $otherStage = SchoolStage::create(['name' => 'Infantil', 'sort_order' => 2]);

        $targetGrade = $this->createGrade('1º Primaria', 1, $targetStage);
        $otherGrade = $this->createGrade('1º Infantil', 1, $otherStage);

        $classroom = Classroom::create([
            'academic_year_id' => $this->year->id,
            'grade_id' => $targetGrade->id,
            'name' => 'A',
        ]);
        $otherClassroom = Classroom::create([
            'academic_year_id' => $this->year->id,
            'grade_id' => $otherGrade->id,
            'name' => 'B',
        ]);

        $student2 = Student::factory()->create(['family_id' => $this->family->id, 'is_active' => true]);
        $this->student->classrooms()->attach($classroom->id, ['enrolled_at' => now()]);
        $student2->classrooms()->attach($otherClassroom->id, ['enrolled_at' => now()]);

        $form = Form::factory()->forStage()->create(['academic_year_id' => $this->year->id]);
        FormTargetItem::create([
            'form_id' => $form->id,
            'targetable_type' => SchoolStage::class,
            'targetable_id' => $targetStage->id,
        ]);
        $form->load('formTargetItems');

        $result = $this->eligible->getEligibleStudents($form, $this->family);

        $this->assertCount(1, $result);
        $this->assertEquals($this->student->id, $result->first()->id);
    }

    // ─── ByGroup targeting ───────────────────────────────────────────────────

    public function test_family_sees_form_directed_at_group_when_has_active_enrollment(): void
    {
        $activity = ExtracurricularActivity::factory()->create(['academic_year_id' => $this->year->id]);
        $group = ActivityGroup::factory()->create(['activity_id' => $activity->id]);
        Enrollment::factory()->create([
            'student_id' => $this->student->id,
            'family_id' => $this->family->id,
            'activity_id' => $activity->id,
            'activity_group_id' => $group->id,
            'academic_year_id' => $this->year->id,
            'status' => EnrollmentStatus::Enrolled,
        ]);

        $form = Form::factory()->forGroup()->create(['academic_year_id' => $this->year->id]);
        FormTargetItem::create([
            'form_id' => $form->id,
            'targetable_type' => ActivityGroup::class,
            'targetable_id' => $group->id,
        ]);
        $form->load('formTargetItems');

        $this->assertTrue($this->visibility->isOpenForFamily($form, $this->family));
    }

    public function test_family_does_not_see_form_directed_at_other_group(): void
    {
        $activity = ExtracurricularActivity::factory()->create(['academic_year_id' => $this->year->id]);
        $myGroup = ActivityGroup::factory()->create(['activity_id' => $activity->id]);
        $otherGroup = ActivityGroup::factory()->create(['activity_id' => $activity->id]);
        Enrollment::factory()->create([
            'student_id' => $this->student->id,
            'family_id' => $this->family->id,
            'activity_id' => $activity->id,
            'activity_group_id' => $myGroup->id,
            'academic_year_id' => $this->year->id,
            'status' => EnrollmentStatus::Enrolled,
        ]);

        $form = Form::factory()->forGroup()->create(['academic_year_id' => $this->year->id]);
        FormTargetItem::create([
            'form_id' => $form->id,
            'targetable_type' => ActivityGroup::class,
            'targetable_id' => $otherGroup->id,
        ]);
        $form->load('formTargetItems');

        $this->assertFalse($this->visibility->isOpenForFamily($form, $this->family));
    }

    public function test_eligible_students_returns_only_students_with_active_enrollment_in_target_group(): void
    {
        $activity = ExtracurricularActivity::factory()->create(['academic_year_id' => $this->year->id]);
        $group = ActivityGroup::factory()->create(['activity_id' => $activity->id]);
        $student2 = Student::factory()->create(['family_id' => $this->family->id, 'is_active' => true]);

        Enrollment::factory()->create([
            'student_id' => $this->student->id,
            'family_id' => $this->family->id,
            'activity_id' => $activity->id,
            'activity_group_id' => $group->id,
            'academic_year_id' => $this->year->id,
            'status' => EnrollmentStatus::Enrolled,
        ]);

        $form = Form::factory()->forGroup()->create(['academic_year_id' => $this->year->id]);
        FormTargetItem::create([
            'form_id' => $form->id,
            'targetable_type' => ActivityGroup::class,
            'targetable_id' => $group->id,
        ]);
        $form->load('formTargetItems');

        $result = $this->eligible->getEligibleStudents($form, $this->family);

        $this->assertCount(1, $result);
        $this->assertEquals($this->student->id, $result->first()->id);
    }

    // ─── getOpenFormsForFamily ───────────────────────────────────────────────

    public function test_get_open_forms_for_family_returns_forms_for_active_year(): void
    {
        $form = Form::factory()->create(['academic_year_id' => $this->year->id]);

        $result = $this->visibility->getOpenFormsForFamily($this->family);

        $this->assertCount(1, $result);
        $this->assertEquals($form->id, $result->first()->id);
    }

    public function test_get_open_forms_for_family_does_not_return_forms_from_other_year(): void
    {
        $otherYear = AcademicYear::factory()->create(['is_active' => false]);
        Form::factory()->create(['academic_year_id' => $otherYear->id]);
        $myForm = Form::factory()->create(['academic_year_id' => $this->year->id]);

        $result = $this->visibility->getOpenFormsForFamily($this->family);

        $this->assertCount(1, $result);
        $this->assertEquals($myForm->id, $result->first()->id);
    }

    public function test_get_open_forms_for_family_returns_empty_when_no_active_year(): void
    {
        $this->year->update(['is_active' => false]);
        Form::factory()->create(['academic_year_id' => $this->year->id]);

        $result = $this->visibility->getOpenFormsForFamily($this->family);

        $this->assertCount(0, $result);
    }
}
