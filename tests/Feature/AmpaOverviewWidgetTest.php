<?php

namespace Tests\Feature;

use App\Enums\ActivityGroupStatus;
use App\Enums\ActivityStatus;
use App\Enums\ConsentResponseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\FormStatus;
use App\Filament\Widgets\AmpaOverviewWidget;
use App\Models\AcademicYear;
use App\Models\ActivityGroup;
use App\Models\ConsentResponse;
use App\Models\ConsentType;
use App\Models\ConsentVersion;
use App\Models\Enrollment;
use App\Models\ExtracurricularActivity;
use App\Models\Family;
use App\Models\Form;
use App\Models\FormResponse;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TestCase;

class AmpaOverviewWidgetTest extends TestCase
{
    use RefreshDatabase;

    private AcademicYear $activeYear;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->activeYear = AcademicYear::factory()->create(['is_active' => true, 'name' => 'Curso 2025/2026']);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function createEnrollment(EnrollmentStatus $status, float $amount = 0): Enrollment
    {
        $family = Family::factory()->create();
        $student = Student::factory()->create(['family_id' => $family->id, 'is_active' => true]);
        $activity = ExtracurricularActivity::factory()->create([
            'academic_year_id' => $this->activeYear->id,
            'status' => ActivityStatus::Published,
        ]);
        $group = ActivityGroup::factory()->create([
            'activity_id' => $activity->id,
            'status' => ActivityGroupStatus::Open,
        ]);

        return Enrollment::factory()->create([
            'student_id' => $student->id,
            'family_id' => $family->id,
            'activity_id' => $activity->id,
            'activity_group_id' => $group->id,
            'academic_year_id' => $this->activeYear->id,
            'status' => $status,
            'amount' => $amount,
        ]);
    }

    private function createConsentResponse(ConsentResponseStatus $status): ConsentResponse
    {
        $family = Family::factory()->create();
        $type = ConsentType::factory()->published()->create();
        $version = ConsentVersion::factory()->published()->create(['consent_type_id' => $type->id]);

        return ConsentResponse::create([
            'consent_type_id' => $type->id,
            'consent_version_id' => $version->id,
            'family_id' => $family->id,
            'student_id' => null,
            'subject_key' => "family:{$family->id}",
            'status' => $status,
            'responded_at' => $status === ConsentResponseStatus::Pending ? null : now(),
        ]);
    }

    // ─── Access / visibility per role ─────────────────────────────────────────

    public function test_super_admin_sees_full_overview(): void
    {
        Livewire::actingAs($this->userWithRole('super_admin'))
            ->test(AmpaOverviewWidget::class)
            ->assertOk()
            ->assertSee('Curso académico')
            ->assertSee('Familias')
            ->assertSee('Lista de espera')
            ->assertSee('Consentimientos pendientes');
    }

    public function test_junta_ampa_sees_full_overview(): void
    {
        Livewire::actingAs($this->userWithRole('junta_ampa'))
            ->test(AmpaOverviewWidget::class)
            ->assertOk()
            ->assertSee('Familias')
            ->assertSee('Lista de espera')
            ->assertSee('Formularios abiertos');
    }

    public function test_admin_extraescolares_sees_only_extracurricular_block(): void
    {
        Livewire::actingAs($this->userWithRole('admin_extraescolares'))
            ->test(AmpaOverviewWidget::class)
            ->assertOk()
            ->assertSee('Curso académico')
            ->assertSee('Lista de espera')
            ->assertDontSee('Familias')
            ->assertDontSee('Consentimientos pendientes');
    }

    public function test_admin_formularios_sees_only_forms_and_consents_block(): void
    {
        Livewire::actingAs($this->userWithRole('admin_formularios'))
            ->test(AmpaOverviewWidget::class)
            ->assertOk()
            ->assertSee('Curso académico')
            ->assertSee('Formularios abiertos')
            ->assertSee('Consentimientos pendientes')
            ->assertDontSee('Familias')
            ->assertDontSee('Lista de espera');
    }

    public function test_widget_is_hidden_for_familia_role(): void
    {
        $user = $this->userWithRole('familia');

        $this->actingAs($user);
        $this->assertFalse(AmpaOverviewWidget::canView());
    }

    public function test_familia_cannot_access_admin_panel(): void
    {
        $family = Family::factory()->create();
        $user = $this->userWithRole('familia');
        Guardian::factory()->create(['user_id' => $user->id, 'family_id' => $family->id]);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    // ─── Academic year ────────────────────────────────────────────────────────

    public function test_shows_active_academic_year_name(): void
    {
        Livewire::actingAs($this->userWithRole('super_admin'))
            ->test(AmpaOverviewWidget::class)
            ->assertSee('Curso 2025/2026');
    }

    public function test_shows_warning_when_no_active_year(): void
    {
        $this->activeYear->update(['is_active' => false]);

        Livewire::actingAs($this->userWithRole('super_admin'))
            ->test(AmpaOverviewWidget::class)
            ->assertSee('Sin curso activo');
    }

    // ─── Data: extracurricular / forms / consents ─────────────────────────────

    public function test_shows_pending_requests_waitlist_and_payments(): void
    {
        $this->createEnrollment(EnrollmentStatus::Pending);
        $this->createEnrollment(EnrollmentStatus::Waitlist);
        $this->createEnrollment(EnrollmentStatus::PendingPayment, 25.00);

        Livewire::actingAs($this->userWithRole('junta_ampa'))
            ->test(AmpaOverviewWidget::class)
            ->assertSee('Solicitudes pendientes')
            ->assertSee('Lista de espera')
            ->assertSee('Pagos pendientes')
            ->assertSee('25,00 €'); // pending payment amount sum

    }

    public function test_shows_open_forms_and_responses(): void
    {
        $form = Form::factory()->create([
            'academic_year_id' => $this->activeYear->id,
            'status' => FormStatus::Published,
        ]);
        $family = Family::factory()->create();
        FormResponse::create([
            'form_id' => $form->id,
            'family_id' => $family->id,
            'student_id' => null,
            'response_key' => 'family:'.$family->id,
            'submitted_at' => now(),
        ]);

        Livewire::actingAs($this->userWithRole('junta_ampa'))
            ->test(AmpaOverviewWidget::class)
            ->assertSee('Formularios abiertos')
            ->assertSee('respuestas recibidas');
    }

    public function test_shows_pending_and_accepted_consents(): void
    {
        $this->createConsentResponse(ConsentResponseStatus::Pending);
        $this->createConsentResponse(ConsentResponseStatus::Accepted);

        Livewire::actingAs($this->userWithRole('junta_ampa'))
            ->test(AmpaOverviewWidget::class)
            ->assertSee('Consentimientos pendientes')
            ->assertSee('Consentimientos aceptados');
    }

    // ─── Dashboard composition (clean dashboard) ──────────────────────────────

    public function test_admin_dashboard_shows_overview_widget_with_links(): void
    {
        $response = $this->actingAs($this->userWithRole('super_admin'))->get('/admin');

        $response->assertOk();
        $response->assertSee('Resumen del AMPA');
        $response->assertSee('Curso académico');
        // Stats link to their resource (super_admin can access Families).
        $response->assertSee('/admin/families', false);
    }

    public function test_admin_dashboard_does_not_show_filament_info_widget(): void
    {
        $this->actingAs($this->userWithRole('super_admin'))
            ->get('/admin')
            ->assertOk()
            ->assertDontSee('GitHub')        // FilamentInfoWidget links removed
            ->assertDontSee('Documentation');
    }

    public function test_default_account_and_info_widgets_are_not_registered(): void
    {
        $widgets = Filament::getPanel('admin')->getWidgets();

        $this->assertContains(AmpaOverviewWidget::class, $widgets);
        $this->assertNotContains(AccountWidget::class, $widgets);
        $this->assertNotContains(FilamentInfoWidget::class, $widgets);
    }

    public function test_admin_logout_route_still_exists(): void
    {
        $this->assertTrue(Route::has('filament.admin.auth.logout'));
    }
}
