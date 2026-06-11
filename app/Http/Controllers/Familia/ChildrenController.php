<?php

namespace App\Http\Controllers\Familia;

use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChildrenController extends Controller
{
    public function __invoke(Request $request): View
    {
        $family = $request->user()->family;

        $students = $family->students()
            ->with([
                'classrooms' => fn ($q) => $q
                    ->whereHas('academicYear', fn ($q) => $q->where('is_active', true))
                    ->with('grade'),
                'enrollments' => fn ($q) => $q
                    ->whereIn('status', array_map(fn ($s) => $s->value, EnrollmentStatus::activeStatuses()))
                    ->with(['activity', 'activityGroup'])
                    ->orderBy('registered_at', 'desc'),
            ])
            ->orderBy('last_name')
            ->get();

        return view('familia.children.index', compact('family', 'students'));
    }
}
