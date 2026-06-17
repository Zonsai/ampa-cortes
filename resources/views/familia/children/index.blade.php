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
        <div class="family-card">
            <div class="family-card__header">
                <div>
                    <div class="family-card__title">{{ $student->last_name }}, {{ $student->first_name }}</div>
                    @php $classroom = $student->classrooms->first(); @endphp
                    @if($classroom)
                        <div class="family-card__sub">
                            {{ $classroom->grade?->name ?? '—' }}@if($classroom->name) — {{ $classroom->name }}@endif
                        </div>
                    @else
                        <div class="family-card__sub">Sin clase asignada este curso</div>
                    @endif
                </div>
                @unless($student->is_active)
                    <span class="family-status-badge status-muted">Inactivo</span>
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
                <div class="family-card__body fam-muted" style="font-size: .85rem;">Sin inscripciones activas.</div>
            @else
                @foreach($studentEnrollments as $enrollment)
                <div class="family-row">
                    <div class="family-row__main">
                        <div class="family-row__title">{{ $enrollment->activity->name ?? '—' }}</div>
                        <div class="family-row__sub">{{ $enrollment->activityGroup->name ?? '—' }}</div>
                    </div>
                    <div class="family-row__side family-row__side--stack">
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
            @endif
        </div>
        @endforeach
    </div>
@endif

@endsection
