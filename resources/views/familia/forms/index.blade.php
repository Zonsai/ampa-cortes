@extends('familia.layouts.app')

@section('title', 'Formularios')

@section('content')

<h1 class="text-2xl font-bold text-gray-900 mb-6">Formularios</h1>

{{-- Formularios pendientes de respuesta --}}
<section class="mb-8">
    <h2 class="text-base font-semibold text-gray-700 mb-3">Pendientes de respuesta</h2>

    @if($pendingForms->isEmpty())
        <div class="bg-white rounded-xl border border-gray-200 px-6 py-8 text-center text-gray-400 text-sm">
            No tienes formularios pendientes.
        </div>
    @else
        <div class="space-y-3">
            @foreach($pendingForms as $form)
                <a href="{{ route('familia.forms.show', $form) }}"
                   class="block bg-white rounded-xl border border-gray-200 hover:border-indigo-300 hover:shadow-sm transition px-5 py-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-gray-900">{{ $form->title }}</div>
                            @if($form->description)
                                <p class="text-sm text-gray-500 mt-0.5 truncate">{{ $form->description }}</p>
                            @endif
                            @if($form->closes_at)
                                <p class="text-xs text-amber-600 mt-1">
                                    Cierra el {{ $form->closes_at->format('d/m/Y') }}
                                    a las {{ $form->closes_at->format('H:i') }}
                                </p>
                            @endif
                        </div>
                        <span class="shrink-0 text-xs px-2.5 py-0.5 rounded-full bg-indigo-100 text-indigo-700 font-medium">
                            Pendiente
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</section>

{{-- Formularios ya respondidos (formulario aún abierto) --}}
@if($respondedOpenForms->isNotEmpty())
    <section class="mb-8">
        <h2 class="text-base font-semibold text-gray-700 mb-3">Respondidos (abiertos)</h2>
        <div class="space-y-3">
            @foreach($respondedOpenForms as $form)
                @php $formResponses = $responsesByFormId->get($form->id, collect()) @endphp
                <div class="bg-white rounded-xl border border-gray-200 px-5 py-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-gray-900">{{ $form->title }}</div>
                            @if($form->closes_at)
                                <p class="text-xs text-gray-400 mt-1">
                                    Cierra el {{ $form->closes_at->format('d/m/Y') }}
                                </p>
                            @endif
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="text-xs px-2.5 py-0.5 rounded-full bg-green-100 text-green-700 font-medium">
                                Respondido
                            </span>
                            @if($formResponses->count() === 1)
                                <a href="{{ route('familia.forms.responses.show', [$form, $formResponses->first()]) }}"
                                   class="text-xs text-gray-600 hover:underline">
                                    Ver respuesta
                                </a>
                                @if($form->allow_edit)
                                    <a href="{{ route('familia.forms.edit', [$form, $formResponses->first()]) }}"
                                       class="text-xs text-indigo-600 hover:underline">
                                        Editar respuesta
                                    </a>
                                @endif
                            @elseif($formResponses->count() > 1)
                                <a href="{{ route('familia.forms.show', $form) }}"
                                   class="text-xs text-gray-600 hover:underline">
                                    Ver respuestas
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif

{{-- Formularios cerrados con respuesta --}}
@if($closedRespondedForms->isNotEmpty())
    <section>
        <h2 class="text-base font-semibold text-gray-700 mb-3">Cerrados</h2>
        <div class="space-y-3">
            @foreach($closedRespondedForms as $form)
                @php $formResponses = $responsesByFormId->get($form->id, collect()) @endphp
                <div class="bg-white rounded-xl border border-gray-100 px-5 py-4 opacity-75">
                    <div class="flex items-start justify-between gap-3">
                        <div class="font-medium text-gray-700">{{ $form->title }}</div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="text-xs px-2.5 py-0.5 rounded-full bg-gray-100 text-gray-500">
                                Cerrado
                            </span>
                            @if($formResponses->count() === 1)
                                <a href="{{ route('familia.forms.responses.show', [$form, $formResponses->first()]) }}"
                                   class="text-xs text-gray-500 hover:underline">
                                    Ver respuesta
                                </a>
                            @elseif($formResponses->count() > 1)
                                <a href="{{ route('familia.forms.show', $form) }}"
                                   class="text-xs text-gray-500 hover:underline">
                                    Ver respuestas
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif

@endsection
