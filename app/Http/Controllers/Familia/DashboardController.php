<?php

namespace App\Http\Controllers\Familia;

use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Family;
use App\Models\Form;
use App\Models\FormResponse;
use App\Services\ConsentStatusService;
use App\Services\FormEligibleStudentsService;
use App\Services\FormVisibilityService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly ConsentStatusService $consentStatusService,
        private readonly FormVisibilityService $formVisibilityService,
        private readonly FormEligibleStudentsService $formEligibleStudentsService,
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

        $familyAnnouncements = Announcement::query()->forFamilies()->ordered()->limit(3)->get();

        return view('familia.dashboard', compact(
            'family',
            'students',
            'activeEnrollments',
            'enrolledCount',
            'pendingRequestCount',
            'waitlistCount',
            'pendingPaymentCount',
            'pendingConsentsCount',
            'familyAnnouncements',
            'pendingFormsCount',
        ));
    }

    /**
     * Number of open forms targeted at the family that are not yet complete.
     * Mirrors the "pending" grouping used in FormsController@index: a per-student
     * form stays pending until every eligible student has responded.
     */
    private function countPendingFormsForFamily(Family $family): int
    {
        $openForms = $this->formVisibilityService->getOpenFormsForFamily($family);

        if ($openForms->isEmpty()) {
            return 0;
        }

        $responsesByFormId = FormResponse::where('family_id', $family->id)
            ->whereIn('form_id', $openForms->pluck('id'))
            ->get()
            ->groupBy('form_id');

        return $openForms->reject(fn (Form $form) => $this->formEligibleStudentsService->isFormCompletedByFamily(
            $form,
            $family,
            $responsesByFormId->get($form->id, collect()),
        ))->count();
    }
}
