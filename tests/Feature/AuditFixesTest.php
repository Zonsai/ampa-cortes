<?php

namespace Tests\Feature;

use App\Filament\Resources\AcademicYears\Pages\CreateAcademicYear;
use App\Filament\Resources\AcademicYears\Pages\EditAcademicYear;
use App\Filament\Resources\Classrooms\Pages\CreateClassroom;
use App\Filament\Resources\Grades\Pages\CreateGrade;
use App\Filament\Resources\SchoolStages\Pages\CreateSchoolStage;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\SchoolStage;
use App\Models\User;
use App\Policies\AcademicYearPolicy;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuditFixesTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $juntaUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->superAdmin = User::factory()->create([
            'email' => 'audit-super@ampa.test',
            'email_verified_at' => now(),
        ]);
        $this->superAdmin->assignRole('super_admin');

        $this->juntaUser = User::factory()->create([
            'email' => 'audit-junta@ampa.test',
            'email_verified_at' => now(),
        ]);
        $this->juntaUser->assignRole('junta_ampa');
    }

    // ── 1. Validaciones unique en formularios ─────────────────────────────────

    public function test_academic_year_name_must_be_unique(): void
    {
        AcademicYear::create([
            'name' => '2025-2026',
            'starts_at' => '2025-09-01',
            'ends_at' => '2026-06-30',
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(CreateAcademicYear::class)
            ->fillForm([
                'name' => '2025-2026',
                'starts_at' => '2025-09-01',
                'ends_at' => '2026-06-30',
            ])
            ->call('create')
            ->assertHasFormErrors(['name' => 'unique']);
    }

    public function test_school_stage_name_must_be_unique(): void
    {
        SchoolStage::create(['name' => 'Primaria', 'sort_order' => 1]);

        Livewire::actingAs($this->superAdmin)
            ->test(CreateSchoolStage::class)
            ->fillForm(['name' => 'Primaria', 'sort_order' => 2])
            ->call('create')
            ->assertHasFormErrors(['name' => 'unique']);
    }

    public function test_grade_name_must_be_unique_within_school_stage(): void
    {
        $schoolStage = SchoolStage::create(['name' => 'Primaria', 'sort_order' => 1]);
        Grade::create(['school_stage_id' => $schoolStage->id, 'name' => '1º Primaria', 'sort_order' => 1]);

        Livewire::actingAs($this->superAdmin)
            ->test(CreateGrade::class)
            ->fillForm([
                'school_stage_id' => $schoolStage->id,
                'name' => '1º Primaria',
                'sort_order' => 2,
            ])
            ->call('create')
            ->assertHasFormErrors(['name']);
    }

    public function test_grade_same_name_allowed_in_different_school_stage(): void
    {
        $infantil = SchoolStage::create(['name' => 'Infantil', 'sort_order' => 1]);
        $primaria = SchoolStage::create(['name' => 'Primaria', 'sort_order' => 2]);
        Grade::create(['school_stage_id' => $infantil->id, 'name' => '3 años', 'sort_order' => 1]);

        Livewire::actingAs($this->superAdmin)
            ->test(CreateGrade::class)
            ->fillForm([
                'school_stage_id' => $primaria->id,
                'name' => '3 años',
                'sort_order' => 1,
            ])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    public function test_classroom_name_must_be_unique_within_year_and_grade(): void
    {
        $year = AcademicYear::create(['name' => '2025-2026', 'starts_at' => '2025-09-01', 'ends_at' => '2026-06-30']);
        $stage = SchoolStage::create(['name' => 'Primaria', 'sort_order' => 1]);
        $grade = Grade::create(['school_stage_id' => $stage->id, 'name' => '1º Primaria', 'sort_order' => 1]);
        Classroom::create(['academic_year_id' => $year->id, 'grade_id' => $grade->id, 'name' => 'A']);

        Livewire::actingAs($this->superAdmin)
            ->test(CreateClassroom::class)
            ->fillForm([
                'academic_year_id' => $year->id,
                'grade_id' => $grade->id,
                'name' => 'A',
            ])
            ->call('create')
            ->assertHasFormErrors(['name']);
    }

    public function test_classroom_same_name_allowed_in_different_grade(): void
    {
        $year = AcademicYear::create(['name' => '2025-2026', 'starts_at' => '2025-09-01', 'ends_at' => '2026-06-30']);
        $stage = SchoolStage::create(['name' => 'Primaria', 'sort_order' => 1]);
        $grade1 = Grade::create(['school_stage_id' => $stage->id, 'name' => '1º Primaria', 'sort_order' => 1]);
        $grade2 = Grade::create(['school_stage_id' => $stage->id, 'name' => '2º Primaria', 'sort_order' => 2]);
        Classroom::create(['academic_year_id' => $year->id, 'grade_id' => $grade1->id, 'name' => 'A']);

        Livewire::actingAs($this->superAdmin)
            ->test(CreateClassroom::class)
            ->fillForm([
                'academic_year_id' => $year->id,
                'grade_id' => $grade2->id,
                'name' => 'A',
            ])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    // ── 2. Protección de histórico: borrar curso escolar con clases ───────────

    public function test_junta_ampa_cannot_delete_academic_year_with_classrooms(): void
    {
        $year = AcademicYear::create(['name' => '2024-2025', 'starts_at' => '2024-09-01', 'ends_at' => '2025-06-30']);
        $stage = SchoolStage::create(['name' => 'Primaria', 'sort_order' => 1]);
        $grade = Grade::create(['school_stage_id' => $stage->id, 'name' => '1º Primaria', 'sort_order' => 1]);
        Classroom::create(['academic_year_id' => $year->id, 'grade_id' => $grade->id, 'name' => 'A']);

        $policy = new AcademicYearPolicy;

        $this->assertFalse($policy->delete($this->juntaUser, $year));
    }

    public function test_junta_ampa_can_delete_academic_year_without_classrooms(): void
    {
        $year = AcademicYear::create(['name' => '2024-2025', 'starts_at' => '2024-09-01', 'ends_at' => '2025-06-30']);

        $policy = new AcademicYearPolicy;

        $this->assertTrue($policy->delete($this->juntaUser, $year));
    }

    // ── 3. Único curso escolar activo ─────────────────────────────────────────

    public function test_activating_academic_year_via_edit_deactivates_others(): void
    {
        $year1 = AcademicYear::create([
            'name' => '2023-2024',
            'starts_at' => '2023-09-01',
            'ends_at' => '2024-06-30',
            'is_active' => true,
        ]);
        $year2 = AcademicYear::create([
            'name' => '2024-2025',
            'starts_at' => '2024-09-01',
            'ends_at' => '2025-06-30',
            'is_active' => false,
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(EditAcademicYear::class, ['record' => $year2->id])
            ->fillForm(['is_active' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse($year1->fresh()->is_active);
        $this->assertTrue($year2->fresh()->is_active);
    }

    public function test_activating_academic_year_via_create_deactivates_others(): void
    {
        $year1 = AcademicYear::create([
            'name' => '2023-2024',
            'starts_at' => '2023-09-01',
            'ends_at' => '2024-06-30',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(CreateAcademicYear::class)
            ->fillForm([
                'name' => '2024-2025',
                'starts_at' => '2024-09-01',
                'ends_at' => '2025-06-30',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertFalse($year1->fresh()->is_active);
        $this->assertTrue(AcademicYear::where('name', '2024-2025')->first()->is_active);
    }
}
