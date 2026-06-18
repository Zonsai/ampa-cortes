<?php

namespace Tests\Feature;

use App\Actions\Enrollments\CancelEnrollmentAction;
use App\Actions\Enrollments\ConfirmEnrollmentAction;
use App\Actions\Enrollments\MoveToWaitlistAction;
use App\Actions\Enrollments\RegisterPaymentAction;
use App\Actions\Enrollments\RequestFamilyEnrollmentAction;
use App\Actions\Enrollments\VoidPaymentAction;
use App\Enums\ActivityGroupStatus;
use App\Enums\ActivityStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\PaymentMethod;
use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Filament\Resources\Guardians\Pages\ListGuardians;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\AcademicYear;
use App\Models\ActivityGroup;
use App\Models\AuditLog;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\ExtracurricularActivity;
use App\Models\Family;
use App\Models\Grade;
use App\Models\Guardian;
use App\Models\SchoolStage;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private AcademicYear $year;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->year = AcademicYear::factory()->create(['is_active' => true]);

        $this->admin = User::factory()->create(['name' => 'Junta Demo']);
        $this->admin->assignRole('junta_ampa');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    /**
     * @return array{enrollment: Enrollment, family: Family, student: Student, group: ActivityGroup}
     */
    private function makeEnrollment(EnrollmentStatus $status = EnrollmentStatus::Enrolled): array
    {
        $family = Family::factory()->create(['is_ampa_member' => true]);
        $student = Student::factory()->create(['family_id' => $family->id, 'is_active' => true]);

        $stage = SchoolStage::firstOrCreate(['name' => 'General', 'sort_order' => 99]);
        $grade = Grade::firstOrCreate(['school_stage_id' => $stage->id, 'name' => 'General'], ['sort_order' => 99]);
        $classroom = Classroom::firstOrCreate(['academic_year_id' => $this->year->id, 'grade_id' => $grade->id, 'name' => 'A']);
        $student->classrooms()->attach($classroom->id, ['enrolled_at' => now()]);

        $activity = ExtracurricularActivity::factory()->create([
            'academic_year_id' => $this->year->id,
            'status' => ActivityStatus::Published,
            'is_visible_for_families' => true,
            'requires_ampa_membership' => false,
            'name' => 'Inglés',
        ]);
        $group = ActivityGroup::factory()->create([
            'activity_id' => $activity->id,
            'max_spots' => 10,
            'status' => ActivityGroupStatus::Open,
            'name' => 'Grupo A',
        ]);

        $enrollment = Enrollment::factory()->create([
            'student_id' => $student->id,
            'family_id' => $family->id,
            'activity_id' => $activity->id,
            'activity_group_id' => $group->id,
            'academic_year_id' => $this->year->id,
            'status' => $status,
            'amount' => 25.00,
        ]);

        return compact('enrollment', 'family', 'student', 'group');
    }

    // ─── Pagos ────────────────────────────────────────────────────────────────

    public function test_register_payment_creates_audit_log(): void
    {
        ['enrollment' => $enrollment] = $this->makeEnrollment(EnrollmentStatus::PendingPayment);

        $this->actingAs($this->admin);
        app(RegisterPaymentAction::class)->execute($enrollment, now(), PaymentMethod::Cash);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::PAYMENT_REGISTERED,
            'actor_id' => $this->admin->id,
            'subject_type' => Enrollment::class,
            'subject_id' => $enrollment->id,
        ]);
    }

    public function test_void_payment_creates_audit_log(): void
    {
        ['enrollment' => $enrollment] = $this->makeEnrollment(EnrollmentStatus::Paid);

        $this->actingAs($this->admin);
        app(VoidPaymentAction::class)->execute($enrollment);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::PAYMENT_VOIDED,
            'actor_id' => $this->admin->id,
            'subject_id' => $enrollment->id,
        ]);
    }

    public function test_register_void_register_void_cycle_creates_four_logs(): void
    {
        ['enrollment' => $enrollment] = $this->makeEnrollment(EnrollmentStatus::PendingPayment);
        $this->actingAs($this->admin);

        app(RegisterPaymentAction::class)->execute($enrollment->fresh(), now(), PaymentMethod::Cash);
        app(VoidPaymentAction::class)->execute($enrollment->fresh());
        app(RegisterPaymentAction::class)->execute($enrollment->fresh(), now(), PaymentMethod::Cash);
        $final = app(VoidPaymentAction::class)->execute($enrollment->fresh());

        // The cycle leaves it back in PendingPayment without breaking state.
        $this->assertSame(EnrollmentStatus::PendingPayment, $final->status);

        $this->assertSame(2, AuditLog::where('action', AuditLog::PAYMENT_REGISTERED)->count());
        $this->assertSame(2, AuditLog::where('action', AuditLog::PAYMENT_VOIDED)->count());
    }

    public function test_payment_log_keeps_actor_subject_and_before_after(): void
    {
        ['enrollment' => $enrollment, 'student' => $student, 'family' => $family] = $this->makeEnrollment(EnrollmentStatus::PendingPayment);

        $this->actingAs($this->admin);
        app(RegisterPaymentAction::class)->execute($enrollment, now(), PaymentMethod::BankTransfer);

        $log = AuditLog::where('action', AuditLog::PAYMENT_REGISTERED)->latest('id')->first();

        $this->assertSame('Junta Demo', $log->actor_name);
        $this->assertSame($student->full_name.' — Inglés', $log->subject_label);
        $this->assertSame('pending_payment', $log->properties['status_before']);
        $this->assertSame('paid', $log->properties['status_after']);
        $this->assertSame($student->full_name, $log->properties['student']);
        $this->assertSame($family->name, $log->properties['family']);
        $this->assertSame('Inglés', $log->properties['activity']);
        $this->assertSame('bank_transfer', $log->properties['payment_method']);
    }

    // ─── Inscripciones ─────────────────────────────────────────────────────────

    public function test_confirm_enrollment_creates_audit_log(): void
    {
        ['enrollment' => $enrollment] = $this->makeEnrollment(EnrollmentStatus::Pending);

        $this->actingAs($this->admin);
        app(ConfirmEnrollmentAction::class)->execute($enrollment);

        $this->assertDatabaseHas('audit_logs', ['action' => AuditLog::ENROLLMENT_CONFIRMED, 'subject_id' => $enrollment->id]);
    }

    public function test_move_to_waitlist_creates_audit_log(): void
    {
        ['enrollment' => $enrollment] = $this->makeEnrollment(EnrollmentStatus::Pending);

        $this->actingAs($this->admin);
        app(MoveToWaitlistAction::class)->execute($enrollment);

        $this->assertDatabaseHas('audit_logs', ['action' => AuditLog::ENROLLMENT_WAITLISTED, 'subject_id' => $enrollment->id]);
    }

    public function test_cancel_enrollment_creates_audit_log(): void
    {
        ['enrollment' => $enrollment] = $this->makeEnrollment(EnrollmentStatus::Pending);

        $this->actingAs($this->admin);
        app(CancelEnrollmentAction::class)->execute($enrollment);

        $this->assertDatabaseHas('audit_logs', ['action' => AuditLog::ENROLLMENT_CANCELLED, 'subject_id' => $enrollment->id]);
    }

    public function test_family_request_creates_audit_log_with_family_actor(): void
    {
        ['family' => $family, 'student' => $student, 'group' => $group] = $this->makeEnrollment(EnrollmentStatus::Pending);

        $familyUser = $this->userWithRole('familia');
        Guardian::factory()->create(['user_id' => $familyUser->id, 'family_id' => $family->id]);

        // A different student in the same family, eligible for the group.
        $newStudent = Student::factory()->create(['family_id' => $family->id, 'is_active' => true]);
        $classroom = $student->classrooms()->first();
        $newStudent->classrooms()->attach($classroom->id, ['enrolled_at' => now()]);

        $this->actingAs($familyUser);
        app(RequestFamilyEnrollmentAction::class)->execute($newStudent, $group, $family, $this->year);

        $log = AuditLog::where('action', AuditLog::ENROLLMENT_REQUESTED)->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame($familyUser->id, $log->actor_id);
    }

    // ─── Usuarios ──────────────────────────────────────────────────────────────

    public function test_password_reset_creates_audit_log_without_the_password(): void
    {
        $superAdmin = $this->userWithRole('super_admin');
        $target = $this->userWithRole('familia');

        Livewire::actingAs($superAdmin)
            ->test(ListUsers::class)
            ->callTableAction('reset_password', $target, data: [
                'password' => 'sup3rsecret-demo',
                'password_confirmation' => 'sup3rsecret-demo',
            ])
            ->assertHasNoTableActionErrors();

        $log = AuditLog::where('action', AuditLog::USER_PASSWORD_RESET)->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame($target->id, $log->subject_id);
        // The password must never appear anywhere in the log.
        $this->assertStringNotContainsString('sup3rsecret-demo', json_encode($log->properties).$log->description);
    }

    public function test_toggle_active_creates_audit_log(): void
    {
        $superAdmin = $this->userWithRole('super_admin');
        $target = $this->userWithRole('familia');

        Livewire::actingAs($superAdmin)
            ->test(ListUsers::class)
            ->callTableAction('toggle_active', $target);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::USER_DEACTIVATED,
            'subject_id' => $target->id,
        ]);
    }

    public function test_create_family_access_creates_audit_log(): void
    {
        $superAdmin = $this->userWithRole('super_admin');
        $family = Family::factory()->create();
        $guardian = Guardian::factory()->create([
            'family_id' => $family->id,
            'user_id' => null,
            'email' => 'tutor.audit@ampa.test',
        ]);

        Livewire::actingAs($superAdmin)
            ->test(ListGuardians::class)
            ->callTableAction('create_family_access', $guardian, data: [
                'name' => 'Tutor Audit',
                'email' => 'tutor.audit@ampa.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'is_email_verified' => true,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('audit_logs', ['action' => AuditLog::FAMILY_ACCESS_CREATED]);
    }

    // ─── Recurso Filament (solo lectura) ───────────────────────────────────────

    public function test_super_admin_can_view_audit_log_resource(): void
    {
        $this->actingAs($this->userWithRole('super_admin'))->get('/admin/audit-logs')->assertOk();
    }

    public function test_junta_ampa_can_view_audit_log_resource(): void
    {
        $this->actingAs($this->admin)->get('/admin/audit-logs')->assertOk();
    }

    public function test_admin_extraescolares_cannot_view_audit_log_resource(): void
    {
        $this->actingAs($this->userWithRole('admin_extraescolares'))->get('/admin/audit-logs')->assertForbidden();
    }

    public function test_admin_formularios_cannot_view_audit_log_resource(): void
    {
        $this->actingAs($this->userWithRole('admin_formularios'))->get('/admin/audit-logs')->assertForbidden();
    }

    public function test_familia_cannot_access_admin_or_audit_log(): void
    {
        $user = $this->userWithRole('familia');
        $family = Family::factory()->create();
        Guardian::factory()->create(['user_id' => $user->id, 'family_id' => $family->id]);

        $this->actingAs($user)->get('/admin/audit-logs')->assertForbidden();
    }

    public function test_audit_log_resource_is_read_only(): void
    {
        $this->assertFalse(AuditLogResource::canCreate());

        // No edit route exists for a read-only resource.
        $log = AuditLog::create(['action' => AuditLog::BRANDING_UPDATED]);
        $this->actingAs($this->userWithRole('super_admin'))
            ->get("/admin/audit-logs/{$log->id}/edit")
            ->assertNotFound();
    }
}
