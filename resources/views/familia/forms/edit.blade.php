@extends('familia.layouts.app')

@section('title', 'Editar respuesta — ' . $form->title)

@section('content')

<a href="{{ route('familia.forms.index') }}" class="family-back-link">← Volver a formularios</a>

<div class="family-page-header">
    <h1>{{ $form->title }}</h1>
    <p>Editando tu respuesta.</p>
</div>

<div class="family-card">
    <div class="family-card__body">
        <form method="POST" action="{{ route('familia.forms.update', [$form, $response]) }}">
            @csrf
            @method('PUT')

            @if($errors->has('form'))
                <div class="family-flash family-flash--error">{{ $errors->first('form') }}</div>
            @endif

            @foreach($fields as $field)
                @include('familia.forms.partials.field', [
                    'value' => $answersMap->get($field->id)?->decodedValue(),
                ])
            @endforeach

            <div class="family-actions" style="justify-content: space-between; margin-top: 18px;">
                <a href="{{ route('familia.forms.index') }}" class="family-link" style="color: var(--fam-muted);">Cancelar</a>
                <button type="submit" class="family-btn family-btn--primary">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

@endsection
