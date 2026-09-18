@extends('familia.layouts.app')

@section('title', 'Calendario')

@section('content')

<div class="family-cal-page">

<div class="family-page-header">
    <h1>Calendario semanal</h1>
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
    @php
        $weekParams = ['week' => $weekStart->format('Y-m-d')];
        if ($selectedStudentId) {
            $weekParams['student'] = $selectedStudentId;
        }
        $todayParams = $selectedStudentId ? ['student' => $selectedStudentId] : [];
        $isEmpty = collect($days)->every(fn ($day) => $day['sessions']->isEmpty());
        $selectedStudent = $selectedStudentId ? $students->firstWhere('id', $selectedStudentId) : null;
    @endphp

    <div class="family-cal-toolbar">
        <div class="family-cal-nav">
            <a href="{{ route('familia.calendar', array_merge($weekParams, ['week' => $prevWeekParam])) }}" class="family-btn family-btn--ghost family-btn--sm">&larr; Semana anterior</a>
            <span class="family-cal-nav__label">
                {{ $weekStart->format('d/m') }} – {{ $weekStart->addDays(6)->format('d/m/Y') }}
            </span>
            <a href="{{ route('familia.calendar', array_merge($weekParams, ['week' => $nextWeekParam])) }}" class="family-btn family-btn--ghost family-btn--sm">Semana siguiente &rarr;</a>
        </div>
        @unless($isCurrentWeek)
            <a href="{{ route('familia.calendar', $todayParams) }}" class="family-btn family-btn--muted family-btn--sm">Esta semana</a>
        @endunless
    </div>

    @if($timeRange)
        <p class="family-cal-range">
            Horario de esta semana: {{ \Carbon\Carbon::parse($timeRange['from'])->format('H:i') }}–{{ \Carbon\Carbon::parse($timeRange['until'])->format('H:i') }}h
        </p>
    @endif

    @if($students->isNotEmpty())
        <div class="family-cal-students">
            <a href="{{ route('familia.calendar', ['week' => $weekStart->format('Y-m-d')]) }}"
               class="family-cal-chip @if(! $selectedStudentId) is-active @endif">
                Todos/as
            </a>
            @foreach($students as $student)
                <a href="{{ route('familia.calendar', ['week' => $weekStart->format('Y-m-d'), 'student' => $student->id]) }}"
                   class="family-cal-chip @if($selectedStudentId === $student->id) is-active @endif">
                    <span class="family-cal-chip__dot family-cal-swatch-{{ $studentColors[$student->id] ?? 1 }}"></span>
                    {{ $student->first_name }}
                </a>
            @endforeach
        </div>
    @endif

    @if($notices->isNotEmpty())
        <div class="family-cal-notices">
            <div class="family-cal-notices__title">Avisos de esta semana</div>
            @foreach($notices as $notice)
                <div class="family-cal-notice">
                    <strong>{{ $notice->date->format('d/m') }}</strong>
                    — {{ $notice->activityName }} ({{ $notice->studentName }}) cancelada
                    @if($notice->reason)
                        · {{ $notice->reason }}
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    @if($isEmpty)
        <div class="family-empty-state">
            <div class="family-empty-state__title">
                @if($selectedStudent)
                    {{ $selectedStudent->first_name }} no tiene actividades esta semana
                @else
                    No hay actividades esta semana
                @endif
            </div>
            <p>No hay sesiones de extraescolares programadas para la semana seleccionada.</p>
            <a href="{{ route('familia.activities.index') }}" class="family-btn family-btn--primary">Ver extraescolares</a>
        </div>
    @else
        {{-- Agenda vertical (móvil / tablet, < 860px): misma estructura de siempre. --}}
        <div class="family-cal-grid family-cal-agenda" style="--fam-cal-days: {{ count($days) }};">
            @foreach($days as $day)
                @php $isToday = $day['date']->isSameDay(\Carbon\CarbonImmutable::today()); @endphp
                <div class="family-cal-day">
                    <div class="family-cal-day__header @if($isToday) family-cal-day__header--today @endif">
                        {{ $day['label'] }} <span class="family-cal-day__date">{{ $day['date']->format('d/m') }}</span>
                    </div>
                    <div class="family-cal-day__body">
                        @forelse($day['sessions'] as $session)
                            <div class="family-cal-session family-cal-session--s{{ $studentColors[$session->studentId] ?? 1 }}">
                                <div class="family-cal-session__time">
                                    {{ \Carbon\Carbon::parse($session->startsAt)->format('H:i') }}–{{ \Carbon\Carbon::parse($session->endsAt)->format('H:i') }}
                                </div>
                                <div class="family-cal-session__activity">{{ $session->activityName }}</div>
                                <div class="family-cal-session__meta">
                                    {{ $session->activityGroupName }}
                                    @if($session->location)
                                        · {{ $session->location }}
                                    @endif
                                </div>
                                <div class="family-cal-session__student">
                                    <span class="family-cal-session__student-dot family-cal-swatch-{{ $studentColors[$session->studentId] ?? 1 }}"></span>
                                    {{ $session->studentName }}
                                </div>
                                @if($session->isModified())
                                    <span class="family-cal-session__badge" title="{{ $session->exceptionReason ?: 'Horario o ubicación modificados puntualmente' }}">
                                        Cambio puntual
                                    </span>
                                @endif
                            </div>
                        @empty
                            <div class="family-cal-day__empty">Sin sesiones</div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Timetable (desktop, >= 860px): eje horario compartido + posición proporcional. --}}
        @if($timetable)
            <div class="family-cal-timetable" style="--fam-cal-days: {{ count($timetable['days']) }}; --fam-tt-total: {{ $timetable['totalMinutes'] }}; --fam-tt-slots: {{ $timetable['totalMinutes'] / 30 }};">
                <div class="family-cal-axis">
                    @foreach($timetable['markers'] as $marker)
                        <div class="family-cal-axis__mark" style="top: calc(2px * {{ $marker['offset'] }});">{{ $marker['label'] }}</div>
                    @endforeach
                </div>

                @foreach($timetable['days'] as $day)
                    @php $isToday = $day['date']->isSameDay(\Carbon\CarbonImmutable::today()); @endphp
                    <div class="family-cal-timetable-day">
                        <div class="family-cal-timetable-day__header @if($isToday) family-cal-timetable-day__header--today @endif">
                            {{ $day['label'] }} <span class="family-cal-day__date">{{ $day['date']->format('d/m') }}</span>
                        </div>
                        <div class="family-cal-timetable-day__body">
                            @forelse($day['items'] as $item)
                                @php $session = $item['session']; @endphp
                                <div class="family-cal-tt-session family-cal-session--s{{ $studentColors[$session->studentId] ?? 1 }}"
                                     data-activity="{{ $session->activityName }}-{{ $session->studentName }}"
                                     data-row-start="{{ $item['rowStart'] }}"
                                     data-row-span="{{ $item['rowSpan'] }}"
                                     data-lane="{{ $item['lane'] }}"
                                     data-lanes="{{ $item['lanes'] }}"
                                     style="top: calc(2px * {{ $item['rowStart'] }}); height: calc(2px * {{ $item['rowSpan'] }}); left: calc((100% / {{ $item['lanes'] }}) * {{ $item['lane'] }}); width: calc((100% / {{ $item['lanes'] }}) - 4px);">
                                    <div class="family-cal-tt-session__time">
                                        {{ \Carbon\Carbon::parse($session->startsAt)->format('H:i') }}–{{ \Carbon\Carbon::parse($session->endsAt)->format('H:i') }}
                                    </div>
                                    <div class="family-cal-tt-session__activity">{{ $session->activityName }}</div>
                                    <div class="family-cal-tt-session__meta">
                                        {{ $session->activityGroupName }}
                                        @if($session->location)
                                            · {{ $session->location }}
                                        @endif
                                    </div>
                                    <div class="family-cal-tt-session__student">
                                        <span class="family-cal-tt-session__student-dot family-cal-swatch-{{ $studentColors[$session->studentId] ?? 1 }}"></span>
                                        {{ $session->studentName }}
                                    </div>
                                    @if($session->isModified())
                                        <span class="family-cal-tt-session__badge" title="{{ $session->exceptionReason ?: 'Horario o ubicación modificados puntualmente' }}">
                                            Cambio puntual
                                        </span>
                                    @endif
                                </div>
                            @empty
                                <div class="family-cal-timetable-day__empty">Sin sesiones</div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif
@endif

</div>

@endsection
