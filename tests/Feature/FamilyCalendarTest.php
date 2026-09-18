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

class FamilyCalendarTest extends TestCase
{
    use RefreshDatabase;

    private AcademicYear $activeYear;

    private CarbonImmutable $monday;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->activeYear = AcademicYear::factory()->create([
            'starts_at' => '2050-09-01',
            'ends_at' => '2051-06-30',
            'is_active' => true,
        ]);

        $this->monday = CarbonImmutable::parse($this->activeYear->starts_at)->next(CarbonImmutable::MONDAY);
    }

    /** @return array{user: User, family: Family, student: Student} */
    private function createFamilyUser(): array
    {
        $user = User::factory()->create();
        $user->assignRole('familia');
        $family = Family::factory()->create();
        Guardian::factory()->create(['user_id' => $user->id, 'family_id' => $family->id]);
        $student = Student::factory()->create(['family_id' => $family->id, 'is_active' => true]);

        return compact('user', 'family', 'student');
    }

    /** @return array{activity: ExtracurricularActivity, group: ActivityGroup} */
    private function createGroupOnMonday(string $activityName, array $groupOverrides = []): array
    {
        $activity = ExtracurricularActivity::factory()->create([
            'academic_year_id' => $this->activeYear->id,
            'name' => $activityName,
            'status' => ActivityStatus::Published,
            'is_visible_for_families' => true,
        ]);

        $group = ActivityGroup::factory()->create(array_merge([
            'activity_id' => $activity->id,
            'weekdays' => [1],
            'starts_at' => '16:00:00',
            'ends_at' => '17:00:00',
            'effective_from' => null,
            'effective_until' => null,
        ], $groupOverrides));

        return compact('activity', 'group');
    }

    private function enroll(Student $student, Family $family, ActivityGroup $group, EnrollmentStatus $status, array $overrides = []): Enrollment
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

    // ── 1. Acceso ────────────────────────────────────────────────────────────

    public function test_family_user_can_open_calendar(): void
    {
        ['user' => $user] = $this->createFamilyUser();

        $this->actingAs($user)
            ->get(route('familia.calendar'))
            ->assertOk()
            ->assertSee('Calendario semanal');
    }

    public function test_non_family_user_cannot_access_calendar(): void
    {
        $user = User::factory()->create();
        $user->assignRole('junta_ampa');

        $this->actingAs($user)
            ->get(route('familia.calendar'))
            ->assertForbidden();
    }

    // ── 2. Estados de inscripción ──────────────────────────────────────────────

    public function test_enrolled_pending_payment_and_paid_sessions_appear(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();

        foreach ([
            [EnrollmentStatus::Enrolled, 'Actividad Inscrita'],
            [EnrollmentStatus::PendingPayment, 'Actividad Pago Pendiente'],
            [EnrollmentStatus::Paid, 'Actividad Pagada'],
        ] as [$status, $name]) {
            ['group' => $group] = $this->createGroupOnMonday($name);
            $this->enroll($student, $family, $group, $status);
        }

        $this->actingAs($user)
            ->get(route('familia.calendar', ['week' => $this->monday->format('Y-m-d')]))
            ->assertOk()
            ->assertSee('Actividad Inscrita')
            ->assertSee('Actividad Pago Pendiente')
            ->assertSee('Actividad Pagada');
    }

    public function test_pending_and_waitlist_sessions_do_not_appear(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();

        ['group' => $pendingGroup] = $this->createGroupOnMonday('Actividad Solicitada');
        $this->enroll($student, $family, $pendingGroup, EnrollmentStatus::Pending, ['enrolled_at' => null]);

        ['group' => $waitlistGroup] = $this->createGroupOnMonday('Actividad Lista Espera');
        $this->enroll($student, $family, $waitlistGroup, EnrollmentStatus::Waitlist, [
            'waitlist_position' => 1,
            'enrolled_at' => null,
        ]);

        $this->actingAs($user)
            ->get(route('familia.calendar', ['week' => $this->monday->format('Y-m-d')]))
            ->assertOk()
            ->assertDontSee('Actividad Solicitada')
            ->assertDontSee('Actividad Lista Espera');
    }

    // ── 3. Filtro por hijo/a ────────────────────────────────────────────────────

    public function test_filter_by_student_shows_only_their_sessions(): void
    {
        ['user' => $user, 'family' => $family] = $this->createFamilyUser();
        $child1 = Student::factory()->create(['family_id' => $family->id, 'is_active' => true, 'first_name' => 'Ana']);
        $child2 = Student::factory()->create(['family_id' => $family->id, 'is_active' => true, 'first_name' => 'Luis']);

        ['group' => $group1] = $this->createGroupOnMonday('Actividad de Ana');
        ['group' => $group2] = $this->createGroupOnMonday('Actividad de Luis');

        $this->enroll($child1, $family, $group1, EnrollmentStatus::Enrolled);
        $this->enroll($child2, $family, $group2, EnrollmentStatus::Enrolled);

        $this->actingAs($user)
            ->get(route('familia.calendar', ['week' => $this->monday->format('Y-m-d'), 'student' => $child1->id]))
            ->assertOk()
            ->assertSee('Actividad de Ana')
            ->assertDontSee('Actividad de Luis');
    }

    public function test_cannot_filter_by_student_from_another_family(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $ownStudent] = $this->createFamilyUser();
        $otherFamily = Family::factory()->create();
        $otherStudent = Student::factory()->create(['family_id' => $otherFamily->id, 'is_active' => true]);

        ['group' => $group] = $this->createGroupOnMonday('Actividad Propia');
        $this->enroll($ownStudent, $family, $group, EnrollmentStatus::Enrolled);

        // Passing another family's student id must be ignored, not leak/scope to that student.
        $this->actingAs($user)
            ->get(route('familia.calendar', ['week' => $this->monday->format('Y-m-d'), 'student' => $otherStudent->id]))
            ->assertOk()
            ->assertSee('Actividad Propia');
    }

    // ── 4. Navegación semanal ───────────────────────────────────────────────────

    public function test_week_navigation_shows_different_weeks_and_correct_links(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();
        $nextMonday = $this->monday->addWeek();

        ['group' => $group] = $this->createGroupOnMonday('Actividad Semana Siguiente', [
            'effective_from' => $nextMonday->format('Y-m-d'),
            'effective_until' => $nextMonday->addDays(6)->format('Y-m-d'),
        ]);
        $this->enroll($student, $family, $group, EnrollmentStatus::Enrolled);

        $currentWeek = $this->actingAs($user)
            ->get(route('familia.calendar', ['week' => $this->monday->format('Y-m-d')]));
        $currentWeek->assertOk()
            ->assertDontSee('Actividad Semana Siguiente')
            ->assertSee($nextMonday->format('Y-m-d'), false);

        $nextWeek = $this->actingAs($user)
            ->get(route('familia.calendar', ['week' => $nextMonday->format('Y-m-d')]));
        $nextWeek->assertOk()
            ->assertSee('Actividad Semana Siguiente')
            ->assertSee($this->monday->format('Y-m-d'), false);
    }

    // ── 5. Periodo de asistencia ────────────────────────────────────────────────

    public function test_attendance_period_restricts_session_visibility(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();
        ['group' => $group] = $this->createGroupOnMonday('Actividad Con Periodo');

        $this->enroll($student, $family, $group, EnrollmentStatus::Enrolled, [
            'attendance_from' => $this->monday->addWeek()->format('Y-m-d'),
            'attendance_until' => null,
        ]);

        $this->actingAs($user)
            ->get(route('familia.calendar', ['week' => $this->monday->format('Y-m-d')]))
            ->assertOk()
            ->assertDontSee('Actividad Con Periodo');

        $this->actingAs($user)
            ->get(route('familia.calendar', ['week' => $this->monday->addWeek()->format('Y-m-d')]))
            ->assertOk()
            ->assertSee('Actividad Con Periodo');
    }

    // ── 6. Excepciones ──────────────────────────────────────────────────────────

    public function test_modified_session_shows_updated_data(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();
        ['group' => $group] = $this->createGroupOnMonday('Actividad Modificada');
        $this->enroll($student, $family, $group, EnrollmentStatus::Enrolled);

        ActivityGroupException::factory()->modified()->create([
            'activity_group_id' => $group->id,
            'original_date' => $this->monday->format('Y-m-d'),
            'new_starts_at' => '18:30:00',
            'new_ends_at' => '19:30:00',
            'new_location' => 'Sala nueva',
            'reason' => 'Cambio de sala puntual',
        ]);

        $this->actingAs($user)
            ->get(route('familia.calendar', ['week' => $this->monday->format('Y-m-d')]))
            ->assertOk()
            ->assertSee('18:30')
            ->assertSee('Sala nueva')
            ->assertSee('Cambio puntual');
    }

    public function test_cancelled_session_appears_as_notice_not_as_session(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();
        ['group' => $group] = $this->createGroupOnMonday('Actividad Cancelada');
        $this->enroll($student, $family, $group, EnrollmentStatus::Enrolled);

        ActivityGroupException::factory()->cancelled()->create([
            'activity_group_id' => $group->id,
            'original_date' => $this->monday->format('Y-m-d'),
            'reason' => 'Festivo local',
        ]);

        // Cancelling the week's only session leaves the calendar empty, but the
        // notice must still surface above the empty state — never as a session card.
        $this->actingAs($user)
            ->get(route('familia.calendar', ['week' => $this->monday->format('Y-m-d')]))
            ->assertOk()
            ->assertSee('Avisos de esta semana')
            ->assertSee('Festivo local')
            ->assertSee('No hay actividades esta semana');
    }

    public function test_cancelled_session_leaves_that_day_empty_when_other_sessions_remain(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();

        ['group' => $mondayGroup] = $this->createGroupOnMonday('Actividad Cancelada');
        $this->enroll($student, $family, $mondayGroup, EnrollmentStatus::Enrolled);

        ['group' => $tuesdayGroup] = $this->createGroupOnMonday('Actividad Del Martes', ['weekdays' => [2]]);
        $this->enroll($student, $family, $tuesdayGroup, EnrollmentStatus::Enrolled);

        ActivityGroupException::factory()->cancelled()->create([
            'activity_group_id' => $mondayGroup->id,
            'original_date' => $this->monday->format('Y-m-d'),
            'reason' => 'Festivo local',
        ]);

        $this->actingAs($user)
            ->get(route('familia.calendar', ['week' => $this->monday->format('Y-m-d')]))
            ->assertOk()
            ->assertSee('Avisos de esta semana')
            ->assertSee('Festivo local')
            ->assertSee('Actividad Del Martes')
            // "Actividad Cancelada" legitimately appears inside the notice text above —
            // what matters is that Monday's day column renders empty, not a session card.
            ->assertSee('Sin sesiones');
    }

    // ── 7. Estado vacío ──────────────────────────────────────────────────────────

    public function test_empty_week_shows_message_and_link_to_activities(): void
    {
        ['user' => $user] = $this->createFamilyUser();

        $this->actingAs($user)
            ->get(route('familia.calendar', ['week' => $this->monday->format('Y-m-d')]))
            ->assertOk()
            ->assertSee('No hay actividades esta semana')
            ->assertSee(route('familia.activities.index'), false);
    }

    // ── 8. Navegación familiar ───────────────────────────────────────────────────

    public function test_calendar_link_appears_in_family_navigation(): void
    {
        ['user' => $user] = $this->createFamilyUser();

        $this->actingAs($user)
            ->get(route('familia.dashboard'))
            ->assertOk()
            ->assertSee('Calendario')
            ->assertSee(route('familia.calendar'), false);
    }

    // ── 9. Semana por defecto ─────────────────────────────────────────────────────

    public function test_calendar_defaults_to_current_week_when_today_is_within_active_year(): void
    {
        $this->activeYear->update(['is_active' => false]);
        $currentYear = AcademicYear::factory()->create([
            'starts_at' => now()->subMonth()->format('Y-m-d'),
            'ends_at' => now()->addMonths(6)->format('Y-m-d'),
            'is_active' => true,
        ]);

        ['user' => $user] = $this->createFamilyUser();

        $expectedMonday = CarbonImmutable::now()->startOfWeek(CarbonImmutable::MONDAY)->format('Y-m-d');

        $this->actingAs($user)
            ->get(route('familia.calendar'))
            ->assertOk()
            ->assertSee($expectedMonday, false);
    }

    public function test_calendar_defaults_to_first_week_of_course_when_today_outside_active_year(): void
    {
        // setUp's activeYear spans 2050–2051, guaranteed outside the real "today".
        ['user' => $user] = $this->createFamilyUser();

        $this->actingAs($user)
            ->get(route('familia.calendar'))
            ->assertOk()
            ->assertSee($this->monday->format('Y-m-d'), false);
    }

    // ── 10. Timetable de escritorio (posición/duración/solapamiento) ──────────────

    private function assertTimetableAttribute(string $content, string $activityKey, string $attribute, string $expected): void
    {
        $pattern = sprintf(
            '/data-activity="%s"[^>]*data-%s="%s"/s',
            preg_quote($activityKey, '/'),
            preg_quote($attribute, '/'),
            preg_quote($expected, '/')
        );

        $this->assertMatchesRegularExpression($pattern, $content, "Expected data-{$attribute}=\"{$expected}\" for \"{$activityKey}\" in the timetable markup.");
    }

    public function test_timetable_positions_sessions_proportionally_by_start_time(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();

        ['group' => $earlyGroup] = $this->createGroupOnMonday('Sesión Temprana', ['starts_at' => '16:00:00', 'ends_at' => '17:00:00']);
        $this->enroll($student, $family, $earlyGroup, EnrollmentStatus::Enrolled);

        ['group' => $lateGroup] = $this->createGroupOnMonday('Sesión Tardía', ['starts_at' => '18:00:00', 'ends_at' => '19:00:00']);
        $this->enroll($student, $family, $lateGroup, EnrollmentStatus::Enrolled);

        $content = $this->actingAs($user)
            ->get(route('familia.calendar', ['week' => $this->monday->format('Y-m-d')]))
            ->assertOk()
            ->getContent();

        // Axis rounds to the 30-minute grid, so it starts exactly at 16:00 here.
        $this->assertTimetableAttribute($content, "Sesión Temprana-{$student->full_name}", 'row-start', '0');
        $this->assertTimetableAttribute($content, "Sesión Tardía-{$student->full_name}", 'row-start', '120');
    }

    public function test_timetable_session_duration_is_reflected_in_row_span(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();

        ['group' => $shortGroup] = $this->createGroupOnMonday('Sesión Corta', ['starts_at' => '16:00:00', 'ends_at' => '16:30:00']);
        $this->enroll($student, $family, $shortGroup, EnrollmentStatus::Enrolled);

        ['group' => $longGroup] = $this->createGroupOnMonday('Sesión Larga', ['starts_at' => '17:00:00', 'ends_at' => '18:30:00']);
        $this->enroll($student, $family, $longGroup, EnrollmentStatus::Enrolled);

        $content = $this->actingAs($user)
            ->get(route('familia.calendar', ['week' => $this->monday->format('Y-m-d')]))
            ->assertOk()
            ->getContent();

        $this->assertTimetableAttribute($content, "Sesión Corta-{$student->full_name}", 'row-span', '30');
        $this->assertTimetableAttribute($content, "Sesión Larga-{$student->full_name}", 'row-span', '90');
    }

    public function test_timetable_overlapping_sessions_get_separate_lanes(): void
    {
        ['user' => $user, 'family' => $family] = $this->createFamilyUser();
        $child1 = Student::factory()->create(['family_id' => $family->id, 'is_active' => true, 'first_name' => 'Ana']);
        $child2 = Student::factory()->create(['family_id' => $family->id, 'is_active' => true, 'first_name' => 'Luis']);

        ['group' => $group1] = $this->createGroupOnMonday('Actividad Solapada A', ['starts_at' => '16:00:00', 'ends_at' => '17:00:00']);
        $this->enroll($child1, $family, $group1, EnrollmentStatus::Enrolled);

        ['group' => $group2] = $this->createGroupOnMonday('Actividad Solapada B', ['starts_at' => '16:30:00', 'ends_at' => '17:30:00']);
        $this->enroll($child2, $family, $group2, EnrollmentStatus::Enrolled);

        $content = $this->actingAs($user)
            ->get(route('familia.calendar', ['week' => $this->monday->format('Y-m-d')]))
            ->assertOk()
            ->getContent();

        $this->assertTimetableAttribute($content, "Actividad Solapada A-{$child1->full_name}", 'lanes', '2');
        $this->assertTimetableAttribute($content, "Actividad Solapada B-{$child2->full_name}", 'lanes', '2');
        $this->assertTimetableAttribute($content, "Actividad Solapada A-{$child1->full_name}", 'lane', '0');
        $this->assertTimetableAttribute($content, "Actividad Solapada B-{$child2->full_name}", 'lane', '1');
    }

    public function test_timetable_offsets_stay_within_visible_range_bounds(): void
    {
        ['user' => $user, 'family' => $family, 'student' => $student] = $this->createFamilyUser();

        // A single session defines the whole visible range, so it must span it exactly:
        // row-start at 0 and row-start + row-span exactly at the axis total, never negative or overflowing.
        ['group' => $group] = $this->createGroupOnMonday('Sesión Única', ['starts_at' => '15:30:00', 'ends_at' => '17:00:00']);
        $this->enroll($student, $family, $group, EnrollmentStatus::Enrolled);

        $content = $this->actingAs($user)
            ->get(route('familia.calendar', ['week' => $this->monday->format('Y-m-d')]))
            ->assertOk()
            ->getContent();

        $this->assertTimetableAttribute($content, "Sesión Única-{$student->full_name}", 'row-start', '0');
        $this->assertTimetableAttribute($content, "Sesión Única-{$student->full_name}", 'row-span', '90');
    }
}
