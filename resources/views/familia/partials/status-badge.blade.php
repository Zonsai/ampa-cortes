{{--
    Badge de estado de inscripción unificado para la zona familiar.
    Espera: $status (App\Enums\EnrollmentStatus)
--}}
@php
    $famClass = match ($status) {
        \App\Enums\EnrollmentStatus::Enrolled, \App\Enums\EnrollmentStatus::Paid => 'status-success',
        \App\Enums\EnrollmentStatus::Waitlist => 'status-warning',
        \App\Enums\EnrollmentStatus::PendingPayment => 'status-info',
        \App\Enums\EnrollmentStatus::Pending => 'status-pending',
        default => 'status-muted',
    };
    $famLabel = match ($status) {
        \App\Enums\EnrollmentStatus::Pending => 'Solicitud enviada',
        \App\Enums\EnrollmentStatus::Paid => 'Pago registrado',
        default => $status->getLabel(),
    };
@endphp
<span class="family-status-badge {{ $famClass }}">{{ $famLabel }}</span>
