<?php

namespace App\Services;

use App\Enums\ExceptionType;
use App\Models\ActivityGroup;
use App\Models\ActivityGroupException;
use App\Models\Enrollment;
use App\Support\ScheduledSession;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class ScheduledSessionCalculator
{
    /**
     * Calculate sessions for an enrollment within a date range.
     *
     * @return Collection<int, ScheduledSession>
     */
    public function forEnrollment(Enrollment $enrollment, CarbonImmutable $rangeStart, CarbonImmutable $rangeEnd): Collection
    {
        $enrollment->loadMissing(['activityGroup.activity.academicYear', 'activityGroup.exceptions', 'student']);

        $group = $enrollment->activityGroup;
        $activity = $group->activity;
        $academicYear = $activity->academicYear;

        $groupStart = $group->effective_from ?? $academicYear->starts_at;
        $groupEnd = $group->effective_until ?? $academicYear->ends_at;

        $effectiveStart = $this->latest($groupStart, $enrollment->attendance_from);
        $effectiveEnd = $this->earliest($groupEnd, $enrollment->attendance_until);

        $start = $this->latest($effectiveStart, $rangeStart);
        $end = $this->earliest($effectiveEnd, $rangeEnd);

        if ($start->greaterThan($end)) {
            return collect();
        }

        return $this->buildSessions(
            group: $group,
            start: $start,
            end: $end,
            enrollmentId: $enrollment->id,
            studentId: $enrollment->student_id,
            studentName: $enrollment->student?->full_name,
        );
    }

    /**
     * Calculate sessions for a group within a date range (no enrollment context).
     *
     * @return Collection<int, ScheduledSession>
     */
    public function forGroup(ActivityGroup $group, CarbonImmutable $rangeStart, CarbonImmutable $rangeEnd): Collection
    {
        $group->loadMissing(['activity.academicYear', 'exceptions']);

        $activity = $group->activity;
        $academicYear = $activity->academicYear;

        $groupStart = $group->effective_from ?? $academicYear->starts_at;
        $groupEnd = $group->effective_until ?? $academicYear->ends_at;

        $start = $this->latest($groupStart, $rangeStart);
        $end = $this->earliest($groupEnd, $rangeEnd);

        if ($start->greaterThan($end)) {
            return collect();
        }

        return $this->buildSessions(
            group: $group,
            start: $start,
            end: $end,
        );
    }

    /**
     * @return Collection<int, ScheduledSession>
     */
    private function buildSessions(
        ActivityGroup $group,
        CarbonImmutable $start,
        CarbonImmutable $end,
        ?int $enrollmentId = null,
        ?int $studentId = null,
        ?string $studentName = null,
    ): Collection {
        $activity = $group->activity;
        $weekdays = $group->weekdays ?? [];
        $exceptions = $group->exceptions->keyBy(fn (ActivityGroupException $e) => $e->original_date?->format('Y-m-d'));

        $sessions = collect();

        $current = $start->copy();
        while ($current->lessThanOrEqualTo($end)) {
            if (in_array($current->dayOfWeekIso, $weekdays)) {
                $dateKey = $current->format('Y-m-d');
                $exception = $exceptions->get($dateKey);

                if ($exception && $exception->type === ExceptionType::Cancelled) {
                    $current = $current->addDay();

                    continue;
                }

                if ($exception && $exception->type === ExceptionType::Modified) {
                    $sessionDate = $exception->new_date ? CarbonImmutable::parse($exception->new_date) : $current;

                    if ($sessionDate->between($start, $end)) {
                        $sessions->push(new ScheduledSession(
                            date: $sessionDate,
                            startsAt: $exception->new_starts_at ?? $group->starts_at,
                            endsAt: $exception->new_ends_at ?? $group->ends_at,
                            location: $exception->new_location ?? $group->location ?? '',
                            activityId: $activity->id,
                            activityName: $activity->name,
                            activityGroupId: $group->id,
                            activityGroupName: $group->name,
                            enrollmentId: $enrollmentId,
                            studentId: $studentId,
                            studentName: $studentName,
                            exceptionType: ExceptionType::Modified,
                            exceptionReason: $exception->reason,
                        ));
                    }
                } else {
                    $sessions->push(new ScheduledSession(
                        date: $current,
                        startsAt: $group->starts_at,
                        endsAt: $group->ends_at,
                        location: $group->location ?? '',
                        activityId: $activity->id,
                        activityName: $activity->name,
                        activityGroupId: $group->id,
                        activityGroupName: $group->name,
                        enrollmentId: $enrollmentId,
                        studentId: $studentId,
                        studentName: $studentName,
                    ));
                }
            }

            $current = $current->addDay();
        }

        $movedIntoRange = $group->exceptions
            ->where('type', ExceptionType::Modified)
            ->filter(function (ActivityGroupException $e) use ($start, $end, $weekdays) {
                if (! $e->new_date || ! $e->original_date) {
                    return false;
                }

                $originalDate = CarbonImmutable::parse($e->original_date);
                $newDate = CarbonImmutable::parse($e->new_date);

                return ! $originalDate->between($start, $end)
                    && $newDate->between($start, $end)
                    && in_array($originalDate->dayOfWeekIso, $weekdays);
            });

        foreach ($movedIntoRange as $mod) {
            $sessions->push(new ScheduledSession(
                date: CarbonImmutable::parse($mod->new_date),
                startsAt: $mod->new_starts_at ?? $group->starts_at,
                endsAt: $mod->new_ends_at ?? $group->ends_at,
                location: $mod->new_location ?? $group->location ?? '',
                activityId: $activity->id,
                activityName: $activity->name,
                activityGroupId: $group->id,
                activityGroupName: $group->name,
                enrollmentId: $enrollmentId,
                studentId: $studentId,
                studentName: $studentName,
                exceptionType: ExceptionType::Modified,
                exceptionReason: $mod->reason,
            ));
        }

        $extras = $group->exceptions
            ->where('type', ExceptionType::Extra)
            ->filter(fn (ActivityGroupException $e) => $e->new_date && $e->new_date->between($start, $end));

        foreach ($extras as $extra) {
            $sessions->push(new ScheduledSession(
                date: CarbonImmutable::parse($extra->new_date),
                startsAt: $extra->new_starts_at,
                endsAt: $extra->new_ends_at,
                location: $extra->new_location ?? $group->location ?? '',
                activityId: $activity->id,
                activityName: $activity->name,
                activityGroupId: $group->id,
                activityGroupName: $group->name,
                enrollmentId: $enrollmentId,
                studentId: $studentId,
                studentName: $studentName,
                exceptionType: ExceptionType::Extra,
                exceptionReason: $extra->reason,
            ));
        }

        return $sessions->sortBy('date')->values();
    }

    private function latest(mixed $a, mixed $b): CarbonImmutable
    {
        $a = $a ? CarbonImmutable::parse($a) : null;
        $b = $b ? CarbonImmutable::parse($b) : null;

        if ($a === null) {
            return $b;
        }
        if ($b === null) {
            return $a;
        }

        return $a->greaterThan($b) ? $a : $b;
    }

    private function earliest(mixed $a, mixed $b): CarbonImmutable
    {
        $a = $a ? CarbonImmutable::parse($a) : null;
        $b = $b ? CarbonImmutable::parse($b) : null;

        if ($a === null) {
            return $b;
        }
        if ($b === null) {
            return $a;
        }

        return $a->lessThan($b) ? $a : $b;
    }
}
