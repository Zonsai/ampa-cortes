@extends('familia.layouts.app')

@section('title', $form->title)

@section('content')

<div class="mb-4">
    <a href="{{ route('familia.forms.index') }}"
       class="text-sm text-indigo-600 hover:underline">← Volver a formularios</a>
</div>

<h1 class="text-2xl font-bold text-gray-900 mb-1">{{ $form->title }}</h1>

@if($form->description)
    <p class="text-gray-600 text-sm mb-4">{{ $form->description }}</p>
@endif

@if($form->closes_at && $form->isOpenNow())
    <p class="text-xs text-amber-600 mb-5">
        Este formulario cierra el {{ $form->closes_at->format('d/m/Y') }}
        a las {{ $form->closes_at->format('H:i') }}.
    </p>
@endif

@if($form->response_scope === \App\Enums\FormResponseScope::PerFamily)

    @php $familyKey = 'family:' . $family->id; $existingResponse = $responses->get($familyKey); @endphp

    @if($existingResponse)
        <div class="mb-5 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-green-800 text-sm">
            Ya has enviado una respuesta para este formulario.
            @if($form->allow_edit && $form->isOpenNow())
                <a href="{{ route('familia.forms.edit', [$form, $existingResponse]) }}"
                   class="ml-2 font-medium text-green-700 hover:underline">
                    Editar respuesta →
                </a>
            @endif
        </div>
    @elseif(! $form->isOpenNow())
        <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-amber-800 text-sm">
            Este formulario ya está cerrado y no admite nuevas respuestas.
        </div>
    @else
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <form method="POST" action="{{ route('familia.forms.submit', $form) }}">
                @csrf

                @if($errors->has('form'))
                    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-red-800 text-sm">
                        {{ $errors->first('form') }}
                    </div>
                @endif

                @foreach($fields as $field)
                    @include('familia.forms.partials.field', ['value' => null])
                @endforeach

                <div class="mt-6 flex justify-end">
                    <button type="submit"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-6 py-2 rounded-lg transition-colors">
                        Enviar respuesta
                    </button>
                </div>
            </form>
        </div>
    @endif

@else

    {{-- Per-student scope --}}
    @php
        $answeredStudentIds = $responses->keys()
            ->map(fn ($key) => (int) str_replace('student:', '', $key))
            ->filter()
            ->values();
        $unansweredStudents = $eligibleStudents->filter(fn ($s) => ! $answeredStudentIds->contains($s->id));
        $answeredStudents = $eligibleStudents->filter(fn ($s) => $answeredStudentIds->contains($s->id));
    @endphp

    {{-- Already responded --}}
    @if($answeredStudents->isNotEmpty())
        <div class="mb-5 space-y-2">
            @foreach($answeredStudents as $student)
                @php $studentResponse = $responses->get('student:' . $student->id); @endphp
                <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-green-800 text-sm flex items-center justify-between">
                    <span>
                        <strong>{{ $student->first_name }} {{ $student->last_name }}</strong>
                        ya ha respondido este formulario.
                    </span>
                    @if($form->allow_edit && $form->isOpenNow() && $studentResponse)
                        <a href="{{ route('familia.forms.edit', [$form, $studentResponse]) }}"
                           class="ml-3 font-medium text-green-700 hover:underline shrink-0">
                            Editar →
                        </a>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- Form for unanswered students --}}
    @if($unansweredStudents->isEmpty())
        <div class="rounded-lg bg-gray-50 border border-gray-200 px-4 py-6 text-center text-gray-500 text-sm">
            Todos los alumnos/as elegibles ya han respondido este formulario.
        </div>
    @elseif(! $form->isOpenNow())
        <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-amber-800 text-sm">
            Este formulario ya está cerrado y no admite nuevas respuestas.
        </div>
    @else
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <form method="POST" action="{{ route('familia.forms.submit', $form) }}">
                @csrf

                @if($errors->has('form') || $errors->has('student_id'))
                    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-red-800 text-sm">
                        {{ $errors->first('form') ?: $errors->first('student_id') }}
                    </div>
                @endif

                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Alumno/a <span class="text-red-500 ml-0.5">*</span>
                    </label>
                    <select name="student_id"
                            class="w-full rounded-lg border {{ $errors->has('student_id') ? 'border-red-400' : 'border-gray-300' }} px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">— Selecciona un alumno/a —</option>
                        @foreach($unansweredStudents as $student)
                            <option value="{{ $student->id }}" @selected(old('student_id') == $student->id)>
                                {{ $student->last_name }}, {{ $student->first_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @foreach($fields as $field)
                    @include('familia.forms.partials.field', ['value' => null])
                @endforeach

                <div class="mt-6 flex justify-end">
                    <button type="submit"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-6 py-2 rounded-lg transition-colors">
                        Enviar respuesta
                    </button>
                </div>
            </form>
        </div>
    @endif

@endif

@endsection
