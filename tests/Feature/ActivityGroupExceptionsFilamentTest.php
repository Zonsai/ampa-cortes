<?php

namespace Tests\Feature;

use App\Enums\ExceptionType;
use App\Filament\Resources\ActivityGroups\ActivityGroupResource;
use App\Filament\Resources\ActivityGroups\Pages\EditActivityGroup;
use App\Filament\Resources\ActivityGroups\RelationManagers\ExceptionsRelationManager;
use App\Filament\Resources\ExtracurricularActivities\Pages\EditExtracurricularActivity;
use App\Filament\Resources\ExtracurricularActivities\RelationManagers\ActivityGroupsRelationManager;
use App\Models\AcademicYear;
use App\Models\ActivityGroup;
use App\Models\ActivityGroupException;
use App\Models\AuditLog;
use App\Models\ExtracurricularActivity;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ActivityGroupExceptionsFilamentTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private AcademicYear $year;

    private ExtracurricularActivity $activity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->superAdmin = User::factory()->create(['email_verified_at' => now()]);
        $this->superAdmin->assignRole('super_admin');

        $this->year = AcademicYear::factory()->create([
            'name' => '2055-2056',
            'starts_at' => '2055-09-01',
            'ends_at' => '2056-06-30',
            'is_active' => true,
        ]);

        $this->activity = ExtracurricularActivity::factory()->create([
            'academic_year_id' => $this->year->id,
        ]);
    }

    // ── 1. Periodo efectivo de grupo ────────────────────────────────────────

    public function test_can_save_valid_effective_period_via_relation_manager(): void
    {
        $group = ActivityGroup::factory()->create(['activity_id' => $this->activity->id]);

        $this->actingAs($this->superAdmin);

        Livewire::test(ActivityGroupsRelationManager::class, [
            'ownerRecord' => $this->activity,
            'pageClass' => EditExtracurricularActivity::class,
        ])
            ->callTableAction('edit', $group, data: [
                'name' => $group->name,
                'weekdays' => $group->weekdays,
                'starts_at' => $group->starts_at,
                'ends_at' => $group->ends_at,
                'effective_from' => '2055-10-01',
                'effective_until' => '2055-12-31',
                'max_spots' => $group->max_spots,
                'price_member' => $group->price_member,
                'price_non_member' => $group->price_non_member,
                'status' => $group->status->value,
            ])
            ->assertHasNoTableActionErrors();

        $fresh = $group->fresh();
        $this->assertEquals('2055-10-01', $fresh->effective_from->format('Y-m-d'));
        $this->assertEquals('2055-12-31', $fresh->effective_until->format('Y-m-d'));
    }

    public function test_cannot_save_effective_period_with_start_after_end(): void
    {
        $group = ActivityGroup::factory()->create(['activity_id' => $this->activity->id]);

        $this->actingAs($this->superAdmin);

        Livewire::test(ActivityGroupsRelationManager::class, [
            'ownerRecord' => $this->activity,
            'pageClass' => EditExtracurricularActivity::class,
        ])
            ->callTableAction('edit', $group, data: [
                'name' => $group->name,
                'weekdays' => $group->weekdays,
                'starts_at' => $group->starts_at,
                'ends_at' => $group->ends_at,
                'effective_from' => '2055-12-31',
                'effective_until' => '2055-10-01',
                'max_spots' => $group->max_spots,
                'price_member' => $group->price_member,
                'price_non_member' => $group->price_non_member,
                'status' => $group->status->value,
            ])
            ->assertHasTableActionErrors(['effective_until']);
    }

    public function test_empty_effective_period_inherits_academic_year_dates(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'effective_from' => null,
            'effective_until' => null,
        ]);

        $this->assertEquals('2055-09-01', $group->effectiveStartDate()->format('Y-m-d'));
        $this->assertEquals('2056-06-30', $group->effectiveEndDate()->format('Y-m-d'));
    }

    // ── 2. Creación de cada tipo de excepción ───────────────────────────────

    public function test_can_create_cancelled_exception_via_filament(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1], // Monday
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(ExceptionsRelationManager::class, [
            'ownerRecord' => $group,
            'pageClass' => EditActivityGroup::class,
        ])
            ->callTableAction('create', data: [
                'type' => ExceptionType::Cancelled->value,
                'original_date' => '2055-09-06', // a Monday
                'reason' => 'Festivo local',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('activity_group_exceptions', [
            'activity_group_id' => $group->id,
            'type' => ExceptionType::Cancelled->value,
            'reason' => 'Festivo local',
        ]);
    }

    public function test_can_create_modified_exception_via_filament(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1],
            'starts_at' => '16:00:00',
            'ends_at' => '17:00:00',
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(ExceptionsRelationManager::class, [
            'ownerRecord' => $group,
            'pageClass' => EditActivityGroup::class,
        ])
            ->callTableAction('create', data: [
                'type' => ExceptionType::Modified->value,
                'original_date' => '2055-09-06',
                'new_starts_at' => '18:00:00',
                'new_ends_at' => '19:00:00',
                'reason' => 'Cambio de horario puntual',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('activity_group_exceptions', [
            'activity_group_id' => $group->id,
            'type' => ExceptionType::Modified->value,
        ]);
    }

    public function test_can_create_extra_exception_via_filament(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1],
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(ExceptionsRelationManager::class, [
            'ownerRecord' => $group,
            'pageClass' => EditActivityGroup::class,
        ])
            ->callTableAction('create', data: [
                'type' => ExceptionType::Extra->value,
                'new_date' => '2055-09-10', // Friday, not a recurring weekday for this group
                'new_starts_at' => '10:00:00',
                'new_ends_at' => '12:00:00',
                'new_location' => 'Gimnasio',
                'reason' => 'Recuperación',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('activity_group_exceptions', [
            'activity_group_id' => $group->id,
            'type' => ExceptionType::Extra->value,
            'new_location' => 'Gimnasio',
        ]);
    }

    // ── 3. Restricciones de campos según tipo ───────────────────────────────

    public function test_creating_extra_without_required_fields_fails_gracefully(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1],
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(ExceptionsRelationManager::class, [
            'ownerRecord' => $group,
            'pageClass' => EditActivityGroup::class,
        ])
            ->callTableAction('create', data: [
                'type' => ExceptionType::Extra->value,
                'new_date' => '2055-09-10',
                // missing new_starts_at / new_ends_at
            ])
            ->assertHasTableActionErrors();

        $this->assertDatabaseCount('activity_group_exceptions', 0);
    }

    public function test_creating_cancelled_with_original_date_not_matching_weekday_shows_error_notification(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1], // Monday only
        ]);

        $this->actingAs($this->superAdmin);

        // 2055-09-05 is a Sunday, not in weekdays
        Livewire::test(ExceptionsRelationManager::class, [
            'ownerRecord' => $group,
            'pageClass' => EditActivityGroup::class,
        ])
            ->callTableAction('create', data: [
                'type' => ExceptionType::Cancelled->value,
                'original_date' => '2055-09-05',
                'reason' => 'Motivo',
            ])
            ->assertNotified();

        $this->assertDatabaseCount('activity_group_exceptions', 0);
    }

    // ── 4. Editar y borrar excepciones ──────────────────────────────────────

    public function test_can_edit_exception_via_filament(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1],
        ]);

        $exception = ActivityGroupException::factory()->cancelled()->create([
            'activity_group_id' => $group->id,
            'original_date' => '2055-09-06',
            'reason' => 'Motivo original',
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(ExceptionsRelationManager::class, [
            'ownerRecord' => $group,
            'pageClass' => EditActivityGroup::class,
        ])
            ->callTableAction('edit', $exception, data: [
                'type' => ExceptionType::Cancelled->value,
                'original_date' => '2055-09-06',
                'reason' => 'Motivo actualizado',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertEquals('Motivo actualizado', $exception->fresh()->reason);
    }

    public function test_can_delete_exception_via_filament(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1],
        ]);

        $exception = ActivityGroupException::factory()->cancelled()->create([
            'activity_group_id' => $group->id,
            'original_date' => '2055-09-06',
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(ExceptionsRelationManager::class, [
            'ownerRecord' => $group,
            'pageClass' => EditActivityGroup::class,
        ])
            ->callTableAction('delete', $exception);

        $this->assertModelMissing($exception);
    }

    // ── 5. Auditoría de excepciones ─────────────────────────────────────────

    public function test_creating_exception_records_audit_log(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1],
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(ExceptionsRelationManager::class, [
            'ownerRecord' => $group,
            'pageClass' => EditActivityGroup::class,
        ])
            ->callTableAction('create', data: [
                'type' => ExceptionType::Cancelled->value,
                'original_date' => '2055-09-06',
                'reason' => 'Festivo',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('audit_logs', ['action' => AuditLog::GROUP_EXCEPTION_CREATED]);
    }

    public function test_editing_exception_records_audit_log(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1],
        ]);

        $exception = ActivityGroupException::factory()->cancelled()->create([
            'activity_group_id' => $group->id,
            'original_date' => '2055-09-06',
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(ExceptionsRelationManager::class, [
            'ownerRecord' => $group,
            'pageClass' => EditActivityGroup::class,
        ])
            ->callTableAction('edit', $exception, data: [
                'type' => ExceptionType::Cancelled->value,
                'original_date' => '2055-09-06',
                'reason' => 'Motivo editado',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('audit_logs', ['action' => AuditLog::GROUP_EXCEPTION_UPDATED]);
    }

    public function test_deleting_exception_records_audit_log(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1],
        ]);

        $exception = ActivityGroupException::factory()->cancelled()->create([
            'activity_group_id' => $group->id,
            'original_date' => '2055-09-06',
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(ExceptionsRelationManager::class, [
            'ownerRecord' => $group,
            'pageClass' => EditActivityGroup::class,
        ])
            ->callTableAction('delete', $exception);

        $this->assertDatabaseHas('audit_logs', ['action' => AuditLog::GROUP_EXCEPTION_DELETED]);
    }

    // ── Acceso a la página oculta ────────────────────────────────────────────

    public function test_hidden_resource_is_not_in_navigation(): void
    {
        $this->assertSame([], ActivityGroupResource::getNavigationItems());
    }
}
