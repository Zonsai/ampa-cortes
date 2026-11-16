<?php

namespace Tests\Unit;

use App\Enums\ExceptionType;
use App\Services\CalendarIcsExporter;
use App\Support\ScheduledSession;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class CalendarIcsExporterTest extends TestCase
{
    private CalendarIcsExporter $exporter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->exporter = new CalendarIcsExporter;
    }

    private function session(array $overrides = []): ScheduledSession
    {
        return new ScheduledSession(...array_merge([
            'date' => CarbonImmutable::parse('2050-10-03'),
            'startsAt' => '16:00:00',
            'endsAt' => '17:00:00',
            'location' => 'Pabellón',
            'activityId' => 1,
            'activityName' => 'Multideporte',
            'activityGroupId' => 5,
            'activityGroupName' => 'Grupo A',
            'enrollmentId' => 7,
            'studentId' => 3,
            'studentName' => 'Laura García López',
        ], $overrides));
    }

    private function export(array $sessions): string
    {
        return $this->exporter->export(
            collect($sessions),
            [3 => 'Laura', 4 => 'Pablo'],
            'AMPA — 2050-2051',
            CarbonImmutable::parse('2050-09-01 10:00:00', 'UTC'),
        );
    }

    /** RFC 5545 unfolding: CRLF followed by one space/tab is removed. */
    private function unfold(string $ics): string
    {
        return preg_replace('/\r\n[ \t]/', '', $ics);
    }

    public function test_empty_calendar_is_structurally_valid(): void
    {
        $ics = $this->export([]);

        $this->assertStringStartsWith("BEGIN:VCALENDAR\r\nVERSION:2.0\r\n", $ics);
        $this->assertStringEndsWith("END:VCALENDAR\r\n", $ics);
        $this->assertStringContainsString("PRODID:-//AMPA Cortes de Aragon//Portal AMPA//ES\r\n", $ics);
        $this->assertStringContainsString("CALSCALE:GREGORIAN\r\n", $ics);
        $this->assertStringContainsString("METHOD:PUBLISH\r\n", $ics);
        $this->assertStringNotContainsString('BEGIN:VEVENT', $ics);
    }

    public function test_every_line_ends_with_crlf_and_no_bare_line_feeds(): void
    {
        $ics = $this->export([$this->session()]);

        $this->assertSame(0, preg_match('/(?<!\r)\n/', $ics));
        $this->assertSame(0, preg_match('/\r(?!\n)/', $ics));
    }

    public function test_event_contains_required_properties(): void
    {
        $ics = $this->unfold($this->export([$this->session()]));

        $this->assertStringContainsString("BEGIN:VEVENT\r\n", $ics);
        $this->assertStringContainsString("UID:enrollment-7-20501003@ampa-cortes.local\r\n", $ics);
        $this->assertStringContainsString("DTSTAMP:20500901T100000Z\r\n", $ics);
        $this->assertStringContainsString("DTSTART;TZID=Europe/Madrid:20501003T160000\r\n", $ics);
        $this->assertStringContainsString("DTEND;TZID=Europe/Madrid:20501003T170000\r\n", $ics);
        $this->assertStringContainsString("SUMMARY:Multideporte — Laura\r\n", $ics);
        $this->assertStringContainsString("LOCATION:Pabellón\r\n", $ics);
        $this->assertStringContainsString("DESCRIPTION:Grupo: Grupo A\\nAlumno/a: Laura García López\r\n", $ics);
        $this->assertStringContainsString("END:VEVENT\r\n", $ics);
    }

    public function test_location_is_omitted_when_empty(): void
    {
        $ics = $this->export([$this->session(['location' => ''])]);

        $this->assertStringNotContainsString('LOCATION', $ics);
    }

    public function test_timezone_component_defines_dst_rules(): void
    {
        $ics = $this->export([]);

        $this->assertStringContainsString("TZID:Europe/Madrid\r\n", $ics);
        $this->assertStringContainsString("RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU\r\n", $ics);
        $this->assertStringContainsString("RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU\r\n", $ics);
        $this->assertStringContainsString("TZOFFSETTO:+0200\r\n", $ics);
        $this->assertStringContainsString("TZOFFSETTO:+0100\r\n", $ics);
    }

    public function test_times_are_local_and_not_shifted_across_dst_change(): void
    {
        $winter = $this->session(['date' => CarbonImmutable::parse('2051-03-19')]);
        $summer = $this->session(['date' => CarbonImmutable::parse('2051-04-02')]);

        $ics = $this->export([$winter, $summer]);

        $this->assertStringContainsString("DTSTART;TZID=Europe/Madrid:20510319T160000\r\n", $ics);
        $this->assertStringContainsString("DTSTART;TZID=Europe/Madrid:20510402T160000\r\n", $ics);
        $this->assertSame(0, preg_match('/^DT(START|END):\d{8}T\d{6}Z/m', $ics));
    }

    public function test_times_without_seconds_are_normalised(): void
    {
        $ics = $this->export([$this->session(['startsAt' => '9:05', 'endsAt' => '10:00'])]);

        $this->assertStringContainsString('T090500', $ics);
        $this->assertStringContainsString('T100000', $ics);
    }

    public function test_text_values_are_escaped(): void
    {
        $this->assertSame('a\\\\b\\;c\\,d\\ne\\nf\\ng', $this->exporter->escapeText("a\\b;c,d\ne\r\nf\rg"));
    }

    public function test_special_characters_are_escaped_in_summary_location_and_description(): void
    {
        $ics = $this->unfold($this->export([$this->session([
            'activityName' => 'Arte, Danza; y \\ más',
            'location' => "Aula 1, planta 2;\nedificio B",
            'activityGroupName' => 'G1, G2',
        ])]));

        $this->assertStringContainsString("SUMMARY:Arte\\, Danza\\; y \\\\ más — Laura\r\n", $ics);
        $this->assertStringContainsString("LOCATION:Aula 1\\, planta 2\\;\\nedificio B\r\n", $ics);
        $this->assertStringContainsString('Grupo: G1\\, G2', $ics);
    }

    public function test_long_lines_are_folded_at_75_octets_without_splitting_utf8(): void
    {
        $name = str_repeat('Robótica y programación — 1.º Primaria ', 8);
        $ics = $this->export([$this->session(['activityName' => $name])]);

        foreach (explode("\r\n", rtrim($ics, "\r\n")) as $line) {
            $this->assertLessThanOrEqual(75, strlen($line), "Line too long: {$line}");
            $this->assertTrue(mb_check_encoding($line, 'UTF-8'), "Invalid UTF-8 in line: {$line}");
        }

        $this->assertStringContainsString($this->exporter->escapeText($name.' — Laura'), $this->unfold($ics));
    }

    public function test_fold_keeps_short_lines_and_round_trips_long_ones(): void
    {
        $this->assertSame('SHORT:line', $this->exporter->fold('SHORT:line'));

        $long = 'SUMMARY:'.str_repeat('ñ', 200);
        $folded = $this->exporter->fold($long);

        $this->assertStringContainsString("\r\n ", $folded);
        $this->assertSame($long, preg_replace('/\r\n /', '', $folded));
        foreach (explode("\r\n", $folded) as $i => $line) {
            $this->assertLessThanOrEqual(75, strlen($line));
        }
    }

    public function test_uid_is_stable_between_exports(): void
    {
        $first = $this->export([$this->session()]);
        $second = $this->exporter->export(
            collect([$this->session()]),
            [3 => 'Laura'],
            'AMPA — 2050-2051',
            CarbonImmutable::parse('2051-01-01 08:00:00', 'UTC'),
        );

        preg_match('/UID:(\S+)/', $first, $a);
        preg_match('/UID:(\S+)/', $second, $b);

        $this->assertSame($a[1], $b[1]);
    }

    public function test_extra_session_has_distinct_uid_from_regular_session_same_day(): void
    {
        $regular = $this->session();
        $extra = $this->session([
            'startsAt' => '18:00:00',
            'endsAt' => '19:00:00',
            'exceptionType' => ExceptionType::Extra,
            'exceptionReason' => 'Recuperación',
        ]);

        $this->assertNotSame($regular->uid(), $extra->uid());

        $ics = $this->unfold($this->export([$regular, $extra]));
        $this->assertStringContainsString('UID:enrollment-7-20501003-extra-1800@ampa-cortes.local', $ics);
        $this->assertStringContainsString('Sesión extra: Recuperación', $ics);
    }

    public function test_modified_session_mentions_the_change_in_description(): void
    {
        $ics = $this->unfold($this->export([$this->session([
            'exceptionType' => ExceptionType::Modified,
            'exceptionReason' => 'Cambio de sala',
        ])]));

        $this->assertStringContainsString('Cambio puntual: Cambio de sala', $ics);
    }

    public function test_events_are_ordered_by_date_then_time(): void
    {
        $late = $this->session(['date' => CarbonImmutable::parse('2050-10-10'), 'activityName' => 'Tarde']);
        $early = $this->session(['date' => CarbonImmutable::parse('2050-10-03'), 'activityName' => 'Pronto']);

        $ics = $this->export([$late, $early]);

        $this->assertLessThan(strpos($ics, 'Tarde'), strpos($ics, 'Pronto'));
    }

    public function test_summary_falls_back_to_activity_name_without_student(): void
    {
        $ics = $this->export([$this->session(['studentId' => null, 'studentName' => null])]);

        $this->assertStringContainsString("SUMMARY:Multideporte\r\n", $ics);
    }
}
