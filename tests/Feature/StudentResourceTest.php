<?php

namespace Tests\Feature;

use App\Filament\Resources\Students\Pages\EditStudent;
use App\Filament\Resources\Students\RelationManagers\ClassroomsRelationManager;
use App\Filament\Resources\Students\RelationManagers\ConsentResponsesRelationManager;
use App\Filament\Resources\Students\RelationManagers\EnrollmentsRelationManager;
use App\Filament\Resources\Students\RelationManagers\FormResponsesRelationManager;
use App\Filament\Resources\Students\RelationManagers\GuardiansRelationManager;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ConsentResponse;
use App\Models\Enrollment;
use App\Models\Family;
use App\Models\FormResponse;
use App\Models\Grade;
use App\Models\Guardian;
use App\Models\SchoolStage;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StudentResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->superAdmin = User::factory()->create(['email_verified_at' => now()]);
        $this->superAdmin->assignRole('super_admin');
    }

    public function test_super_admin_can_list_students(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/students')
            ->assertOk();
    }

    public function test_super_admin_can_access_edit_student_page(): void
    {
        $student = Student::factory()->create();

        $this->actingAs($this->superAdmin)
            ->get("/admin/students/{$student->id}/edit")
            ->assertOk();
    }

    public function test_classrooms_relation_manager_shows_classrooms(): void
    {
        $family = Family::factory()->create();
        $student = Student::factory()->create(['family_id' => $family->id]);
        $academicYear = AcademicYear::factory()->create(['is_active' => true]);
        $schoolStage = SchoolStage::create(['name' => 'Primaria', 'sort_order' => 1]);
        $grade = Grade::create(['school_stage_id' => $schoolStage->id, 'name' => '1º Primaria', 'sort_order' => 1]);
        $classroom = Classroom::create([
            'academic_year_id' => $academicYear->id,
            'grade_id' => $grade->id,
            'name' => 'A',
        ]);
        $student->classrooms()->attach($classroom->id, ['enrolled_at' => now()]);

        $this->actingAs($this->superAdmin);

        Livewire::test(ClassroomsRelationManager::class, [
            'ownerRecord' => $student,
            'pageClass' => EditStudent::class,
        ])->assertOk()
            ->assertCanSeeTableRecords([$classroom]);
    }

    public function test_guardians_relation_manager_shows_guardians(): void
    {
        $family = Family::factory()->create();
        $student = Student::factory()->create(['family_id' => $family->id]);
        $guardian = Guardian::factory()->create(['family_id' => $family->id]);

        $this->actingAs($this->superAdmin);

        Livewire::test(GuardiansRelationManager::class, [
            'ownerRecord' => $student,
            'pageClass' => EditStudent::class,
        ])->assertOk()
            ->assertCanSeeTableRecords([$guardian]);
    }

    public function test_enrollments_relation_manager_shows_enrollments(): void
    {
        $student = Student::factory()->create();
        $enrollment = Enrollment::factory()->create([
            'student_id' => $student->id,
            'family_id' => $student->family_id,
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(EnrollmentsRelationManager::class, [
            'ownerRecord' => $student,
            'pageClass' => EditStudent::class,
        ])->assertOk()
            ->assertCanSeeTableRecords([$enrollment]);
    }

    public function test_form_responses_relation_manager_shows_responses(): void
    {
        $student = Student::factory()->create();
        $response = FormResponse::factory()->forStudent($student)->create();

        $this->actingAs($this->superAdmin);

        Livewire::test(FormResponsesRelationManager::class, [
            'ownerRecord' => $student,
            'pageClass' => EditStudent::class,
        ])->assertOk()
            ->assertCanSeeTableRecords([$response]);
    }

    public function test_consent_responses_relation_manager_shows_responses(): void
    {
        $student = Student::factory()->create();
        $consentResponse = ConsentResponse::factory()->create([
            'family_id' => $student->family_id,
            'student_id' => $student->id,
            'subject_key' => "student:{$student->id}",
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(ConsentResponsesRelationManager::class, [
            'ownerRecord' => $student,
            'pageClass' => EditStudent::class,
        ])->assertOk()
            ->assertCanSeeTableRecords([$consentResponse]);
    }

    public function test_unauthorized_user_cannot_access_students(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->get('/admin/students')
            ->assertForbidden();
    }

    public function test_students_from_other_families_are_not_shown_in_guardians(): void
    {
        $family = Family::factory()->create();
        $student = Student::factory()->create(['family_id' => $family->id]);
        $guardian = Guardian::factory()->create(['family_id' => $family->id]);

        $otherFamily = Family::factory()->create();
        $otherGuardian = Guardian::factory()->create(['family_id' => $otherFamily->id]);

        $this->actingAs($this->superAdmin);

        Livewire::test(GuardiansRelationManager::class, [
            'ownerRecord' => $student,
            'pageClass' => EditStudent::class,
        ])->assertOk()
            ->assertCanSeeTableRecords([$guardian])
            ->assertCanNotSeeTableRecords([$otherGuardian]);
    }
}
