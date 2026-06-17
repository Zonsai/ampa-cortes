@extends('familia.layouts.app')

@section('title', $response->consentType->name)

@section('content')

<a href="{{ route('familia.consents.index') }}" class="family-back-link">← Volver a consentimientos</a>

<div class="family-page-header">
    <h1>{{ $response->consentType->name }}</h1>
</div>

@php
    $consentStatus = $response->status;
    $statusMap = [
        \App\Enums\ConsentResponseStatus::Pending->value  => ['Pendiente', 'status-pending'],
        \App\Enums\ConsentResponseStatus::Accepted->value => ['Aceptado', 'status-success'],
        \App\Enums\ConsentResponseStatus::Rejected->value => ['Rechazado', 'status-danger'],
        \App\Enums\ConsentResponseStatus::Revoked->value  => ['Revocado', 'status-muted'],
    ];
    [$statusLabel, $statusClass] = $statusMap[$consentStatus->value] ?? ['—', 'status-muted'];
@endphp

{{-- Aviso de revocación --}}
@if($consentStatus === \App\Enums\ConsentResponseStatus::Revoked)
    <div class="family-alert family-alert--warning" style="margin-bottom: 18px;">
        <span>Has revocado este consentimiento. A partir de ese momento no se considera autorizado.</span>
    </div>
@endif

{{-- Resumen y estado --}}
<div class="family-card" style="margin-bottom: 18px;">
    <div class="family-card__header">
        <div class="family-row__main">
            <div class="family-card__sub">Estado actual</div>
            <div class="fam-row-wrap" style="margin-top: 6px;">
                <span class="family-status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                @if($response->consentType->requires_image_review)
                    <span class="family-status-badge status-warning">Requiere revisión de imagen</span>
                @endif
                @if($response->responded_at)
                    <span class="fam-muted" style="font-size: .8rem;">{{ $response->responded_at->format('d/m/Y H:i') }}</span>
                @endif
            </div>
        </div>
    </div>
    <div class="family-card__body">
        <div class="family-meta-row">
            <div class="family-meta-row__term">Finalidad</div>
            <div class="family-meta-row__value">{{ $response->consentType->purpose }}</div>
        </div>
        <div class="family-meta-row">
            <div class="family-meta-row__term">Ámbito</div>
            <div class="family-meta-row__value">{{ $response->consentType->scope->getLabel() }}</div>
        </div>
        @if($response->student)
        <div class="family-meta-row">
            <div class="family-meta-row__term">Alumno/a</div>
            <div class="family-meta-row__value">{{ $response->student->full_name }}</div>
        </div>
        @endif
    </div>
</div>

{{-- Resumen --}}
@if($response->consentVersion?->summary)
<div class="family-card" style="margin-bottom: 18px;">
    <div class="family-card__header"><div class="family-card__title">Resumen</div></div>
    <div class="family-card__body" style="font-size: .9rem; color: #374151;">{{ $response->consentVersion->summary }}</div>
</div>
@endif

{{-- Texto legal completo --}}
@if($response->consentVersion?->legal_text)
<div class="family-card" style="margin-bottom: 18px;">
    <div class="family-card__header"><div class="family-card__title">Texto completo</div></div>
    <div class="family-card__body">
        <div class="family-legal">{{ $response->consentVersion->legal_text }}</div>
    </div>
</div>
@endif

{{-- Historial --}}
@if($response->histories->isNotEmpty())
<div class="family-card" style="margin-bottom: 18px;">
    <div class="family-card__header"><div class="family-card__title">Historial</div></div>
    <div class="family-card__body">
        <ul class="family-history">
            @foreach($response->histories as $history)
                <li>
                    <time>{{ $history->created_at->format('d/m/Y H:i') }}</time>
                    — {{ $history->event_type->getLabel() }}
                    @if($history->performedBy)
                        <span class="fam-muted">({{ $history->performedBy->name }})</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</div>
@endif

{{-- Acciones --}}
@if($consentStatus === \App\Enums\ConsentResponseStatus::Pending)
    <div class="family-actions">
        <form method="POST" action="{{ route('familia.consents.accept', $response) }}">
            @csrf
            <button type="submit" class="family-btn family-btn--primary">Aceptar</button>
        </form>
        @if($response->consentType->is_rejectable)
            <form method="POST" action="{{ route('familia.consents.reject', $response) }}">
                @csrf
                <button type="submit" class="family-btn family-btn--danger">Rechazar</button>
            </form>
        @endif
    </div>
@endif

@if($consentStatus === \App\Enums\ConsentResponseStatus::Accepted && $response->consentType->is_revocable)
    <div class="family-actions">
        <form method="POST" action="{{ route('familia.consents.revoke', $response) }}">
            @csrf
            <button type="submit" class="family-btn family-btn--muted">Revocar consentimiento</button>
        </form>
    </div>
@endif

@if($consentStatus === \App\Enums\ConsentResponseStatus::Revoked)
    <div class="family-actions">
        <form method="POST" action="{{ route('familia.consents.accept', $response) }}">
            @csrf
            <button type="submit" class="family-btn family-btn--primary">Volver a aceptar</button>
        </form>
    </div>
@endif

@endsection
