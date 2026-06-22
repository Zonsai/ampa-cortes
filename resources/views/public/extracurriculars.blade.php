@extends('public.layouts.app')

@section('title', 'Extraescolares')
@section('meta_description', 'Información pública sobre las actividades extraescolares organizadas por el AMPA.')

@section('content')
<section class="pub-section">
    <div class="pub-container">
        <div class="pub-section__head">
            <span class="pub-eyebrow">Curso actual</span>
            <h1>Actividades extraescolares</h1>
            <p class="pub-lead">El AMPA organiza cada curso una oferta de actividades extraescolares para complementar la formación del alumnado. La inscripción se realiza desde la zona privada de familias.</p>
        </div>

        @if($activeYear && $activities->isNotEmpty())
            <div class="pub-grid pub-grid--3">
                @foreach($activities as $activity)
                    <div class="pub-card">
                        <h3>{{ $activity->name }}</h3>
                        @if($activity->short_description)
                            <p>{{ \Illuminate\Support\Str::limit($activity->short_description, 130) }}</p>
                        @endif
                        @if($activity->requires_ampa_membership)
                            <div style="margin-top:10px;"><span class="pub-chip">Solo socios/as AMPA</span></div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="pub-empty">
                La oferta de extraescolares del curso se publicará próximamente.
            </div>
        @endif

        <div style="margin-top:32px;">
            <div class="pub-cta">
                <h2>¿Quieres inscribir a tu hijo/a?</h2>
                <p>La inscripción en extraescolares se realiza desde la zona privada de familias.</p>
                <a href="{{ url('/familia/login') }}" class="pub-btn pub-btn--primary" style="margin-top:8px;">Acceso familias</a>
            </div>
        </div>
    </div>
</section>
@endsection
