@extends('familia.layouts.app')

@section('title', 'Consentimientos')

@section('content')

<div class="family-page-header">
    <h1>Consentimientos</h1>
    <p>Autorizaciones de tu familia. Revisa primero los pendientes.</p>
</div>

{{-- Pendientes --}}
<div class="family-section">
    <h2 class="family-section__title">Pendientes</h2>

    @if($pending->isEmpty())
        <div class="family-empty-state">
            <div class="family-empty-state__title">No tienes consentimientos pendientes.</div>
            <p>Cuando el AMPA solicite una nueva autorización, aparecerá aquí.</p>
        </div>
    @else
        <div class="family-card-list fam-stack">
            @foreach($pending as $response)
                <div class="family-card" style="border-left: 3px solid var(--brand-primary);">
                    <div class="family-card__body family-row" style="padding: 16px;">
                        <div class="family-row__main">
                            <div class="family-card__title">{{ $response->consentType->name }}</div>
                            @if($response->student)
                                <div class="family-card__sub">Alumno/a: {{ $response->student->full_name }}</div>
                            @endif
                            <div class="family-card__sub">{{ $response->consentType->purpose }}</div>
                            <div class="fam-row-wrap fam-mt-1">
                                <span class="family-status-badge status-pending">Pendiente</span>
                                @if($response->consentType->requires_image_review)
                                    <span class="family-status-badge status-warning">Requiere revisión de imagen</span>
                                @endif
                            </div>
                        </div>
                        <a href="{{ route('familia.consents.show', $response) }}" class="family-btn family-btn--primary family-btn--sm">Revisar consentimiento →</a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- Aceptados --}}
@if($accepted->isNotEmpty())
<div class="family-section">
    <h2 class="family-section__title">Aceptados</h2>
    <div class="family-card-list fam-stack">
        @foreach($accepted as $response)
            <div class="family-card">
                <div class="family-card__body family-row" style="padding: 16px;">
                    <div class="family-row__main">
                        <div class="family-card__title">{{ $response->consentType->name }}</div>
                        @if($response->student)
                            <div class="family-card__sub">Alumno/a: {{ $response->student->full_name }}</div>
                        @endif
                        <div class="family-card__sub">{{ $response->consentType->purpose }}</div>
                        <div class="fam-row-wrap fam-mt-1">
                            <span class="family-status-badge status-success">Aceptado</span>
                            @if($response->consentType->requires_image_review)
                                <span class="family-status-badge status-warning">Requiere revisión de imagen</span>
                            @endif
                            @if($response->responded_at)
                                <span class="fam-muted" style="font-size: .76rem;">{{ $response->responded_at->format('d/m/Y') }}</span>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('familia.consents.show', $response) }}" class="family-link">Ver detalle →</a>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif

{{-- Rechazados --}}
@if($rejected->isNotEmpty())
<div class="family-section">
    <h2 class="family-section__title">Rechazados</h2>
    <div class="family-card-list fam-stack">
        @foreach($rejected as $response)
            <div class="family-card">
                <div class="family-card__body family-row" style="padding: 16px;">
                    <div class="family-row__main">
                        <div class="family-card__title">{{ $response->consentType->name }}</div>
                        @if($response->student)
                            <div class="family-card__sub">Alumno/a: {{ $response->student->full_name }}</div>
                        @endif
                        <div class="family-card__sub">{{ $response->consentType->purpose }}</div>
                        <div class="fam-row-wrap fam-mt-1">
                            <span class="family-status-badge status-danger">Rechazado</span>
                            @if($response->responded_at)
                                <span class="fam-muted" style="font-size: .76rem;">{{ $response->responded_at->format('d/m/Y') }}</span>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('familia.consents.show', $response) }}" class="family-link">Ver detalle →</a>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif

{{-- Revocados --}}
@if($revoked->isNotEmpty())
<div class="family-section">
    <h2 class="family-section__title">Revocados</h2>
    <div class="family-card-list fam-stack">
        @foreach($revoked as $response)
            <div class="family-card">
                <div class="family-card__body family-row" style="padding: 16px;">
                    <div class="family-row__main">
                        <div class="family-card__title">{{ $response->consentType->name }}</div>
                        @if($response->student)
                            <div class="family-card__sub">Alumno/a: {{ $response->student->full_name }}</div>
                        @endif
                        <div class="family-card__sub">{{ $response->consentType->purpose }}</div>
                        <div class="fam-row-wrap fam-mt-1">
                            <span class="family-status-badge status-muted">Revocado</span>
                            @if($response->revoked_at)
                                <span class="fam-muted" style="font-size: .76rem;">{{ $response->revoked_at->format('d/m/Y') }}</span>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('familia.consents.show', $response) }}" class="family-link">Revisar y volver a aceptar →</a>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif

@endsection
