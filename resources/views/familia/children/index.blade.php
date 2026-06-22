@extends('familia.layouts.app')

@section('title', 'Mis hijos/as')

@section('content')

<div class="family-page-header">
    <h1>Mis hijos/as</h1>
    <p>Alumnos/as de tu familia, su clase e inscripciones de este curso.</p>
</div>

@if($students->isEmpty())
    <div class="family-empty-state">
        <div class="family-empty-state__title">No hay alumnos/as registrados/as en tu familia</div>
        <p>Contacta con el AMPA si crees que es un error.</p>
    </div>
@else
    <div class="family-card-list fam-stack">
        @foreach($students as $student)
        @php
            $classroom = $student->classrooms->first();
            $studentEnrollments = $student->enrollments
                ->whereIn('status', [
                    \App\Enums\EnrollmentStatus::Pending,
                    \App\Enums\EnrollmentStatus::Waitlist,
                    \App\Enums\EnrollmentStatus::Enrolled,
                    \App\Enums\EnrollmentStatus::PendingPayment,
                    \App\Enums\EnrollmentStatus::Paid,
                ]);
        @endphp
        <div class="family-card">
            <div class="family-card__header">
                <div>
                    <div class="family-card__title">{{ $student->first_name }} {{ $student->last_name }}</div>
                    @if($classroom)
                        <div class="family-card__sub">
                            {{ $classroom->grade?->name ?? '—' }}@if($classroom->name) · {{ $classroom->name }}@endif
                        </div>
                    @else
                        <div class="family-card__sub">
                            <span class="family-chip">Sin clase asignada</span>
                        </div>
                    @endif
                </div>
                @unless($student->is_active)
                    <span class="family-status-badge status-muted">Inactivo</span>
                @endunless
            </div>

            <div class="family-card__body">
                @if($studentEnrollments->isEmpty())
                    <p class="fam-muted" style="font-size: .85rem; margin: 0;">
                        Sin inscripciones activas.
                        <a href="{{ route('familia.activities.index') }}" class="family-link">Ver extraescolares</a>
                    </p>
                @else
                    <div class="family-child-enrollments">
                        @foreach($studentEnrollments as $enrollment)
                        <div class="family-child-enrollment">
                            <div class="family-child-enrollment__info">
                                <span class="family-child-enrollment__name">{{ $enrollment->activity->name ?? '—' }}</span>
                                <span class="family-child-enrollment__group">{{ $enrollment->activityGroup->name ?? '—' }}</span>
                            </div>
                            <div class="family-child-enrollment__status">
                                @include('familia.partials.status-badge', ['status' => $enrollment->status])
                                @if(in_array($enrollment->status, [\App\Enums\EnrollmentStatus::Pending, \App\Enums\EnrollmentStatus::Waitlist]))
                                    <form method="POST"
                                          action="{{ route('familia.enrollment.cancel', $enrollment) }}"
                                          onsubmit="return confirm('¿Seguro que quieres cancelar esta solicitud?')">
                                        @csrf
                                        <button type="submit" class="family-link-danger">Cancelar solicitud</button>
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
