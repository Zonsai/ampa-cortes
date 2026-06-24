<?php

namespace Tests\Feature;

use App\Actions\Enrollments\DropEnrollmentAction;
use App\Actions\Enrollments\RegisterPaymentAction;
use App\Actions\Enrollments\VoidPaymentAction;
use App\Enums\EnrollmentStatus;
use App\Enums\PaymentMethod;
use App\Filament\Resources\Enrollments\Pages\EditEnrollment;
use App\Filament\Resources\Enrollments\Pages\ListEnrollments;
use App\Models\AcademicYear;
use App\Models\ActivityGroup;
use App\Models\AuditLog;
use App\Models\Enrollment;
use App\Models\ExtracurricularActivity;
use App\Models\Family;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EnrollmentAttendanceActionsTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private AcademicYear $year;

    private ActivityGroup $group;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->superAdmin = User::factory()->create(['email_verified_at' => now()]);
        $this->superAdmin->assignRole('super_admin');

        $this->year = AcademicYear::factory()->create([
            'name' => '2060-2061',
            'starts_at' => '2060-09-01',
            'ends_at' => '2061-06-30',
            'is_active' => true,
        ]);

        $activity = ExtracurricularActivity::factory()->create(['academic_year_id' => $this->year->id]);

        $this->group = ActivityGroup::factory()->create([
            'activity_id' => $activity->id,
            'effective_from' => null,
            'effective_until' => null,
        ]);
    }

    // ── 5. Confirmar solicitud con fecha de incorporación sugerida ──────────

    public function test_confirm_request_suggests_group_start_when_course_has_not_started_yet(): void
    {
        // $this->group's course starts in 2060, which is after "today" in tests.
        $enrollment = Enrollment::factory()->create([
            'activity_id' => $this->group->activity_id,
            'activity_group_id' => $this->group->id,
            'academic_year_id' => $this->year->id,
            'status' => EnrollmentStatus::Pending,
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(ListEnrollments::class)
            ->mountTableAction('confirm_request', $enrollment)
            ->assertTableActionDataSet([
                'attendance_from' => '2060-09-01',
            ]);
    }

    public function test_confirm_request_suggests_today_when_course_already_started(): void
    {
        $pastYear = AcademicYear::factory()->create([
            'name' => '2021-2022',
            'starts_at' => '2021-09-01',
            'ends_at' => '2022-06-30',
            'is_active' => false,
        ]);
        $pastActivity = ExtracurricularActivity::factory()->create(['academic_year_id' => $pastYear->id]);
        $pastGroup = ActivityGroup::factory()->create([
            'activity_id' => $pastActivity->id,
            'effective_from' => null,
            'effective_until' => null,
        ]);

        $enrollment = Enrollment::factory()->create([
            'activity_id' => $pastGroup->activity_id,
            'activity_group_id' => $pastGroup->id,
            'academic_year_id' => $pastYear->id,
            'status' => EnrollmentStatus::Pending,
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(ListEnrollments::class)
            ->removeTableFilters()
            ->mountTableAction('confirm_request', $enrollment)
            ->assertTableActionDataSet([
                'attendance_from' => today()->format('Y-m-d'),
            ]);
    }

    public function test_confirm_request_applies_chosen_attendance_from(): void
    {
        $enrollment = Enrollment::factory()->create([
            'activity_id' => $this->group->activity_id,
            'activity_group_id' => $this->group->id,
            'academic_year_id' => $this->year->id,
            'status' => EnrollmentStatus::Pending,
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(ListEnrollments::class)
            ->callTableAction('confirm_request', $enrollment, data: [
                'attendance_from' => '2060-10-15',
            ])
            ->assertHasNoTableActionErrors();

        $fresh = $enrollment->fresh();
        $this->assertEquals(EnrollmentStatus::Enrolled, $fresh->status);
        $this->assertEquals('2060-10-15', $fresh->attendance_from->format('Y-m-d'));
    }

    // ── 6. Promover desde lista de espera con fecha de incorporación sugerida ─

    public function test_promote_suggests_group_start_when_course_has_not_started_yet(): void
    {
        // $this->group's course starts in 2060, which is after "today" in tests.
        $enrollment = Enrollment::factory()->create([
            'activity_id' => $this->group->activity_id,
            'activity_group_id' => $this->group->id,
            'academic_year_id' => $this->year->id,
            'status' => EnrollmentStatus::Waitlist,
            'waitlist_position' => 1,
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(ListEnrollments::class)
            ->mountTableAction('promote', $enrollment)
            ->assertTableActionDataSet([
                'attendance_from' => '2060-09-01',
            ]);
    }

    public function test_promote_applies_chosen_attendance_from(): void
    {
        $enrollment = Enrollment::factory()->create([
            'activity_id' => $this->group->activity_id,
            'activity_group_id' => $this->group->id,
            'academic_year_id' => $this->year->id,
            'status' => EnrollmentStatus::Waitlist,
            'waitlist_position' => 1,
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(ListEnrollments::class)
            ->callTableAction('promote', $enrollment, data: [
                'attendance_from' => '2060-11-01',
            ])
            ->assertHasNoTableActionErrors();

        $fresh = $enrollment->fresh();
        $this->assertEquals(EnrollmentStatus::Enrolled, $fresh->status);
        $this->assertEquals('2060-11-01', $fresh->attendance_from->format('Y-m-d'));
    }

    // ── 7. Baja con fecha final sugerida ──────────────────────────────────────

    public function test_drop_suggests_today_as_attendance_until(): void
    {
        $enrollment = Enrollment::factory()->create([
            'activity_id' => $this->group->activity_id,
            'activity_group_id' => $this->group->id,
            'academic_year_id' => $this->year->id,
            'status' => EnrollmentStatus::Enrolled,
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(ListEnrollments::class)
            ->mountTableAction('drop', $enrollment)
            ->assertTableActionDataSet([
                'attendance_until' => today()->format('Y-m-d'),
            ]);
    }

    public function test_drop_applies_chosen_attendance_until(): void
    {
        $enrollment = Enrollment::factory()->create([
            'activity_id' => $this->group->activity_id,
            'activity_group_id' => $this->group->id,
            'academic_year_id' => $this->year->id,
            'status' => EnrollmentStatus::Enrolled,
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(ListEnrollments::class)
            ->callTableAction('drop', $enrollment, data: [
                'attendance_until' => '2060-12-20',
            ])
            ->assertHasNoTableActionErrors();

        $fresh = $enrollment->fresh();
        $this->assertEquals(EnrollmentStatus::Dropped, $fresh->status);
        $this->assertEquals('2060-12-20', $fresh->attendance_until->format('Y-m-d'));
    }

    // ── 8. Pagos y anulaciones no alteran fechas de asistencia ─────────────────

    public function test_register_payment_does_not_change_attendance_dates(): void
    {
        $enrollment = Enrollment::factory()->create([
            'activity_id' => $this->group->activity_id,
            'activity_group_id' => $this->group->id,
            'academic_year_id' => $this->year->id,
            'status' => EnrollmentStatus::Enrolled,
            'attendance_from' => '2060-10-01',
            'attendance_until' => '2061-05-01',
        ]);

        app(RegisterPaymentAction::class)->execute($enrollment, now(), PaymentMethod::Cash);

        $fresh = $enrollment->fresh();
        $this->assertEquals('2060-10-01', $fresh->attendance_from->format('Y-m-d'));
        $this->assertEquals('2061-05-01', $fresh->attendance_until->format('Y-m-d'));
    }

    public function test_void_payment_does_not_change_attendance_dates(): void
    {
        $enrollment = Enrollment::factory()->create([
            'activity_id' => $this->group->activity_id,
            'activity_group_id' => $this->group->id,
            'academic_year_id' => $this->year->id,
            'status' => EnrollmentStatus::Paid,
            'paid_at' => now(),
            'payment_method' => PaymentMethod::Cash,
            'attendance_from' => '2060-10-01',
            'attendance_until' => '2061-05-01',
        ]);

        app(VoidPaymentAction::class)->execute($enrollment);

        $fresh = $enrollment->fresh();
        $this->assertEquals('2060-10-01', $fresh->attendance_from->format('Y-m-d'));
        $this->assertEquals('2061-05-01', $fresh->attendance_until->format('Y-m-d'));
    }

    public function test_cancel_does_not_set_attendance_dates(): void
    {
        $enrollment = Enrollment::factory()->create([
            'activity_id' => $this->group->activity_id,
            'activity_group_id' => $this->group->id,
            'academic_year_id' => $this->year->id,
            'status' => EnrollmentStatus::Pending,
            'attendance_from' => null,
            'attendance_until' => null,
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(ListEnrollments::class)
            ->callTableAction('cancel', $enrollment)
            ->assertHasNoTableActionErrors();

        $fresh = $enrollment->fresh();
        $this->assertEquals(EnrollmentStatus::Cancelled, $fresh->status);
        $this->assertNull($fresh->attendance_from);
        $this->assertNull($fresh->attendance_until);
    }

    public function test_move_to_waitlist_does_not_set_attendance_dates(): void
    {
        $enrollment = Enrollment::factory()->create([
            'activity_id' => $this->group->activity_id,
            'activity_group_id' => $this->group->id,
            'academic_year_id' => $this->year->id,
            'status' => EnrollmentStatus::Pending,
            'attendance_from' => null,
            'attendance_until' => null,
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(ListEnrollments::class)
            ->callTableAction('move_to_waitlist', $enrollment)
            ->assertHasNoTableActionErrors();

        $fresh = $enrollment->fresh();
        $this->assertEquals(EnrollmentStatus::Waitlist, $fresh->status);
        $this->assertNull($fresh->attendance_from);
        $this->assertNull($fresh->attendance_until);
    }

    public function test_drop_without_attendance_until_does_not_overwrite_with_null(): void
    {
        $enrollment = Enrollment::factory()->create([
            'activity_id' => $this->group->activity_id,
            'activity_group_id' => $this->group->id,
            'academic_year_id' => $this->year->id,
            'status' => EnrollmentStatus::Enrolled,
        ]);

        app(DropEnrollmentAction::class)->execute($enrollment);

        $this->assertNull($enrollment->fresh()->attendance_until);
    }

    // ── 9. Auditoría de fechas de asistencia ───────────────────────────────────

    public function test_manual_attendance_edit_records_audit_log(): void
    {
        $family = Family::factory()->create();
        $student = Student::factory()->create(['family_id' => $family->id]);

        $enrollment = Enrollment::factory()->create([
            'activity_id' => $this->group->activity_id,
            'activity_group_id' => $this->group->id,
            'academic_year_id' => $this->year->id,
            'family_id' => $family->id,
            'student_id' => $student->id,
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(EditEnrollment::class, ['record' => $enrollment->getRouteKey()])
            ->fillForm(['attendance_from' => '2060-10-10'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('audit_logs', ['action' => AuditLog::ENROLLMENT_ATTENDANCE_UPDATED]);
    }

    public function test_editing_unrelated_field_does_not_record_attendance_audit_log(): void
    {
        $family = Family::factory()->create();
        $student = Student::factory()->create(['family_id' => $family->id]);

        $enrollment = Enrollment::factory()->create([
            'activity_id' => $this->group->activity_id,
            'activity_group_id' => $this->group->id,
            'academic_year_id' => $this->year->id,
            'family_id' => $family->id,
            'student_id' => $student->id,
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(EditEnrollment::class, ['record' => $enrollment->getRouteKey()])
            ->fillForm(['internal_notes' => 'Nota interna sin relación con asistencia'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseMissing('audit_logs', ['action' => AuditLog::ENROLLMENT_ATTENDANCE_UPDATED]);
    }

    public function test_confirm_request_records_audit_log_with_attendance_from(): void
    {
        $enrollment = Enrollment::factory()->create([
            'activity_id' => $this->group->activity_id,
            'activity_group_id' => $this->group->id,
            'academic_year_id' => $this->year->id,
            'status' => EnrollmentStatus::Pending,
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(ListEnrollments::class)
            ->callTableAction('confirm_request', $enrollment, data: [
                'attendance_from' => '2060-10-15',
            ])
            ->assertHasNoTableActionErrors();

        $log = AuditLog::where('action', AuditLog::ENROLLMENT_CONFIRMED)->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertEquals('2060-10-15', $enrollment->fresh()->attendance_from->format('Y-m-d'));
    }
}
