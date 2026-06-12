<?php

namespace App\Http\Controllers\Familia;

use App\Actions\Consents\AcceptConsentAction;
use App\Actions\Consents\RejectConsentAction;
use App\Actions\Consents\RevokeConsentAction;
use App\Enums\ConsentResponseStatus;
use App\Http\Controllers\Controller;
use App\Models\ConsentResponse;
use App\Services\ConsentStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ConsentController extends Controller
{
    public function __construct(
        private readonly ConsentStatusService $consentStatusService,
        private readonly AcceptConsentAction $acceptAction,
        private readonly RejectConsentAction $rejectAction,
        private readonly RevokeConsentAction $revokeAction,
    ) {}

    public function index(Request $request): View
    {
        $family = $request->user()->family;

        $responses = $this->consentStatusService->getResponsesForFamily($family)
            ->load(['student']);

        $pending = $responses->filter(fn ($r) => $r->status === ConsentResponseStatus::Pending);
        $accepted = $responses->filter(fn ($r) => $r->status === ConsentResponseStatus::Accepted);
        $rejected = $responses->filter(fn ($r) => $r->status === ConsentResponseStatus::Rejected);
        $revoked = $responses->filter(fn ($r) => $r->status === ConsentResponseStatus::Revoked);

        return view('familia.consents.index', compact('pending', 'accepted', 'rejected', 'revoked'));
    }

    public function show(Request $request, ConsentResponse $response): View
    {
        $family = $request->user()->family;

        if ($response->family_id !== $family->id) {
            abort(403);
        }

        $response->load(['consentType', 'consentVersion', 'student', 'histories.performedBy']);

        return view('familia.consents.show', compact('response', 'family'));
    }

    public function aceptar(Request $request, ConsentResponse $response): RedirectResponse
    {
        $family = $request->user()->family;

        if ($response->family_id !== $family->id) {
            abort(403);
        }

        try {
            $this->acceptAction->execute(
                $response,
                $family,
                $request->user(),
                $request->ip(),
                $request->userAgent(),
            );
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()->route('familia.consents.index')
            ->with('success', 'Consentimiento aceptado correctamente.');
    }

    public function rechazar(Request $request, ConsentResponse $response): RedirectResponse
    {
        $family = $request->user()->family;

        if ($response->family_id !== $family->id) {
            abort(403);
        }

        try {
            $this->rejectAction->execute(
                $response,
                $family,
                $request->user(),
                $request->ip(),
                $request->userAgent(),
            );
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()->route('familia.consents.index')
            ->with('success', 'Consentimiento rechazado.');
    }

    public function revocar(Request $request, ConsentResponse $response): RedirectResponse
    {
        $family = $request->user()->family;

        if ($response->family_id !== $family->id) {
            abort(403);
        }

        try {
            $this->revokeAction->execute(
                $response,
                $family,
                $request->user(),
                $request->ip(),
                $request->userAgent(),
            );
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()->route('familia.consents.index')
            ->with('success', 'Consentimiento revocado. A partir de ahora no se considera autorizado.');
    }
}
