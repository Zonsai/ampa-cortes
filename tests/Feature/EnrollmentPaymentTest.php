<?php

namespace Tests\Feature;

use App\Actions\Enrollments\MarkPendingPaymentAction;
use App\Actions\Enrollments\RegisterPaymentAction;
use App\Actions\Enrollments\VoidPaymentAction;
use App\Enums\EnrollmentStatus;
use App\Enums\PaymentMethod;
use App\Filament\Resources\Enrollments\Pages\ListEnrollments;
use App\Models\Enrollment;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class EnrollmentPaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $juntaAmpa;

    private User $adminExtraescolares;

    private User $adminFormularios;

    private User $familiaUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->superAdmin = User::factory()->create(['email_verified_at' => now()]);
        $this->superAdmin->assignRole('super_admin');

        $this->juntaAmpa = User::factory()->create(['email_verified_at' => now()]);
        $this->juntaAmpa->assignRole('junta_ampa');

        $this->adminExtraescolares = User::factory()->create(['email_verified_at' => now()]);
        $this->adminExtraescolares->assignRole('admin_extraescolares');

        $this->adminFormularios = User::factory()->create(['email_verified_at' => now()]);
        $this->adminFormularios->assignRole('admin_formularios');

        $this->familiaUser = User::factory()->create(['email_verified_at' => now()]);
        $this->familiaUser->assignRole('familia');
    }

    // ── Modelo ─────────────────────────────────────────────────────────────────

    public function test_enrollment_can_store_paid_at(): void
    {
        $enrollment = Enrollment::factory()->create();
        $paidAt = now()->setSecond(0)->setMicrosecond(0);

        $enrollment->update(['paid_at' => $paidAt]);

        $this->assertEquals($paidAt->toDateTimeString(), $enrollment->fresh()->paid_at->toDateTimeString());
    }

    public function test_enrollment_can_store_payment_method(): void
    {
        $enrollment = Enrollment::factory()->create();

        $enrollment->update(['payment_method' => PaymentMethod::Bizum]);

        $this->assertEquals(PaymentMethod::Bizum, $enrollment->fresh()->payment_method);
    }

    // ── MarkPendingPaymentAction ────────────────────────────────────────────────

    public function test_mark_pending_payment_transitions_enrolled_to_pending_payment(): void
    {
        $enrollment = Enrollment::factory()->create(['status' => EnrollmentStatus::Enrolled]);

        app(MarkPendingPaymentAction::class)->execute($enrollment);

        $this->assertEquals(EnrollmentStatus::PendingPayment, $enrollment->fresh()->status);
    }

    public function test_mark_pending_payment_clears_paid_fields(): void
    {
        $enrollment = Enrollment::factory()->create([
            'status' => EnrollmentStatus::Enrolled,
            'paid_at' => now(),
            'payment_method' => PaymentMethod::Cash,
        ]);

        app(MarkPendingPaymentAction::class)->execute($enrollment);

        $fresh = $enrollment->fresh();
        $this->assertNull($fresh->paid_at);
        $this->assertNull($fresh->payment_method);
    }

    public function test_mark_pending_payment_fails_for_non_enrolled_status(): void
    {
        $enrollment = Enrollment::factory()->create(['status' => EnrollmentStatus::Waitlist]);

        $this->expectException(ValidationException::class);
        app(MarkPendingPaymentAction::class)->execute($enrollment);
    }

    // ── RegisterPaymentAction ──────────────────────────────────────────────────

    public function test_register_payment_transitions_pending_payment_to_paid(): void
    {
        $enrollment = Enrollment::factory()->create(['status' => EnrollmentStatus::PendingPayment]);

        app(RegisterPaymentAction::class)->execute($enrollment, now(), PaymentMethod::Cash);

        $this->assertEquals(EnrollmentStatus::Paid, $enrollment->fresh()->status);
    }

    public function test_register_payment_transitions_enrolled_directly_to_paid(): void
    {
        $enrollment = Enrollment::factory()->create(['status' => EnrollmentStatus::Enrolled]);

        app(RegisterPaymentAction::class)->execute($enrollment, now(), PaymentMethod::BankTransfer);

        $this->assertEquals(EnrollmentStatus::Paid, $enrollment->fresh()->status);
    }

    public function test_register_payment_saves_paid_at(): void
    {
        $enrollment = Enrollment::factory()->create(['status' => EnrollmentStatus::PendingPayment]);
        $paidAt = now()->setSecond(0)->setMicrosecond(0);

        app(RegisterPaymentAction::class)->execute($enrollment, $paidAt, PaymentMethod::Card);

        $this->assertEquals($paidAt->toDateTimeString(), $enrollment->fresh()->paid_at->toDateTimeString());
    }

    public function test_register_payment_saves_payment_method(): void
    {
        $enrollment = Enrollment::factory()->create(['status' => EnrollmentStatus::PendingPayment]);

        app(RegisterPaymentAction::class)->execute($enrollment, now(), PaymentMethod::Bizum);

        $this->assertEquals(PaymentMethod::Bizum, $enrollment->fresh()->payment_method);
    }

    public function test_register_payment_appends_notes_without_destroying_existing(): void
    {
        $enrollment = Enrollment::factory()->create([
            'status' => EnrollmentStatus::PendingPayment,
            'internal_notes' => 'Nota previa',
        ]);

        app(RegisterPaymentAction::class)->execute($enrollment, now(), PaymentMethod::Cash, 'Nota nueva');

        $this->assertStringContainsString('Nota previa', $enrollment->fresh()->internal_notes);
        $this->assertStringContainsString('Nota nueva', $enrollment->fresh()->internal_notes);
    }

    public function test_register_payment_fails_for_waitlist(): void
    {
        $enrollment = Enrollment::factory()->create(['status' => EnrollmentStatus::Waitlist]);

        $this->expectException(ValidationException::class);
        app(RegisterPaymentAction::class)->execute($enrollment, now(), PaymentMethod::Cash);
    }

    public function test_register_payment_fails_for_cancelled(): void
    {
        $enrollment = Enrollment::factory()->create(['status' => EnrollmentStatus::Cancelled]);

        $this->expectException(ValidationException::class);
        app(RegisterPaymentAction::class)->execute($enrollment, now(), PaymentMethod::Cash);
    }

    public function test_register_payment_fails_for_dropped(): void
    {
        $enrollment = Enrollment::factory()->create(['status' => EnrollmentStatus::Dropped]);

        $this->expectException(ValidationException::class);
        app(RegisterPaymentAction::class)->execute($enrollment, now(), PaymentMethod::Cash);
    }

    // ── VoidPaymentAction ──────────────────────────────────────────────────────

    public function test_void_payment_transitions_paid_to_pending_payment(): void
    {
        $enrollment = Enrollment::factory()->create([
            'status' => EnrollmentStatus::Paid,
            'paid_at' => now(),
            'payment_method' => PaymentMethod::Cash,
        ]);

        app(VoidPaymentAction::class)->execute($enrollment);

        $this->assertEquals(EnrollmentStatus::PendingPayment, $enrollment->fresh()->status);
    }

    public function test_void_payment_clears_paid_at(): void
    {
        $enrollment = Enrollment::factory()->create([
            'status' => EnrollmentStatus::Paid,
            'paid_at' => now(),
            'payment_method' => PaymentMethod::Cash,
        ]);

        app(VoidPaymentAction::class)->execute($enrollment);

        $this->assertNull($enrollment->fresh()->paid_at);
    }

    public function test_void_payment_clears_payment_method(): void
    {
        $enrollment = Enrollment::factory()->create([
            'status' => EnrollmentStatus::Paid,
            'paid_at' => now(),
            'payment_method' => PaymentMethod::BankTransfer,
        ]);

        app(VoidPaymentAction::class)->execute($enrollment);

        $this->assertNull($enrollment->fresh()->payment_method);
    }

    public function test_void_payment_appends_void_note_to_internal_notes(): void
    {
        $enrollment = Enrollment::factory()->create([
            'status' => EnrollmentStatus::Paid,
            'paid_at' => now(),
            'payment_method' => PaymentMethod::Cash,
        ]);

        app(VoidPaymentAction::class)->execute($enrollment);

        $this->assertStringContainsString('Pago anulado el', $enrollment->fresh()->internal_notes);
    }

    public function test_void_payment_fails_for_non_paid_status(): void
    {
        $enrollment = Enrollment::factory()->create(['status' => EnrollmentStatus::PendingPayment]);

        $this->expectException(ValidationException::class);
        app(VoidPaymentAction::class)->execute($enrollment);
    }

    // ── Permisos ───────────────────────────────────────────────────────────────

    public function test_super_admin_can_manage_payments(): void
    {
        $enrollment = Enrollment::factory()->create();

        $this->assertTrue($this->superAdmin->can('managePayment', $enrollment));
    }

    public function test_junta_ampa_can_manage_payments(): void
    {
        $enrollment = Enrollment::factory()->create();

        $this->assertTrue($this->juntaAmpa->can('managePayment', $enrollment));
    }

    public function test_admin_extraescolares_cannot_manage_payments(): void
    {
        $enrollment = Enrollment::factory()->create();

        $this->assertFalse($this->adminExtraescolares->can('managePayment', $enrollment));
    }

    public function test_admin_formularios_cannot_manage_payments(): void
    {
        $enrollment = Enrollment::factory()->create();

        $this->assertFalse($this->adminFormularios->can('managePayment', $enrollment));
    }

    public function test_familia_cannot_manage_payments(): void
    {
        $enrollment = Enrollment::factory()->create();

        $this->assertFalse($this->familiaUser->can('managePayment', $enrollment));
    }

    // ── UI / Filament ──────────────────────────────────────────────────────────

    public function test_super_admin_can_access_enrollments_list(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/enrollments')
            ->assertOk();
    }

    public function test_junta_ampa_can_access_enrollments_list(): void
    {
        $this->actingAs($this->juntaAmpa)
            ->get('/admin/enrollments')
            ->assertOk();
    }

    public function test_payment_columns_render_without_error_for_super_admin(): void
    {
        Enrollment::factory()->create([
            'status' => EnrollmentStatus::Paid,
            'paid_at' => now(),
            'payment_method' => PaymentMethod::Cash,
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(ListEnrollments::class)
            ->assertOk();
    }

    public function test_payment_actions_visible_for_junta_ampa(): void
    {
        $enrollment = Enrollment::factory()->create(['status' => EnrollmentStatus::Enrolled]);

        $this->actingAs($this->juntaAmpa);

        Livewire::test(ListEnrollments::class)
            ->assertTableActionVisible('mark_pending_payment', $enrollment)
            ->assertTableActionVisible('register_payment', $enrollment);
    }

    public function test_payment_actions_hidden_for_admin_extraescolares(): void
    {
        $enrollment = Enrollment::factory()->create(['status' => EnrollmentStatus::Enrolled]);

        $this->actingAs($this->adminExtraescolares);

        Livewire::test(ListEnrollments::class)
            ->assertTableActionHidden('mark_pending_payment', $enrollment)
            ->assertTableActionHidden('register_payment', $enrollment);
    }
}
