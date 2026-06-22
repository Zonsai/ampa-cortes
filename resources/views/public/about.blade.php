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

        <div class="pub-prose">
            <h2>¿Qué es el AMPA?</h2>
            <p>El AMPA es una asociación sin ánimo de lucro formada por las familias del alumnado del centro. Trabajamos de forma voluntaria para mejorar la experiencia educativa de nuestros hijos e hijas y para representar la voz de las familias ante el colegio.</p>

            <h2>¿Para qué sirve?</h2>
            <p>Canalizamos las inquietudes de las familias, colaboramos con el equipo docente y organizamos servicios y actividades que complementan la vida escolar. Buscamos un colegio más participativo, abierto y cercano.</p>

            <h2>Cómo ayudamos a las familias</h2>
            <ul>
                <li>Gestión de <strong>actividades extraescolares</strong> y sus inscripciones.</li>
                <li>Recogida de <strong>autorizaciones y formularios</strong> de forma sencilla.</li>
                <li>Gestión de <strong>consentimientos</strong> con su historial y trazabilidad.</li>
                <li><strong>Comunicación</strong> de novedades a través del tablón de anuncios.</li>
            </ul>

            <h2>Gestión de extraescolares</h2>
            <p>Coordinamos con proveedores y el centro la oferta de actividades extraescolares de cada curso, gestionando grupos, plazas y la comunicación con las familias.</p>

            <h2>Comunicación y participación</h2>
            <p>Tu participación cuenta. Hazte socio/a, propón ideas y colabora en las actividades. Cuantas más familias participamos, más cosas podemos lograr para nuestros hijos e hijas.</p>

            <div style="margin-top:24px; display:flex; gap:12px; flex-wrap:wrap;">
                <a href="{{ url('/familia/login') }}" class="pub-btn pub-btn--primary">Acceso familias</a>
                <a href="{{ route('public.contact') }}" class="pub-btn pub-btn--ghost">Contacta con el AMPA</a>
            </div>
        </div>
    </div>
</section>
@endsection
