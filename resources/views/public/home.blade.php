@extends('public.layouts.app')

@section('title', 'Inicio')

@section('content')
@php
    $ampaName = $branding['ampa_name'] ?? 'AMPA';
    $schoolName = $branding['school_name'] ?? '';
@endphp

<section class="pub-hero">
    <div class="pub-container pub-hero__grid">
        <div>
            <span class="pub-eyebrow">{{ $ampaName }}</span>
            @if($schoolName)
                <h1>Familias unidas por la educación en {{ $schoolName }}</h1>
                <p class="pub-lead">Organizamos extraescolares, gestionamos formularios y consentimientos, y mantenemos a las familias del {{ $schoolName }} siempre informadas.</p>
            @else
                <h1>Familias unidas por la educación de nuestros hijos e hijas</h1>
                <p class="pub-lead">Organizamos extraescolares, gestionamos formularios y consentimientos, y mantenemos a las familias siempre informadas.</p>
            @endif
            <div class="pub-hero__cta">
                <a href="{{ url('/familia/login') }}" class="pub-btn pub-btn--primary">Acceso familias</a>
                <a href="{{ route('public.about') }}" class="pub-btn pub-btn--ghost">Conoce el AMPA</a>
            </div>
        </div>
        <div class="pub-hero__panel">
            <span class="pub-hero__panel-label">Qué hacemos</span>
            <ul class="pub-hero__list">
                <li><span>🎨</span> Extraescolares</li>
                <li><span>📝</span> Formularios y autorizaciones</li>
                <li><span>✅</span> Consentimientos</li>
                <li><span>📣</span> Comunicación con familias</li>
            </ul>
        </div>
    </div>
</section>

<section class="pub-section pub-section--soft">
    <div class="pub-container">
        <div class="pub-section__head">
            <span class="pub-eyebrow">Qué ofrecemos</span>
            <h2>Todo lo que gestiona el AMPA, en un solo lugar</h2>
        </div>
        <div class="pub-grid pub-grid--4">
            <div class="pub-card">
                <div class="pub-card__icon">🎨</div>
                <h3>Extraescolares</h3>
                <p>Organizamos y coordinamos las actividades extraescolares del curso.</p>
            </div>
            <div class="pub-card">
                <div class="pub-card__icon">📝</div>
                <h3>Formularios y autorizaciones</h3>
                <p>Recogemos autorizaciones y preferencias de forma sencilla y segura.</p>
            </div>
            <div class="pub-card">
                <div class="pub-card__icon">✅</div>
                <h3>Consentimientos</h3>
                <p>Gestión clara de consentimientos, con su historial y trazabilidad.</p>
            </div>
            <div class="pub-card">
                <div class="pub-card__icon">📣</div>
                <h3>Comunicación con familias</h3>
                <p>Anuncios y novedades para mantener a las familias informadas.</p>
            </div>
        </div>
    </div>
</section>

<section class="pub-section">
    <div class="pub-container">
        <div class="pub-section__head" style="display:flex; align-items:flex-end; justify-content:space-between; gap:16px; max-width:none;">
            <div>
                <span class="pub-eyebrow">Tablón</span>
                <h2 style="margin:0;">Últimos anuncios</h2>
            </div>
            <a href="{{ route('public.announcements.index') }}" class="pub-btn pub-btn--ghost pub-btn--sm">Ver todos →</a>
        </div>

        @if($announcements->isEmpty())
            <div class="pub-empty">Aún no hay anuncios publicados. Vuelve pronto.</div>
        @else
            <div class="pub-grid pub-grid--ann">
                @foreach($announcements as $announcement)
                    @include('public.announcements._card', ['announcement' => $announcement])
                @endforeach
            </div>
        @endif
    </div>
</section>

<section class="pub-section pub-section--soft">
    <div class="pub-container">
        <div class="pub-cta">
            <h2>¿Eres una familia del centro?</h2>
            <p>Accede a tu zona privada para gestionar extraescolares, formularios y consentimientos.</p>
            <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap; margin-top:8px;">
                <a href="{{ url('/familia/login') }}" class="pub-btn pub-btn--primary">Acceso familias</a>
                <a href="{{ route('public.contact') }}" class="pub-btn pub-btn--ghost">Contacta con el AMPA</a>
            </div>
        </div>
    </div>
</section>
@endsection
