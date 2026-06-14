@extends('familia.layouts.app')

@section('title', 'Inicio')

@section('content')

<h1 class="text-2xl font-bold text-gray-900 mb-1">
    Hola, familia {{ $family->name }}
</h1>
<p class="text-gray-500 text-sm mb-6">
    Bienvenidos al portal de familias del AMPA.
    @if($family->is_ampa_member)
        <span class="inline-flex items-center ml-2 bg-green-100 text-green-800 text-xs font-medium px-2 py-0.5 rounded-full">
            Socios AMPA
        </span>
    @else
        <span class="inline-flex items-center ml-2 bg-gray-100 text-gray-600 text-xs font-medium px-2 py-0.5 rounded-full">
            No socios
        </span>
    @endif
</p>

{{-- Avisos calculados --}}
@if($waitlistCount > 0 || $pendingPaymentCount > 0 || $pendingConsentsCount > 0)
<div class="mb-6 space-y-2">
    @if($waitlistCount > 0)
        <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-amber-800 text-sm">
            <strong>Lista de espera:</strong>
            tienes {{ $waitlistCount }} {{ $waitlistCount === 1 ? 'inscripción' : 'inscripciones' }} en lista de espera.
            El AMPA te contactará si queda una plaza libre.
        </div>
    @endif
    @if($pendingPaymentCount > 0)
        <div class="rounded-lg bg-blue-50 border border-blue-200 px-4 py-3 text-blue-800 text-sm">
            <strong>Pendiente de pago:</strong>
            tienes {{ $pendingPaymentCount }} {{ $pendingPaymentCount === 1 ? 'inscripción' : 'inscripciones' }}
            pendiente{{ $pendingPaymentCount === 1 ? '' : 's' }} de pago.
            Contacta con el AMPA para completarla.
        </div>
    @endif
    @if($pendingConsentsCount > 0)
        <div class="rounded-lg bg-indigo-50 border border-indigo-200 px-4 py-3 text-indigo-800 text-sm">
            <strong>Consentimientos pendientes:</strong>
            tienes {{ $pendingConsentsCount }} {{ $pendingConsentsCount === 1 ? 'consentimiento' : 'consentimientos' }}
            pendiente{{ $pendingConsentsCount === 1 ? '' : 's' }} de revisar.
            <a href="{{ route('familia.consents.index') }}" class="underline font-medium">Ver consentimientos →</a>
        </div>
    @endif
</div>
@endif

{{-- Resumen en tarjetas --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="text-3xl font-bold text-indigo-600">{{ $students->count() }}</div>
        <div class="text-sm text-gray-600 mt-1">
            {{ $students->count() === 1 ? 'Alumno/a' : 'Alumnos/as' }}
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="text-3xl font-bold text-green-600">{{ $enrolledCount }}</div>
        <div class="text-sm text-gray-600 mt-1">
            {{ $enrolledCount === 1 ? 'Inscripción activa' : 'Inscripciones activas' }}
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="text-3xl font-bold {{ $waitlistCount > 0 ? 'text-amber-500' : 'text-gray-300' }}">
            {{ $waitlistCount }}
        </div>
        <div class="text-sm text-gray-600 mt-1">En lista de espera</div>
    </div>
</div>

{{-- Inscripciones activas --}}
@if($activeEnrollments->count() > 0)
<h2 class="text-base font-semibold text-gray-800 mb-3">Inscripciones y solicitudes</h2>
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-6">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="text-left px-4 py-2 font-medium text-gray-600">Actividad</th>
                <th class="text-left px-4 py-2 font-medium text-gray-600">Alumno/a</th>
                <th class="text-left px-4 py-2 font-medium text-gray-600">Estado</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach($activeEnrollments as $enrollment)
            <tr>
                <td class="px-4 py-3">
                    <div class="font-medium">{{ $enrollment->activity->name }}</div>
                    <div class="text-xs text-gray-500">{{ $enrollment->activityGroup->name }}</div>
                </td>
                <td class="px-4 py-3 text-gray-700">
                    {{ $enrollment->student->full_name ?? '—' }}
                </td>
                <td class="px-4 py-3">
                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium
                        @if($enrollment->status->value === 'enrolled' || $enrollment->status->value === 'paid') bg-green-100 text-green-800
                        @elseif($enrollment->status->value === 'waitlist') bg-amber-100 text-amber-800
                        @elseif($enrollment->status->value === 'pending_payment') bg-blue-100 text-blue-800
                        @else bg-gray-100 text-gray-700
                        @endif">
                        {{ $enrollment->status->getLabel() }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Accesos rápidos --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <a href="{{ route('familia.children') }}"
       class="block bg-white rounded-xl border border-gray-200 p-4 hover:border-indigo-300 hover:shadow-sm transition-all group">
        <div class="font-medium text-gray-800 group-hover:text-indigo-700">Mis hijos/as →</div>
        <div class="text-sm text-gray-500 mt-1">Ver alumnos, clases e inscripciones por alumno</div>
    </a>
    <a href="{{ route('familia.activities.index') }}"
       class="block bg-white rounded-xl border border-gray-200 p-4 hover:border-indigo-300 hover:shadow-sm transition-all group">
        <div class="font-medium text-gray-800 group-hover:text-indigo-700">Extraescolares →</div>
        <div class="text-sm text-gray-500 mt-1">Ver actividades disponibles y solicitar inscripción</div>
    </a>
    <a href="{{ route('familia.forms.index') }}"
       class="block bg-white rounded-xl border border-gray-200 p-4 hover:border-indigo-300 hover:shadow-sm transition-all group">
        <div class="font-medium text-gray-800 group-hover:text-indigo-700">Formularios →</div>
        <div class="text-sm text-gray-500 mt-1">Consultar y responder formularios del AMPA</div>
    </a>
    <a href="{{ route('familia.consents.index') }}"
       class="block bg-white rounded-xl border border-gray-200 p-4 hover:border-indigo-300 hover:shadow-sm transition-all group">
        <div class="font-medium text-gray-800 group-hover:text-indigo-700">Consentimientos →</div>
        <div class="text-sm text-gray-500 mt-1">Revisar y gestionar los consentimientos de tu familia</div>
    </a>
</div>

@endsection
