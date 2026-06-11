<?php

namespace Tests\Feature;

use App\Enums\EnrollmentStatus;
use App\Enums\PriceType;
use App\Exports\Enrollments\EnrollmentsByActivityExport;
use App\Exports\Enrollments\EnrollmentsByClassroomExport;
use App\Exports\Enrollments\EnrollmentsByGroupExport;
use App\Exports\Enrollments\GlobalEnrollmentsExport;
use App\Exports\Enrollments\PendingPaymentsExport;
use App\Exports\Enrollments\WaitlistExport;
use App\Models\AcademicYear;
use App\Models\ActivityGroup;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\ExtracurricularActivity;
use App\Models\Family;
use App\Models\Grade;
use App\Models\SchoolStage;
use App\Models\Student;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExportsTest extends TestCase
{
    use RefreshDatabase;

    private AcademicYear $academicYear;

    private ExtracurricularActivity $activity;

    private ActivityGroup $group;

    private Family $family;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->academicYear = AcademicYear::factory()->create(['is_active' => true]);
        $this->activity = ExtracurricularActivity::factory()->create([
            'academic_year_id' => $this->academicYear->id,
        ]);
        $this->group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'max_spots' => 10,
        ]);
        $this->family = Family::factory()->ampaMember()->create();
        $this->student = Student::factory()->create(['family_id' => $this->family->id]);
    }

    private function createEnrollment(array $overrides = []): Enrollment
    {
        return Enrollment::create(array_merge([
            'student_id' => $this->student->id,
            'family_id' => $this->family->id,
            'activity_id' => $this->activity->id,
            'activity_group_id' => $this->group->id,
            'academic_year_id' => $this->academicYear->id,
            'status' => EnrollmentStatus::Enrolled,
            'registered_at' => now(),
            'enrolled_at' => now(),
            'amount' => 30.00,
            'price_type' => PriceType::Member,
        ], $overrides));
    }

    // ── 1. EnrollmentsByActivityExport ────────────────────────────────────────

    public function test_enrollments_by_activity_returns_only_matching_activity(): void
    {
        $otherActivity = ExtracurricularActivity::factory()->create([
            'academic_year_id' => $this->academicYear->id,
        ]);
        $otherGroup = ActivityGroup::factory()->create(['activity_id' => $otherActivity->id]);
        $otherFamily = Family::factory()->create();
        $otherStudent = Student::factory()->create(['family_id' => $otherFamily->id]);

        $this->createEnrollment(); // matches
        Enrollment::create([       // different activity — should be excluded
            'student_id' => $otherStudent->id,
            'family_id' => $otherFamily->id,
            'activity_id' => $otherActivity->id,
            'activity_group_id' => $otherGroup->id,
            'academic_year_id' => $this->academicYear->id,
            'status' => EnrollmentStatus::Enrolled,
            'registered_at' => now(),
            'enrolled_at' => now(),
            'amount' => 25.00,
            'price_type' => PriceType::NonMember,
        ]);

        $results = (new EnrollmentsByActivityExport($this->activity->id))->query()->get();

        $this->assertCount(1, $results);
        $this->assertEquals($this->activity->id, $results->first()->activity_id);
    }

    // ── 2. EnrollmentsByGroupExport ───────────────────────────────────────────

    public function test_enrollments_by_group_returns_only_matching_group(): void
    {
        $otherGroup = ActivityGroup::factory()->create(['activity_id' => $this->activity->id]);
        $otherFamily = Family::factory()->create();
        $otherStudent = Student::factory()->create(['family_id' => $otherFamily->id]);

        $this->createEnrollment(); // matches
        Enrollment::create([       // different group
            'student_id' => $otherStudent->id,
            'family_id' => $otherFamily->id,
            'activity_id' => $this->activity->id,
            'activity_group_id' => $otherGroup->id,
            'academic_year_id' => $this->academicYear->id,
            'status' => EnrollmentStatus::Enrolled,
            'registered_at' => now(),
            'enrolled_at' => now(),
            'amount' => 30.00,
            'price_type' => PriceType::Member,
        ]);

        $results = (new EnrollmentsByGroupExport($this->group->id))->query()->get();

        $this->assertCount(1, $results);
        $this->assertEquals($this->group->id, $results->first()->activity_group_id);
    }

    // ── 3. WaitlistExport ─────────────────────────────────────────────────────

    public function test_waitlist_export_returns_only_waitlist_status(): void
    {
        $this->createEnrollment(['status' => EnrollmentStatus::Enrolled]);
        $this->createEnrollment([
            'status' => EnrollmentStatus::Waitlist,
            'enrolled_at' => null,
            'waitlist_position' => 1,
        ]);

        $results = (new WaitlistExport($this->group->id))->query()->get();

        $this->assertCount(1, $results);
        $this->assertEquals(EnrollmentStatus::Waitlist, $results->first()->status);
    }

    // ── 4. PendingPaymentsExport ──────────────────────────────────────────────

    public function test_pending_payments_export_returns_only_pending_payment_status(): void
    {
        $this->createEnrollment(['status' => EnrollmentStatus::Enrolled]);
        $this->createEnrollment(['status' => EnrollmentStatus::PendingPayment]);

        $otherYear = AcademicYear::factory()->create();
        $otherActivity = ExtracurricularActivity::factory()->create(['academic_year_id' => $otherYear->id]);
        $otherGroup = ActivityGroup::factory()->create(['activity_id' => $otherActivity->id]);
        $otherFamily = Family::factory()->create();
        $otherStudent = Student::factory()->create(['family_id' => $otherFamily->id]);
        Enrollment::create([
            'student_id' => $otherStudent->id,
            'family_id' => $otherFamily->id,
            'activity_id' => $otherActivity->id,
            'activity_group_id' => $otherGroup->id,
            'academic_year_id' => $otherYear->id,
            'status' => EnrollmentStatus::PendingPayment,
            'registered_at' => now(),
            'enrolled_at' => now(),
            'amount' => 30.00,
            'price_type' => PriceType::Member,
        ]);

        $results = (new PendingPaymentsExport($this->academicYear->id))->query()->get();

        $this->assertCount(1, $results);
        $this->assertEquals(EnrollmentStatus::PendingPayment, $results->first()->status);
        $this->assertEquals($this->academicYear->id, $results->first()->academic_year_id);
    }

    // ── 5. GlobalEnrollmentsExport ────────────────────────────────────────────

    public function test_global_enrollments_export_filters_by_academic_year(): void
    {
        $this->createEnrollment(); // current year
        $this->createEnrollment(['status' => EnrollmentStatus::Waitlist, 'enrolled_at' => null, 'waitlist_position' => 1]);

        $otherYear = AcademicYear::factory()->create();
        $otherActivity = ExtracurricularActivity::factory()->create(['academic_year_id' => $otherYear->id]);
        $otherGroup = ActivityGroup::factory()->create(['activity_id' => $otherActivity->id]);
        $otherFamily = Family::factory()->create();
        $otherStudent = Student::factory()->create(['family_id' => $otherFamily->id]);
        Enrollment::create([
            'student_id' => $otherStudent->id,
            'family_id' => $otherFamily->id,
            'activity_id' => $otherActivity->id,
            'activity_group_id' => $otherGroup->id,
            'academic_year_id' => $otherYear->id,
            'status' => EnrollmentStatus::Enrolled,
            'registered_at' => now(),
            'enrolled_at' => now(),
            'amount' => 30.00,
            'price_type' => PriceType::Member,
        ]);

        $results = (new GlobalEnrollmentsExport($this->academicYear->id))->query()->get();

        $this->assertCount(2, $results);
        $results->each(fn ($e) => $this->assertEquals($this->academicYear->id, $e->academic_year_id));
    }

    // ── 6. EnrollmentsByClassroomExport ───────────────────────────────────────

    public function test_enrollments_by_classroom_filters_by_classroom_and_year(): void
    {
        $stage = SchoolStage::create(['name' => 'Primaria', 'sort_order' => 1]);
        $grade = Grade::create(['school_stage_id' => $stage->id, 'name' => '3º Primaria', 'sort_order' => 3]);
        $classroom = Classroom::create([
            'academic_year_id' => $this->academicYear->id,
            'grade_id' => $grade->id,
            'name' => 'A',
            'tutor' => null,
        ]);

        DB::table('student_classroom')->insert([
            'student_id' => $this->student->id,
            'classroom_id' => $classroom->id,
            'enrolled_at' => now(),
        ]);

        $this->createEnrollment(); // student in classroom — should be included

        // Another student NOT in classroom
        $otherFamily = Family::factory()->create();
        $otherStudent = Student::factory()->create(['family_id' => $otherFamily->id]);
        Enrollment::create([
            'student_id' => $otherStudent->id,
            'family_id' => $otherFamily->id,
            'activity_id' => $this->activity->id,
            'activity_group_id' => $this->group->id,
            'academic_year_id' => $this->academicYear->id,
            'status' => EnrollmentStatus::Enrolled,
            'registered_at' => now(),
            'enrolled_at' => now(),
            'amount' => 30.00,
            'price_type' => PriceType::NonMember,
        ]);

        $results = (new EnrollmentsByClassroomExport($classroom->id, $this->academicYear->id))->query()->get();

        $this->assertCount(1, $results);
        $this->assertEquals($this->student->id, $results->first()->student_id);
    }
}
