@extends('familia.layouts.app')

@section('title', 'Calendario')

@section('content')

<div class="family-cal-page">

<div class="family-page-header">
    <h1>Calendario semanal</h1>
    @if($activeYear)
        <p>
            Curso {{ $activeYear->name }}
            @if($timeRange)
                · Horario de esta semana: {{ \Carbon\Carbon::parse($timeRange['from'])->format('H:i') }}–{{ \Carbon\Carbon::parse($timeRange['until'])->format('H:i') }}h
            @endif
        </p>
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
        $firstNames = $students->pluck('first_name', 'id');
        $showGroupFor = array_flip($groupNameActivityIds);
    @endphp

    <div class="family-cal-toolbar">
        <div class="family-cal-nav">
            <a href="{{ route('familia.calendar', array_merge($weekParams, ['week' => $prevWeekParam])) }}" class="family-cal-navbtn" aria-label="Semana anterior">
                <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 5l-5 5 5 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span class="family-cal-navbtn__text" aria-hidden="true">Semana anterior</span>
            </a>
            <span class="family-cal-nav__label">
                {{ $weekStart->format('d/m') }} – {{ $weekStart->addDays(6)->format('d/m/Y') }}
            </span>
            <a href="{{ route('familia.calendar', array_merge($weekParams, ['week' => $nextWeekParam])) }}" class="family-cal-navbtn" aria-label="Semana siguiente">
                <span class="family-cal-navbtn__text" aria-hidden="true">Semana siguiente</span>
                <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M8 5l5 5-5 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
            @unless($isCurrentWeek)
                <a href="{{ route('familia.calendar', $todayParams) }}" class="family-cal-navbtn family-cal-navbtn--today">Esta semana</a>
            @endunless
        </div>

        @if($students->isNotEmpty())
            <div class="family-cal-students" role="group" aria-label="Filtrar por hijo/a">
                <a href="{{ route('familia.calendar', ['week' => $weekStart->format('Y-m-d')]) }}"
                   class="family-cal-chip @if(! $selectedStudentId) is-active @endif"
                   @if(! $selectedStudentId) aria-current="true" @endif>
                    Todos/as
                </a>
                @foreach($students as $student)
                    <a href="{{ route('familia.calendar', ['week' => $weekStart->format('Y-m-d'), 'student' => $student->id]) }}"
                       class="family-cal-chip @if($selectedStudentId === $student->id) is-active @endif"
                       @if($selectedStudentId === $student->id) aria-current="true" @endif>
                        <span class="family-cal-chip__dot family-cal-swatch-{{ $studentColors[$student->id] ?? 1 }}" aria-hidden="true"></span>
                        {{ $student->first_name }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    @if($notices->isNotEmpty())
        <div class="family-cal-notices">
            <div class="family-cal-notices__title">
                <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M10 3.5l7 12.5H3L10 3.5z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M10 8.5v3.2M10 13.9v.1" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                Avisos de esta semana
            </div>
            <ul class="family-cal-notices__list">
                @foreach($notices as $notice)
                    <li class="family-cal-notice">
                        <strong>{{ $notice->date->format('d/m') }}</strong>
                        — {{ $notice->activityName }} ({{ $notice->studentName }}) cancelada
                        @if($notice->reason)
                            · {{ $notice->reason }}
                        @endif
                    </li>
                @endforeach
            </ul>
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
                                @if(isset($showGroupFor[$session->activityId]) || $session->location)
                                    <div class="family-cal-session__meta">
                                        @if(isset($showGroupFor[$session->activityId]))
                                            {{ $session->activityGroupName }}
                                        @endif
                                        @if(isset($showGroupFor[$session->activityId]) && $session->location)
                                            ·
                                        @endif
                                        @if($session->location)
                                            {{ $session->location }}
                                        @endif
                                    </div>
                                @endif
                                <div class="family-cal-session__student">
                                    <span class="family-cal-session__student-dot family-cal-swatch-{{ $studentColors[$session->studentId] ?? 1 }}" aria-hidden="true"></span>
                                    {{ $session->studentName }}
                                </div>
                                @if($session->isModified())
                                    <span class="family-cal-session__badge" title="{{ $session->exceptionReason ?: 'Horario o ubicación modificados puntualmente' }}">
                                        Cambio puntual
                                    </span>
                                    @if($session->exceptionReason)
                                        <span class="fam-sr-only">Motivo: {{ $session->exceptionReason }}</span>
                                    @endif
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
            @php $hourOffset = (60 - ($timetable['axisStart'] % 60)) % 60; @endphp
            <div class="family-cal-timetable" style="--fam-cal-days: {{ count($timetable['days']) }}; --fam-tt-total: {{ $timetable['totalMinutes'] }}; --fam-tt-hour-offset: {{ $hourOffset }};">
                <div class="family-cal-axis" aria-hidden="true">
                    @foreach($timetable['markers'] as $marker)
                        <div class="family-cal-axis__mark @unless(str_ends_with($marker['label'], ':00')) family-cal-axis__mark--half @endunless" style="top: calc(2px * {{ $marker['offset'] }});">{{ $marker['label'] }}</div>
                    @endforeach
                </div>

                @foreach($timetable['days'] as $day)
                    @php
                        $isToday = $day['date']->isSameDay(\Carbon\CarbonImmutable::today());
                        $dayIsEmpty = count($day['items']) === 0;
                    @endphp
                    <div class="family-cal-timetable-day @if($isToday) is-today @endif @if($dayIsEmpty) is-empty @endif">
                        <div class="family-cal-timetable-day__header @if($isToday) family-cal-timetable-day__header--today @endif">
                            <span class="family-cal-timetable-day__name">{{ $day['label'] }}</span>
                            <span class="family-cal-timetable-day__num">{{ $day['date']->format('d/m') }}</span>
                            @if($isToday)
                                <span class="family-cal-timetable-day__today">Hoy</span>
                            @endif
                        </div>
                        <div class="family-cal-timetable-day__body">
                            @forelse($day['items'] as $item)
                                @php
                                    $session = $item['session'];
                                    $showGroup = isset($showGroupFor[$session->activityId]);
                                    $studentFirstName = $firstNames[$session->studentId] ?? $session->studentName;
                                @endphp
                                <div class="family-cal-tt-session family-cal-session--s{{ $studentColors[$session->studentId] ?? 1 }} @if($session->isModified()) is-modified @endif"
                                     data-activity="{{ $session->activityName }}-{{ $session->studentName }}"
                                     data-row-start="{{ $item['rowStart'] }}"
                                     data-row-span="{{ $item['rowSpan'] }}"
                                     data-lane="{{ $item['lane'] }}"
                                     data-lanes="{{ $item['lanes'] }}"
                                     style="top: calc(2px * {{ $item['rowStart'] }} + 1px); height: calc(2px * {{ $item['rowSpan'] }} - 2px); left: calc((100% / {{ $item['lanes'] }}) * {{ $item['lane'] }} + 3px); width: calc((100% / {{ $item['lanes'] }}) - 6px);">
                                    <div class="family-cal-tt-session__time">
                                        <span>{{ \Carbon\Carbon::parse($session->startsAt)->format('H:i') }}–{{ \Carbon\Carbon::parse($session->endsAt)->format('H:i') }}</span>
                                        @if($session->isModified())
                                            <span class="family-cal-tt-session__mod" title="{{ $session->exceptionReason ?: 'Horario o ubicación modificados puntualmente' }}">
                                                <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M4 14.5l.6-3L13 3.1a1.4 1.4 0 0 1 2 0l1.9 1.9a1.4 1.4 0 0 1 0 2L8.5 15.4l-4.5-.9z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>
                                                Cambio puntual
                                            </span>
                                        @endif
                                    </div>
                                    <div class="family-cal-tt-session__activity">{{ $session->activityName }}</div>
                                    @if($showGroup)
                                        <div class="family-cal-tt-session__group" title="{{ $session->activityGroupName }}">{{ $session->activityGroupName }}</div>
                                    @endif
                                    <div class="family-cal-tt-session__who">
                                        <span class="family-cal-tt-session__student-dot family-cal-swatch-{{ $studentColors[$session->studentId] ?? 1 }}" aria-hidden="true"></span>
                                        <span class="family-cal-tt-session__who-text">
                                            <span class="fam-sr-only">Alumno/a: {{ $session->studentName }}. </span><span aria-hidden="true">{{ $studentFirstName }}</span>@if($session->location)<span class="family-cal-tt-session__loc"> · {{ $session->location }}</span>@endif
                                        </span>
                                    </div>
                                    @if($session->isModified() && $session->exceptionReason)
                                        <span class="fam-sr-only">Motivo: {{ $session->exceptionReason }}</span>
                                    @endif
                                </div>
                            @empty
                                <span class="fam-sr-only">Sin sesiones</span>
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
