<?php

namespace App\Http\Controllers\Familia;

use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $family = $request->user()->family;

        $activeEnrollments = $family->enrollments()
            ->with(['activity', 'activityGroup'])
            ->whereIn('status', array_map(fn ($s) => $s->value, EnrollmentStatus::activeStatuses()))
            ->get();

        $waitlistCount = $activeEnrollments->filter(fn ($e) => $e->status === EnrollmentStatus::Waitlist)->count();
        $pendingPaymentCount = $activeEnrollments->filter(fn ($e) => $e->status === EnrollmentStatus::PendingPayment)->count();
        $enrolledCount = $activeEnrollments->filter(fn ($e) => in_array($e->status, [
            EnrollmentStatus::Enrolled,
            EnrollmentStatus::Paid,
        ]))->count();

        $students = $family->students()->where('is_active', true)->get();

        return view('familia.dashboard', compact(
            'family',
            'students',
            'activeEnrollments',
            'enrolledCount',
            'waitlistCount',
            'pendingPaymentCount',
        ));
    }
}
