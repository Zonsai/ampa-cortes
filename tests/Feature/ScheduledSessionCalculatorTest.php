<?php

namespace Tests\Feature;

use App\Enums\ExceptionType;
use App\Models\AcademicYear;
use App\Models\ActivityGroup;
use App\Models\ActivityGroupException;
use App\Models\Enrollment;
use App\Models\ExtracurricularActivity;
use App\Services\ScheduledSessionCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduledSessionCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private ScheduledSessionCalculator $calculator;

    private AcademicYear $year;

    private ExtracurricularActivity $activity;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new ScheduledSessionCalculator;

        $this->year = AcademicYear::factory()->create([
            'name' => '2050-2051',
            'starts_at' => '2050-09-01',
            'ends_at' => '2051-06-30',
            'is_active' => true,
        ]);

        $this->activity = ExtracurricularActivity::factory()->create([
            'academic_year_id' => $this->year->id,
            'name' => 'Fútbol',
        ]);
    }

    // ── 1. Normal recurrence ────────────────────────────────────────────────

    public function test_generates_sessions_for_recurring_weekdays(): void
    {
        // Monday (1) and Wednesday (3) — week of 2050-09-05 (Mon) to 2050-09-09 (Fri)
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1, 3],
            'starts_at' => '16:00:00',
            'ends_at' => '17:30:00',
            'location' => 'Patio',
        ]);

        $sessions = $this->calculator->forGroup(
            $group,
            CarbonImmutable::parse('2050-09-05'),
            CarbonImmutable::parse('2050-09-09'),
        );

        $this->assertCount(2, $sessions);
        $this->assertEquals('2050-09-05', $sessions[0]->date->format('Y-m-d'));
        $this->assertEquals('2050-09-07', $sessions[1]->date->format('Y-m-d'));
        $this->assertEquals('16:00:00', $sessions[0]->startsAt);
        $this->assertEquals('17:30:00', $sessions[0]->endsAt);
        $this->assertEquals('Patio', $sessions[0]->location);
        $this->assertTrue($sessions[0]->isRegular());
    }

    // ── 2. Group inherits dates from academic year ──────────────────────────

    public function test_group_inherits_dates_from_academic_year(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1], // Monday
            'starts_at' => '16:00:00',
            'ends_at' => '17:00:00',
            'effective_from' => null,
            'effective_until' => null,
        ]);

        // Request range before academic year — no sessions
        $sessions = $this->calculator->forGroup(
            $group,
            CarbonImmutable::parse('2050-08-01'),
            CarbonImmutable::parse('2050-08-31'),
        );

        $this->assertCount(0, $sessions);

        // Request range within academic year
        $sessions = $this->calculator->forGroup(
            $group,
            CarbonImmutable::parse('2050-09-01'),
            CarbonImmutable::parse('2050-09-30'),
        );

        $this->assertGreaterThan(0, $sessions->count());
    }

    // ── 3. Group with custom effective dates ────────────────────────────────

    public function test_group_with_custom_effective_dates(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1], // Monday
            'starts_at' => '16:00:00',
            'ends_at' => '17:00:00',
            'effective_from' => '2050-10-01',
            'effective_until' => '2050-12-31',
        ]);

        // September — before effective_from — no sessions
        $sessions = $this->calculator->forGroup(
            $group,
            CarbonImmutable::parse('2050-09-01'),
            CarbonImmutable::parse('2050-09-30'),
        );
        $this->assertCount(0, $sessions);

        // October — within effective period
        $sessions = $this->calculator->forGroup(
            $group,
            CarbonImmutable::parse('2050-10-01'),
            CarbonImmutable::parse('2050-10-31'),
        );
        $this->assertGreaterThan(0, $sessions->count());

        foreach ($sessions as $session) {
            $this->assertGreaterThanOrEqual('2050-10-01', $session->date->format('Y-m-d'));
            $this->assertLessThanOrEqual('2050-10-31', $session->date->format('Y-m-d'));
        }
    }

    // ── 4. Enrollment starts after course start ─────────────────────────────

    public function test_enrollment_attendance_from_restricts_start(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1], // Monday
            'starts_at' => '16:00:00',
            'ends_at' => '17:00:00',
        ]);

        $enrollment = Enrollment::factory()->create([
            'activity_id' => $this->activity->id,
            'activity_group_id' => $group->id,
            'academic_year_id' => $this->year->id,
            'attendance_from' => '2050-10-01',
            'attendance_until' => null,
        ]);

        // September — before attendance_from
        $sessions = $this->calculator->forEnrollment(
            $enrollment,
            CarbonImmutable::parse('2050-09-01'),
            CarbonImmutable::parse('2050-09-30'),
        );
        $this->assertCount(0, $sessions);

        // October — after attendance_from
        $sessions = $this->calculator->forEnrollment(
            $enrollment,
            CarbonImmutable::parse('2050-10-01'),
            CarbonImmutable::parse('2050-10-31'),
        );
        $this->assertGreaterThan(0, $sessions->count());
    }

    // ── 5. Enrollment ends before course end ────────────────────────────────

    public function test_enrollment_attendance_until_restricts_end(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1], // Monday
            'starts_at' => '16:00:00',
            'ends_at' => '17:00:00',
        ]);

        $enrollment = Enrollment::factory()->create([
            'activity_id' => $this->activity->id,
            'activity_group_id' => $group->id,
            'academic_year_id' => $this->year->id,
            'attendance_from' => null,
            'attendance_until' => '2050-11-30',
        ]);

        // December — after attendance_until
        $sessions = $this->calculator->forEnrollment(
            $enrollment,
            CarbonImmutable::parse('2050-12-01'),
            CarbonImmutable::parse('2050-12-31'),
        );
        $this->assertCount(0, $sessions);

        // November — within attendance period
        $sessions = $this->calculator->forEnrollment(
            $enrollment,
            CarbonImmutable::parse('2050-11-01'),
            CarbonImmutable::parse('2050-11-30'),
        );
        $this->assertGreaterThan(0, $sessions->count());
    }

    // ── 6. Cancellation ─────────────────────────────────────────────────────

    public function test_cancelled_session_is_removed(): void
    {
        // 2050-09-05 is Monday
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1], // Monday
            'starts_at' => '16:00:00',
            'ends_at' => '17:00:00',
        ]);

        ActivityGroupException::factory()->create([
            'activity_group_id' => $group->id,
            'original_date' => '2050-09-05',
            'type' => ExceptionType::Cancelled,
            'reason' => 'Festivo',
        ]);

        $sessions = $this->calculator->forGroup(
            $group,
            CarbonImmutable::parse('2050-09-05'),
            CarbonImmutable::parse('2050-09-11'),
        );

        // Only the following Monday (09-12 is not in range, so only 09-05 was a Monday, and it's cancelled)
        $this->assertCount(0, $sessions);
    }

    // ── 7. Schedule modification ────────────────────────────────────────────

    public function test_modified_session_changes_time(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1], // Monday
            'starts_at' => '16:00:00',
            'ends_at' => '17:00:00',
        ]);

        ActivityGroupException::factory()->create([
            'activity_group_id' => $group->id,
            'original_date' => '2050-09-05',
            'type' => ExceptionType::Modified,
            'new_starts_at' => '18:00:00',
            'new_ends_at' => '19:30:00',
        ]);

        $sessions = $this->calculator->forGroup(
            $group,
            CarbonImmutable::parse('2050-09-05'),
            CarbonImmutable::parse('2050-09-05'),
        );

        $this->assertCount(1, $sessions);
        $this->assertEquals('18:00:00', $sessions[0]->startsAt);
        $this->assertEquals('19:30:00', $sessions[0]->endsAt);
        $this->assertTrue($sessions[0]->isModified());
    }

    // ── 8. Date modification ────────────────────────────────────────────────

    public function test_modified_session_changes_date(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1], // Monday
            'starts_at' => '16:00:00',
            'ends_at' => '17:00:00',
        ]);

        ActivityGroupException::factory()->create([
            'activity_group_id' => $group->id,
            'original_date' => '2050-09-05',
            'type' => ExceptionType::Modified,
            'new_date' => '2050-09-06',
            'new_starts_at' => '16:00:00',
            'new_ends_at' => '17:00:00',
        ]);

        $sessions = $this->calculator->forGroup(
            $group,
            CarbonImmutable::parse('2050-09-05'),
            CarbonImmutable::parse('2050-09-11'),
        );

        $this->assertCount(1, $sessions);
        $this->assertEquals('2050-09-06', $sessions[0]->date->format('Y-m-d'));
        $this->assertTrue($sessions[0]->isModified());
    }

    // ── 9. Location modification ────────────────────────────────────────────

    public function test_modified_session_changes_location(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1], // Monday
            'starts_at' => '16:00:00',
            'ends_at' => '17:00:00',
            'location' => 'Patio',
        ]);

        ActivityGroupException::factory()->create([
            'activity_group_id' => $group->id,
            'original_date' => '2050-09-05',
            'type' => ExceptionType::Modified,
            'new_location' => 'Gimnasio',
        ]);

        $sessions = $this->calculator->forGroup(
            $group,
            CarbonImmutable::parse('2050-09-05'),
            CarbonImmutable::parse('2050-09-05'),
        );

        $this->assertCount(1, $sessions);
        $this->assertEquals('Gimnasio', $sessions[0]->location);
        $this->assertTrue($sessions[0]->isModified());
    }

    // ── 10. Extra session ───────────────────────────────────────────────────

    public function test_extra_session_is_added(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1], // Monday
            'starts_at' => '16:00:00',
            'ends_at' => '17:00:00',
        ]);

        // Extra session on a Saturday
        ActivityGroupException::factory()->create([
            'activity_group_id' => $group->id,
            'original_date' => null,
            'type' => ExceptionType::Extra,
            'new_date' => '2050-09-10', // Saturday
            'new_starts_at' => '10:00:00',
            'new_ends_at' => '12:00:00',
            'reason' => 'Recuperación',
        ]);

        $sessions = $this->calculator->forGroup(
            $group,
            CarbonImmutable::parse('2050-09-05'),
            CarbonImmutable::parse('2050-09-11'),
        );

        // Monday 09-05 (regular) + Saturday 09-10 (extra) = 2
        $this->assertCount(2, $sessions);

        $extra = $sessions->first(fn ($s) => $s->isExtra());
        $this->assertNotNull($extra);
        $this->assertEquals('2050-09-10', $extra->date->format('Y-m-d'));
        $this->assertEquals('10:00:00', $extra->startsAt);
        $this->assertEquals('12:00:00', $extra->endsAt);
        $this->assertEquals('Recuperación', $extra->exceptionReason);
    }

    // ── 11. No duplicate cancellation and modification on same date ─────────

    public function test_unique_constraint_prevents_duplicate_exception_on_same_date(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1],
        ]);

        ActivityGroupException::factory()->create([
            'activity_group_id' => $group->id,
            'original_date' => '2050-09-05',
            'type' => ExceptionType::Cancelled,
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        ActivityGroupException::factory()->create([
            'activity_group_id' => $group->id,
            'original_date' => '2050-09-05',
            'type' => ExceptionType::Modified,
            'new_starts_at' => '18:00:00',
            'new_ends_at' => '19:00:00',
        ]);
    }

    // ── 12. No sessions outside range ───────────────────────────────────────

    public function test_no_sessions_generated_outside_requested_range(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1, 2, 3, 4, 5], // All weekdays
            'starts_at' => '16:00:00',
            'ends_at' => '17:00:00',
        ]);

        $rangeStart = CarbonImmutable::parse('2050-10-03'); // Monday
        $rangeEnd = CarbonImmutable::parse('2050-10-07'); // Friday

        $sessions = $this->calculator->forGroup($group, $rangeStart, $rangeEnd);

        foreach ($sessions as $session) {
            $this->assertGreaterThanOrEqual('2050-10-03', $session->date->format('Y-m-d'));
            $this->assertLessThanOrEqual('2050-10-07', $session->date->format('Y-m-d'));
        }
    }

    // ── 13. No sessions outside enrollment attendance period ────────────────

    public function test_no_sessions_outside_enrollment_attendance_period(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1], // Monday
            'starts_at' => '16:00:00',
            'ends_at' => '17:00:00',
        ]);

        $enrollment = Enrollment::factory()->create([
            'activity_id' => $this->activity->id,
            'activity_group_id' => $group->id,
            'academic_year_id' => $this->year->id,
            'attendance_from' => '2050-10-01',
            'attendance_until' => '2050-10-31',
        ]);

        // Full year range, but attendance restricts to October
        $sessions = $this->calculator->forEnrollment(
            $enrollment,
            CarbonImmutable::parse('2050-09-01'),
            CarbonImmutable::parse('2051-06-30'),
        );

        foreach ($sessions as $session) {
            $this->assertGreaterThanOrEqual('2050-10-01', $session->date->format('Y-m-d'));
            $this->assertLessThanOrEqual('2050-10-31', $session->date->format('Y-m-d'));
        }

        $this->assertGreaterThan(0, $sessions->count());
    }

    // ── Additional: session DTO properties ──────────────────────────────────

    public function test_session_carries_activity_and_group_data(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'name' => 'Grupo A',
            'weekdays' => [1],
            'starts_at' => '16:00:00',
            'ends_at' => '17:00:00',
        ]);

        $sessions = $this->calculator->forGroup(
            $group,
            CarbonImmutable::parse('2050-09-05'),
            CarbonImmutable::parse('2050-09-05'),
        );

        $this->assertCount(1, $sessions);
        $this->assertEquals($this->activity->id, $sessions[0]->activityId);
        $this->assertEquals('Fútbol', $sessions[0]->activityName);
        $this->assertEquals($group->id, $sessions[0]->activityGroupId);
        $this->assertEquals('Grupo A', $sessions[0]->activityGroupName);
    }

    public function test_enrollment_session_carries_student_data(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1],
            'starts_at' => '16:00:00',
            'ends_at' => '17:00:00',
        ]);

        $enrollment = Enrollment::factory()->create([
            'activity_id' => $this->activity->id,
            'activity_group_id' => $group->id,
            'academic_year_id' => $this->year->id,
        ]);

        $sessions = $this->calculator->forEnrollment(
            $enrollment,
            CarbonImmutable::parse('2050-09-05'),
            CarbonImmutable::parse('2050-09-05'),
        );

        $this->assertCount(1, $sessions);
        $this->assertEquals($enrollment->id, $sessions[0]->enrollmentId);
        $this->assertEquals($enrollment->student_id, $sessions[0]->studentId);
        $this->assertNotNull($sessions[0]->studentName);
    }

    public function test_uid_is_scoped_per_enrollment(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1],
            'starts_at' => '16:00:00',
            'ends_at' => '17:00:00',
        ]);

        $enrollment = Enrollment::factory()->create([
            'activity_id' => $this->activity->id,
            'activity_group_id' => $group->id,
            'academic_year_id' => $this->year->id,
        ]);

        $sessions = $this->calculator->forEnrollment(
            $enrollment,
            CarbonImmutable::parse('2050-09-05'),
            CarbonImmutable::parse('2050-09-05'),
        );

        $uid = $sessions[0]->uid();
        $this->assertStringContainsString("enrollment-{$enrollment->id}", $uid);
        $this->assertStringContainsString('20500905', $uid);
    }

    public function test_sessions_are_sorted_by_date(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1, 3, 5], // Mon, Wed, Fri
            'starts_at' => '16:00:00',
            'ends_at' => '17:00:00',
        ]);

        // Add extra on Tuesday
        ActivityGroupException::factory()->create([
            'activity_group_id' => $group->id,
            'type' => ExceptionType::Extra,
            'original_date' => null,
            'new_date' => '2050-09-06', // Tuesday
            'new_starts_at' => '10:00:00',
            'new_ends_at' => '11:00:00',
        ]);

        $sessions = $this->calculator->forGroup(
            $group,
            CarbonImmutable::parse('2050-09-05'),
            CarbonImmutable::parse('2050-09-09'),
        );

        $dates = $sessions->pluck('date')->map(fn ($d) => $d->format('Y-m-d'))->all();
        $sorted = $dates;
        sort($sorted);
        $this->assertEquals($sorted, $dates);
    }

    public function test_extra_session_outside_range_is_excluded(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1],
            'starts_at' => '16:00:00',
            'ends_at' => '17:00:00',
        ]);

        ActivityGroupException::factory()->create([
            'activity_group_id' => $group->id,
            'type' => ExceptionType::Extra,
            'original_date' => null,
            'new_date' => '2050-12-25',
            'new_starts_at' => '10:00:00',
            'new_ends_at' => '11:00:00',
        ]);

        $sessions = $this->calculator->forGroup(
            $group,
            CarbonImmutable::parse('2050-09-05'),
            CarbonImmutable::parse('2050-09-11'),
        );

        $extras = $sessions->filter(fn ($s) => $s->isExtra());
        $this->assertCount(0, $extras);
    }

    public function test_combined_group_effective_and_enrollment_attendance(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1], // Monday
            'starts_at' => '16:00:00',
            'ends_at' => '17:00:00',
            'effective_from' => '2050-09-15',
            'effective_until' => '2051-03-31',
        ]);

        $enrollment = Enrollment::factory()->create([
            'activity_id' => $this->activity->id,
            'activity_group_id' => $group->id,
            'academic_year_id' => $this->year->id,
            'attendance_from' => '2050-10-01',
            'attendance_until' => '2051-02-28',
        ]);

        // Effective window: max(2050-09-15, 2050-10-01) to min(2051-03-31, 2051-02-28) = Oct 1 to Feb 28
        $sessions = $this->calculator->forEnrollment(
            $enrollment,
            CarbonImmutable::parse('2050-09-01'),
            CarbonImmutable::parse('2051-06-30'),
        );

        foreach ($sessions as $session) {
            $this->assertGreaterThanOrEqual('2050-10-01', $session->date->format('Y-m-d'));
            $this->assertLessThanOrEqual('2051-02-28', $session->date->format('Y-m-d'));
        }

        $this->assertGreaterThan(0, $sessions->count());
    }

    // ── Validation: cancelled exceptions ────────────────────────────────────

    public function test_cancelled_exception_rejects_new_fields(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1],
        ]);

        $this->expectException(\InvalidArgumentException::class);

        ActivityGroupException::create([
            'activity_group_id' => $group->id,
            'original_date' => '2050-09-05',
            'type' => ExceptionType::Cancelled,
            'new_starts_at' => '18:00:00',
        ]);
    }

    public function test_cancelled_exception_requires_original_date(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1],
        ]);

        $this->expectException(\InvalidArgumentException::class);

        ActivityGroupException::create([
            'activity_group_id' => $group->id,
            'original_date' => null,
            'type' => ExceptionType::Cancelled,
        ]);
    }

    // ── Validation: modified exceptions ─────────────────────────────────────

    public function test_modified_exception_requires_at_least_one_change(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1],
        ]);

        $this->expectException(\InvalidArgumentException::class);

        ActivityGroupException::create([
            'activity_group_id' => $group->id,
            'original_date' => '2050-09-05',
            'type' => ExceptionType::Modified,
        ]);
    }

    public function test_modified_exception_requires_original_date(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1],
        ]);

        $this->expectException(\InvalidArgumentException::class);

        ActivityGroupException::create([
            'activity_group_id' => $group->id,
            'original_date' => null,
            'type' => ExceptionType::Modified,
            'new_starts_at' => '18:00:00',
        ]);
    }

    // ── Validation: extra exceptions ────────────────────────────────────────

    public function test_extra_exception_requires_date_and_times(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1],
        ]);

        $this->expectException(\InvalidArgumentException::class);

        ActivityGroupException::create([
            'activity_group_id' => $group->id,
            'original_date' => null,
            'type' => ExceptionType::Extra,
            'new_date' => '2050-09-10',
        ]);
    }

    public function test_extra_exception_rejects_original_date(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1],
        ]);

        $this->expectException(\InvalidArgumentException::class);

        ActivityGroupException::create([
            'activity_group_id' => $group->id,
            'original_date' => '2050-09-05',
            'type' => ExceptionType::Extra,
            'new_date' => '2050-09-10',
            'new_starts_at' => '10:00:00',
            'new_ends_at' => '11:00:00',
        ]);
    }

    // ── Validation: original_date must match recurring weekday ──────────────

    public function test_original_date_must_match_group_weekday(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1], // Monday only
        ]);

        $this->expectException(\InvalidArgumentException::class);

        // 2050-09-06 is Tuesday
        ActivityGroupException::create([
            'activity_group_id' => $group->id,
            'original_date' => '2050-09-06',
            'type' => ExceptionType::Cancelled,
        ]);
    }

    // ── Validation: extra must not duplicate regular ────────────────────────

    public function test_extra_session_must_not_duplicate_regular(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1], // Monday
            'starts_at' => '16:00:00',
            'ends_at' => '17:00:00',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        // 2050-09-05 is Monday, same start time as regular session
        ActivityGroupException::create([
            'activity_group_id' => $group->id,
            'original_date' => null,
            'type' => ExceptionType::Extra,
            'new_date' => '2050-09-05',
            'new_starts_at' => '16:00:00',
            'new_ends_at' => '18:00:00',
        ]);
    }

    // ── Validation: duplicate extras ────────────────────────────────────────

    public function test_duplicate_extra_same_date_and_time_is_rejected(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1],
        ]);

        ActivityGroupException::create([
            'activity_group_id' => $group->id,
            'original_date' => null,
            'type' => ExceptionType::Extra,
            'new_date' => '2050-09-10', // Saturday
            'new_starts_at' => '10:00:00',
            'new_ends_at' => '11:00:00',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        ActivityGroupException::create([
            'activity_group_id' => $group->id,
            'original_date' => null,
            'type' => ExceptionType::Extra,
            'new_date' => '2050-09-10',
            'new_starts_at' => '10:00:00',
            'new_ends_at' => '12:00:00',
        ]);
    }

    public function test_duplicate_extra_same_date_different_time_is_allowed(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1],
        ]);

        ActivityGroupException::create([
            'activity_group_id' => $group->id,
            'original_date' => null,
            'type' => ExceptionType::Extra,
            'new_date' => '2050-09-10',
            'new_starts_at' => '10:00:00',
            'new_ends_at' => '11:00:00',
        ]);

        $second = ActivityGroupException::create([
            'activity_group_id' => $group->id,
            'original_date' => null,
            'type' => ExceptionType::Extra,
            'new_date' => '2050-09-10',
            'new_starts_at' => '12:00:00',
            'new_ends_at' => '13:00:00',
        ]);

        $this->assertTrue($second->exists);
    }

    public function test_editing_extra_excludes_self(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1],
        ]);

        $extra = ActivityGroupException::create([
            'activity_group_id' => $group->id,
            'original_date' => null,
            'type' => ExceptionType::Extra,
            'new_date' => '2050-09-10',
            'new_starts_at' => '10:00:00',
            'new_ends_at' => '11:00:00',
        ]);

        $extra->update(['new_ends_at' => '11:30:00']);
        $this->assertEquals('11:30:00', $extra->fresh()->new_ends_at);
    }

    // ── NULL unique index: multiple extras allowed ──────────────────────────

    public function test_null_original_date_allows_multiple_extras_in_db(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1],
        ]);

        $first = ActivityGroupException::create([
            'activity_group_id' => $group->id,
            'original_date' => null,
            'type' => ExceptionType::Extra,
            'new_date' => '2050-09-10',
            'new_starts_at' => '10:00:00',
            'new_ends_at' => '11:00:00',
        ]);

        $second = ActivityGroupException::create([
            'activity_group_id' => $group->id,
            'original_date' => null,
            'type' => ExceptionType::Extra,
            'new_date' => '2050-09-17',
            'new_starts_at' => '10:00:00',
            'new_ends_at' => '11:00:00',
        ]);

        $this->assertTrue($first->exists);
        $this->assertTrue($second->exists);
        $this->assertNull($first->original_date);
        $this->assertNull($second->original_date);
        $this->assertEquals(2, ActivityGroupException::where('activity_group_id', $group->id)->count());
    }

    // ── Cross-range modifications ───────────────────────────────────────────

    public function test_modified_session_moved_into_range_appears(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1], // Monday
            'starts_at' => '16:00:00',
            'ends_at' => '17:00:00',
        ]);

        // Move Monday Sep 5 → Wednesday Sep 14 (outside→inside query range)
        ActivityGroupException::factory()->create([
            'activity_group_id' => $group->id,
            'original_date' => '2050-09-05',
            'type' => ExceptionType::Modified,
            'new_date' => '2050-09-14',
            'new_starts_at' => '16:00:00',
            'new_ends_at' => '17:00:00',
        ]);

        // Query range starts AFTER original_date
        $sessions = $this->calculator->forGroup(
            $group,
            CarbonImmutable::parse('2050-09-12'),
            CarbonImmutable::parse('2050-09-18'),
        );

        $modified = $sessions->filter(fn ($s) => $s->isModified());
        $this->assertCount(1, $modified);
        $this->assertEquals('2050-09-14', $modified->first()->date->format('Y-m-d'));
    }

    public function test_modified_session_moved_out_of_range_disappears(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1], // Monday
            'starts_at' => '16:00:00',
            'ends_at' => '17:00:00',
        ]);

        // Move Monday Sep 12 → Wednesday Sep 21 (inside→outside query range)
        ActivityGroupException::factory()->create([
            'activity_group_id' => $group->id,
            'original_date' => '2050-09-12',
            'type' => ExceptionType::Modified,
            'new_date' => '2050-09-21',
            'new_starts_at' => '16:00:00',
            'new_ends_at' => '17:00:00',
        ]);

        // Query range contains original_date but not new_date
        $sessions = $this->calculator->forGroup(
            $group,
            CarbonImmutable::parse('2050-09-12'),
            CarbonImmutable::parse('2050-09-18'),
        );

        // The regular session on Sep 19 (Mon) would appear, Sep 12 is moved out
        $dates = $sessions->pluck('date')->map(fn ($d) => $d->format('Y-m-d'))->all();
        $this->assertNotContains('2050-09-12', $dates);
        $this->assertNotContains('2050-09-21', $dates);
    }

    public function test_modified_session_moved_into_range_for_enrollment(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1], // Monday
            'starts_at' => '16:00:00',
            'ends_at' => '17:00:00',
        ]);

        // Move Monday Sep 5 → Wednesday Sep 14
        ActivityGroupException::factory()->create([
            'activity_group_id' => $group->id,
            'original_date' => '2050-09-05',
            'type' => ExceptionType::Modified,
            'new_date' => '2050-09-14',
            'new_starts_at' => '16:00:00',
            'new_ends_at' => '17:00:00',
        ]);

        $enrollment = Enrollment::factory()->create([
            'activity_id' => $this->activity->id,
            'activity_group_id' => $group->id,
            'academic_year_id' => $this->year->id,
        ]);

        // Query range starts after original_date
        $sessions = $this->calculator->forEnrollment(
            $enrollment,
            CarbonImmutable::parse('2050-09-12'),
            CarbonImmutable::parse('2050-09-18'),
        );

        $modified = $sessions->filter(fn ($s) => $s->isModified());
        $this->assertCount(1, $modified);
        $this->assertEquals('2050-09-14', $modified->first()->date->format('Y-m-d'));
    }

    // ── Enrollment attendance + exceptions ──────────────────────────────────

    public function test_extra_outside_enrollment_attendance_is_excluded(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1], // Monday
            'starts_at' => '16:00:00',
            'ends_at' => '17:00:00',
        ]);

        // Extra on Dec 1 — outside attendance Oct-Nov
        ActivityGroupException::factory()->create([
            'activity_group_id' => $group->id,
            'type' => ExceptionType::Extra,
            'original_date' => null,
            'new_date' => '2050-12-01',
            'new_starts_at' => '10:00:00',
            'new_ends_at' => '11:00:00',
        ]);

        $enrollment = Enrollment::factory()->create([
            'activity_id' => $this->activity->id,
            'activity_group_id' => $group->id,
            'academic_year_id' => $this->year->id,
            'attendance_from' => '2050-10-01',
            'attendance_until' => '2050-11-30',
        ]);

        $sessions = $this->calculator->forEnrollment(
            $enrollment,
            CarbonImmutable::parse('2050-09-01'),
            CarbonImmutable::parse('2051-06-30'),
        );

        $extras = $sessions->filter(fn ($s) => $s->isExtra());
        $this->assertCount(0, $extras);
    }

    public function test_modified_session_moved_outside_attendance_is_excluded(): void
    {
        $group = ActivityGroup::factory()->create([
            'activity_id' => $this->activity->id,
            'weekdays' => [1], // Monday
            'starts_at' => '16:00:00',
            'ends_at' => '17:00:00',
        ]);

        // Move Monday Oct 3 → Dec 5 (inside attendance → outside)
        ActivityGroupException::factory()->create([
            'activity_group_id' => $group->id,
            'original_date' => '2050-10-03',
            'type' => ExceptionType::Modified,
            'new_date' => '2050-12-05',
            'new_starts_at' => '16:00:00',
            'new_ends_at' => '17:00:00',
        ]);

        $enrollment = Enrollment::factory()->create([
            'activity_id' => $this->activity->id,
            'activity_group_id' => $group->id,
            'academic_year_id' => $this->year->id,
            'attendance_from' => '2050-10-01',
            'attendance_until' => '2050-11-30',
        ]);

        $sessions = $this->calculator->forEnrollment(
            $enrollment,
            CarbonImmutable::parse('2050-09-01'),
            CarbonImmutable::parse('2051-06-30'),
        );

        $dates = $sessions->pluck('date')->map(fn ($d) => $d->format('Y-m-d'))->all();
        $this->assertNotContains('2050-10-03', $dates);
        $this->assertNotContains('2050-12-05', $dates);
    }
}
