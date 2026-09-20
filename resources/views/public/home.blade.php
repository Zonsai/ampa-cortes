@extends('public.layouts.app')

@section('title', 'Inicio')

@section('content')
@php
    $ampaName = $branding['ampa_name'] ?? 'AMPA';
    $schoolName = $branding['school_name'] ?? '';
    $featuredAnnouncement = $announcements->first();
    $otherAnnouncements = $featuredAnnouncement ? $announcements->slice(1) : collect();
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

        <div class="pub-hero__community">
            <span class="pub-hero__community-icon" aria-hidden="true">
                <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="7" cy="7" r="2.3" stroke="currentColor" stroke-width="1.5"/><circle cx="14" cy="8" r="1.8" stroke="currentColor" stroke-width="1.5"/><path d="M2.8 16c0-2.5 1.9-4.2 4.2-4.2s4.2 1.7 4.2 4.2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M12 12.4c1.8 0 3.4 1.4 3.6 3.6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
            </span>
            <span class="pub-hero__community-label">Comunidad educativa</span>
            <p class="pub-hero__community-text">Somos familias del centro trabajando de forma voluntaria por la educación de nuestros hijos e hijas.</p>
            <ul class="pub-hero__community-list">
                <li>
                    <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M4 10l4 4 8-8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Gestión abierta y cercana con las familias
                </li>
                <li>
                    <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M4 10l4 4 8-8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Participación voluntaria de madres y padres
                </li>
                <li>
                    <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M4 10l4 4 8-8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Al servicio del centro y del alumnado
                </li>
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

        <div class="pub-offer">
            <div class="pub-offer__featured">
                <span class="pub-offer__featured-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 13.5L6 20l6-2.5L18 20l-3-6.5" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><circle cx="12" cy="8.5" r="5.5" stroke="currentColor" stroke-width="1.6"/><path d="M12 6v5M9.8 8.5h4.4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                </span>
                <h3>Extraescolares</h3>
                <p>Organizamos y coordinamos las actividades extraescolares del curso: grupos, horarios e inscripciones desde tu zona de familias.</p>
                <a href="{{ route('public.extracurriculars') }}" class="pub-link-arrow">Ver extraescolares →</a>
            </div>

            <div class="pub-offer__list">
                <div class="pub-offer__row">
                    <span class="pub-offer__row-icon" aria-hidden="true">
                        <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 2.5h7l3 3v12h-10v-15z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M7.5 9h5M7.5 12h5M7.5 15h3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                    </span>
                    <div>
                        <h3>Formularios y autorizaciones</h3>
                        <p>Recogemos autorizaciones y preferencias de forma sencilla y segura.</p>
                    </div>
                </div>
                <div class="pub-offer__row">
                    <span class="pub-offer__row-icon" aria-hidden="true">
                        <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 2.5l6 2.2v4.3c0 4-2.6 6.7-6 8-3.4-1.3-6-4-6-8V4.7l6-2.2z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M7.5 10l1.8 1.8L12.8 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <div>
                        <h3>Consentimientos</h3>
                        <p>Gestión clara de consentimientos, con su historial y trazabilidad.</p>
                    </div>
                </div>
                <div class="pub-offer__row">
                    <span class="pub-offer__row-icon" aria-hidden="true">
                        <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 5.5A1.5 1.5 0 0 1 4.5 4h11A1.5 1.5 0 0 1 17 5.5v7A1.5 1.5 0 0 1 15.5 14H8l-3.5 3v-3H4.5A1.5 1.5 0 0 1 3 12.5v-7z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>
                    </span>
                    <div>
                        <h3>Comunicación con familias</h3>
                        <p>Anuncios y novedades para mantener a las familias informadas.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="pub-section">
    <div class="pub-container pub-about-teaser">
        <div class="pub-about-teaser__grid">
            <div class="pub-about-teaser__photo">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.5"/><circle cx="9" cy="11" r="2" stroke="currentColor" stroke-width="1.5"/><path d="M3 16l4.5-4 3 2.5L15 10l6 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span>Fotografía de la comunidad del AMPA — pendiente de asset real</span>
            </div>
            <div>
                <span class="pub-about-teaser__icon" aria-hidden="true">
                    <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="7" cy="7" r="2.3" stroke="currentColor" stroke-width="1.5"/><circle cx="14" cy="8" r="1.8" stroke="currentColor" stroke-width="1.5"/><path d="M2.8 16c0-2.5 1.9-4.2 4.2-4.2s4.2 1.7 4.2 4.2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M12 12.4c1.8 0 3.4 1.4 3.6 3.6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </span>
                <span class="pub-eyebrow">Quiénes somos</span>
                <h2>El AMPA</h2>
                <p>{{ $ampaName }} es una asociación sin ánimo de lucro formada por las familias del alumnado{{ $schoolName ? " de {$schoolName}" : '' }}. Trabajamos de forma voluntaria para mejorar la experiencia educativa de nuestros hijos e hijas y para representar la voz de las familias ante el colegio.</p>
                <a href="{{ route('public.about') }}" class="pub-link-arrow">Conoce el AMPA →</a>
            </div>
        </div>
    </div>
</section>

<section class="pub-section pub-section--soft">
    <div class="pub-container">
        <div class="pub-section__head" style="display:flex; align-items:flex-end; justify-content:space-between; gap:16px; max-width:none;">
            <div>
                <span class="pub-eyebrow">Actualidad</span>
                <h2 style="margin:0;">Últimos anuncios</h2>
            </div>
            <a href="{{ route('public.announcements.index') }}" class="pub-btn pub-btn--ghost pub-btn--sm">Ver todos →</a>
        </div>

        @if($announcements->isEmpty())
            <div class="pub-empty">Aún no hay anuncios publicados. Vuelve pronto.</div>
        @else
            @if($featuredAnnouncement->is_pinned)
                <a href="{{ route('public.announcements.show', $featuredAnnouncement) }}" class="pub-ann-featured">
                    <div class="pub-ann-featured__meta">
                        <span class="pub-ann-featured__pin">
                            <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M10 2.5v3M6 12.5h8l-1.3-4.2a2 2 0 0 0-1.9-1.4h-1.6a2 2 0 0 0-1.9 1.4L6 12.5z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M10 12.5v5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
                            Destacado
                        </span>
                        <span class="pub-ann-featured__date">{{ optional($featuredAnnouncement->published_at)->translatedFormat('d \d\e F \d\e Y') }}</span>
                    </div>
                    <h3 class="pub-ann-featured__title">{{ $featuredAnnouncement->title }}</h3>
                    @if($featuredAnnouncement->summary)
                        <p class="pub-ann-featured__summary">{{ \Illuminate\Support\Str::limit($featuredAnnouncement->summary, 160) }}</p>
                    @endif
                </a>
                @if($otherAnnouncements->isNotEmpty())
                    <div class="pub-ann-rows">
                        @foreach($otherAnnouncements as $announcement)
                            <a href="{{ route('public.announcements.show', $announcement) }}" class="pub-ann-row">
                                <span class="pub-ann-row__date">{{ optional($announcement->published_at)->translatedFormat('d/m/Y') }}</span>
                                <span class="pub-ann-row__title">{{ $announcement->title }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            @else
                <div class="pub-ann-rows">
                    @foreach($announcements as $announcement)
                        <a href="{{ route('public.announcements.show', $announcement) }}" class="pub-ann-row">
                            <span class="pub-ann-row__date">{{ optional($announcement->published_at)->translatedFormat('d/m/Y') }}</span>
                            <span class="pub-ann-row__title">{{ $announcement->title }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
</section>

<section class="pub-section">
    <div class="pub-container">
        <div class="pub-cta pub-cta--flat">
            <h2>¿Eres una familia del centro?</h2>
            <p>Accede a tu zona privada para gestionar extraescolares, formularios y consentimientos.</p>
            <div style="display:flex; flex-direction:column; align-items:center; gap:10px; margin-top:8px;">
                <a href="{{ url('/familia/login') }}" class="pub-btn pub-btn--primary">Acceso familias</a>
                <a href="{{ route('public.contact') }}" class="pub-link-quiet">Contacta con el AMPA</a>
            </div>
        </div>
    </div>
</section>
@endsection
