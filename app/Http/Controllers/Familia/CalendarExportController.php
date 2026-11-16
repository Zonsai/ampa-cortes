<?php

namespace App\Http\Controllers\Familia;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Services\CalendarIcsExporter;
use App\Services\ScheduledSessionCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;

class CalendarExportController extends Controller
{
    public function __invoke(Request $request, ScheduledSessionCalculator $calculator, CalendarIcsExporter $exporter): Response
    {
        $family = $request->user()->family;
        $activeYear = AcademicYear::where('is_active', true)->first();

        abort_if($activeYear === null, 404);

        $students = $family->students()->where('is_active', true)->orderBy('first_name')->get();

        $selectedStudent = null;
        $rawStudent = $request->query('student');
        if ($rawStudent !== null && $rawStudent !== '') {
            abort_unless(is_string($rawStudent) && ctype_digit($rawStudent), 404);
            $selectedStudent = $students->firstWhere('id', (int) $rawStudent);
            abort_if($selectedStudent === null, 404);
        }

        $enrollments = Enrollment::where('family_id', $family->id)
            ->where('academic_year_id', $activeYear->id)
            ->inFamilyCalendar()
            ->when($selectedStudent, fn ($q) => $q->where('student_id', $selectedStudent->id))
            ->with(['student', 'activityGroup.activity', 'activityGroup.exceptions'])
            ->get();

        $rangeStart = CarbonImmutable::parse($activeYear->starts_at);
        $rangeEnd = CarbonImmutable::parse($activeYear->ends_at);

        $sessions = $enrollments->flatMap(
            fn (Enrollment $enrollment) => $calculator->forEnrollment($enrollment, $rangeStart, $rangeEnd)
        );

        $firstNames = $enrollments->mapWithKeys(fn (Enrollment $e) => [$e->student_id => $e->student->first_name])->all();

        $ics = $exporter->export(
            $sessions,
            $firstNames,
            'AMPA Cortés de Aragón — '.$activeYear->name,
        );

        $who = $selectedStudent ? Str::slug($selectedStudent->first_name) : 'familia';
        $filename = 'calendario-'.($who !== '' ? $who : 'familia').'-'.(Str::slug($activeYear->name) ?: 'curso').'.ics';

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=UTF-8',
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $filename),
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
