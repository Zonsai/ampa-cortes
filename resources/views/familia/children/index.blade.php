@extends('familia.layouts.app')

@section('title', 'Mis hijos/as')

@section('content')

<h1 class="text-2xl font-bold text-gray-900 mb-6">Mis hijos/as</h1>

@if($students->isEmpty())
    <div class="bg-white rounded-xl border border-gray-200 px-6 py-10 text-center text-gray-500">
        No hay alumnos/as registrados/as en tu familia.<br>
        Contacta con el AMPA si crees que es un error.
    </div>
@else
    <div class="space-y-6">
        @foreach($students as $student)
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-start justify-between">
                <div>
                    <div class="font-semibold text-gray-900">
                        {{ $student->last_name }}, {{ $student->first_name }}
                    </div>
                    @php $classroom = $student->classrooms->first(); @endphp
                    @if($classroom)
                        <div class="text-sm text-gray-500 mt-0.5">
                            {{ $classroom->grade?->name ?? '—' }}
                            @if($classroom->name)
                                — {{ $classroom->name }}
                            @endif
                        </div>
                    @else
                        <div class="text-sm text-gray-400 mt-0.5">Sin clase asignada este curso</div>
                    @endif
                </div>
                @unless($student->is_active)
                    <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">Inactivo</span>
                @endunless
            </div>

            {{-- Inscripciones de este alumno --}}
            @php
                $studentEnrollments = $student->enrollments
                    ->whereIn('status', [
                        \App\Enums\EnrollmentStatus::Pending,
                        \App\Enums\EnrollmentStatus::Waitlist,
                        \App\Enums\EnrollmentStatus::Enrolled,
                        \App\Enums\EnrollmentStatus::PendingPayment,
                        \App\Enums\EnrollmentStatus::Paid,
                    ]);
            @endphp

            @if($studentEnrollments->isEmpty())
                <div class="px-5 py-4 text-sm text-gray-400">Sin inscripciones activas.</div>
            @else
                <ul class="divide-y divide-gray-50">
                    @foreach($studentEnrollments as $enrollment)
                    <li class="px-5 py-3 flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <div class="font-medium text-sm text-gray-800 truncate">
                                {{ $enrollment->activity->name ?? '—' }}
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ $enrollment->activityGroup->name ?? '—' }}
                            </div>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium
                                @if($enrollment->status === \App\Enums\EnrollmentStatus::Enrolled || $enrollment->status === \App\Enums\EnrollmentStatus::Paid) bg-green-100 text-green-800
                                @elseif($enrollment->status === \App\Enums\EnrollmentStatus::Waitlist) bg-amber-100 text-amber-800
                                @elseif($enrollment->status === \App\Enums\EnrollmentStatus::PendingPayment) bg-blue-100 text-blue-800
                                @else bg-gray-100 text-gray-700
                                @endif">
                                {{ $enrollment->status->getLabel() }}
                            </span>

                            @if(in_array($enrollment->status, [\App\Enums\EnrollmentStatus::Pending, \App\Enums\EnrollmentStatus::Waitlist]))
                                <form method="POST"
                                      action="{{ route('familia.enrollment.cancel', $enrollment) }}"
                                      onsubmit="return confirm('¿Seguro que quieres cancelar esta solicitud?')">
                                    @csrf
                                    <button type="submit"
                                            class="text-xs text-red-500 hover:text-red-700 hover:underline transition-colors">
                                        Cancelar solicitud
                                    </button>
                                </form>
                            @endif
                        </div>
                    </li>
                    @endforeach
                </ul>
            @endif
        </div>
        @endforeach
    </div>
@endif

@endsection
