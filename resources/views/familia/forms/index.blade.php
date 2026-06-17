@extends('familia.layouts.app')

@section('title', 'Formularios')

@section('content')

<div class="family-page-header">
    <h1>Formularios</h1>
    <p>Formularios del AMPA dirigidos a tu familia.</p>
</div>

{{-- Pendientes de respuesta --}}
<div class="family-section">
    <h2 class="family-section__title">Pendientes de respuesta</h2>

    @if($pendingForms->isEmpty())
        <div class="family-empty-state">
            <div class="family-empty-state__title">No tienes formularios pendientes</div>
            <p>Cuando el AMPA publique un formulario para tu familia, aparecerá aquí.</p>
        </div>
    @else
        <div class="family-card-list fam-stack">
            @foreach($pendingForms as $form)
                <a href="{{ route('familia.forms.show', $form) }}" class="family-resource-card">
                    <div class="family-resource-card__head">
                        <span class="family-resource-card__title">{{ $form->title }}</span>
                        <span class="family-status-badge status-pending">Pendiente</span>
                    </div>
                    @if($form->description)
                        <div class="family-resource-card__desc">{{ \Illuminate\Support\Str::limit($form->description, 120) }}</div>
                    @endif
                    <div class="fam-row-wrap" style="margin-top: 10px; justify-content: space-between;">
                        <span style="font-size: .8rem;" class="fam-muted">
                            @if($form->closes_at)
                                Cierra el {{ $form->closes_at->format('d/m/Y') }} a las {{ $form->closes_at->format('H:i') }}
                            @else
                                Sin fecha de cierre
                            @endif
                        </span>
                        <span class="family-link">Responder →</span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>

{{-- Respondidos (formulario aún abierto) --}}
@if($respondedOpenForms->isNotEmpty())
    <div class="family-section">
        <h2 class="family-section__title">Respondidos</h2>
        <div class="family-card-list fam-stack">
            @foreach($respondedOpenForms as $form)
                @php $formResponses = $responsesByFormId->get($form->id, collect()) @endphp
                <div class="family-card">
                    <div class="family-card__header">
                        <div class="family-row__main">
                            <div class="family-card__title">{{ $form->title }}</div>
                            @if($form->closes_at)
                                <div class="family-card__sub">Cierra el {{ $form->closes_at->format('d/m/Y') }}</div>
                            @endif
                        </div>
                        <div class="fam-row-wrap" style="justify-content: flex-end;">
                            <span class="family-status-badge status-success">Respondido</span>
                            @if($form->allow_edit)
                                <span class="family-status-badge status-warning">Editable</span>
                            @endif
                        </div>
                    </div>
                    <div class="family-card__body fam-row-wrap" style="gap: 16px;">
                        @if($formResponses->count() === 1)
                            <a href="{{ route('familia.forms.responses.show', [$form, $formResponses->first()]) }}" class="family-link">Ver respuesta</a>
                            @if($form->allow_edit)
                                <a href="{{ route('familia.forms.edit', [$form, $formResponses->first()]) }}" class="family-link">Editar respuesta</a>
                            @endif
                        @elseif($formResponses->count() > 1)
                            <a href="{{ route('familia.forms.show', $form) }}" class="family-link">Ver respuestas</a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

{{-- Cerrados con respuesta --}}
@if($closedRespondedForms->isNotEmpty())
    <div class="family-section">
        <h2 class="family-section__title">Cerrados</h2>
        <div class="family-card-list fam-stack">
            @foreach($closedRespondedForms as $form)
                @php $formResponses = $responsesByFormId->get($form->id, collect()) @endphp
                <div class="family-card" style="opacity: .8;">
                    <div class="family-card__header">
                        <div class="family-card__title" style="font-weight: 500;">{{ $form->title }}</div>
                        <div class="fam-row-wrap" style="justify-content: flex-end;">
                            <span class="family-status-badge status-muted">Cerrado</span>
                            @if($formResponses->count() === 1)
                                <a href="{{ route('familia.forms.responses.show', [$form, $formResponses->first()]) }}" class="family-link">Ver respuesta</a>
                            @elseif($formResponses->count() > 1)
                                <a href="{{ route('familia.forms.show', $form) }}" class="family-link">Ver respuestas</a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

@endsection
