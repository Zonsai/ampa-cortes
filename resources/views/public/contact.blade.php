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
                <div class="pub-card__icon" aria-hidden="true">
                    <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="2.5" y="4.5" width="15" height="11" rx="1.5" stroke="currentColor" stroke-width="1.5"/><path d="M3 5.5l7 5.5 7-5.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <h3>Correo electrónico</h3>
                @if($contactEmail)
                    <p><a href="mailto:{{ $contactEmail }}" style="color:var(--brand-primary); font-weight:600;">{{ $contactEmail }}</a></p>
                @else
                    <p>Puedes contactar con el AMPA a través de la secretaría de {{ $schoolName }}, que hará llegar tu mensaje a la junta.</p>
                @endif
            </div>
            <div class="pub-card">
                <div class="pub-card__icon" aria-hidden="true">
                    <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 17V5.5L10 2.5l6 3V17" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M4 17h12M7.5 17v-4h5v4" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M8 8h.01M12 8h.01M8 11h.01M12 11h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                </div>
                <h3>En el centro</h3>
                <p>Encontrarás al AMPA en {{ $schoolName }}. Pregunta en conserjería por el horario de atención de la junta.</p>
            </div>
        </div>

        <div style="margin-top:28px;">
            <div class="pub-cta pub-cta--flat">
                <h2>¿Eres una familia del centro?</h2>
                <p>Gestiona extraescolares, formularios y consentimientos desde tu zona privada.</p>
                <a href="{{ url('/familia/login') }}" class="pub-btn pub-btn--primary" style="margin-top:8px;">Acceso familias</a>
            </div>
        </div>
    </div>
</section>
@endsection
