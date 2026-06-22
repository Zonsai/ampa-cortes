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

{{-- Avisos calculados --}}
@if($pendingRequestCount > 0 || $waitlistCount > 0 || $pendingPaymentCount > 0 || $pendingConsentsCount > 0 || $pendingFormsCount > 0)
<div class="family-alerts">
    @if($pendingRequestCount > 0)
        <div class="family-alert family-alert--brand">
            <span>
                <strong>Solicitudes enviadas:</strong>
                tienes {{ $pendingRequestCount }} {{ $pendingRequestCount === 1 ? 'solicitud' : 'solicitudes' }}
                a la espera de que el AMPA las revise.
                La solicitud no reserva plaza hasta que el AMPA la confirme.
            </span>
        </div>
    @endif
    @if($pendingPaymentCount > 0)
        <div class="family-alert family-alert--info">
            <span>
                <strong>Pendiente de pago:</strong>
                tienes {{ $pendingPaymentCount }} {{ $pendingPaymentCount === 1 ? 'inscripción' : 'inscripciones' }}
                pendiente{{ $pendingPaymentCount === 1 ? '' : 's' }} de pago.
                Contacta con el AMPA para completarla.
            </span>
        </div>
    @endif
    @if($waitlistCount > 0)
        <div class="family-alert family-alert--warning">
            <span>
                <strong>Lista de espera:</strong>
                tienes {{ $waitlistCount }} {{ $waitlistCount === 1 ? 'inscripción' : 'inscripciones' }} en lista de espera.
                El AMPA te contactará si queda una plaza libre.
            </span>
        </div>
    @endif
    @if($pendingConsentsCount > 0)
        <div class="family-alert family-alert--brand">
            <span>
                <strong>Consentimientos pendientes:</strong>
                tienes {{ $pendingConsentsCount }} {{ $pendingConsentsCount === 1 ? 'consentimiento' : 'consentimientos' }}
                pendiente{{ $pendingConsentsCount === 1 ? '' : 's' }} de revisar.
                <a href="{{ route('familia.consents.index') }}">Ver consentimientos →</a>
            </span>
        </div>
    @endif
    @if($pendingFormsCount > 0)
        <div class="family-alert family-alert--violet">
            <span>
                <strong>Formularios pendientes:</strong>
                tienes {{ $pendingFormsCount }} {{ $pendingFormsCount === 1 ? 'formulario' : 'formularios' }}
                pendiente{{ $pendingFormsCount === 1 ? '' : 's' }} de responder.
                <a href="{{ route('familia.forms.index') }}">Ver formularios →</a>
            </span>
        </div>
    @endif
</div>
@else
<div class="family-alerts">
    <div class="family-alert family-alert--success">
        <span>Todo al día: no tienes solicitudes, pagos, consentimientos ni formularios pendientes.</span>
    </div>
</div>
@endif

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
<div class="family-section">
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
