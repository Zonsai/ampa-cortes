@extends('familia.layouts.app')

@section('title', $response->consentType->name)

@section('content')
<div class="mb-4">
    <a href="{{ route('familia.consents.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">
        ← Volver a consentimientos
    </a>
</div>

<h1 class="text-2xl font-bold text-gray-900 mb-6">{{ $response->consentType->name }}</h1>

{{-- Aviso de revocación --}}
@if($response->status === \App\Enums\ConsentResponseStatus::Revoked)
    <div class="mb-6 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-amber-800 text-sm">
        Has revocado este consentimiento. A partir de ese momento no se considera autorizado.
    </div>
@endif

{{-- Información del consentimiento --}}
<div class="bg-white rounded-xl border border-gray-200 px-5 py-4 mb-6 space-y-3">
    <div>
        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Finalidad</span>
        <p class="text-gray-800 mt-0.5">{{ $response->consentType->purpose }}</p>
    </div>

    <div>
        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Ámbito</span>
        <p class="text-gray-800 mt-0.5">{{ $response->consentType->scope->getLabel() }}</p>
    </div>

    @if($response->student)
    <div>
        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Alumno/a</span>
        <p class="text-gray-800 mt-0.5">{{ $response->student->full_name }}</p>
    </div>
    @endif

    <div>
        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Estado actual</span>
        <div class="mt-1">
            @if($response->status === \App\Enums\ConsentResponseStatus::Pending)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">Pendiente</span>
            @elseif($response->status === \App\Enums\ConsentResponseStatus::Accepted)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Aceptado</span>
            @elseif($response->status === \App\Enums\ConsentResponseStatus::Rejected)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Rechazado</span>
            @elseif($response->status === \App\Enums\ConsentResponseStatus::Revoked)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Revocado</span>
            @endif
            @if($response->responded_at)
                <span class="ml-2 text-sm text-gray-400">{{ $response->responded_at->format('d/m/Y H:i') }}</span>
            @endif
        </div>
    </div>
</div>

{{-- Resumen --}}
@if($response->consentVersion?->summary)
<div class="bg-white rounded-xl border border-gray-200 px-5 py-4 mb-6">
    <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wide mb-2">Resumen</h2>
    <p class="text-gray-700 text-sm">{{ $response->consentVersion->summary }}</p>
</div>
@endif

{{-- Texto legal completo --}}
@if($response->consentVersion?->legal_text)
<div class="bg-white rounded-xl border border-gray-200 px-5 py-4 mb-6">
    <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wide mb-2">Texto completo</h2>
    <div class="text-gray-700 text-sm whitespace-pre-wrap">{{ $response->consentVersion->legal_text }}</div>
</div>
@endif

{{-- Historial --}}
@if($response->histories->isNotEmpty())
<div class="bg-white rounded-xl border border-gray-200 px-5 py-4 mb-6">
    <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wide mb-3">Historial</h2>
    <ul class="space-y-2">
        @foreach($response->histories as $history)
            <li class="text-sm text-gray-600">
                <span class="text-gray-400">{{ $history->created_at->format('d/m/Y H:i') }}</span>
                — {{ $history->event_type->getLabel() }}
                @if($history->performedBy)
                    <span class="text-gray-400">({{ $history->performedBy->name }})</span>
                @endif
            </li>
        @endforeach
    </ul>
</div>
@endif

{{-- Acciones --}}
@if($response->status === \App\Enums\ConsentResponseStatus::Pending)
<div class="flex gap-3">
    <form method="POST" action="{{ route('familia.consents.accept', $response) }}">
        @csrf
        <button type="submit"
                class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors">
            Aceptar
        </button>
    </form>

    @if($response->consentType->is_rejectable)
    <form method="POST" action="{{ route('familia.consents.reject', $response) }}">
        @csrf
        <button type="submit"
                class="px-4 py-2 rounded-lg bg-red-100 text-red-700 text-sm font-medium hover:bg-red-200 transition-colors">
            Rechazar
        </button>
    </form>
    @endif
</div>
@endif

@if($response->status === \App\Enums\ConsentResponseStatus::Accepted && $response->consentType->is_revocable)
<form method="POST" action="{{ route('familia.consents.revoke', $response) }}">
    @csrf
    <button type="submit"
            class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200 transition-colors">
        Revocar consentimiento
    </button>
</form>
@endif

@endsection
