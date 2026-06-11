@extends('familia.layouts.app')

@section('title', $activity->name)

@section('content')

<div class="mb-4">
    <a href="{{ route('familia.activities.index') }}"
       class="text-sm text-indigo-600 hover:underline">← Volver a extraescolares</a>
</div>

<h1 class="text-2xl font-bold text-gray-900 mb-1">{{ $activity->name }}</h1>

@if($activity->requires_ampa_membership && ! $family->is_ampa_member)
    <div class="mt-3 mb-5 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-amber-800 text-sm">
        <strong>Actividad exclusiva para socios/as del AMPA.</strong>
        Tu familia no está registrada como socia. Contacta con el AMPA si deseas hacerte socio/a.
    </div>
    @php $canEnroll = false; @endphp
@else
    @php $canEnroll = true; @endphp
@endif

@if($activity->description)
    <p class="text-gray-600 text-sm mb-6">{{ $activity->description }}</p>
@endif

@if($activity->activityGroups->isEmpty())
    <div class="bg-white rounded-xl border border-gray-200 px-6 py-10 text-center text-gray-500">
        No hay grupos disponibles para esta actividad.
    </div>
@else
    <div class="space-y-5">
        @foreach($activity->activityGroups as $group)
        @php
            $enrolledInGroup = $familyEnrollments->where('activity_group_id', $group->id)->first();
            $groupIsFull = $group->available_spots !== null && $group->available_spots <= 0
                           && $group->status?->value === 'full';
            $groupIsClosed = in_array($group->status?->value ?? '', ['closed', 'archived']);
        @endphp

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <div class="font-semibold text-gray-900">{{ $group->name }}</div>
                        <div class="text-sm text-gray-500 flex flex-wrap gap-x-4 mt-0.5">
                            @if($group->day_of_week)
                                <span>{{ $group->day_of_week }}</span>
                            @endif
                            @if($group->start_time && $group->end_time)
                                <span>{{ \Carbon\Carbon::parse($group->start_time)->format('H:i') }} – {{ \Carbon\Carbon::parse($group->end_time)->format('H:i') }}</span>
                            @endif
                            @if($group->price !== null && $group->price > 0)
                                <span>{{ number_format($group->price, 2, ',', '.') }} €/mes</span>
                            @endif
                        </div>
                    </div>
                    @if($group->status)
                        <span class="shrink-0 text-xs px-2 py-0.5 rounded-full
                            @if($group->status?->value === 'open') bg-green-100 text-green-800
                            @elseif($group->status?->value === 'full') bg-orange-100 text-orange-800
                            @else bg-gray-100 text-gray-600
                            @endif">
                            {{ $group->status->getLabel() }}
                        </span>
                    @endif
                </div>
                @if($group->max_students)
                    <div class="text-xs text-gray-400 mt-1">
                        Plazas: {{ $group->current_students_count ?? '—' }}/{{ $group->max_students }}
                    </div>
                @endif
            </div>

            <div class="px-5 py-4">
                @if($enrolledInGroup)
                    {{-- Familia ya inscrita en este grupo --}}
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            @if($enrolledInGroup->status === \App\Enums\EnrollmentStatus::Waitlist)
                                <span class="font-medium">{{ $enrolledInGroup->student->first_name }}</span>
                                ya está en lista de espera para este grupo.
                            @else
                                <span class="font-medium">{{ $enrolledInGroup->student->first_name }}</span>
                                ya está inscrito/a en este grupo.
                            @endif
                        </div>
                        @if(in_array($enrolledInGroup->status, [\App\Enums\EnrollmentStatus::Pending, \App\Enums\EnrollmentStatus::Waitlist]))
                            <form method="POST"
                                  action="{{ route('familia.enrollment.cancel', $enrolledInGroup) }}"
                                  onsubmit="return confirm('¿Seguro que quieres cancelar esta solicitud?')">
                                @csrf
                                <button type="submit"
                                        class="text-xs text-red-500 hover:text-red-700 hover:underline transition-colors">
                                    Cancelar solicitud
                                </button>
                            </form>
                        @endif
                    </div>

                @elseif($groupIsClosed)
                    <p class="text-sm text-gray-400">Este grupo está cerrado.</p>

                @elseif(! $canEnroll)
                    <p class="text-sm text-gray-400">Necesitas ser socio/a del AMPA para inscribirte.</p>

                @elseif($students->isEmpty())
                    <p class="text-sm text-gray-400">No hay alumnos/as activos/as en tu familia.</p>

                @else
                    {{-- Formulario de inscripción --}}
                    @if($groupIsFull)
                        <p class="text-xs text-amber-700 mb-2">El grupo está completo. Puedes apuntarte a lista de espera.</p>
                    @endif
                    <form method="POST" action="{{ route('familia.enroll') }}" class="flex flex-wrap items-end gap-3">
                        @csrf
                        <input type="hidden" name="activity_group_id" value="{{ $group->id }}">
                        <div class="flex-1 min-w-40">
                            <label for="student_{{ $group->id }}"
                                   class="block text-xs font-medium text-gray-700 mb-1">
                                Alumno/a
                            </label>
                            <select name="student_id"
                                    id="student_{{ $group->id }}"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                @foreach($students as $student)
                                    <option value="{{ $student->id }}">
                                        {{ $student->last_name }}, {{ $student->first_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-1.5 rounded-lg transition-colors">
                            {{ $groupIsFull ? 'Apuntarse a lista de espera' : 'Inscribirse' }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
        @endforeach
    </div>
@endif

@endsection
