<?php

namespace App\Http\Controllers\Familia;

use App\Enums\ActivityStatus;
use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\ExtracurricularActivity;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivitiesController extends Controller
{
    public function index(Request $request): View
    {
        $family = $request->user()->family;
        $activeYear = AcademicYear::where('is_active', true)->first();

        $activities = null;
        $familyEnrollmentsByActivity = collect();

        if ($activeYear) {
            $activities = ExtracurricularActivity::query()
                ->where('academic_year_id', $activeYear->id)
                ->where('status', ActivityStatus::Published)
                ->where('is_visible_for_families', true)
                ->withCount('activityGroups')
                ->with(['activityGroups' => fn ($q) => $q->select('id', 'activity_id', 'price_member', 'price_non_member')])
                ->orderBy('name')
                ->get();

            if ($family) {
                $familyEnrollmentsByActivity = Enrollment::where('family_id', $family->id)
                    ->where('academic_year_id', $activeYear->id)
                    ->whereIn('status', array_map(fn ($s) => $s->value, EnrollmentStatus::activeStatuses()))
                    ->with('student:id,first_name')
                    ->get()
                    ->groupBy('activity_id');
            }
        }

        return view('familia.activities.index', compact('family', 'activeYear', 'activities', 'familyEnrollmentsByActivity'));
    }

    public function show(Request $request, ExtracurricularActivity $activity): View
    {
        $activeYear = AcademicYear::where('is_active', true)->first();

        if (
            ! $activeYear
            || $activity->academic_year_id !== $activeYear->id
            || $activity->status !== ActivityStatus::Published
            || ! $activity->is_visible_for_families
        ) {
            abort(404);
        }

        $family = $request->user()->family;
        $students = $family->students()->where('is_active', true)->orderBy('last_name')->get();

        $studentCurrentGradeIds = $students->mapWithKeys(
            fn ($s) => [$s->id => $s->currentClassroom()?->grade_id]
        );

        $activity->load([
            'activityGroups' => fn ($q) => $q
                ->withCount('occupyingEnrollments')
                ->with('grades:id,name')
                ->orderBy('name'),
        ]);

        $allEnrollments = Enrollment::where('family_id', $family->id)
            ->where('activity_id', $activity->id)
            ->whereIn('status', array_map(fn ($s) => $s->value, EnrollmentStatus::activeStatuses()))
            ->get();

        $enrollmentMap = $allEnrollments->keyBy(fn ($e) => $e->student_id.'_'.$e->activity_group_id);

        $studentEnrolledGroupIds = $allEnrollments
            ->groupBy('student_id')
            ->map(fn ($es) => $es->pluck('activity_group_id')->toArray());

        return view('familia.activities.show', compact(
            'activity',
            'activeYear',
            'family',
            'students',
            'enrollmentMap',
            'studentCurrentGradeIds',
            'studentEnrolledGroupIds',
        ));
    }
}
