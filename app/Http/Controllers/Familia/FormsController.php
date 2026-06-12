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

        $respondedFormIds = FormResponse::where('family_id', $family->id)
            ->pluck('form_id')
            ->unique();

        $pendingForms = $openForms->filter(fn ($f) => ! $respondedFormIds->contains($f->id));
        $respondedOpenForms = $openForms->filter(fn ($f) => $respondedFormIds->contains($f->id));

        $closedRespondedForms = Form::query()
            ->whereIn('id', $respondedFormIds)
            ->whereNotIn('id', $openForms->pluck('id'))
            ->get();

        return view('familia.forms.index', compact(
            'pendingForms',
            'respondedOpenForms',
            'closedRespondedForms',
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
