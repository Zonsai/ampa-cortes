<?php

namespace App\Http\Controllers\Familia;

use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Models\FormResponse;
use App\Services\ConsentStatusService;
use App\Services\FormVisibilityService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly ConsentStatusService $consentStatusService,
        private readonly FormVisibilityService $formVisibilityService,
    ) {}

    public function __invoke(Request $request): View
    {
        $family = $request->user()->family;

        $activeEnrollments = $family->enrollments()
            ->with(['activity', 'activityGroup', 'student'])
            ->whereIn('status', array_map(fn ($s) => $s->value, EnrollmentStatus::activeStatuses()))
            ->get();

        $pendingRequestCount = $activeEnrollments->filter(fn ($e) => $e->status === EnrollmentStatus::Pending)->count();
        $waitlistCount = $activeEnrollments->filter(fn ($e) => $e->status === EnrollmentStatus::Waitlist)->count();
        $pendingPaymentCount = $activeEnrollments->filter(fn ($e) => $e->status === EnrollmentStatus::PendingPayment)->count();
        $enrolledCount = $activeEnrollments->filter(fn ($e) => in_array($e->status, [
            EnrollmentStatus::Enrolled,
            EnrollmentStatus::Paid,
        ]))->count();

        $students = $family->students()->where('is_active', true)->get();

        $pendingConsentsCount = $this->consentStatusService->countPendingForFamily($family);

        $pendingFormsCount = $this->countPendingFormsForFamily($family);

        return view('familia.dashboard', compact(
            'family',
            'students',
            'activeEnrollments',
            'enrolledCount',
            'pendingRequestCount',
            'waitlistCount',
            'pendingPaymentCount',
            'pendingConsentsCount',
            'pendingFormsCount',
        ));
    }

    /**
     * Number of open forms targeted at the family that still have no response.
     * Mirrors the "pending" grouping used in FormsController@index without
     * altering any form business logic.
     */
    private function countPendingFormsForFamily(Family $family): int
    {
        $openForms = $this->formVisibilityService->getOpenFormsForFamily($family);

        $respondedFormIds = FormResponse::where('family_id', $family->id)
            ->pluck('form_id')
            ->unique();

        return $openForms->reject(fn ($form) => $respondedFormIds->contains($form->id))->count();
    }
}
