@extends('public.layouts.app')

@section('title', 'El AMPA')
@section('meta_description', 'Qué es el AMPA, para qué sirve y cómo ayuda a las familias del centro.')

@section('content')
@php $ampaName = $branding['ampa_name'] ?? 'AMPA'; $schoolName = $branding['school_name'] ?? 'el centro'; @endphp

<section class="pub-section">
    <div class="pub-container">
        <div class="pub-section__head">
            <span class="pub-eyebrow">Quiénes somos</span>
            <h1>El AMPA</h1>
            <p class="pub-lead">{{ $ampaName }} es la Asociación de Madres y Padres del Alumnado de {{ $schoolName }}.</p>
        </div>

        <div class="pub-grid pub-grid--2" style="margin-bottom:18px;">
            <div class="pub-card">
                <h2>¿Qué es el AMPA?</h2>
                <p>El AMPA es una asociación sin ánimo de lucro formada por las familias del alumnado del centro. Trabajamos de forma voluntaria para mejorar la experiencia educativa de nuestros hijos e hijas y para representar la voz de las familias ante el colegio.</p>
            </div>
            <div class="pub-card">
                <h2>¿Para qué sirve?</h2>
                <p>Canalizamos las inquietudes de las familias, colaboramos con el equipo docente y organizamos servicios y actividades que complementan la vida escolar. Buscamos un colegio más participativo, abierto y cercano.</p>
            </div>
        </div>

        <div class="pub-section__head" style="margin-top:36px;">
            <span class="pub-eyebrow">En el día a día</span>
            <h2>Cómo ayudamos a las familias</h2>
        </div>
        <div class="pub-offer__list" style="margin-bottom:18px;">
            <div class="pub-offer__row">
                <span class="pub-offer__row-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 13.5L6 20l6-2.5L18 20l-3-6.5" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><circle cx="12" cy="8.5" r="5.5" stroke="currentColor" stroke-width="1.6"/><path d="M12 6v5M9.8 8.5h4.4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                </span>
                <div>
                    <h3>Actividades extraescolares</h3>
                    <p>Gestión de <strong>actividades extraescolares</strong> y sus inscripciones.</p>
                </div>
            </div>
            <div class="pub-offer__row">
                <span class="pub-offer__row-icon" aria-hidden="true">
                    <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 2.5h7l3 3v12h-10v-15z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M7.5 9h5M7.5 12h5M7.5 15h3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </span>
                <div>
                    <h3>Formularios</h3>
                    <p>Recogida de <strong>autorizaciones y formularios</strong> de forma sencilla.</p>
                </div>
            </div>
            <div class="pub-offer__row">
                <span class="pub-offer__row-icon" aria-hidden="true">
                    <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 2.5l6 2.2v4.3c0 4-2.6 6.7-6 8-3.4-1.3-6-4-6-8V4.7l6-2.2z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M7.5 10l1.8 1.8L12.8 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <div>
                    <h3>Consentimientos</h3>
                    <p>Gestión de <strong>consentimientos</strong> con su historial y trazabilidad.</p>
                </div>
            </div>
            <div class="pub-offer__row">
                <span class="pub-offer__row-icon" aria-hidden="true">
                    <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 5.5A1.5 1.5 0 0 1 4.5 4h11A1.5 1.5 0 0 1 17 5.5v7A1.5 1.5 0 0 1 15.5 14H8l-3.5 3v-3H4.5A1.5 1.5 0 0 1 3 12.5v-7z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>
                </span>
                <div>
                    <h3>Comunicación</h3>
                    <p><strong>Comunicación</strong> de novedades a través del tablón de anuncios.</p>
                </div>
            </div>
        </div>

        <div class="pub-grid pub-grid--2" style="margin-top:18px;">
            <div class="pub-card pub-card--quiet">
                <h3>Gestión de extraescolares</h3>
                <p>Coordinamos con proveedores y el centro la oferta de actividades extraescolares de cada curso, gestionando grupos, plazas y la comunicación con las familias.</p>
            </div>
            <div class="pub-card pub-card--quiet">
                <h3>Comunicación y participación</h3>
                <p>Tu participación cuenta. Hazte socio/a, propón ideas y colabora en las actividades. Cuantas más familias participamos, más cosas podemos lograr para nuestros hijos e hijas.</p>
            </div>
        </div>

        <div style="margin-top:32px;">
            <div class="pub-cta pub-cta--flat">
                <h2>¿Quieres saber más?</h2>
                <p>Accede a tu zona privada o ponte en contacto con la junta del AMPA.</p>
                <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap; margin-top:8px;">
                    <a href="{{ url('/familia/login') }}" class="pub-btn pub-btn--primary">Acceso familias</a>
                    <a href="{{ route('public.contact') }}" class="pub-btn pub-btn--ghost">Contacta con el AMPA</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
