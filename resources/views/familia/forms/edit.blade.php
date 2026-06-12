@extends('familia.layouts.app')

@section('title', 'Editar respuesta — ' . $form->title)

@section('content')

<div class="mb-4">
    <a href="{{ route('familia.forms.index') }}"
       class="text-sm text-indigo-600 hover:underline">← Volver a formularios</a>
</div>

<h1 class="text-2xl font-bold text-gray-900 mb-1">{{ $form->title }}</h1>
<p class="text-sm text-gray-500 mb-5">Editando tu respuesta.</p>

<div class="bg-white rounded-xl border border-gray-200 p-6">
    <form method="POST" action="{{ route('familia.forms.update', [$form, $response]) }}">
        @csrf
        @method('PUT')

        @if($errors->has('form'))
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-red-800 text-sm">
                {{ $errors->first('form') }}
            </div>
        @endif

        @foreach($fields as $field)
            @include('familia.forms.partials.field', [
                'value' => $answersMap->get($field->id)?->decodedValue(),
            ])
        @endforeach

        <div class="mt-6 flex items-center justify-between">
            <a href="{{ route('familia.forms.index') }}"
               class="text-sm text-gray-500 hover:text-gray-700">
                Cancelar
            </a>
            <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-6 py-2 rounded-lg transition-colors">
                Guardar cambios
            </button>
        </div>
    </form>
</div>

@endsection
