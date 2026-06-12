@extends('familia.layouts.app')

@section('title', 'Consentimientos')

@section('content')
<h1 class="text-2xl font-bold text-gray-900 mb-6">Consentimientos</h1>

{{-- Pendientes --}}
<section class="mb-8">
    <h2 class="text-lg font-semibold text-gray-700 mb-3">Pendientes</h2>

    @if($pending->isEmpty())
        <p class="text-gray-400 text-sm">No tienes consentimientos pendientes.</p>
    @else
        <div class="space-y-3">
            @foreach($pending as $response)
                <div class="bg-white rounded-xl border border-gray-200 px-5 py-4 flex items-center justify-between gap-4">
                    <div>
                        <p class="font-medium text-gray-900">{{ $response->consentType->name }}</p>
                        @if($response->student)
                            <p class="text-sm text-gray-500">Alumno/a: {{ $response->student->full_name }}</p>
                        @endif
                        <p class="text-sm text-gray-500">{{ $response->consentType->purpose }}</p>
                        <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                            Pendiente
                        </span>
                    </div>
                    <a href="{{ route('familia.consents.show', $response) }}"
                       class="shrink-0 text-sm font-medium text-indigo-600 hover:text-indigo-800">
                        Ver detalle →
                    </a>
                </div>
            @endforeach
        </div>
    @endif
</section>

{{-- Aceptados --}}
@if($accepted->isNotEmpty())
<section class="mb-8">
    <h2 class="text-lg font-semibold text-gray-700 mb-3">Aceptados</h2>
    <div class="space-y-3">
        @foreach($accepted as $response)
            <div class="bg-white rounded-xl border border-gray-200 px-5 py-4 flex items-center justify-between gap-4">
                <div>
                    <p class="font-medium text-gray-900">{{ $response->consentType->name }}</p>
                    @if($response->student)
                        <p class="text-sm text-gray-500">Alumno/a: {{ $response->student->full_name }}</p>
                    @endif
                    <p class="text-sm text-gray-500">{{ $response->consentType->purpose }}</p>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                            Aceptado
                        </span>
                        @if($response->responded_at)
                            <span class="text-xs text-gray-400">{{ $response->responded_at->format('d/m/Y') }}</span>
                        @endif
                    </div>
                </div>
                <a href="{{ route('familia.consents.show', $response) }}"
                   class="shrink-0 text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    Ver detalle →
                </a>
            </div>
        @endforeach
    </div>
</section>
@endif

{{-- Rechazados --}}
@if($rejected->isNotEmpty())
<section class="mb-8">
    <h2 class="text-lg font-semibold text-gray-700 mb-3">Rechazados</h2>
    <div class="space-y-3">
        @foreach($rejected as $response)
            <div class="bg-white rounded-xl border border-gray-200 px-5 py-4 flex items-center justify-between gap-4">
                <div>
                    <p class="font-medium text-gray-900">{{ $response->consentType->name }}</p>
                    @if($response->student)
                        <p class="text-sm text-gray-500">Alumno/a: {{ $response->student->full_name }}</p>
                    @endif
                    <p class="text-sm text-gray-500">{{ $response->consentType->purpose }}</p>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                            Rechazado
                        </span>
                        @if($response->responded_at)
                            <span class="text-xs text-gray-400">{{ $response->responded_at->format('d/m/Y') }}</span>
                        @endif
                    </div>
                </div>
                <a href="{{ route('familia.consents.show', $response) }}"
                   class="shrink-0 text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    Ver detalle →
                </a>
            </div>
        @endforeach
    </div>
</section>
@endif

{{-- Revocados --}}
@if($revoked->isNotEmpty())
<section class="mb-8">
    <h2 class="text-lg font-semibold text-gray-700 mb-3">Revocados</h2>
    <div class="space-y-3">
        @foreach($revoked as $response)
            <div class="bg-white rounded-xl border border-gray-200 px-5 py-4 flex items-center justify-between gap-4">
                <div>
                    <p class="font-medium text-gray-900">{{ $response->consentType->name }}</p>
                    @if($response->student)
                        <p class="text-sm text-gray-500">Alumno/a: {{ $response->student->full_name }}</p>
                    @endif
                    <p class="text-sm text-gray-500">{{ $response->consentType->purpose }}</p>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">
                            Revocado
                        </span>
                        @if($response->revoked_at)
                            <span class="text-xs text-gray-400">{{ $response->revoked_at->format('d/m/Y') }}</span>
                        @endif
                    </div>
                </div>
                <a href="{{ route('familia.consents.show', $response) }}"
                   class="shrink-0 text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    Ver detalle →
                </a>
            </div>
        @endforeach
    </div>
</section>
@endif

@endsection
