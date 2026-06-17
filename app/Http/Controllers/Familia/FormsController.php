<?php

namespace App\Http\Controllers\Familia;

use App\Enums\FormResponseScope;
use App\Enums\FormStatus;
use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormResponse;
use App\Services\FormEligibleStudentsService;
use App\Services\FormVisibilityService;
use Illuminate\Http\Request;

class FormsController extends Controller
{
    public function __construct(
        private readonly FormVisibilityService $visibilityService,
        private readonly FormEligibleStudentsService $eligibleStudentsService,
    ) {}

    public function index(Request $request)
    {
        $family = $request->user()->family;

        $openForms = $this->visibilityService->getOpenFormsForFamily($family);

        $respondedResponses = FormResponse::where('family_id', $family->id)
            ->get();

        // Responses grouped by form_id for "Ver respuesta" links in the index
        $responsesByFormId = $respondedResponses->groupBy('form_id');

        // A per-student form stays pending until every eligible student has
        // responded; a per-family form is complete once any response exists.
        $isComplete = fn (Form $form): bool => $this->eligibleStudentsService->isFormCompletedByFamily(
            $form,
            $family,
            $responsesByFormId->get($form->id, collect()),
        );

        $pendingForms = $openForms->reject($isComplete)->values();
        $respondedOpenForms = $openForms->filter($isComplete)->values();

        $closedRespondedForms = Form::query()
            ->whereIn('id', $respondedResponses->pluck('form_id')->unique())
            ->whereNotIn('id', $openForms->pluck('id'))
            ->get();

        // Eligible students still pending per form (per_student scope) for clear messaging.
        $pendingStudentsByFormId = $pendingForms->mapWithKeys(fn (Form $form) => [
            $form->id => $this->eligibleStudentsService->getPendingStudents(
                $form,
                $family,
                $responsesByFormId->get($form->id, collect()),
            ),
        ]);

        return view('familia.forms.index', compact(
            'pendingForms',
            'respondedOpenForms',
            'closedRespondedForms',
            'responsesByFormId',
            'pendingStudentsByFormId',
        ));
    }

    public function show(Request $request, Form $form)
    {
        $family = $request->user()->family;

        if (! $this->visibilityService->isTargetedAt($form, $family)) {
            abort(403);
        }

        if ($form->status === FormStatus::Draft || $form->status === FormStatus::Archived) {
            abort(404);
        }

        $fields = $form->formFields()->orderBy('sort_order')->get();

        $responses = FormResponse::where('form_id', $form->id)
            ->where('family_id', $family->id)
            ->with('answers')
            ->get()
            ->keyBy('response_key');

        $eligibleStudents = collect();
        if ($form->response_scope === FormResponseScope::PerStudent) {
            $eligibleStudents = $this->eligibleStudentsService->getEligibleStudents($form, $family);
        }

        return view('familia.forms.show', compact(
            'form',
            'family',
            'fields',
            'responses',
            'eligibleStudents',
        ));
    }
}
