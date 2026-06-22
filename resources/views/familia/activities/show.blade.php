@extends('familia.layouts.app')

@section('title', $activity->name)

@section('content')

<a href="{{ route('familia.activities.index') }}" class="family-back-link">← Volver a extraescolares</a>

<div class="family-page-header">
    <h1>{{ $activity->name }}</h1>
</div>

@php
    $canEnroll = ! $activity->requires_ampa_membership || $family->is_ampa_member;
    $weekdayNames = [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'];
@endphp

@if(! $canEnroll)
    <div class="family-alert family-alert--warning" style="margin-bottom: 16px;">
        <span>
            <strong>Actividad exclusiva para socios/as del AMPA.</strong>
            Tu familia no está registrada como socia. Contacta con el AMPA si deseas hacerte socio/a.
        </span>
    </div>
@endif

@if($activity->short_description)
    <p class="fam-muted" style="font-size: .9rem; margin: 0 0 6px;">{{ $activity->short_description }}</p>
@endif

@if($activity->long_description)
    <p class="fam-muted" style="font-size: .9rem; margin: 0 0 6px;">{{ $activity->long_description }}</p>
@endif

@if($canEnroll)
    <div class="family-alert family-alert--brand" style="margin: 14px 0 4px;">
        <span>
            <strong>Al solicitar plaza</strong> envías una solicitud al AMPA:
            no reserva plaza hasta que el AMPA la confirme y actualice el estado.
        </span>
    </div>
@endif

@if($activity->activityGroups->isEmpty())
    <div class="family-empty-state" style="margin-top: 20px;">
        <div class="family-empty-state__title">No hay grupos disponibles</div>
        <p>Esta actividad todavía no tiene grupos abiertos para inscripción.</p>
    </div>
@else
    <div class="family-card-list fam-stack" style="margin-top: 20px;">
        @foreach($activity->activityGroups as $group)
        @php
            $occupied = $group->occupying_enrollments_count ?? 0;
            $available = max(0, $group->max_spots - $occupied);
            $isUnavailable = in_array($group->status, [\App\Enums\ActivityGroupStatus::Closed, \App\Enums\ActivityGroupStatus::Archived]);
            $isFull = $group->status === \App\Enums\ActivityGroupStatus::Full || $available <= 0;
            $days = collect($group->weekdays ?? [])->sort()->map(fn ($d) => $weekdayNames[$d] ?? "Día $d")->join(' · ');
        @endphp

        <div class="family-card">
            <div class="family-card__header">
                <div class="family-row__main">
                    <div class="family-card__title">{{ $group->name }}</div>

                    <div class="family-group-details">
                        @if($days)
                            <span class="family-group-detail">{{ $days }}</span>
                        @endif
                        @if($group->starts_at && $group->ends_at)
                            <span class="family-group-detail">{{ \Carbon\Carbon::parse($group->starts_at)->format('H:i') }}–{{ \Carbon\Carbon::parse($group->ends_at)->format('H:i') }}h</span>
                        @endif
                        @if($group->location)
                            <span class="family-group-detail">{{ $group->location }}</span>
                        @endif
                    </div>
                </div>

                <span class="family-status-badge
                    @if($group->status === \App\Enums\ActivityGroupStatus::Open) status-success
                    @elseif($group->status === \App\Enums\ActivityGroupStatus::Full) status-warning
                    @else status-muted @endif">
                    {{ $group->status->getLabel() }}
                </span>
            </div>

            <div class="family-card__body">
                <div class="family-group-info-grid">
                    <div class="family-group-info-item">
                        <span class="family-group-info-label">Plazas</span>
                        <span class="family-group-info-value">
                            {{ $occupied }}/{{ $group->max_spots }}
                            @if($available > 0)
                                · <span style="color: #16a34a; font-weight: 600;">{{ $available }} libres</span>
                            @else
                                · <span style="color: #ea580c; font-weight: 600;">Completo</span>
                            @endif
                        </span>
                    </div>

                    @if($group->price_member && (float) $group->price_member > 0)
                        <div class="family-group-info-item">
                            <span class="family-group-info-label">Socio</span>
                            <span class="family-group-info-value"><strong>{{ number_format($group->price_member, 2, ',', '.') }} €/mes</strong></span>
                        </div>
                    @endif
                    @if($group->price_non_member && (float) $group->price_non_member > 0)
                        <div class="family-group-info-item">
                            <span class="family-group-info-label">No socio</span>
                            <span class="family-group-info-value"><strong>{{ number_format($group->price_non_member, 2, ',', '.') }} €/mes</strong></span>
                        </div>
                    @endif

                    @if($group->grades->isNotEmpty())
                        <div class="family-group-info-item">
                            <span class="family-group-info-label">Cursos</span>
                            <span class="family-group-info-value">{{ $group->grades->pluck('name')->join(', ') }}</span>
                        </div>
                    @endif
                </div>

                {{-- Alumnos/as --}}
                @if($isUnavailable)
                    <p class="fam-muted" style="font-size: .85rem; margin: 12px 0 0;">Este grupo no está disponible para inscripciones.</p>
                @elseif(! $canEnroll)
                    <p class="fam-muted" style="font-size: .85rem; margin: 12px 0 0;">Necesitas ser socio/a del AMPA para inscribirte en esta actividad.</p>
                @elseif($students->isEmpty())
                    <p class="fam-muted" style="font-size: .85rem; margin: 12px 0 0;">No hay alumnos/as activos/as en tu familia.</p>
                @else
                    <div class="family-group-students">
                        @foreach($students as $student)
                        @php
                            $key = $student->id.'_'.$group->id;
                            $enrollment = $enrollmentMap->get($key);
                            $enrolledGroupIds = $studentEnrolledGroupIds->get($student->id, []);
                            $enrolledInOtherGroup = ! $enrollment && count($enrolledGroupIds) > 0;
                            $currentGradeId = $studentCurrentGradeIds->get($student->id);
                            $hasNoClassroom = $currentGradeId === null;
                            $gradeRestricted = $group->grades->isNotEmpty() && ! $group->grades->contains('id', $currentGradeId);
                        @endphp
                        <div class="family-child-enrollment">
                            <div class="family-child-enrollment__info">
                                <span class="family-child-enrollment__name">{{ $student->first_name }} {{ $student->last_name }}</span>
                            </div>

                            <div class="family-child-enrollment__status">
                                @if($enrollment)
                                    @include('familia.partials.status-badge', ['status' => $enrollment->status])
                                    @if(in_array($enrollment->status, [\App\Enums\EnrollmentStatus::Pending, \App\Enums\EnrollmentStatus::Waitlist]))
                                        <form method="POST"
                                              action="{{ route('familia.enrollment.cancel', $enrollment) }}"
                                              onsubmit="return confirm('¿Seguro que quieres cancelar esta solicitud?')">
                                            @csrf
                                            <button type="submit" class="family-link-danger">Cancelar solicitud</button>
                                        </form>
                                    @endif

                                @elseif($enrolledInOtherGroup)
                                    <span class="fam-muted" style="font-size: .78rem;">Ya inscrito/a en otro grupo</span>

                                @elseif($hasNoClassroom)
                                    <span class="fam-muted" style="font-size: .78rem;">Sin clase asignada este curso</span>

                                @elseif($gradeRestricted)
                                    <span class="fam-muted" style="font-size: .78rem;">No disponible para su curso</span>

                                @elseif($isFull)
                                    <form method="POST" action="{{ route('familia.enroll') }}">
                                        @csrf
                                        <input type="hidden" name="student_id" value="{{ $student->id }}">
                                        <input type="hidden" name="activity_group_id" value="{{ $group->id }}">
                                        <button type="submit" class="family-btn family-btn--warning family-btn--sm">Apuntarse a lista de espera</button>
                                    </form>

                                @else
                                    <form method="POST" action="{{ route('familia.enroll') }}">
                                        @csrf
                                        <input type="hidden" name="student_id" value="{{ $student->id }}">
                                        <input type="hidden" name="activity_group_id" value="{{ $group->id }}">
                                        <button type="submit" class="family-btn family-btn--primary family-btn--sm">Solicitar plaza</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
@endif

@endsection
