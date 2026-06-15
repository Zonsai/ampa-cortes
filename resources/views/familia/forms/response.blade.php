@extends('familia.layouts.app')

@section('title', $form->title . ' — Respuesta')

@section('content')

<div class="mb-4">
    <a href="{{ route('familia.forms.index') }}"
       class="text-sm text-indigo-600 hover:underline">← Volver a formularios</a>
</div>

<h1 class="text-2xl font-bold text-gray-900 mb-1">{{ $form->title }}</h1>

@if($form->description)
    <p class="text-gray-600 text-sm mb-4">{{ $form->description }}</p>
@endif

{{-- Metadatos de la respuesta --}}
<div class="mb-6 flex flex-wrap gap-4 text-sm text-gray-500">
    @if($response->student)
        <span>
            <span class="font-medium text-gray-700">Alumno/a:</span>
            {{ $response->student->first_name }} {{ $response->student->last_name }}
        </span>
    @endif
    @if($response->submitted_at)
        <span>
            <span class="font-medium text-gray-700">Enviado el:</span>
            {{ $response->submitted_at->format('d/m/Y') }}
            a las {{ $response->submitted_at->format('H:i') }}
        </span>
    @endif
    @if($response->updated_at && $response->updated_at->gt($response->submitted_at ?? $response->created_at))
        <span class="text-amber-600">
            Última modificación: {{ $response->updated_at->format('d/m/Y H:i') }}
        </span>
    @endif
</div>

{{-- Respuestas --}}
<div class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100">
    @foreach($fields as $field)
        @if($field->type === \App\Enums\FormFieldType::InfoText)
            {{-- InfoText: mostrar como bloque informativo de contexto --}}
            <div class="px-5 py-4">
                <div class="rounded-lg bg-blue-50 border border-blue-200 px-4 py-3 text-sm text-blue-800">
                    {{ $field->label }}
                    @if($field->description)
                        <p class="mt-1 text-xs text-blue-600">{{ $field->description }}</p>
                    @endif
                </div>
            </div>
        @else
            @php
                $answer = $answersMap->get($field->id);
                $rawValue = $answer?->value;

                if ($rawValue === null || $rawValue === '') {
                    $displayValue = '—';
                } elseif ($field->type === \App\Enums\FormFieldType::Checkboxes) {
                    $decoded = json_decode($rawValue, true);
                    $displayValue = is_array($decoded) && count($decoded) > 0
                        ? implode(', ', $decoded)
                        : '—';
                } elseif ($field->type === \App\Enums\FormFieldType::YesNo) {
                    $displayValue = match ($rawValue) {
                        '1', 1, true => 'Sí',
                        '0', 0, false => 'No',
                        default => $rawValue,
                    };
                } elseif ($field->type === \App\Enums\FormFieldType::Checkbox) {
                    $displayValue = ($rawValue === '1' || $rawValue === 1) ? 'Sí' : 'No';
                } else {
                    $displayValue = $rawValue;
                }
            @endphp
            <div class="px-5 py-4">
                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">
                    {{ $field->label }}
                </dt>
                <dd class="text-sm text-gray-900 {{ $displayValue === '—' ? 'text-gray-400 italic' : '' }}">
                    {{ $displayValue }}
                </dd>
            </div>
        @endif
    @endforeach
</div>

{{-- Enlace a editar si aplica --}}
@if($form->allow_edit && $form->isOpenNow())
    <div class="mt-5">
        <a href="{{ route('familia.forms.edit', [$form, $response]) }}"
           class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">
            Editar respuesta
        </a>
    </div>
@endif

@endsection
