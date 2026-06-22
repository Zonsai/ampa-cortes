@extends('public.layouts.app')

@section('title', 'Contacto')
@section('meta_description', 'Cómo contactar con el AMPA del centro.')

@section('content')
@php
    $ampaName = $branding['ampa_name'] ?? 'AMPA';
    $schoolName = $branding['school_name'] ?? 'el centro';
    $contactEmail = $branding['contact_email'] ?? null;
@endphp

<section class="pub-section">
    <div class="pub-container">
        <div class="pub-section__head">
            <span class="pub-eyebrow">Hablemos</span>
            <h1>Contacto</h1>
            <p class="pub-lead">¿Tienes una consulta o quieres colaborar con {{ $ampaName }}? Estaremos encantados de ayudarte.</p>
        </div>

        <div class="pub-grid pub-grid--2">
            <div class="pub-card">
                <div class="pub-card__icon">✉️</div>
                <h3>Correo electrónico</h3>
                @if($contactEmail)
                    <p><a href="mailto:{{ $contactEmail }}" style="color:var(--brand-primary); font-weight:600;">{{ $contactEmail }}</a></p>
                @else
                    <p>Puedes contactar con el AMPA a través de la secretaría de {{ $schoolName }}, que hará llegar tu mensaje a la junta.</p>
                @endif
            </div>
            <div class="pub-card">
                <div class="pub-card__icon">🏫</div>
                <h3>En el centro</h3>
                <p>Encontrarás al AMPA en {{ $schoolName }}. Pregunta en conserjería por el horario de atención de la junta.</p>
            </div>
        </div>

        <div style="margin-top:28px;">
            <div class="pub-cta">
                <h2>¿Eres una familia del centro?</h2>
                <p>Gestiona extraescolares, formularios y consentimientos desde tu zona privada.</p>
                <a href="{{ url('/familia/login') }}" class="pub-btn pub-btn--primary" style="margin-top:8px;">Acceso familias</a>
            </div>
        </div>
    </div>
</section>
@endsection
