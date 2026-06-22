@extends('familia.layouts.app')

@section('title', 'Inicio')

@section('content')

<div class="family-page-header">
    <h1>Hola, familia {{ $family->name }}</h1>
    <p class="fam-row-wrap">
        <span>Bienvenidos al portal de familias del AMPA.</span>
        @if($family->is_ampa_member)
            <span class="family-chip family-chip--member">Socios AMPA</span>
        @else
            <span class="family-chip">No socios</span>
        @endif
    </p>
</div>

{{-- Pendiente de ti --}}
@php
    $hasPending = $pendingConsentsCount > 0 || $pendingFormsCount > 0 || $pendingPaymentCount > 0 || $pendingRequestCount > 0 || $waitlistCount > 0;
@endphp
<div class="family-section">
    <h2 class="family-section__title">Pendiente de ti</h2>

    @if($hasPending)
        <div class="family-pending-list">
            @if($pendingConsentsCount > 0)
                <div class="family-pending-item">
                    <span class="family-pending-item__icon" aria-hidden="true">
                        <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 2.5l6 2.2v4.3c0 4-2.6 6.7-6 8-3.4-1.3-6-4-6-8V4.7l6-2.2z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M7.5 10l1.8 1.8L12.8 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <div class="family-pending-item__body">
                        <div class="family-pending-item__title">
                            Consentimientos pendientes
                            <span class="family-chip family-chip--count">{{ $pendingConsentsCount }}</span>
                        </div>
                        <div class="family-pending-item__desc">
                            Tienes {{ $pendingConsentsCount }} {{ $pendingConsentsCount === 1 ? 'consentimiento' : 'consentimientos' }} pendiente{{ $pendingConsentsCount === 1 ? '' : 's' }} de revisar.
                        </div>
                    </div>
                    <div class="family-pending-item__cta">
                        <a href="{{ route('familia.consents.index') }}" class="family-btn family-btn--ghost family-btn--sm">Ver consentimientos</a>
                    </div>
                </div>
            @endif

            @if($pendingFormsCount > 0)
                <div class="family-pending-item">
                    <span class="family-pending-item__icon" aria-hidden="true">
                        <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 2.5h7l3 3v12h-10v-15z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M7.5 9h5M7.5 12h5M7.5 15h3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                    </span>
                    <div class="family-pending-item__body">
                        <div class="family-pending-item__title">
                            Formularios pendientes
                            <span class="family-chip family-chip--count">{{ $pendingFormsCount }}</span>
                        </div>
                        <div class="family-pending-item__desc">
                            Queda{{ $pendingFormsCount === 1 ? '' : 'n' }} {{ $pendingFormsCount }} {{ $pendingFormsCount === 1 ? 'formulario' : 'formularios' }} por responder.
                        </div>
                    </div>
                    <div class="family-pending-item__cta">
                        <a href="{{ route('familia.forms.index') }}" class="family-btn family-btn--ghost family-btn--sm">Ver formularios</a>
                    </div>
                </div>
            @endif

            @if($pendingPaymentCount > 0)
                <div class="family-pending-item">
                    <span class="family-pending-item__icon" aria-hidden="true">
                        <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="2.5" y="5" width="15" height="10" rx="1.5" stroke="currentColor" stroke-width="1.5"/><path d="M2.5 8.5h15" stroke="currentColor" stroke-width="1.5"/></svg>
                    </span>
                    <div class="family-pending-item__body">
                        <div class="family-pending-item__title">
                            Pendiente de pago
                            <span class="family-chip family-chip--count">{{ $pendingPaymentCount }}</span>
                        </div>
                        <div class="family-pending-item__desc">
                            Tienes {{ $pendingPaymentCount }} {{ $pendingPaymentCount === 1 ? 'inscripción' : 'inscripciones' }} pendiente{{ $pendingPaymentCount === 1 ? '' : 's' }} de pago. Contacta con el AMPA para completarla.
                        </div>
                    </div>
                    <div class="family-pending-item__cta">
                        <a href="#family-enrollments" class="family-btn family-btn--ghost family-btn--sm">Ver inscripciones</a>
                    </div>
                </div>
            @endif

            @if($pendingRequestCount > 0)
                <div class="family-pending-item">
                    <span class="family-pending-item__icon" aria-hidden="true">
                        <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="10" cy="10.5" r="7" stroke="currentColor" stroke-width="1.5"/><path d="M10 6.5v4l3 2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <div class="family-pending-item__body">
                        <div class="family-pending-item__title">
                            Solicitudes enviadas
                            <span class="family-chip family-chip--count">{{ $pendingRequestCount }}</span>
                        </div>
                        <div class="family-pending-item__desc">
                            {{ $pendingRequestCount === 1 ? 'Tu solicitud está' : "Tus {$pendingRequestCount} solicitudes están" }} pendiente{{ $pendingRequestCount === 1 ? '' : 's' }} de confirmación por el AMPA. La solicitud no reserva plaza hasta que se confirme.
                        </div>
                    </div>
                </div>
            @endif

            @if($waitlistCount > 0)
                <div class="family-pending-item">
                    <span class="family-pending-item__icon" aria-hidden="true">
                        <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="7" cy="7" r="2.3" stroke="currentColor" stroke-width="1.5"/><circle cx="14" cy="8" r="1.8" stroke="currentColor" stroke-width="1.5"/><path d="M2.8 16c0-2.5 1.9-4.2 4.2-4.2s4.2 1.7 4.2 4.2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M12 12.4c1.8 0 3.4 1.4 3.6 3.6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                    </span>
                    <div class="family-pending-item__body">
                        <div class="family-pending-item__title">
                            Lista de espera
                            <span class="family-chip family-chip--count">{{ $waitlistCount }}</span>
                        </div>
                        <div class="family-pending-item__desc">
                            Tienes {{ $waitlistCount }} {{ $waitlistCount === 1 ? 'inscripción' : 'inscripciones' }} en lista de espera. El AMPA te contactará si queda una plaza libre.
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @else
        <div class="family-empty-state">
            <div class="family-empty-state__title">Todo al día</div>
            <p>No tienes gestiones pendientes.</p>
        </div>
    @endif
</div>

{{-- Resumen en tarjetas --}}
<div class="family-stats-grid">
    <div class="family-stat-card family-stat-card--brand">
        <div class="family-stat-card__value">{{ $students->count() }}</div>
        <div class="family-stat-card__label">{{ $students->count() === 1 ? 'Hijo/a' : 'Hijos/as' }}</div>
    </div>
    <div class="family-stat-card {{ $pendingRequestCount > 0 ? 'family-stat-card--brand' : 'family-stat-card--muted' }}">
        <div class="family-stat-card__value">{{ $pendingRequestCount }}</div>
        <div class="family-stat-card__label">{{ $pendingRequestCount === 1 ? 'Solicitud pendiente' : 'Solicitudes pendientes' }}</div>
    </div>
    <div class="family-stat-card {{ $enrolledCount > 0 ? 'family-stat-card--success' : 'family-stat-card--muted' }}">
        <div class="family-stat-card__value">{{ $enrolledCount }}</div>
        <div class="family-stat-card__label">{{ $enrolledCount === 1 ? 'Inscrito/a' : 'Inscritos/as' }}</div>
    </div>
    <div class="family-stat-card {{ $waitlistCount > 0 ? 'family-stat-card--warning' : 'family-stat-card--muted' }}">
        <div class="family-stat-card__value">{{ $waitlistCount }}</div>
        <div class="family-stat-card__label">En lista de espera</div>
    </div>
</div>

{{-- Inscripciones activas --}}
<div class="family-section" id="family-enrollments">
    <h2 class="family-section__title">Inscripciones y solicitudes</h2>
    @if($activeEnrollments->count() > 0)
        <div class="family-card">
            <div class="family-table-wrap">
                <table class="family-table">
                    <thead>
                        <tr>
                            <th>Actividad</th>
                            <th>Alumno/a</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($activeEnrollments as $enrollment)
                        <tr>
                            <td>
                                <div class="cell-title">{{ $enrollment->activity->name }}</div>
                                <div class="cell-sub">{{ $enrollment->activityGroup->name }}</div>
                            </td>
                            <td style="white-space: nowrap;">{{ $enrollment->student->full_name ?? '—' }}</td>
                            <td>
                                @include('familia.partials.status-badge', ['status' => $enrollment->status])
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="family-empty-state">
            <div class="family-empty-state__title">Todavía no tienes inscripciones</div>
            <p>Cuando solicites una actividad extraescolar, aparecerá aquí con su estado.</p>
            <a href="{{ route('familia.activities.index') }}" class="family-btn family-btn--primary">Ver extraescolares →</a>
        </div>
    @endif
</div>

{{-- Anuncios del AMPA --}}
@if($familyAnnouncements->isNotEmpty())
<div class="family-section">
    <h2 class="family-section__title">Anuncios del AMPA</h2>
    <div class="family-card-list fam-stack">
        @foreach($familyAnnouncements as $announcement)
            <div class="family-card">
                <div class="family-card__body">
                    <div class="fam-row-wrap" style="justify-content: space-between; gap: 8px;">
                        <span class="family-card__title">{{ $announcement->title }}</span>
                        @if($announcement->published_at)
                            <span class="fam-muted" style="font-size: .78rem;">{{ $announcement->published_at->format('d/m/Y') }}</span>
                        @endif
                    </div>
                    @if($announcement->summary)
                        <div class="family-card__sub" style="margin-top: 4px;">{{ \Illuminate\Support\Str::limit($announcement->summary, 160) }}</div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif

{{-- Accesos rápidos --}}
<div class="family-section">
    <h2 class="family-section__title">Accesos rápidos</h2>
    <div class="family-action-grid">
        <a href="{{ route('familia.children') }}" class="family-resource-card">
            <div class="family-resource-card__head">
                <span class="family-resource-card__title">Mis hijos/as →</span>
            </div>
            <div class="family-resource-card__desc">Ver alumnos, clases e inscripciones por alumno</div>
        </a>
        <a href="{{ route('familia.activities.index') }}" class="family-resource-card">
            <div class="family-resource-card__head">
                <span class="family-resource-card__title">Extraescolares →</span>
            </div>
            <div class="family-resource-card__desc">Ver actividades disponibles y solicitar inscripción</div>
        </a>
        <a href="{{ route('familia.forms.index') }}" class="family-resource-card">
            <div class="family-resource-card__head">
                <span class="family-resource-card__title">Formularios →</span>
                @if($pendingFormsCount > 0)
                    <span class="family-chip family-chip--count">{{ $pendingFormsCount }} pendiente{{ $pendingFormsCount === 1 ? '' : 's' }}</span>
                @endif
            </div>
            <div class="family-resource-card__desc">Consultar y responder formularios del AMPA</div>
        </a>
        <a href="{{ route('familia.consents.index') }}" class="family-resource-card">
            <div class="family-resource-card__head">
                <span class="family-resource-card__title">Consentimientos →</span>
                @if($pendingConsentsCount > 0)
                    <span class="family-chip family-chip--count">{{ $pendingConsentsCount }} pendiente{{ $pendingConsentsCount === 1 ? '' : 's' }}</span>
                @endif
            </div>
            <div class="family-resource-card__desc">Revisar y gestionar los consentimientos de tu familia</div>
        </a>
    </div>
</div>

@endsection
