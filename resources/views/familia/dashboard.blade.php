@extends('familia.layouts.app')

@section('title', 'Inicio')

@section('content')
@php
    $hasPending = $pendingConsentsCount > 0 || $pendingFormsCount > 0 || $pendingPaymentCount > 0 || $pendingRequestCount > 0 || $waitlistCount > 0;
@endphp

<div class="family-dash">

{{-- Encabezado --}}
<header class="family-dash-head">
    <h1>Hola, familia {{ $family->name }}</h1>
    <p class="family-dash-head__meta">
        @if($family->is_ampa_member)
            <span class="family-chip family-chip--member">
                <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M4.5 10.5l3.5 3.5 7.5-8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Socios AMPA
            </span>
        @else
            <span class="family-chip">No socios</span>
        @endif
        <span>Bienvenidos al portal de familias del AMPA.</span>
    </p>
</header>

{{-- Pendiente de ti --}}
<section class="family-dash-section" aria-labelledby="dash-pending-title">
    <h2 id="dash-pending-title" class="family-dash-title family-dash-title--primary">Pendiente de ti</h2>

    @if($hasPending)
        <ul class="family-dash-panel family-pending-list">
            @if($pendingConsentsCount > 0)
                <li class="family-pending-item">
                    <span class="family-pending-item__icon" aria-hidden="true">
                        <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 2.5l6 2.2v4.3c0 4-2.6 6.7-6 8-3.4-1.3-6-4-6-8V4.7l6-2.2z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M7.5 10l1.8 1.8L12.8 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <div class="family-pending-item__body">
                        <div class="family-pending-item__head">
                            <h3 class="family-pending-item__title">Consentimientos pendientes</h3>
                            <span class="family-pending-item__count">{{ $pendingConsentsCount }}</span>
                        </div>
                        <p class="family-pending-item__desc">
                            Tienes {{ $pendingConsentsCount }} {{ $pendingConsentsCount === 1 ? 'consentimiento' : 'consentimientos' }} pendiente{{ $pendingConsentsCount === 1 ? '' : 's' }} de revisar.
                        </p>
                    </div>
                    <div class="family-pending-item__side">
                        <a href="{{ route('familia.consents.index') }}" class="family-btn family-btn--ghost family-btn--sm family-pending-item__action">
                            Ver consentimientos
                            <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M8 5l5 5-5 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </a>
                    </div>
                </li>
            @endif

            @if($pendingFormsCount > 0)
                <li class="family-pending-item">
                    <span class="family-pending-item__icon" aria-hidden="true">
                        <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 2.5h7l3 3v12h-10v-15z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M7.5 9h5M7.5 12h5M7.5 15h3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                    </span>
                    <div class="family-pending-item__body">
                        <div class="family-pending-item__head">
                            <h3 class="family-pending-item__title">Formularios pendientes</h3>
                            <span class="family-pending-item__count">{{ $pendingFormsCount }}</span>
                        </div>
                        <p class="family-pending-item__desc">
                            Queda{{ $pendingFormsCount === 1 ? '' : 'n' }} {{ $pendingFormsCount }} {{ $pendingFormsCount === 1 ? 'formulario' : 'formularios' }} por responder.
                        </p>
                    </div>
                    <div class="family-pending-item__side">
                        <a href="{{ route('familia.forms.index') }}" class="family-btn family-btn--ghost family-btn--sm family-pending-item__action">
                            Ver formularios
                            <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M8 5l5 5-5 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </a>
                    </div>
                </li>
            @endif

            @if($pendingPaymentCount > 0)
                <li class="family-pending-item family-pending-item--warning">
                    <span class="family-pending-item__icon" aria-hidden="true">
                        <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="2.5" y="5" width="15" height="10" rx="1.5" stroke="currentColor" stroke-width="1.5"/><path d="M2.5 8.5h15" stroke="currentColor" stroke-width="1.5"/></svg>
                    </span>
                    <div class="family-pending-item__body">
                        <div class="family-pending-item__head">
                            <h3 class="family-pending-item__title">Pendiente de pago</h3>
                            <span class="family-pending-item__count">{{ $pendingPaymentCount }}</span>
                        </div>
                        <p class="family-pending-item__desc">
                            Tienes {{ $pendingPaymentCount }} {{ $pendingPaymentCount === 1 ? 'inscripción' : 'inscripciones' }} pendiente{{ $pendingPaymentCount === 1 ? '' : 's' }} de pago. Contacta con el AMPA para completarla.
                        </p>
                    </div>
                    <div class="family-pending-item__side">
                        <a href="#family-enrollments" class="family-btn family-btn--ghost family-btn--sm family-pending-item__action">
                            Ver inscripciones
                            <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M8 5l5 5-5 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </a>
                    </div>
                </li>
            @endif

            @if($pendingRequestCount > 0)
                <li class="family-pending-item">
                    <span class="family-pending-item__icon" aria-hidden="true">
                        <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="10" cy="10.5" r="7" stroke="currentColor" stroke-width="1.5"/><path d="M10 6.5v4l3 2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <div class="family-pending-item__body">
                        <div class="family-pending-item__head">
                            <h3 class="family-pending-item__title">Solicitudes enviadas</h3>
                            <span class="family-pending-item__count">{{ $pendingRequestCount }}</span>
                        </div>
                        <p class="family-pending-item__desc">
                            {{ $pendingRequestCount === 1 ? 'Tu solicitud está' : "Tus {$pendingRequestCount} solicitudes están" }} pendiente{{ $pendingRequestCount === 1 ? '' : 's' }} de confirmación por el AMPA. La solicitud no reserva plaza hasta que se confirme.
                        </p>
                    </div>
                    <div class="family-pending-item__side family-pending-item__side--state">
                        <span class="family-pending-item__state">En espera del AMPA</span>
                    </div>
                </li>
            @endif

            @if($waitlistCount > 0)
                <li class="family-pending-item family-pending-item--warning">
                    <span class="family-pending-item__icon" aria-hidden="true">
                        <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="7" cy="7" r="2.3" stroke="currentColor" stroke-width="1.5"/><circle cx="14" cy="8" r="1.8" stroke="currentColor" stroke-width="1.5"/><path d="M2.8 16c0-2.5 1.9-4.2 4.2-4.2s4.2 1.7 4.2 4.2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M12 12.4c1.8 0 3.4 1.4 3.6 3.6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                    </span>
                    <div class="family-pending-item__body">
                        <div class="family-pending-item__head">
                            <h3 class="family-pending-item__title">Lista de espera</h3>
                            <span class="family-pending-item__count">{{ $waitlistCount }}</span>
                        </div>
                        <p class="family-pending-item__desc">
                            Tienes {{ $waitlistCount }} {{ $waitlistCount === 1 ? 'inscripción' : 'inscripciones' }} en lista de espera. El AMPA te contactará si queda una plaza libre.
                        </p>
                    </div>
                    <div class="family-pending-item__side family-pending-item__side--state">
                        <span class="family-pending-item__state">En espera del AMPA</span>
                    </div>
                </li>
            @endif
        </ul>
    @else
        <div class="family-dash-clear">
            <span class="family-dash-clear__icon" aria-hidden="true">
                <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="10" cy="10" r="7" stroke="currentColor" stroke-width="1.5"/><path d="M6.8 10.2l2.2 2.2 4.2-4.6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <div>
                <h3>Todo al día</h3>
                <p>No tienes gestiones pendientes.</p>
            </div>
        </div>
    @endif
</section>

{{-- Resumen --}}
<section class="family-dash-section" aria-labelledby="dash-summary-title">
    <h2 id="dash-summary-title" class="family-dash-title">Resumen</h2>
    <ul class="family-summary">
        <li class="family-summary__item {{ $students->count() > 0 ? 'family-summary__item--brand' : 'family-summary__item--zero' }}">
            <span class="family-summary__icon" aria-hidden="true">
                <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="10" cy="7" r="3" stroke="currentColor" stroke-width="1.5"/><path d="M4 16.5c0-3.1 2.7-5 6-5s6 1.9 6 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
            </span>
            <span class="family-summary__num">{{ $students->count() }}</span>
            <span class="family-summary__label">{{ $students->count() === 1 ? 'Hijo/a' : 'Hijos/as' }}</span>
        </li>
        <li class="family-summary__item {{ $pendingRequestCount > 0 ? 'family-summary__item--brand' : 'family-summary__item--zero' }}">
            <span class="family-summary__icon" aria-hidden="true">
                <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="10" cy="10.5" r="7" stroke="currentColor" stroke-width="1.5"/><path d="M10 6.5v4l3 2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <span class="family-summary__num">{{ $pendingRequestCount }}</span>
            <span class="family-summary__label">{{ $pendingRequestCount === 1 ? 'Solicitud pendiente' : 'Solicitudes pendientes' }}</span>
        </li>
        <li class="family-summary__item {{ $enrolledCount > 0 ? 'family-summary__item--success' : 'family-summary__item--zero' }}">
            <span class="family-summary__icon" aria-hidden="true">
                <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="10" cy="10" r="7" stroke="currentColor" stroke-width="1.5"/><path d="M6.8 10.2l2.2 2.2 4.2-4.6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <span class="family-summary__num">{{ $enrolledCount }}</span>
            <span class="family-summary__label">{{ $enrolledCount === 1 ? 'Inscrito/a' : 'Inscritos/as' }}</span>
        </li>
        <li class="family-summary__item {{ $waitlistCount > 0 ? 'family-summary__item--warning' : 'family-summary__item--zero' }}">
            <span class="family-summary__icon" aria-hidden="true">
                <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="7" cy="7" r="2.3" stroke="currentColor" stroke-width="1.5"/><circle cx="14" cy="8" r="1.8" stroke="currentColor" stroke-width="1.5"/><path d="M2.8 16c0-2.5 1.9-4.2 4.2-4.2s4.2 1.7 4.2 4.2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M12 12.4c1.8 0 3.4 1.4 3.6 3.6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
            </span>
            <span class="family-summary__num">{{ $waitlistCount }}</span>
            <span class="family-summary__label">En lista de espera</span>
        </li>
    </ul>
</section>

{{-- Inscripciones activas --}}
<section class="family-dash-section" id="family-enrollments" aria-labelledby="dash-enrollments-title">
    <h2 id="dash-enrollments-title" class="family-dash-title">Inscripciones y solicitudes</h2>
    @if($activeEnrollments->count() > 0)
        <div class="family-dash-panel">
            <div class="family-dash-enroll__head" aria-hidden="true">
                <span>Actividad</span>
                <span>Alumno/a</span>
                <span>Estado</span>
            </div>
            <ul class="family-dash-enroll__list">
                @foreach($activeEnrollments as $enrollment)
                    <li class="family-dash-enroll__row">
                        <div class="family-dash-enroll__act">
                            <span class="family-dash-enroll__title">{{ $enrollment->activity->name }}</span>
                            <span class="family-dash-enroll__sub">{{ $enrollment->activityGroup->name }}</span>
                        </div>
                        <div class="family-dash-enroll__stu">
                            <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><circle cx="10" cy="7" r="3" stroke="currentColor" stroke-width="1.5"/><path d="M4 16.5c0-3.1 2.7-5 6-5s6 1.9 6 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                            <span><span class="fam-sr-only">Alumno/a: </span>{{ $enrollment->student->full_name ?? '—' }}</span>
                        </div>
                        <div class="family-dash-enroll__status">
                            @include('familia.partials.status-badge', ['status' => $enrollment->status])
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @else
        <div class="family-empty-state">
            <div class="family-empty-state__title">Todavía no tienes inscripciones</div>
            <p>Cuando solicites una actividad extraescolar, aparecerá aquí con su estado.</p>
            <a href="{{ route('familia.activities.index') }}" class="family-btn family-btn--primary">Ver extraescolares →</a>
        </div>
    @endif
</section>

{{-- Anuncios del AMPA --}}
@if($familyAnnouncements->isNotEmpty())
<section class="family-dash-section" aria-labelledby="dash-news-title">
    <h2 id="dash-news-title" class="family-dash-title">Anuncios del AMPA</h2>
    <ul class="family-dash-panel">
        @foreach($familyAnnouncements as $announcement)
            <li class="family-dash-news__item">
                <div class="family-dash-news__head">
                    <h3 class="family-dash-news__title">{{ $announcement->title }}</h3>
                    <span class="family-dash-news__meta">
                        @if($announcement->is_pinned)
                            <span class="family-dash-news__pin">
                                <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M10 2.5v3M6 12.5h8l-1.3-4.2a2 2 0 0 0-1.9-1.4h-1.6a2 2 0 0 0-1.9 1.4L6 12.5z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M10 12.5v5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                                Destacado
                            </span>
                        @endif
                        @if($announcement->published_at)
                            <time datetime="{{ $announcement->published_at->toDateString() }}">{{ $announcement->published_at->format('d/m/Y') }}</time>
                        @endif
                    </span>
                </div>
                @if($announcement->summary)
                    <p class="family-dash-news__summary">{{ \Illuminate\Support\Str::limit($announcement->summary, 160) }}</p>
                @endif
            </li>
        @endforeach
    </ul>
</section>
@endif

{{-- Accesos rápidos --}}
<section class="family-dash-section" aria-labelledby="dash-quick-title">
    <h2 id="dash-quick-title" class="family-dash-title">Accesos rápidos</h2>
    <ul class="family-quick">
        <li>
            <a href="{{ route('familia.children') }}" class="family-quick__link">
                <span class="family-quick__icon" aria-hidden="true">
                    <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="10" cy="7" r="3" stroke="currentColor" stroke-width="1.5"/><path d="M4 16.5c0-3.1 2.7-5 6-5s6 1.9 6 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </span>
                <span>
                    <span class="family-quick__title">Mis hijos/as</span>
                    <span class="family-quick__desc">Ver alumnos, clases e inscripciones por alumno</span>
                </span>
                <span class="family-quick__side" aria-hidden="true">
                    <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8 5l5 5-5 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
            </a>
        </li>
        <li>
            <a href="{{ route('familia.activities.index') }}" class="family-quick__link">
                <span class="family-quick__icon" aria-hidden="true">
                    <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7.5 11.2L5.5 17l4.5-2 4.5 2-2-5.8" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><circle cx="10" cy="7.5" r="4.5" stroke="currentColor" stroke-width="1.5"/><path d="M10 5.5v4M8 7.5h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </span>
                <span>
                    <span class="family-quick__title">Extraescolares</span>
                    <span class="family-quick__desc">Ver actividades disponibles y solicitar inscripción</span>
                </span>
                <span class="family-quick__side" aria-hidden="true">
                    <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8 5l5 5-5 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
            </a>
        </li>
        <li>
            <a href="{{ route('familia.forms.index') }}" class="family-quick__link">
                <span class="family-quick__icon" aria-hidden="true">
                    <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 2.5h7l3 3v12h-10v-15z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M7.5 9h5M7.5 12h5M7.5 15h3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </span>
                <span>
                    <span class="family-quick__title">Formularios</span>
                    <span class="family-quick__desc">Consultar y responder formularios del AMPA</span>
                </span>
                <span class="family-quick__side">
                    @if($pendingFormsCount > 0)
                        <span class="family-chip family-chip--count">{{ $pendingFormsCount }} pendiente{{ $pendingFormsCount === 1 ? '' : 's' }}</span>
                    @endif
                    <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M8 5l5 5-5 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
            </a>
        </li>
        <li>
            <a href="{{ route('familia.consents.index') }}" class="family-quick__link">
                <span class="family-quick__icon" aria-hidden="true">
                    <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 2.5l6 2.2v4.3c0 4-2.6 6.7-6 8-3.4-1.3-6-4-6-8V4.7l6-2.2z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M7.5 10l1.8 1.8L12.8 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <span>
                    <span class="family-quick__title">Consentimientos</span>
                    <span class="family-quick__desc">Revisar y gestionar los consentimientos de tu familia</span>
                </span>
                <span class="family-quick__side">
                    @if($pendingConsentsCount > 0)
                        <span class="family-chip family-chip--count">{{ $pendingConsentsCount }} pendiente{{ $pendingConsentsCount === 1 ? '' : 's' }}</span>
                    @endif
                    <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M8 5l5 5-5 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
            </a>
        </li>
    </ul>
</section>

</div>
@endsection
