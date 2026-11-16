<?php

namespace Tests\Feature;

use App\Enums\ActivityStatus;
use App\Enums\EnrollmentStatus;
use App\Models\AcademicYear;
use App\Models\ActivityGroup;
use App\Models\ActivityGroupException;
use App\Models\Enrollment;
use App\Models\ExtracurricularActivity;
use App\Models\Family;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyCalendarIcsExportTest extends TestCase
{
    use RefreshDatabase;

    private AcademicYear $activeYear;

    private CarbonImmutable $monday;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->activeYear = AcademicYear::factory()->create([
            'name' => '2050-2051',
            'starts_at' => '2050-09-01',
            'ends_at' => '2051-06-30',
            'is_active' => true,
        ]);

        $this->monday = CarbonImmutable::parse($this->activeYear->starts_at)->next(CarbonImmutable::MONDAY);
    }

    /** @return array{user: User, family: Family, student: Student} */
    private function createFamilyUser(string $firstName = 'Laura'): array
    {
        $user = User::factory()->create();
        $user->assignRole('familia');
        $family = Family::factory()->create();
        Guardian::factory()->create(['user_id' => $user->id, 'family_id' => $family->id]);
        $student = Student::factory()->create([
            'family_id' => $family->id,
            'first_name' => $firstName,
            'last_name' => 'García',
            'is_active' => true,
        ]);

        return compact('user', 'family', 'student');
    }

    /** Group meeting on Mondays, limited to $weeks sessions starting on the first Monday. */
    private function createGroup(string $activityName, int $weeks = 3, array $groupOverrides = []): ActivityGroup
    {
        $activity = ExtracurricularActivity::factory()->create([
            'academic_year_id' => $this->activeYear->id,
            'name' => $activityName,
            'status' => ActivityStatus::Published,
            'is_visible_for_families' => true,
        ]);

        return ActivityGroup::factory()->create(array_merge([
            'activity_id' => $activity->id,
            'name' => 'Grupo A',
            'weekdays' => [1],
            'starts_at' => '16:00:00',
            'ends_at' => '17:00:00',
            'location' => 'Pabellón',
            'effective_from' => $this->monday->format('Y-m-d'),
            'effective_until' => $this->monday->addWeeks($weeks - 1)->format('Y-m-d'),
        ], $groupOverrides));
    }

    private function enroll(Student $student, Family $family, ActivityGroup $group, EnrollmentStatus $status = EnrollmentStatus::Enrolled, array $overrides = []): Enrollment
    {
        return Enrollment::factory()->create(array_merge([
            'student_id' => $student->id,
            'family_id' => $family->id,
            'activity_id' => $group->activity_id,
            'activity_group_id' => $group->id,
            'academic_year_id' => $this->activeYear->id,
            'status' => $status,
        ], $overrides));
    }

    private function exportAs(User $user, array $query = []): string
    {
        $response = $this->actingAs($user)->get(route('familia.calendar.export', $query));
        $response->assertOk();

        return $this->unfold($response->getContent());
    }

    private function unfold(string $ics): string
    {
        return preg_replace('/\r\n[ \t]/', '', $ics);
    }

    private function countEvents(string $ics): int
    {
        return substr_count($ics, "BEGIN:VEVENT\r\n");
    }

    // ── Acceso ──────────────────────────────────────────────────────────────

    public function test_guest_cannot_export(): void
    {
        $this->get(route('familia.calendar.export'))->assertRedirect(route('familia.login'));
    }

    public function test_non_family_user_cannot_export(): void
    {
        $user = User::factory()->create();
        $user->assignRole('junta_ampa');

        $this->actingAs($user)->get(route('familia.calendar.export'))->assertForbidden();
    }

    public function test_family_user_can_export(): void
    {
        ['user' => $user] = $this->createFamilyUser();

        $this->actingAs($user)->get(route('familia.calendar.export'))->assertOk();
    }

    public function test_cannot_export_student_of_another_family(): void
    {
        ['user' => $user] = $this->createFamilyUser();
        ['student' => $foreign] = $this->createFamilyUser('Otro');

        $this->actingAs($user)
            ->get(route('familia.calendar.export', ['student' => $foreign->id]))
            ->assertNotFound();
    }

    public function test_invalid_student_parameter_is_rejected(): void
    {
        ['user' => $user] = $this->createFamilyUser();

        $this->actingAs($user)->get(route('familia.calendar.export', ['student' => 'abc']))->assertNotFound();
        $this->actingAs($user)->get(route('familia.calendar.export', ['student' => '999999']))->assertNotFound();
    }

    public function test_export_is_not_available_without_active_academic_year(): void
    {
        ['user' => $user] = $this->createFamilyUser();
        $this->activeYear->update(['is_active' => false]);

        $this->actingAs($user)->get(route('familia.calendar.export'))->assertNotFound();
    }

    // ── Respuesta ───────────────────────────────────────────────────────────

    public function test_response_is_a_text_calendar_download(): void
    {
        ['user' => $user] = $this->createFamilyUser();

        $response = $this->actingAs($user)->get(route('familia.calendar.export'));

        $response->assertOk();
        $this->assertSame('text/calendar; charset=UTF-8', $response->headers->get('Content-Type'));
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringStartsWith('attachment', $disposition);
        $this->assertStringContainsString('filename=calendario-familia-2050-2051.ics', $disposition);
    }

    public function test_filename_uses_sanitised_student_name_when_filtered(): void
    {
        ['user' => $user, 'student' => $student] = $this->createFamilyUser('María José');

        $response = $this->actingAs($user)->get(route('familia.calendar.export', ['student' => $student->id]));

        $this->assertStringContainsString('filename=calendario-maria-jose-2050-2051.ics', $response->headers->get('Content-Disposition'));
    }

    public function test_family_without_sessions_gets_a_valid_empty_calendar(): void
    {
        ['user' => $user] = $this->createFamilyUser();

        $ics = $this->exportAs($user);

        $this->assertStringStartsWith("BEGIN:VCALENDAR\r\nVERSION:2.0\r\n", $ics);
        $this->assertStringEndsWith("END:VCALENDAR\r\n", $ics);
        $this->assertSame(0, $this->countEvents($ics));
    }

    public function test_student_without_sessions_gets_an_empty_calendar(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $laura] = $this->createFamilyUser();
        $pablo = Student::factory()->create(['family_id' => $family->id, 'first_name' => 'Pablo', 'is_active' => true]);
        $this->enroll($laura, $family, $this->createGroup('Multideporte'));

        $ics = $this->exportAs($user, ['student' => $pablo->id]);

        $this->assertSame(0, $this->countEvents($ics));
        $this->assertStringEndsWith("END:VCALENDAR\r\n", $ics);
    }

    // ── Contenido ───────────────────────────────────────────────────────────

    public function test_regular_sessions_become_events_over_the_group_range(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();
        $this->enroll($student, $family, $this->createGroup('Multideporte', 3));

        $ics = $this->exportAs($user);

        $this->assertSame(3, $this->countEvents($ics));
        $day = $this->monday->format('Ymd');
        $this->assertStringContainsString("DTSTART;TZID=Europe/Madrid:{$day}T160000\r\n", $ics);
        $this->assertStringContainsString("DTEND;TZID=Europe/Madrid:{$day}T170000\r\n", $ics);
        $this->assertStringContainsString("SUMMARY:Multideporte — Laura\r\n", $ics);
        $this->assertStringContainsString("LOCATION:Pabellón\r\n", $ics);
    }

    public function test_export_covers_the_whole_active_year_not_only_one_week(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();
        $group = $this->createGroup('Multideporte', 1, ['effective_from' => null, 'effective_until' => null]);
        $this->enroll($student, $family, $group);

        $count = $this->countEvents($this->exportAs($user));

        $this->assertGreaterThan(30, $count);
    }

    public function test_cancelled_session_is_not_exported(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();
        $group = $this->createGroup('Multideporte', 3);
        $this->enroll($student, $family, $group);
        $cancelled = $this->monday->addWeek();
        ActivityGroupException::factory()->cancelled()->create([
            'activity_group_id' => $group->id,
            'original_date' => $cancelled->format('Y-m-d'),
        ]);

        $ics = $this->exportAs($user);

        $this->assertSame(2, $this->countEvents($ics));
        $this->assertStringNotContainsString($cancelled->format('Ymd'), $ics);
    }

    public function test_modified_session_is_exported_with_final_date_time_and_location(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();
        $group = $this->createGroup('Multideporte', 3);
        $this->enroll($student, $family, $group);
        $original = $this->monday->addWeek();
        $newDate = $original->addDays(2);
        ActivityGroupException::factory()->modified()->create([
            'activity_group_id' => $group->id,
            'original_date' => $original->format('Y-m-d'),
            'new_date' => $newDate->format('Y-m-d'),
            'new_starts_at' => '17:00:00',
            'new_ends_at' => '18:30:00',
            'new_location' => 'Sala multiusos',
            'reason' => 'Mantenimiento',
        ]);

        $ics = $this->exportAs($user);

        $this->assertSame(3, $this->countEvents($ics));
        $this->assertStringNotContainsString($original->format('Ymd').'T', $ics);
        $this->assertStringContainsString("DTSTART;TZID=Europe/Madrid:{$newDate->format('Ymd')}T170000\r\n", $ics);
        $this->assertStringContainsString("DTEND;TZID=Europe/Madrid:{$newDate->format('Ymd')}T183000\r\n", $ics);
        $this->assertStringContainsString("LOCATION:Sala multiusos\r\n", $ics);
        $this->assertStringContainsString('Cambio puntual: Mantenimiento', $ics);
    }

    public function test_extra_session_is_exported(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();
        $group = $this->createGroup('Multideporte', 2);
        $this->enroll($student, $family, $group);
        $extraDate = $this->monday->addDays(2);
        ActivityGroupException::factory()->extra()->create([
            'activity_group_id' => $group->id,
            'new_date' => $extraDate->format('Y-m-d'),
            'new_starts_at' => '10:00:00',
            'new_ends_at' => '11:30:00',
            'new_location' => null,
        ]);

        $ics = $this->exportAs($user);

        $this->assertSame(3, $this->countEvents($ics));
        $this->assertStringContainsString("DTSTART;TZID=Europe/Madrid:{$extraDate->format('Ymd')}T100000\r\n", $ics);
        $this->assertStringContainsString('Sesión extra', $ics);
    }

    public function test_group_without_location_omits_location_property(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();
        $this->enroll($student, $family, $this->createGroup('Multideporte', 1, ['location' => null]));

        $ics = $this->exportAs($user);

        $this->assertSame(1, $this->countEvents($ics));
        $this->assertStringNotContainsString('LOCATION', $ics);
    }

    public function test_attendance_range_of_the_enrollment_is_respected(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();
        $group = $this->createGroup('Multideporte', 4);
        $this->enroll($student, $family, $group, EnrollmentStatus::Enrolled, [
            'attendance_from' => $this->monday->addWeek()->format('Y-m-d'),
            'attendance_until' => $this->monday->addWeeks(2)->format('Y-m-d'),
        ]);

        $ics = $this->exportAs($user);

        $this->assertSame(2, $this->countEvents($ics));
        $this->assertStringNotContainsString($this->monday->format('Ymd').'T', $ics);
        $this->assertStringNotContainsString($this->monday->addWeeks(3)->format('Ymd').'T', $ics);
    }

    public function test_only_enrollments_shown_in_the_calendar_are_exported(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();
        $this->enroll($student, $family, $this->createGroup('Visible', 1), EnrollmentStatus::Paid);
        $this->enroll($student, $family, $this->createGroup('En espera', 1), EnrollmentStatus::Waitlist);
        $this->enroll($student, $family, $this->createGroup('Cancelada', 1), EnrollmentStatus::Cancelled);

        $ics = $this->exportAs($user);

        $this->assertSame(1, $this->countEvents($ics));
        $this->assertStringContainsString('SUMMARY:Visible', $ics);
    }

    public function test_student_filter_excludes_other_children_sessions(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $laura] = $this->createFamilyUser('Laura');
        $pablo = Student::factory()->create(['family_id' => $family->id, 'first_name' => 'Pablo', 'is_active' => true]);
        $this->enroll($laura, $family, $this->createGroup('Danza', 1));
        $this->enroll($pablo, $family, $this->createGroup('Ajedrez', 1));

        $all = $this->exportAs($user);
        $onlyLaura = $this->exportAs($user, ['student' => $laura->id]);

        $this->assertSame(2, $this->countEvents($all));
        $this->assertSame(1, $this->countEvents($onlyLaura));
        $this->assertStringContainsString('Danza — Laura', $onlyLaura);
        $this->assertStringNotContainsString('Ajedrez', $onlyLaura);
        $this->assertStringNotContainsString('Pablo', $onlyLaura);
    }

    public function test_other_families_sessions_never_leak(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();
        ['family' => $otherFamily, 'student' => $otherStudent] = $this->createFamilyUser('Otro');
        $this->enroll($student, $family, $this->createGroup('Mía', 1));
        $this->enroll($otherStudent, $otherFamily, $this->createGroup('Ajena', 1));

        $ics = $this->exportAs($user);

        $this->assertStringContainsString('Mía', $ics);
        $this->assertStringNotContainsString('Ajena', $ics);
    }

    // ── RFC 5545 ────────────────────────────────────────────────────────────

    public function test_calendar_is_structurally_valid_and_uses_crlf(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();
        $this->enroll($student, $family, $this->createGroup('Multideporte', 2));

        $raw = $this->actingAs($user)->get(route('familia.calendar.export'))->getContent();

        $this->assertStringStartsWith("BEGIN:VCALENDAR\r\nVERSION:2.0\r\n", $raw);
        $this->assertStringEndsWith("END:VCALENDAR\r\n", $raw);
        $this->assertStringContainsString("CALSCALE:GREGORIAN\r\n", $raw);
        $this->assertStringContainsString("METHOD:PUBLISH\r\n", $raw);
        $this->assertSame(0, preg_match('/(?<!\r)\n/', $raw));
        $this->assertSame(substr_count($raw, 'BEGIN:VEVENT'), substr_count($raw, 'END:VEVENT'));
        $this->assertSame(2, substr_count($raw, 'BEGIN:VEVENT'));
        $this->assertSame(2, substr_count($raw, 'DTSTAMP:'));
    }

    public function test_utf8_and_special_characters_are_escaped_and_lines_folded_by_octets(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();
        $name = 'Robótica y programación — 1.º Primaria, nivel A; B\\C';
        $this->enroll($student, $family, $this->createGroup($name, 1));

        $raw = $this->actingAs($user)->get(route('familia.calendar.export'))->getContent();

        foreach (explode("\r\n", rtrim($raw, "\r\n")) as $line) {
            $this->assertLessThanOrEqual(75, strlen($line));
            $this->assertTrue(mb_check_encoding($line, 'UTF-8'));
        }

        $this->assertStringContainsString(
            'SUMMARY:Robótica y programación — 1.º Primaria\\, nivel A\\; B\\\\C — Laura',
            $this->unfold($raw),
        );
    }

    public function test_uid_is_stable_across_exports(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();
        $this->enroll($student, $family, $this->createGroup('Multideporte', 3));

        $uids = function (string $ics): array {
            preg_match_all('/^UID:(.+)$/m', $ics, $m);

            return array_map('trim', $m[1]);
        };

        $first = $uids($this->exportAs($user));
        $second = $uids($this->exportAs($user));

        $this->assertCount(3, $first);
        $this->assertSame($first, $second);
        $this->assertCount(3, array_unique($first));
        foreach ($first as $uid) {
            $this->assertStringEndsWith('@ampa-cortes.local', $uid);
        }
    }

    public function test_sessions_around_the_dst_change_keep_their_local_time(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();
        $dstSunday = CarbonImmutable::parse('last sunday of march 2051');
        $before = $dstSunday->subDays(6);
        $after = $dstSunday->addDay();
        $group = $this->createGroup('Multideporte', 1, [
            'effective_from' => $before->format('Y-m-d'),
            'effective_until' => $after->format('Y-m-d'),
        ]);
        $this->enroll($student, $family, $group);

        $ics = $this->exportAs($user);

        $this->assertSame(2, $this->countEvents($ics));
        $this->assertStringContainsString("DTSTART;TZID=Europe/Madrid:{$before->format('Ymd')}T160000\r\n", $ics);
        $this->assertStringContainsString("DTSTART;TZID=Europe/Madrid:{$after->format('Ymd')}T160000\r\n", $ics);
        $this->assertStringContainsString('RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU', $ics);
    }

    // ── UI ──────────────────────────────────────────────────────────────────

    public function test_calendar_page_links_to_the_export_keeping_the_student_filter(): void
    {
        ['user' => $user, 'student' => $student] = $this->createFamilyUser();

        $this->actingAs($user)
            ->get(route('familia.calendar'))
            ->assertOk()
            ->assertSee('Exportar calendario')
            ->assertSee(route('familia.calendar.export'), false);

        $this->actingAs($user)
            ->get(route('familia.calendar', ['student' => $student->id]))
            ->assertSee(e(route('familia.calendar.export', ['student' => $student->id])), false);
    }
}
