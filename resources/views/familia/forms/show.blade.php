@extends('familia.layouts.app')

@section('title', $form->title)

@section('content')

<a href="{{ route('familia.forms.index') }}" class="family-back-link">← Volver a formularios</a>

<div class="family-page-header">
    <h1>{{ $form->title }}</h1>
    @if($form->description)
        <p>{{ $form->description }}</p>
    @endif
</div>

@if($form->closes_at && $form->isOpenNow())
    <p class="fam-muted" style="font-size: .8rem; margin: 0 0 16px; color: #b45309;">
        Este formulario cierra el {{ $form->closes_at->format('d/m/Y') }} a las {{ $form->closes_at->format('H:i') }}.
    </p>
@endif

@if($form->response_scope === \App\Enums\FormResponseScope::PerFamily)

    @php $familyKey = 'family:' . $family->id; $existingResponse = $responses->get($familyKey); @endphp

    @if($existingResponse)
        <div class="family-card">
            <div class="family-card__body fam-row-wrap" style="justify-content: space-between;">
                <span class="family-status-badge status-success">Ya has enviado una respuesta</span>
                <span class="fam-row-wrap" style="gap: 16px;">
                    <a href="{{ route('familia.forms.responses.show', [$form, $existingResponse]) }}" class="family-link">Ver respuesta →</a>
                    @if($form->allow_edit && $form->isOpenNow())
                        <a href="{{ route('familia.forms.edit', [$form, $existingResponse]) }}" class="family-link">Editar →</a>
                    @endif
                </span>
            </div>
        </div>
    @elseif(! $form->isOpenNow())
        <div class="family-alert family-alert--warning">
            <span>Este formulario ya está cerrado y no admite nuevas respuestas.</span>
        </div>
    @else
        <div class="family-card">
            <div class="family-card__body">
                <form method="POST" action="{{ route('familia.forms.submit', $form) }}">
                    @csrf

                    @if($errors->has('form'))
                        <div class="family-flash family-flash--error">{{ $errors->first('form') }}</div>
                    @endif

                    @foreach($fields as $field)
                        @include('familia.forms.partials.field', ['value' => null])
                    @endforeach

                    <div class="family-actions" style="justify-content: flex-end; margin-top: 18px;">
                        <button type="submit" class="family-btn family-btn--primary">Enviar respuesta</button>
                    </div>
                </form>
            </div>
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

    {{-- Ya respondidos --}}
    @if($answeredStudents->isNotEmpty())
        <div class="family-card-list fam-stack" style="margin-bottom: 18px;">
            @foreach($answeredStudents as $student)
                @php $studentResponse = $responses->get('student:' . $student->id); @endphp
                <div class="family-card">
                    <div class="family-card__body fam-row-wrap" style="justify-content: space-between;">
                        <span>
                            <span class="family-status-badge status-success">Respondido</span>
                            <strong style="margin-left: 8px;">{{ $student->first_name }} {{ $student->last_name }}</strong>
                        </span>
                        <span class="fam-row-wrap" style="gap: 16px;">
                            @if($studentResponse)
                                <a href="{{ route('familia.forms.responses.show', [$form, $studentResponse]) }}" class="family-link">Ver respuesta →</a>
                            @endif
                            @if($form->allow_edit && $form->isOpenNow() && $studentResponse)
                                <a href="{{ route('familia.forms.edit', [$form, $studentResponse]) }}" class="family-link">Editar →</a>
                            @endif
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Formulario para alumnos/as sin responder --}}
    @if($unansweredStudents->isEmpty())
        <div class="family-empty-state">
            <div class="family-empty-state__title">Todos los alumnos/as elegibles ya han respondido este formulario.</div>
        </div>
    @elseif(! $form->isOpenNow())
        <div class="family-alert family-alert--warning">
            <span>Este formulario ya está cerrado y no admite nuevas respuestas.</span>
        </div>
    @else
        <div class="family-card">
            <div class="family-card__body">
                <form method="POST" action="{{ route('familia.forms.submit', $form) }}">
                    @csrf

                    @if($errors->has('form') || $errors->has('student_id'))
                        <div class="family-flash family-flash--error">{{ $errors->first('form') ?: $errors->first('student_id') }}</div>
                    @endif

                    <div class="family-field">
                        <label class="family-label">Alumno/a <span class="req">*</span></label>
                        <select name="student_id" class="family-input @if($errors->has('student_id')) family-input--error @endif">
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

                    <div class="family-actions" style="justify-content: flex-end; margin-top: 18px;">
                        <button type="submit" class="family-btn family-btn--primary">Enviar respuesta</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

@endif

@endsection
