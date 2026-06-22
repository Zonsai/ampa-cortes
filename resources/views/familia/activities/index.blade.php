@extends('familia.layouts.app')

@section('title', 'Extraescolares')

@section('content')

<div class="family-page-header">
    <h1>Actividades extraescolares</h1>
    @if($activeYear)
        <p>Curso {{ $activeYear->name }}</p>
    @endif
</div>

@if(! $activeYear)
    <div class="family-alert family-alert--warning">
        <span>
            <strong>No hay curso académico activo.</strong>
            El AMPA aún no ha configurado el curso activo. Vuelve a consultar más adelante o contacta con el AMPA.
        </span>
    </div>
@else
    <div class="family-alert family-alert--brand" style="margin-bottom: 20px;">
        <span>
            Al apuntar a un hijo/a envías una <strong>solicitud</strong>:
            no reserva plaza hasta que el AMPA la confirme y actualice el estado.
        </span>
    </div>

    @if($activities->isEmpty())
        <div class="family-empty-state">
            <div class="family-empty-state__title">No hay actividades disponibles</div>
            <p>Por ahora no hay extraescolares publicadas. Vuelve a consultar más adelante.</p>
        </div>
    @else
        <div class="family-activity-list fam-stack">
            @foreach($activities as $activity)
            @php
                $activityEnrollments = $familyEnrollmentsByActivity->get($activity->id, collect());
                $prices = $activity->activityGroups
                    ->pluck('price_member')
                    ->filter(fn ($p) => $p !== null && (float) $p > 0)
                    ->sort()
                    ->values();
                $minPrice = $prices->first();
                $maxPrice = $prices->last();
            @endphp
            <div class="family-card">
                <div class="family-card__header">
                    <div>
                        <div class="family-card__title">{{ $activity->name }}</div>
                        @if($activity->short_description)
                            <div class="family-card__sub">{{ $activity->short_description }}</div>
                        @endif
                    </div>
                </div>

                <div class="family-card__body">
                    <div class="family-activity-meta">
                        @if($activity->activity_groups_count > 0)
                            <span class="family-chip family-chip--brand">{{ $activity->activity_groups_count }} {{ $activity->activity_groups_count === 1 ? 'grupo' : 'grupos' }}</span>
                        @endif
                        @if($activity->requires_ampa_membership)
                            <span class="family-chip family-chip--ampa">Solo socios/as AMPA</span>
                        @endif
                        @if($minPrice !== null)
                            <span class="family-chip">
                                @if((float) $minPrice === (float) $maxPrice)
                                    {{ number_format($minPrice, 2, ',', '.') }} €/mes
                                @else
                                    {{ number_format($minPrice, 2, ',', '.') }}–{{ number_format($maxPrice, 2, ',', '.') }} €/mes
                                @endif
                            </span>
                        @endif
                    </div>

                    @if($activityEnrollments->isNotEmpty())
                        <div class="family-enroll-list">
                            @foreach($activityEnrollments as $enr)
                                <div class="family-enroll-line">
                                    <span class="family-enroll-line__name">{{ $enr->student->first_name }}</span>
                                    @include('familia.partials.status-badge', ['status' => $enr->status])
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div style="margin-top: 14px;">
                        <a href="{{ route('familia.activities.show', $activity) }}" class="family-btn family-btn--primary">
                            Ver grupos y apuntarse
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @endif
@endif

@endsection
