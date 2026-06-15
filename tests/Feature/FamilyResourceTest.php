<?php

namespace Tests\Feature;

use App\Filament\Resources\Families\Pages\EditFamily;
use App\Filament\Resources\Families\RelationManagers\ConsentResponsesRelationManager;
use App\Filament\Resources\Families\RelationManagers\EnrollmentsRelationManager;
use App\Filament\Resources\Families\RelationManagers\FormResponsesRelationManager;
use App\Filament\Resources\Families\RelationManagers\GuardiansRelationManager;
use App\Filament\Resources\Families\RelationManagers\StudentsRelationManager;
use App\Models\ConsentResponse;
use App\Models\Enrollment;
use App\Models\Family;
use App\Models\FormResponse;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FamilyResourceTest extends TestCase
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

    public function test_super_admin_can_list_families(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/families')
            ->assertOk();
    }

    public function test_super_admin_can_access_edit_family_page(): void
    {
        $family = Family::factory()->create();

        $this->actingAs($this->superAdmin)
            ->get("/admin/families/{$family->id}/edit")
            ->assertOk();
    }

    public function test_guardians_relation_manager_shows_guardians(): void
    {
        $family = Family::factory()->create();
        $guardian = Guardian::factory()->create(['family_id' => $family->id]);

        $this->actingAs($this->superAdmin);

        Livewire::test(GuardiansRelationManager::class, [
            'ownerRecord' => $family,
            'pageClass' => EditFamily::class,
        ])->assertOk()
            ->assertCanSeeTableRecords([$guardian]);
    }

    public function test_students_relation_manager_shows_students(): void
    {
        $family = Family::factory()->create();
        $student = Student::factory()->create(['family_id' => $family->id]);

        $this->actingAs($this->superAdmin);

        Livewire::test(StudentsRelationManager::class, [
            'ownerRecord' => $family,
            'pageClass' => EditFamily::class,
        ])->assertOk()
            ->assertCanSeeTableRecords([$student]);
    }

    public function test_enrollments_relation_manager_shows_enrollments(): void
    {
        $family = Family::factory()->create();
        $enrollment = Enrollment::factory()->create(['family_id' => $family->id]);

        $this->actingAs($this->superAdmin);

        Livewire::test(EnrollmentsRelationManager::class, [
            'ownerRecord' => $family,
            'pageClass' => EditFamily::class,
        ])->assertOk()
            ->assertCanSeeTableRecords([$enrollment]);
    }

    public function test_form_responses_relation_manager_shows_responses(): void
    {
        $family = Family::factory()->create();
        $response = FormResponse::factory()->create(['family_id' => $family->id]);

        $this->actingAs($this->superAdmin);

        Livewire::test(FormResponsesRelationManager::class, [
            'ownerRecord' => $family,
            'pageClass' => EditFamily::class,
        ])->assertOk()
            ->assertCanSeeTableRecords([$response]);
    }

    public function test_consent_responses_relation_manager_shows_responses(): void
    {
        $family = Family::factory()->create();
        $consentResponse = ConsentResponse::factory()->create(['family_id' => $family->id]);

        $this->actingAs($this->superAdmin);

        Livewire::test(ConsentResponsesRelationManager::class, [
            'ownerRecord' => $family,
            'pageClass' => EditFamily::class,
        ])->assertOk()
            ->assertCanSeeTableRecords([$consentResponse]);
    }

    public function test_unauthorized_user_cannot_access_families(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->get('/admin/families')
            ->assertForbidden();
    }
}
