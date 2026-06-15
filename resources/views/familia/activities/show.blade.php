@extends('familia.layouts.app')

@section('title', $activity->name)

@section('content')

<div class="mb-4">
    <a href="{{ route('familia.activities.index') }}"
       class="text-sm text-indigo-600 hover:underline">← Volver a extraescolares</a>
</div>

<h1 class="text-2xl font-bold text-gray-900 mb-1">{{ $activity->name }}</h1>

@php
    $canEnroll = ! $activity->requires_ampa_membership || $family->is_ampa_member;
    $weekdayNames = [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'];
@endphp

@if(! $canEnroll)
    <div class="mt-3 mb-5 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-amber-800 text-sm">
        <strong>Actividad exclusiva para socios/as del AMPA.</strong>
        Tu familia no está registrada como socia. Contacta con el AMPA si deseas hacerte socio/a.
    </div>
@endif

@if($activity->short_description)
    <p class="text-gray-600 text-sm mt-3">{{ $activity->short_description }}</p>
@endif

@if($activity->long_description)
    <p class="text-gray-600 text-sm mt-2">{{ $activity->long_description }}</p>
@endif

@if($activity->activityGroups->isEmpty())
    <div class="mt-6 bg-white rounded-xl border border-gray-200 px-6 py-10 text-center text-gray-500">
        No hay grupos disponibles para esta actividad.
    </div>
@else
    <div class="mt-6 space-y-5">
        @foreach($activity->activityGroups as $group)
        @php
            $occupied = $group->occupying_enrollments_count ?? 0;
            $available = max(0, $group->max_spots - $occupied);
            $isUnavailable = in_array($group->status, [\App\Enums\ActivityGroupStatus::Closed, \App\Enums\ActivityGroupStatus::Archived]);
            $isFull = $group->status === \App\Enums\ActivityGroupStatus::Full || $available <= 0;
            $days = collect($group->weekdays ?? [])->sort()->map(fn ($d) => $weekdayNames[$d] ?? "Día $d")->join(' · ');
        @endphp

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            {{-- Group header --}}
            <div class="px-5 py-4 border-b border-gray-100">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-gray-900">{{ $group->name }}</div>

                        <div class="mt-1.5 flex flex-wrap gap-x-4 gap-y-0.5 text-sm text-gray-500">
                            @if($days)
                                <span>{{ $days }}</span>
                            @endif
                            @if($group->starts_at && $group->ends_at)
                                <span>{{ \Carbon\Carbon::parse($group->starts_at)->format('H:i') }}–{{ \Carbon\Carbon::parse($group->ends_at)->format('H:i') }}h</span>
                            @endif
                            @if($group->location)
                                <span>{{ $group->location }}</span>
                            @endif
                        </div>

                        <div class="mt-1 flex flex-wrap gap-x-4 gap-y-0.5 text-sm text-gray-500">
                            @if($group->price_member && (float) $group->price_member > 0)
                                <span>Socio: <strong class="text-gray-700">{{ number_format($group->price_member, 2, ',', '.') }} €/mes</strong></span>
                            @endif
                            @if($group->price_non_member && (float) $group->price_non_member > 0)
                                <span>No socio: <strong class="text-gray-700">{{ number_format($group->price_non_member, 2, ',', '.') }} €/mes</strong></span>
                            @endif
                        </div>

                        <div class="mt-1 text-xs text-gray-400">
                            Plazas: {{ $occupied }}/{{ $group->max_spots }}
                            @if($available > 0)
                                · <span class="text-green-600">{{ $available }} disponibles</span>
                            @else
                                · <span class="text-orange-600">Sin plazas libres</span>
                            @endif
                        </div>

                        @if($group->grades->isNotEmpty())
                            <div class="mt-1 text-xs text-gray-400">
                                Cursos: {{ $group->grades->pluck('name')->join(', ') }}
                            </div>
                        @endif
                    </div>

                    <span class="shrink-0 text-xs px-2 py-0.5 rounded-full
                        @if($group->status === \App\Enums\ActivityGroupStatus::Open) bg-green-100 text-green-800
                        @elseif($group->status === \App\Enums\ActivityGroupStatus::Full) bg-orange-100 text-orange-800
                        @else bg-gray-100 text-gray-600
                        @endif">
                        {{ $group->status->getLabel() }}
                    </span>
                </div>
            </div>

            {{-- Students section --}}
            <div class="px-5 py-4">
                @if($isUnavailable)
                    <p class="text-sm text-gray-400">Este grupo no está disponible para inscripciones.</p>
                @elseif(! $canEnroll)
                    <p class="text-sm text-gray-400">Necesitas ser socio/a del AMPA para inscribirte en esta actividad.</p>
                @elseif($students->isEmpty())
                    <p class="text-sm text-gray-400">No hay alumnos/as activos/as en tu familia.</p>
                @else
                    <div class="divide-y divide-gray-100">
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
                        <div class="flex items-center justify-between py-3 first:pt-0 last:pb-0">
                            <span class="text-sm font-medium text-gray-800">
                                {{ $student->first_name }} {{ $student->last_name }}
                            </span>

                            <div class="text-right shrink-0 ml-4">
                                @if($enrollment)
                                    @php
                                        $pillClass = match($enrollment->status) {
                                            \App\Enums\EnrollmentStatus::Enrolled,
                                            \App\Enums\EnrollmentStatus::Paid => 'bg-green-100 text-green-800',
                                            \App\Enums\EnrollmentStatus::Waitlist => 'bg-amber-100 text-amber-800',
                                            \App\Enums\EnrollmentStatus::PendingPayment => 'bg-blue-100 text-blue-800',
                                            \App\Enums\EnrollmentStatus::Pending => 'bg-indigo-100 text-indigo-800',
                                            default => 'bg-gray-100 text-gray-600',
                                        };
                                        $statusLabel = match($enrollment->status) {
                                            \App\Enums\EnrollmentStatus::Pending => 'Solicitud enviada',
                                            \App\Enums\EnrollmentStatus::Paid => 'Pago registrado',
                                            default => $enrollment->status->label(),
                                        };
                                    @endphp
                                    <span class="inline-block text-xs px-2 py-0.5 rounded-full {{ $pillClass }}">
                                        {{ $statusLabel }}
                                    </span>
                                    @if(in_array($enrollment->status, [\App\Enums\EnrollmentStatus::Pending, \App\Enums\EnrollmentStatus::Waitlist]))
                                        <form method="POST"
                                              action="{{ route('familia.enrollment.cancel', $enrollment) }}"
                                              class="mt-1"
                                              onsubmit="return confirm('¿Seguro que quieres cancelar esta solicitud?')">
                                            @csrf
                                            <button type="submit"
                                                    class="text-xs text-red-500 hover:text-red-700 hover:underline">
                                                Cancelar solicitud
                                            </button>
                                        </form>
                                    @endif

                                @elseif($enrolledInOtherGroup)
                                    <span class="text-xs text-gray-400">Ya inscrito/a en otro grupo</span>

                                @elseif($hasNoClassroom)
                                    <span class="text-xs text-gray-400">Sin clase asignada este curso</span>

                                @elseif($gradeRestricted)
                                    <span class="text-xs text-gray-400">No disponible para su curso</span>

                                @elseif($isFull)
                                    <form method="POST" action="{{ route('familia.enroll') }}">
                                        @csrf
                                        <input type="hidden" name="student_id" value="{{ $student->id }}">
                                        <input type="hidden" name="activity_group_id" value="{{ $group->id }}">
                                        <button type="submit"
                                                class="text-xs bg-amber-100 hover:bg-amber-200 text-amber-800 font-medium px-3 py-1 rounded-lg transition-colors">
                                            Apuntarse a lista de espera
                                        </button>
                                    </form>

                                @else
                                    <form method="POST" action="{{ route('familia.enroll') }}">
                                        @csrf
                                        <input type="hidden" name="student_id" value="{{ $student->id }}">
                                        <input type="hidden" name="activity_group_id" value="{{ $group->id }}">
                                        <button type="submit"
                                                class="text-xs bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-3 py-1 rounded-lg transition-colors">
                                            Solicitar plaza
                                        </button>
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
