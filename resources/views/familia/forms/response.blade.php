@extends('familia.layouts.app')

@section('title', $form->title . ' — Respuesta')

@section('content')

<a href="{{ route('familia.forms.index') }}" class="family-back-link">← Volver a formularios</a>

<div class="family-page-header">
    <h1>{{ $form->title }}</h1>
    @if($form->description)
        <p>{{ $form->description }}</p>
    @endif
</div>

{{-- Metadatos de la respuesta --}}
<div class="fam-row-wrap" style="gap: 8px; margin-bottom: 18px;">
    @if($response->student)
        <span class="family-chip">Alumno/a: {{ $response->student->first_name }} {{ $response->student->last_name }}</span>
    @endif
    @if($response->submitted_at)
        <span class="family-chip">Enviado el {{ $response->submitted_at->format('d/m/Y') }} a las {{ $response->submitted_at->format('H:i') }}</span>
    @endif
    @if($response->updated_at && $response->updated_at->gt($response->submitted_at ?? $response->created_at))
        <span class="family-chip family-chip--ampa">Última modificación: {{ $response->updated_at->format('d/m/Y H:i') }}</span>
    @endif
</div>

{{-- Respuestas --}}
<div class="family-card">
    @foreach($fields as $field)
        @if($field->type === \App\Enums\FormFieldType::InfoText)
            <div class="family-dl__row">
                <div class="family-alert family-alert--info" style="margin: 0;">
                    <span>
                        {{ $field->label }}
                        @if($field->description)
                            <span class="fam-muted" style="display: block; margin-top: 4px; font-size: .8rem;">{{ $field->description }}</span>
                        @endif
                    </span>
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
            <div class="family-dl__row">
                <div class="family-dl__term">{{ $field->label }}</div>
                <div class="family-dl__desc {{ $displayValue === '—' ? 'family-dl__desc--empty' : '' }}">{{ $displayValue }}</div>
            </div>
        @endif
    @endforeach
</div>

{{-- Enlace a editar si aplica --}}
@if($form->allow_edit && $form->isOpenNow())
    <div class="family-actions" style="margin-top: 18px;">
        <a href="{{ route('familia.forms.edit', [$form, $response]) }}" class="family-btn family-btn--primary">Editar respuesta</a>
    </div>
@endif

@endsection
