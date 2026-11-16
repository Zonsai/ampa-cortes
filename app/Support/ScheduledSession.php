<?php

namespace App\Support;

use App\Enums\ExceptionType;
use Carbon\CarbonImmutable;

class ScheduledSession
{
    public function __construct(
        public readonly CarbonImmutable $date,
        public readonly string $startsAt,
        public readonly string $endsAt,
        public readonly string $location,
        public readonly int $activityId,
        public readonly string $activityName,
        public readonly int $activityGroupId,
        public readonly string $activityGroupName,
        public readonly ?int $enrollmentId = null,
        public readonly ?int $studentId = null,
        public readonly ?string $studentName = null,
        public readonly ?ExceptionType $exceptionType = null,
        public readonly ?string $exceptionReason = null,
    ) {}

    public function isCancelled(): bool
    {
        return $this->exceptionType === ExceptionType::Cancelled;
    }

    public function isModified(): bool
    {
        return $this->exceptionType === ExceptionType::Modified;
    }

    public function isExtra(): bool
    {
        return $this->exceptionType === ExceptionType::Extra;
    }

    public function isRegular(): bool
    {
        return $this->exceptionType === null;
    }

    /**
     * @return string Stable UID for the .ics export, scoped per enrollment (same session => same UID across exports).
     */
    public function uid(string $domain = 'ampa-cortes.local'): string
    {
        $prefix = $this->enrollmentId
            ? "enrollment-{$this->enrollmentId}"
            : "group-{$this->activityGroupId}";

        // An extra session may share a day with the regular one, so it carries its own start time.
        $suffix = $this->isExtra() ? '-extra-'.str_replace(':', '', substr($this->startsAt, 0, 5)) : '';

        return "{$prefix}-{$this->date->format('Ymd')}{$suffix}@{$domain}";
    }
}
