<?php

namespace App\Services;

use App\Support\ScheduledSession;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Serializes already-authorized scheduled sessions as an RFC 5545 calendar.
 *
 * It holds no business logic: recurrences, exceptions and attendance ranges are
 * resolved upstream by ScheduledSessionCalculator.
 */
class CalendarIcsExporter
{
    public const TIMEZONE = 'Europe/Madrid';

    private const PRODID = '-//AMPA Cortes de Aragon//Portal AMPA//ES';

    private const MAX_LINE_OCTETS = 75;

    /**
     * @param  Collection<int, ScheduledSession>  $sessions  Cancelled sessions are never part of the calculator output.
     * @param  array<int, string>  $studentFirstNames  Keyed by student id, used in SUMMARY.
     */
    public function export(Collection $sessions, array $studentFirstNames, string $calendarName, ?CarbonImmutable $generatedAt = null): string
    {
        $stamp = ($generatedAt ?? CarbonImmutable::now())->utc()->format('Ymd\THis\Z');

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:'.self::PRODID,
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:'.$this->escapeText($calendarName),
            'X-WR-TIMEZONE:'.self::TIMEZONE,
            ...$this->timezoneComponent(),
        ];

        $ordered = $sessions
            ->sortBy([
                fn (ScheduledSession $a, ScheduledSession $b) => $a->date->format('Y-m-d') <=> $b->date->format('Y-m-d'),
                fn (ScheduledSession $a, ScheduledSession $b) => $a->startsAt <=> $b->startsAt,
                fn (ScheduledSession $a, ScheduledSession $b) => $a->uid() <=> $b->uid(),
            ])
            ->values();

        foreach ($ordered as $session) {
            array_push($lines, ...$this->eventLines($session, $studentFirstNames, $stamp));
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", array_map(fn (string $line) => $this->fold($line), $lines))."\r\n";
    }

    /**
     * @param  array<int, string>  $studentFirstNames
     * @return array<int, string>
     */
    private function eventLines(ScheduledSession $session, array $studentFirstNames, string $stamp): array
    {
        $firstName = $session->studentId !== null ? ($studentFirstNames[$session->studentId] ?? null) : null;
        $summary = $firstName ? "{$session->activityName} — {$firstName}" : $session->activityName;

        $day = $session->date->format('Ymd');

        $lines = [
            'BEGIN:VEVENT',
            'UID:'.$session->uid(),
            'DTSTAMP:'.$stamp,
            'DTSTART;TZID='.self::TIMEZONE.':'.$day.'T'.$this->timeValue($session->startsAt),
            'DTEND;TZID='.self::TIMEZONE.':'.$day.'T'.$this->timeValue($session->endsAt),
            'SUMMARY:'.$this->escapeText($summary),
        ];

        if ($session->location !== '') {
            $lines[] = 'LOCATION:'.$this->escapeText($session->location);
        }

        $description = $this->description($session);
        if ($description !== '') {
            $lines[] = 'DESCRIPTION:'.$this->escapeText($description);
        }

        $lines[] = 'END:VEVENT';

        return $lines;
    }

    private function description(ScheduledSession $session): string
    {
        $parts = [];

        if ($session->activityGroupName !== '') {
            $parts[] = "Grupo: {$session->activityGroupName}";
        }

        if ($session->studentName) {
            $parts[] = "Alumno/a: {$session->studentName}";
        }

        if ($session->isModified()) {
            $parts[] = 'Cambio puntual'.($session->exceptionReason ? ": {$session->exceptionReason}" : '');
        } elseif ($session->isExtra()) {
            $parts[] = 'Sesión extra'.($session->exceptionReason ? ": {$session->exceptionReason}" : '');
        }

        return implode("\n", $parts);
    }

    /** Converts "H:i" or "H:i:s" into the iCalendar local time "HHMMSS". */
    private function timeValue(string $time): string
    {
        $parts = explode(':', $time);

        return sprintf('%02d%02d%02d', (int) $parts[0], (int) ($parts[1] ?? 0), (int) ($parts[2] ?? 0));
    }

    /**
     * Europe/Madrid (CET/CEST, EU rules): lets clients apply DST themselves
     * instead of receiving pre-converted UTC times.
     *
     * @return array<int, string>
     */
    private function timezoneComponent(): array
    {
        return [
            'BEGIN:VTIMEZONE',
            'TZID:'.self::TIMEZONE,
            'BEGIN:STANDARD',
            'DTSTART:19701025T030000',
            'TZOFFSETFROM:+0200',
            'TZOFFSETTO:+0100',
            'TZNAME:CET',
            'RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU',
            'END:STANDARD',
            'BEGIN:DAYLIGHT',
            'DTSTART:19700329T020000',
            'TZOFFSETFROM:+0100',
            'TZOFFSETTO:+0200',
            'TZNAME:CEST',
            'RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU',
            'END:DAYLIGHT',
            'END:VTIMEZONE',
        ];
    }

    /** Escapes a TEXT value (RFC 5545 §3.3.11): backslash first, then ; , and newlines. */
    public function escapeText(string $value): string
    {
        return str_replace(
            ['\\', ';', ',', "\r\n", "\n", "\r"],
            ['\\\\', '\\;', '\\,', '\\n', '\\n', '\\n'],
            $value,
        );
    }

    /**
     * Folds a content line at 75 octets (RFC 5545 §3.1). Continuation lines start
     * with one space, so they carry 74 content octets. Cuts never split a UTF-8 sequence.
     */
    public function fold(string $line): string
    {
        $out = '';
        $limit = self::MAX_LINE_OCTETS;

        while (strlen($line) > $limit) {
            $cut = $limit;
            while ($cut > 0 && (ord($line[$cut]) & 0xC0) === 0x80) {
                $cut--;
            }

            $out .= substr($line, 0, $cut)."\r\n ";
            $line = substr($line, $cut);
            $limit = self::MAX_LINE_OCTETS - 1;
        }

        return $out.$line;
    }
}
