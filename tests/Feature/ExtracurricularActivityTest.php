<?php

namespace Tests\Feature;

use App\Enums\ActivityGroupStatus;
use App\Enums\ActivityStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\PriceType;
use App\Models\AcademicYear;
use App\Models\ExtracurricularActivity;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtracurricularActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    // ── Slug automático ───────────────────────────────────────────────────────

    public function test_slug_is_auto_generated_on_creation(): void
    {
        $year = AcademicYear::factory()->create();
        $activity = ExtracurricularActivity::factory()->create([
            'academic_year_id' => $year->id,
            'name' => 'Fútbol Sala',
        ]);

        $this->assertEquals('futbol-sala', $activity->slug);
    }

    public function test_slug_is_made_unique_within_same_academic_year(): void
    {
        $year = AcademicYear::factory()->create();

        $first = ExtracurricularActivity::factory()->create([
            'academic_year_id' => $year->id,
            'name' => 'Tenis',
        ]);
        $second = ExtracurricularActivity::factory()->create([
            'academic_year_id' => $year->id,
            'name' => 'Tenis',
        ]);
        $third = ExtracurricularActivity::factory()->create([
            'academic_year_id' => $year->id,
            'name' => 'Tenis',
        ]);

        $this->assertEquals('tenis', $first->slug);
        $this->assertEquals('tenis-2', $second->slug);
        $this->assertEquals('tenis-3', $third->slug);
    }

    public function test_same_name_allowed_in_different_academic_years(): void
    {
        $year1 = AcademicYear::factory()->create();
        $year2 = AcademicYear::factory()->create();

        $activity1 = ExtracurricularActivity::factory()->create([
            'academic_year_id' => $year1->id,
            'name' => 'Natación',
        ]);
        $activity2 = ExtracurricularActivity::factory()->create([
            'academic_year_id' => $year2->id,
            'name' => 'Natación',
        ]);

        $this->assertEquals('natacion', $activity1->slug);
        $this->assertEquals('natacion', $activity2->slug);
    }

    public function test_slug_regenerates_when_name_changes(): void
    {
        $year = AcademicYear::factory()->create();
        $activity = ExtracurricularActivity::factory()->create([
            'academic_year_id' => $year->id,
            'name' => 'Ballet',
        ]);

        $this->assertEquals('ballet', $activity->slug);

        $activity->update(['name' => 'Ballet Clásico']);

        $this->assertEquals('ballet-clasico', $activity->fresh()->slug);
    }

    public function test_slug_does_not_regenerate_when_other_fields_change(): void
    {
        $year = AcademicYear::factory()->create();
        $activity = ExtracurricularActivity::factory()->create([
            'academic_year_id' => $year->id,
            'name' => 'Kárate',
        ]);

        $originalSlug = $activity->slug;
        $activity->update(['status' => ActivityStatus::Closed]);

        $this->assertEquals($originalSlug, $activity->fresh()->slug);
    }

    // ── Etiquetas en español (HasLabel / HasColor) ────────────────────────────

    public function test_activity_status_labels_are_in_spanish(): void
    {
        $this->assertEquals('Borrador', ActivityStatus::Draft->getLabel());
        $this->assertEquals('Publicada', ActivityStatus::Published->getLabel());
        $this->assertEquals('Cerrada', ActivityStatus::Closed->getLabel());
        $this->assertEquals('Archivada', ActivityStatus::Archived->getLabel());
    }

    public function test_activity_group_status_labels_are_in_spanish(): void
    {
        $this->assertEquals('Abierto', ActivityGroupStatus::Open->getLabel());
        $this->assertEquals('Cerrado', ActivityGroupStatus::Closed->getLabel());
        $this->assertEquals('Completo', ActivityGroupStatus::Full->getLabel());
        $this->assertEquals('Archivado', ActivityGroupStatus::Archived->getLabel());
    }

    public function test_enrollment_status_labels_are_in_spanish(): void
    {
        $this->assertEquals('Pendiente', EnrollmentStatus::Pending->getLabel());
        $this->assertEquals('Inscrito/a', EnrollmentStatus::Enrolled->getLabel());
        $this->assertEquals('Lista de espera', EnrollmentStatus::Waitlist->getLabel());
        $this->assertEquals('Baja', EnrollmentStatus::Dropped->getLabel());
        $this->assertEquals('Pendiente de pago', EnrollmentStatus::PendingPayment->getLabel());
        $this->assertEquals('Pagado', EnrollmentStatus::Paid->getLabel());
        $this->assertEquals('Cancelado', EnrollmentStatus::Cancelled->getLabel());
    }

    public function test_price_type_labels_are_in_spanish(): void
    {
        $this->assertEquals('Socio AMPA', PriceType::Member->getLabel());
        $this->assertEquals('No socio', PriceType::NonMember->getLabel());
    }

    public function test_enum_getcolor_returns_correct_filament_colors(): void
    {
        $this->assertEquals('success', ActivityStatus::Published->getColor());
        $this->assertEquals('gray', ActivityStatus::Draft->getColor());
        $this->assertEquals('warning', ActivityStatus::Closed->getColor());

        $this->assertEquals('success', ActivityGroupStatus::Open->getColor());
        $this->assertEquals('danger', ActivityGroupStatus::Full->getColor());

        $this->assertEquals('success', EnrollmentStatus::Enrolled->getColor());
        $this->assertEquals('warning', EnrollmentStatus::Waitlist->getColor());
        $this->assertEquals('danger', EnrollmentStatus::Dropped->getColor());

        $this->assertEquals('success', PriceType::Member->getColor());
        $this->assertEquals('gray', PriceType::NonMember->getColor());
    }
}
