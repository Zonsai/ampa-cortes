<?php

namespace Tests\Feature;

use App\Enums\ActivityStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\FormStatus;
use App\Filament\Resources\Enrollments\Pages\ListEnrollments;
use App\Filament\Resources\ExtracurricularActivities\Pages\ListExtracurricularActivities;
use App\Filament\Resources\Families\Pages\EditFamily;
use App\Filament\Resources\Families\RelationManagers\EnrollmentsRelationManager as FamilyEnrollmentsRelationManager;
use App\Filament\Resources\Forms\Pages\ListForms;
use App\Filament\Resources\Students\Pages\EditStudent;
use App\Filament\Resources\Students\RelationManagers\EnrollmentsRelationManager as StudentEnrollmentsRelationManager;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\ExtracurricularActivity;
use App\Models\Family;
use App\Models\Form;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AcademicYearFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private AcademicYear $activeYear;

    private AcademicYear $oldYear;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->create(['email_verified_at' => now()]);
        $this->admin->assignRole('super_admin');

        $this->activeYear = AcademicYear::factory()->create([
            'name' => '2040-2041',
            'is_active' => true,
            'starts_at' => '2040-09-01',
            'ends_at' => '2041-06-30',
        ]);

        $this->oldYear = AcademicYear::factory()->create([
            'name' => '2039-2040',
            'is_active' => false,
            'starts_at' => '2039-09-01',
            'ends_at' => '2040-06-30',
        ]);
    }

    // ── Enrollments table ────────────────────────────────────────────────────

    public function test_enrollments_table_defaults_to_active_year(): void
    {
        $current = Enrollment::factory()->create(['academic_year_id' => $this->activeYear->id]);
        $old = Enrollment::factory()->create(['academic_year_id' => $this->oldYear->id]);

        $this->actingAs($this->admin);

        Livewire::test(ListEnrollments::class)
            ->assertCanSeeTableRecords([$current])
            ->assertCanNotSeeTableRecords([$old]);
    }

    public function test_enrollments_table_shows_all_when_filter_cleared(): void
    {
        $current = Enrollment::factory()->create(['academic_year_id' => $this->activeYear->id]);
        $old = Enrollment::factory()->create(['academic_year_id' => $this->oldYear->id]);

        $this->actingAs($this->admin);

        Livewire::test(ListEnrollments::class)
            ->removeTableFilters()
            ->assertCanSeeTableRecords([$current, $old]);
    }

    public function test_enrollments_table_can_filter_to_old_year(): void
    {
        $current = Enrollment::factory()->create(['academic_year_id' => $this->activeYear->id]);
        $old = Enrollment::factory()->create(['academic_year_id' => $this->oldYear->id]);

        $this->actingAs($this->admin);

        Livewire::test(ListEnrollments::class)
            ->filterTable('academic_year_id', $this->oldYear->id)
            ->assertCanSeeTableRecords([$old])
            ->assertCanNotSeeTableRecords([$current]);
    }

    public function test_enrollments_filters_are_visible(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ListEnrollments::class)
            ->assertTableFilterVisible('status')
            ->assertTableFilterVisible('academic_year_id');
    }

    public function test_enrollments_status_filter_works(): void
    {
        $enrolled = Enrollment::factory()->create([
            'academic_year_id' => $this->activeYear->id,
            'status' => EnrollmentStatus::Enrolled,
        ]);
        $pending = Enrollment::factory()->create([
            'academic_year_id' => $this->activeYear->id,
            'status' => EnrollmentStatus::Pending,
        ]);

        $this->actingAs($this->admin);

        Livewire::test(ListEnrollments::class)
            ->filterTable('status', EnrollmentStatus::Enrolled->value)
            ->assertCanSeeTableRecords([$enrolled])
            ->assertCanNotSeeTableRecords([$pending]);
    }

    // ── Activities table ─────────────────────────────────────────────────────

    public function test_activities_table_defaults_to_active_year(): void
    {
        $current = ExtracurricularActivity::factory()->create([
            'academic_year_id' => $this->activeYear->id,
            'status' => ActivityStatus::Published,
        ]);
        $old = ExtracurricularActivity::factory()->create([
            'academic_year_id' => $this->oldYear->id,
            'status' => ActivityStatus::Published,
        ]);

        $this->actingAs($this->admin);

        Livewire::test(ListExtracurricularActivities::class)
            ->assertCanSeeTableRecords([$current])
            ->assertCanNotSeeTableRecords([$old]);
    }

    public function test_activities_table_shows_all_when_filter_cleared(): void
    {
        $current = ExtracurricularActivity::factory()->create(['academic_year_id' => $this->activeYear->id]);
        $old = ExtracurricularActivity::factory()->create(['academic_year_id' => $this->oldYear->id]);

        $this->actingAs($this->admin);

        Livewire::test(ListExtracurricularActivities::class)
            ->removeTableFilters()
            ->assertCanSeeTableRecords([$current, $old]);
    }

    public function test_activities_table_can_filter_to_old_year(): void
    {
        $current = ExtracurricularActivity::factory()->create(['academic_year_id' => $this->activeYear->id]);
        $old = ExtracurricularActivity::factory()->create(['academic_year_id' => $this->oldYear->id]);

        $this->actingAs($this->admin);

        Livewire::test(ListExtracurricularActivities::class)
            ->filterTable('academic_year_id', $this->oldYear->id)
            ->assertCanSeeTableRecords([$old])
            ->assertCanNotSeeTableRecords([$current]);
    }

    // ── Forms table ──────────────────────────────────────────────────────────

    public function test_forms_table_defaults_to_active_year(): void
    {
        $current = Form::factory()->create([
            'academic_year_id' => $this->activeYear->id,
            'status' => FormStatus::Published,
        ]);
        $old = Form::factory()->create([
            'academic_year_id' => $this->oldYear->id,
            'status' => FormStatus::Published,
        ]);

        $this->actingAs($this->admin);

        Livewire::test(ListForms::class)
            ->assertCanSeeTableRecords([$current])
            ->assertCanNotSeeTableRecords([$old]);
    }

    public function test_forms_table_shows_all_when_filter_cleared(): void
    {
        $current = Form::factory()->create(['academic_year_id' => $this->activeYear->id]);
        $old = Form::factory()->create(['academic_year_id' => $this->oldYear->id]);

        $this->actingAs($this->admin);

        Livewire::test(ListForms::class)
            ->removeTableFilters()
            ->assertCanSeeTableRecords([$current, $old]);
    }

    public function test_forms_table_can_filter_to_old_year(): void
    {
        $current = Form::factory()->create(['academic_year_id' => $this->activeYear->id]);
        $old = Form::factory()->create(['academic_year_id' => $this->oldYear->id]);

        $this->actingAs($this->admin);

        Livewire::test(ListForms::class)
            ->filterTable('academic_year_id', $this->oldYear->id)
            ->assertCanSeeTableRecords([$old])
            ->assertCanNotSeeTableRecords([$current]);
    }

    // ── No active year scenario ──────────────────────────────────────────────

    public function test_enrollments_table_shows_all_when_no_active_year(): void
    {
        $this->activeYear->update(['is_active' => false]);

        $e1 = Enrollment::factory()->create(['academic_year_id' => $this->activeYear->id]);
        $e2 = Enrollment::factory()->create(['academic_year_id' => $this->oldYear->id]);

        $this->actingAs($this->admin);

        Livewire::test(ListEnrollments::class)
            ->assertCanSeeTableRecords([$e1, $e2]);
    }

    public function test_activities_table_shows_all_when_no_active_year(): void
    {
        $this->activeYear->update(['is_active' => false]);

        $a1 = ExtracurricularActivity::factory()->create(['academic_year_id' => $this->activeYear->id]);
        $a2 = ExtracurricularActivity::factory()->create(['academic_year_id' => $this->oldYear->id]);

        $this->actingAs($this->admin);

        Livewire::test(ListExtracurricularActivities::class)
            ->assertCanSeeTableRecords([$a1, $a2]);
    }

    public function test_forms_table_shows_all_when_no_active_year(): void
    {
        $this->activeYear->update(['is_active' => false]);

        $f1 = Form::factory()->create(['academic_year_id' => $this->activeYear->id]);
        $f2 = Form::factory()->create(['academic_year_id' => $this->oldYear->id]);

        $this->actingAs($this->admin);

        Livewire::test(ListForms::class)
            ->assertCanSeeTableRecords([$f1, $f2]);
    }

    // ── Family enrollments RelationManager ───────────────────────────────────

    public function test_family_enrollments_relation_defaults_to_active_year(): void
    {
        $family = Family::factory()->create();
        $student = Student::factory()->create(['family_id' => $family->id]);

        $current = Enrollment::factory()->create([
            'family_id' => $family->id,
            'student_id' => $student->id,
            'academic_year_id' => $this->activeYear->id,
        ]);
        $old = Enrollment::factory()->create([
            'family_id' => $family->id,
            'student_id' => $student->id,
            'academic_year_id' => $this->oldYear->id,
        ]);

        $this->actingAs($this->admin);

        Livewire::test(FamilyEnrollmentsRelationManager::class, [
            'ownerRecord' => $family,
            'pageClass' => EditFamily::class,
        ])
            ->assertCanSeeTableRecords([$current])
            ->assertCanNotSeeTableRecords([$old]);
    }

    public function test_family_enrollments_relation_shows_all_when_filter_cleared(): void
    {
        $family = Family::factory()->create();
        $student = Student::factory()->create(['family_id' => $family->id]);

        $current = Enrollment::factory()->create([
            'family_id' => $family->id,
            'student_id' => $student->id,
            'academic_year_id' => $this->activeYear->id,
        ]);
        $old = Enrollment::factory()->create([
            'family_id' => $family->id,
            'student_id' => $student->id,
            'academic_year_id' => $this->oldYear->id,
        ]);

        $this->actingAs($this->admin);

        Livewire::test(FamilyEnrollmentsRelationManager::class, [
            'ownerRecord' => $family,
            'pageClass' => EditFamily::class,
        ])
            ->removeTableFilters()
            ->assertCanSeeTableRecords([$current, $old]);
    }

    // ── Student enrollments RelationManager ──────────────────────────────────

    public function test_student_enrollments_relation_defaults_to_active_year(): void
    {
        $family = Family::factory()->create();
        $student = Student::factory()->create(['family_id' => $family->id]);

        $current = Enrollment::factory()->create([
            'student_id' => $student->id,
            'family_id' => $family->id,
            'academic_year_id' => $this->activeYear->id,
        ]);
        $old = Enrollment::factory()->create([
            'student_id' => $student->id,
            'family_id' => $family->id,
            'academic_year_id' => $this->oldYear->id,
        ]);

        $this->actingAs($this->admin);

        Livewire::test(StudentEnrollmentsRelationManager::class, [
            'ownerRecord' => $student,
            'pageClass' => EditStudent::class,
        ])
            ->assertCanSeeTableRecords([$current])
            ->assertCanNotSeeTableRecords([$old]);
    }

    public function test_student_enrollments_relation_shows_all_when_filter_cleared(): void
    {
        $family = Family::factory()->create();
        $student = Student::factory()->create(['family_id' => $family->id]);

        $current = Enrollment::factory()->create([
            'student_id' => $student->id,
            'family_id' => $family->id,
            'academic_year_id' => $this->activeYear->id,
        ]);
        $old = Enrollment::factory()->create([
            'student_id' => $student->id,
            'family_id' => $family->id,
            'academic_year_id' => $this->oldYear->id,
        ]);

        $this->actingAs($this->admin);

        Livewire::test(StudentEnrollmentsRelationManager::class, [
            'ownerRecord' => $student,
            'pageClass' => EditStudent::class,
        ])
            ->removeTableFilters()
            ->assertCanSeeTableRecords([$current, $old]);
    }
}
