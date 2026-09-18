<?php

namespace App\Http\Controllers\Familia;

use App\Enums\EnrollmentStatus;
use App\Enums\ExceptionType;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ActivityGroup;
use App\Models\Enrollment;
use App\Models\Student;
use App\Services\ScheduledSessionCalculator;
use App\Support\ScheduledSession;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CalendarController extends Controller
{
    private const VISIBLE_WEEKDAYS = 6;

    private const TIMETABLE_SLOT_MINUTES = 30;

    /** @var array<int, string> */
    private const DAY_LABELS = [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
    ];

    public function __invoke(Request $request, ScheduledSessionCalculator $calculator): View
    {
        $family = $request->user()->family;
        $activeYear = AcademicYear::where('is_active', true)->first();
        $students = $family->students()->where('is_active', true)->orderBy('first_name')->get();

        if (! $activeYear) {
            return view('familia.calendar.index', [
                'activeYear' => null,
                'weekStart' => null,
                'students' => $students,
                'selectedStudentId' => null,
                'studentColors' => [],
                'days' => [],
                'notices' => collect(),
                'timeRange' => null,
                'timetable' => null,
                'prevWeekParam' => null,
                'nextWeekParam' => null,
                'isCurrentWeek' => true,
            ]);
        }

        $weekStart = $this->resolveWeekStart($request->query('week'), $activeYear);

        $selectedStudentId = $this->resolveSelectedStudentId($request->query('student'), $students);

        $enrollments = Enrollment::where('family_id', $family->id)
            ->where('academic_year_id', $activeYear->id)
            ->whereIn('status', [
                EnrollmentStatus::Enrolled->value,
                EnrollmentStatus::PendingPayment->value,
                EnrollmentStatus::Paid->value,
            ])
            ->when($selectedStudentId, fn ($q) => $q->where('student_id', $selectedStudentId))
            ->with(['student', 'activityGroup.activity', 'activityGroup.exceptions'])
            ->get();

        $weekEnd = $weekStart->addDays(self::VISIBLE_WEEKDAYS - 1);

        $sessions = collect();
        foreach ($enrollments as $enrollment) {
            $sessions = $sessions->merge($calculator->forEnrollment($enrollment, $weekStart, $weekEnd));
        }
        $sessions = $sessions->sortBy(fn ($s) => $s->date->format('Y-m-d').$s->startsAt)->values();

        $notices = $this->cancelledNotices($enrollments, $weekStart, $weekEnd);

        $sessionsByDate = $sessions->groupBy(fn ($s) => $s->date->format('Y-m-d'));

        $days = [];
        foreach (range(1, self::VISIBLE_WEEKDAYS) as $iso) {
            $date = $weekStart->addDays($iso - 1);
            $days[] = [
                'date' => $date,
                'label' => self::DAY_LABELS[$iso],
                'sessions' => $sessionsByDate->get($date->format('Y-m-d'), collect())->sortBy('startsAt')->values(),
            ];
        }

        $showSaturday = collect($days[5]['sessions'])->isNotEmpty();
        if (! $showSaturday) {
            array_pop($days);
        }

        $times = $sessions->flatMap(fn ($s) => [$s->startsAt, $s->endsAt]);
        $timeRange = $times->isEmpty() ? null : [
            'from' => $times->min(),
            'until' => $times->max(),
        ];

        $timetable = $this->buildTimetable($days, $timeRange);

        $studentColors = [];
        foreach ($students as $index => $student) {
            $studentColors[$student->id] = ($index % 6) + 1;
        }

        $today = CarbonImmutable::today();
        $isCurrentWeek = $today->betweenIncluded($weekStart, $weekStart->addDays(6));

        return view('familia.calendar.index', [
            'activeYear' => $activeYear,
            'weekStart' => $weekStart,
            'students' => $students,
            'selectedStudentId' => $selectedStudentId,
            'studentColors' => $studentColors,
            'days' => $days,
            'notices' => $notices,
            'timeRange' => $timeRange,
            'timetable' => $timetable,
            'prevWeekParam' => $weekStart->subWeek()->format('Y-m-d'),
            'nextWeekParam' => $weekStart->addWeek()->format('Y-m-d'),
            'isCurrentWeek' => $isCurrentWeek,
        ]);
    }

    private function resolveWeekStart(?string $raw, AcademicYear $activeYear): CarbonImmutable
    {
        if ($raw) {
            try {
                $parsed = CarbonImmutable::createFromFormat('Y-m-d', $raw);
                if ($parsed && $parsed->format('Y-m-d') === $raw) {
                    return $parsed->startOfWeek(CarbonImmutable::MONDAY);
                }
            } catch (\Exception) {
                // Falls through to default week below.
            }
        }

        $today = CarbonImmutable::today();
        $courseStart = CarbonImmutable::parse($activeYear->starts_at);
        $courseEnd = CarbonImmutable::parse($activeYear->ends_at);

        if ($today->betweenIncluded($courseStart, $courseEnd)) {
            return $today->startOfWeek(CarbonImmutable::MONDAY);
        }

        return $courseStart->startOfWeek(CarbonImmutable::MONDAY);
    }

    /** @param  \Illuminate\Database\Eloquent\Collection<int, Student>  $students */
    private function resolveSelectedStudentId(?string $raw, $students): ?int
    {
        if (! $raw || ! ctype_digit($raw)) {
            return null;
        }

        $studentId = (int) $raw;

        return $students->contains('id', $studentId) ? $studentId : null;
    }

    /**
     * @param  Collection<int, Enrollment>  $enrollments
     * @return Collection<int, object>
     */
    private function cancelledNotices(Collection $enrollments, CarbonImmutable $weekStart, CarbonImmutable $weekEnd): Collection
    {
        $notices = collect();

        foreach ($enrollments as $enrollment) {
            /** @var ActivityGroup $group */
            $group = $enrollment->activityGroup;

            $from = $group->effectiveStartDate();
            if ($enrollment->attendance_from) {
                $from = $from->max(CarbonImmutable::parse($enrollment->attendance_from));
            }

            $until = $group->effectiveEndDate();
            if ($enrollment->attendance_until) {
                $until = $until->min(CarbonImmutable::parse($enrollment->attendance_until));
            }

            foreach ($group->exceptions as $exception) {
                if ($exception->type !== ExceptionType::Cancelled) {
                    continue;
                }

                $date = CarbonImmutable::parse($exception->original_date);

                if (! $date->between($weekStart, $weekEnd) || ! $date->between($from, $until)) {
                    continue;
                }

                $notices->push((object) [
                    'date' => $date,
                    'activityName' => $group->activity->name,
                    'groupName' => $group->name,
                    'studentName' => $enrollment->student->full_name,
                    'reason' => $exception->reason,
                ]);
            }
        }

        return $notices->sortBy('date')->values();
    }

    /**
     * Builds the desktop timetable layout: a shared time axis plus, per day, each
     * session's proportional row offset/span (in minutes from the axis start) and
     * its lane within any group of time-overlapping sessions. Purely a rendering
     * aid — computed from the sessions already produced by the calculator, no
     * recurrence/business logic here.
     *
     * @param  array<int, array{date: CarbonImmutable, label: string, sessions: Collection<int, ScheduledSession>}>  $days
     * @param  array{from: string, until: string}|null  $timeRange
     * @return array{axisStart: int, axisEnd: int, totalMinutes: int, markers: array<int, array{label: string, offset: int}>, days: array<int, array{date: CarbonImmutable, label: string, items: array<int, array<string, mixed>>}>}|null
     */
    private function buildTimetable(array $days, ?array $timeRange): ?array
    {
        if (! $timeRange) {
            return null;
        }

        $axisStart = $this->floorToSlot($this->minutesOfDay($timeRange['from']));
        $axisEnd = $this->ceilToSlot($this->minutesOfDay($timeRange['until']));
        $totalMinutes = max($axisEnd - $axisStart, self::TIMETABLE_SLOT_MINUTES);

        $markers = [];
        for ($minute = $axisStart; $minute <= $axisEnd; $minute += self::TIMETABLE_SLOT_MINUTES) {
            $markers[] = [
                'label' => sprintf('%02d:%02d', intdiv($minute, 60), $minute % 60),
                'offset' => $minute - $axisStart,
            ];
        }

        $timetableDays = [];
        foreach ($days as $day) {
            $items = [];

            /** @var ScheduledSession $session */
            foreach ($day['sessions'] as $session) {
                $start = $this->minutesOfDay($session->startsAt);
                $end = $this->minutesOfDay($session->endsAt);

                $rowStart = min(max($start - $axisStart, 0), $totalMinutes);
                $rowEnd = min(max($end - $axisStart, 0), $totalMinutes);

                $items[] = [
                    'session' => $session,
                    'start' => $start,
                    'end' => $end,
                    'rowStart' => $rowStart,
                    'rowSpan' => max($rowEnd - $rowStart, 1),
                ];
            }

            $timetableDays[] = [
                'date' => $day['date'],
                'label' => $day['label'],
                'items' => $this->assignLanes($items),
            ];
        }

        return [
            'axisStart' => $axisStart,
            'axisEnd' => $axisEnd,
            'totalMinutes' => $totalMinutes,
            'markers' => $markers,
            'days' => $timetableDays,
        ];
    }

    private function minutesOfDay(string $time): int
    {
        [$hours, $minutes] = explode(':', $time);

        return ((int) $hours * 60) + (int) $minutes;
    }

    private function floorToSlot(int $minutes): int
    {
        return intdiv($minutes, self::TIMETABLE_SLOT_MINUTES) * self::TIMETABLE_SLOT_MINUTES;
    }

    private function ceilToSlot(int $minutes): int
    {
        return (int) ceil($minutes / self::TIMETABLE_SLOT_MINUTES) * self::TIMETABLE_SLOT_MINUTES;
    }

    /**
     * Groups a day's sessions into clusters of mutually overlapping time ranges and
     * assigns each session a lane (0-based) within its cluster via greedy interval
     * partitioning, plus the total lane count so the view can size them side by side.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function assignLanes(array $items): array
    {
        usort($items, fn ($a, $b) => $a['start'] <=> $b['start']);

        $result = [];
        $cluster = [];
        $clusterEnd = null;

        foreach ($items as $item) {
            if ($cluster !== [] && $item['start'] >= $clusterEnd) {
                array_push($result, ...$this->layoutCluster($cluster));
                $cluster = [];
            }

            $cluster[] = $item;
            $clusterEnd = $clusterEnd === null ? $item['end'] : max($clusterEnd, $item['end']);
        }

        if ($cluster !== []) {
            array_push($result, ...$this->layoutCluster($cluster));
        }

        return $result;
    }

    /**
     * @param  array<int, array<string, mixed>>  $cluster
     * @return array<int, array<string, mixed>>
     */
    private function layoutCluster(array $cluster): array
    {
        $laneEnds = [];

        foreach ($cluster as $index => $item) {
            $lane = null;
            foreach ($laneEnds as $laneIndex => $end) {
                if ($end <= $item['start']) {
                    $lane = $laneIndex;
                    break;
                }
            }
            $lane ??= count($laneEnds);

            $laneEnds[$lane] = $item['end'];
            $cluster[$index]['lane'] = $lane;
        }

        $lanes = count($laneEnds);
        foreach ($cluster as $index => $item) {
            $cluster[$index]['lanes'] = $lanes;
        }

        return $cluster;
    }
}
