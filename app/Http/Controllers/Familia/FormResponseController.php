<?php

namespace App\Http\Controllers\Familia;

use App\Actions\Forms\SubmitFormResponseAction;
use App\Actions\Forms\UpdateFormResponseAction;
use App\Enums\FormResponseScope;
use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FormResponseController extends Controller
{
    public function __construct(
        private readonly SubmitFormResponseAction $submitAction,
        private readonly UpdateFormResponseAction $updateAction,
    ) {}

    public function store(Request $request, Form $form)
    {
        $family = $request->user()->family;

        $student = null;
        if ($form->response_scope === FormResponseScope::PerStudent) {
            $studentId = $request->input('student_id');
            $student = $studentId ? $family->students()->find($studentId) : null;
        }

        $answers = $this->extractAnswers($request, $form);

        try {
            $this->submitAction->execute($form, $family, $student, $answers);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()
            ->route('familia.forms.index')
            ->with('success', '¡Respuesta enviada correctamente!');
    }

    public function edit(Request $request, Form $form, FormResponse $response)
    {
        $family = $request->user()->family;

        if ($response->form_id !== $form->id) {
            abort(403);
        }

        if ($response->family_id !== $family->id) {
            abort(403);
        }

        if (! $form->allow_edit || ! $form->isOpenNow()) {
            abort(403);
        }

        $fields = $form->formFields()->orderBy('sort_order')->get();
        $answersMap = $response->answers->keyBy('form_field_id');

        return view('familia.forms.edit', compact('form', 'response', 'fields', 'answersMap'));
    }

    public function update(Request $request, Form $form, FormResponse $response)
    {
        $family = $request->user()->family;

        if ($response->form_id !== $form->id) {
            abort(403);
        }

        if ($response->family_id !== $family->id) {
            abort(403);
        }

        $answers = $this->extractAnswers($request, $form);

        try {
            $this->updateAction->execute($response, $family, $answers);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()
            ->route('familia.forms.index')
            ->with('success', '¡Respuesta actualizada correctamente!');
    }

    /**
     * Extracts field answers from the request, keyed by form_field_id.
     *
     * @return array<int|string, mixed>
     */
    private function extractAnswers(Request $request, Form $form): array
    {
        $rawFields = $request->input('fields', []);
        $answers = [];

        $form->loadMissing('formFields');

        foreach ($form->formFields as $field) {
            if (! $field->type->storesAnswer()) {
                continue;
            }

            $answers[$field->id] = $rawFields[$field->id] ?? null;
        }

        return $answers;
    }
}
