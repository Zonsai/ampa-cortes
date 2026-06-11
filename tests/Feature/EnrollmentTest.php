<?php

namespace Tests\Feature;

use App\Actions\Enrollments\CancelEnrollmentAction;
use App\Actions\Enrollments\DropEnrollmentAction;
use App\Actions\Enrollments\EnrollStudentAction;
use App\Actions\Enrollments\PromoteFromWaitlistAction;
use App\Actions\Enrollments\SyncGroupStatusAction;
use App\Enums\ActivityGroupStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\PriceType;
use App\Models\AcademicYear;
use App\Models\ActivityGroup;
use App\Models\Enrollment;
use App\Models\ExtracurricularActivity;
use App\Models\Family;
use App\Models\Student;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EnrollmentTest extends TestCase
{
    use RefreshDatabase;

    private AcademicYear $academicYear;

    private ExtracurricularActivity $activity;

    private ActivityGroup $group;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->academicYear = AcademicYear::factory()->create(['is_active' => true]);
        $this->activity = ExtracurricularActivity::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'requires_ampa_membership' => false,
        ]);
        $this->group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'max_spots' => 3,
            'status' => ActivityGroupStatus::Open,
        ]);
    }

    private function enroll(Family $family, ?Student $student = null, ?ActivityGroup $group = null): Enrollment
    {
        $student ??= Student::factory()->create(['family_id' => $family->id]);
        $group ??= $this->group;

        return app(EnrollStudentAction::class)->execute(
            student: $student,
            group: $group,
            family: $family,
            academicYear: $this->academicYear,
        );
    }

    // ── EnrollStudentAction ────────────────────────────────────────────────────

    public function test_enroll_creates_enrolled_record_with_correct_data(): void
    {
        $family = Family::factory()->ampaMember()->create();
        $student = Student::factory()->create(['family_id' => $family->id]);

        $enrollment = $this->enroll($family, $student);

        $this->assertEquals(EnrollmentStatus::Enrolled, $enrollment->status);
        $this->assertEquals(PriceType::Member, $enrollment->price_type);
        $this->assertEquals($this->group->price_member, $enrollment->amount);
        $this->assertNotNull($enrollment->enrolled_at);
        $this->assertNull($enrollment->waitlist_position);
    }

    public function test_non_member_family_pays_non_member_price(): void
    {
        $family = Family::factory()->notAmpaMember()->create();

        $enrollment = $this->enroll($family);

        $this->assertEquals(PriceType::NonMember, $enrollment->price_type);
        $this->assertEquals($this->group->price_non_member, $enrollment->amount);
    }

    public function test_enrollment_goes_to_waitlist_when_group_is_full(): void
    {
        $this->enroll(Family::factory()->create());
        $this->enroll(Family::factory()->create());
        $this->enroll(Family::factory()->create());

        $waitlistEnrollment = $this->enroll(Family::factory()->create());

        $this->assertEquals(EnrollmentStatus::Waitlist, $waitlistEnrollment->status);
        $this->assertEquals(1, $waitlistEnrollment->waitlist_position);
        $this->assertNull($waitlistEnrollment->enrolled_at);
    }

    public function test_waitlist_position_increments_for_each_queued_student(): void
    {
        for ($i = 0; $i < $this->group->max_spots; $i++) {
            $this->enroll(Family::factory()->create());
        }

        $e1 = $this->enroll(Family::factory()->create());
        $e2 = $this->enroll(Family::factory()->create());

        $this->assertEquals(1, $e1->waitlist_position);
        $this->assertEquals(2, $e2->waitlist_position);
    }

    public function test_group_status_becomes_full_when_max_spots_occupied(): void
    {
        for ($i = 0; $i < $this->group->max_spots; $i++) {
            $this->enroll(Family::factory()->create());
        }

        $this->assertEquals(ActivityGroupStatus::Full, $this->group->fresh()->status);
    }

    public function test_group_status_stays_open_below_max_spots(): void
    {
        $this->enroll(Family::factory()->create());

        $this->assertEquals(ActivityGroupStatus::Open, $this->group->fresh()->status);
    }

    public function test_blocks_duplicate_active_enrollment_for_same_student_and_group(): void
    {
        $family = Family::factory()->create();
        $student = Student::factory()->create(['family_id' => $family->id]);

        $this->enroll($family, $student);

        $this->expectException(ValidationException::class);
        $this->enroll($family, $student);
    }

    public function test_requires_ampa_membership_blocks_non_member(): void
    {
        $activity = ExtracurricularActivity::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'requires_ampa_membership' => true,
        ]);
        $group = ActivityGroup::factory()->create(['activity_id' => $activity->id]);
        $family = Family::factory()->notAmpaMember()->create();
        $student = Student::factory()->create(['family_id' => $family->id]);

        $this->expectException(ValidationException::class);

        app(EnrollStudentAction::class)->execute(
            student: $student,
            group: $group,
            family: $family,
            academicYear: $this->academicYear,
        );
    }

    public function test_requires_ampa_membership_allows_member(): void
    {
        $activity = ExtracurricularActivity::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'requires_ampa_membership' => true,
        ]);
        $group = ActivityGroup::factory()->create(['activity_id' => $activity->id]);
        $family = Family::factory()->ampaMember()->create();
        $student = Student::factory()->create(['family_id' => $family->id]);

        $enrollment = app(EnrollStudentAction::class)->execute(
            student: $student,
            group: $group,
            family: $family,
            academicYear: $this->academicYear,
        );

        $this->assertEquals(EnrollmentStatus::Enrolled, $enrollment->status);
    }

    public function test_re_enrollment_allowed_after_dropped(): void
    {
        $family = Family::factory()->create();
        $student = Student::factory()->create(['family_id' => $family->id]);

        $first = $this->enroll($family, $student);
        app(DropEnrollmentAction::class)->execute($first);

        $second = $this->enroll($family, $student);

        $this->assertEquals(EnrollmentStatus::Enrolled, $second->status);
    }

    // ── DropEnrollmentAction ──────────────────────────────────────────────────

    public function test_drop_transitions_enrolled_to_dropped(): void
    {
        $family = Family::factory()->create();
        $enrollment = $this->enroll($family);

        $result = app(DropEnrollmentAction::class)->execute($enrollment);

        $this->assertEquals(EnrollmentStatus::Dropped, $result->status);
        $this->assertNotNull($result->ended_at);
    }

    public function test_drop_frees_spot_and_group_becomes_open(): void
    {
        $families = Family::factory()->count($this->group->max_spots)->create();
        $enrollments = $families->map(fn ($f) => $this->enroll($f));

        $this->assertEquals(ActivityGroupStatus::Full, $this->group->fresh()->status);

        app(DropEnrollmentAction::class)->execute($enrollments->first());

        $this->assertEquals(ActivityGroupStatus::Open, $this->group->fresh()->status);
    }

    public function test_drop_does_not_auto_promote_waitlist_after_freeing_spot(): void
    {
        $families = Family::factory()->count($this->group->max_spots)->create();
        $enrollments = $families->map(fn ($f) => $this->enroll($f));

        $waitlistFamily = Family::factory()->create();
        $waitlisted = $this->enroll($waitlistFamily);

        $this->assertEquals(EnrollmentStatus::Waitlist, $waitlisted->status);
        $this->assertEquals(ActivityGroupStatus::Full, $this->group->fresh()->status);

        app(DropEnrollmentAction::class)->execute($enrollments->first());

        $this->assertEquals(ActivityGroupStatus::Open, $this->group->fresh()->status);
        $this->assertEquals(EnrollmentStatus::Waitlist, $waitlisted->fresh()->status);
        $this->assertEquals(1, $waitlisted->fresh()->waitlist_position);
    }

    public function test_drop_fails_on_waitlist_enrollment(): void
    {
        for ($i = 0; $i < $this->group->max_spots; $i++) {
            $this->enroll(Family::factory()->create());
        }
        $waitlisted = $this->enroll(Family::factory()->create());

        $this->expectException(ValidationException::class);
        app(DropEnrollmentAction::class)->execute($waitlisted);
    }

    // ── CancelEnrollmentAction ────────────────────────────────────────────────

    public function test_cancel_transitions_waitlist_to_cancelled(): void
    {
        for ($i = 0; $i < $this->group->max_spots; $i++) {
            $this->enroll(Family::factory()->create());
        }
        $waitlisted = $this->enroll(Family::factory()->create());

        $result = app(CancelEnrollmentAction::class)->execute($waitlisted);

        $this->assertEquals(EnrollmentStatus::Cancelled, $result->status);
        $this->assertNull($result->waitlist_position);
    }

    public function test_cancel_fails_on_enrolled_status(): void
    {
        $enrollment = $this->enroll(Family::factory()->create());

        $this->expectException(ValidationException::class);
        app(CancelEnrollmentAction::class)->execute($enrollment);
    }

    // ── PromoteFromWaitlistAction ──────────────────────────────────────────────

    public function test_promote_transitions_waitlist_to_enrolled(): void
    {
        for ($i = 0; $i < $this->group->max_spots; $i++) {
            $this->enroll(Family::factory()->create());
        }
        $waitlisted = $this->enroll(Family::factory()->create());

        app(DropEnrollmentAction::class)->execute(
            Enrollment::where('activity_group_id', $this->group->id)
                ->where('status', EnrollmentStatus::Enrolled)
                ->first()
        );

        $result = app(PromoteFromWaitlistAction::class)->execute($waitlisted->fresh());

        $this->assertEquals(EnrollmentStatus::Enrolled, $result->status);
        $this->assertNotNull($result->enrolled_at);
        $this->assertNull($result->waitlist_position);
    }

    public function test_promote_fails_when_group_still_full(): void
    {
        for ($i = 0; $i < $this->group->max_spots; $i++) {
            $this->enroll(Family::factory()->create());
        }
        $waitlisted = $this->enroll(Family::factory()->create());

        $this->expectException(ValidationException::class);
        app(PromoteFromWaitlistAction::class)->execute($waitlisted->fresh());
    }

    // ── SyncGroupStatusAction ──────────────────────────────────────────────────

    public function test_sync_does_not_change_manually_closed_group(): void
    {
        $this->group->update(['status' => ActivityGroupStatus::Closed]);

        app(SyncGroupStatusAction::class)->execute($this->group);

        $this->assertEquals(ActivityGroupStatus::Closed, $this->group->fresh()->status);
    }

    public function test_sync_does_not_change_archived_group(): void
    {
        $this->group->update(['status' => ActivityGroupStatus::Archived]);

        app(SyncGroupStatusAction::class)->execute($this->group);

        $this->assertEquals(ActivityGroupStatus::Archived, $this->group->fresh()->status);
    }
}
