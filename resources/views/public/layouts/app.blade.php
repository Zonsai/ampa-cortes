<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $branding['ampa_name'] ?? 'AMPA') — {{ $branding['ampa_name'] ?? 'AMPA' }}</title>
    <meta name="description" content="@yield('meta_description', 'Web del AMPA del colegio. Extraescolares, anuncios, formularios y zona de familias.')">
    <style>
        :root {
            --brand-primary: {{ $branding['primary_color'] ?? '#245b63' }};
            --brand-accent: {{ $branding['accent_color'] ?? '#4f7c70' }};
            --brand-warm: #d87552;
            --surface-warm: #faf8f4;
            --pub-bg: #ffffff;
            --pub-soft: #f6f7f9;
            --pub-border: #e5e7eb;
            --pub-text: #111827;
            --pub-muted: #6b7280;
            --pub-radius: 16px;
            --pub-shadow: 0 1px 2px rgba(16,24,40,.04), 0 8px 24px rgba(16,24,40,.06);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--pub-bg);
            color: var(--pub-text);
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }
        a { color: inherit; }
        .pub-container { width: 100%; max-width: 1080px; margin: 0 auto; padding: 0 20px; }

        /* Focus (DESIGN.md §21) */
        :focus-visible {
            outline: 2px solid var(--brand-primary);
            outline-offset: 2px;
            border-radius: 4px;
        }

        /* Header */
        .pub-header { position: sticky; top: 0; z-index: 30; background: rgba(255,255,255,.96); border-bottom: 1px solid var(--pub-border); }
        .pub-header__bar { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 12px 0; }
        .pub-brand { display: inline-flex; align-items: center; gap: 10px; text-decoration: none; min-width: 0; }
        .pub-brand__logo { height: 38px; width: auto; object-fit: contain; flex-shrink: 0; }
        .pub-brand__badge { display: inline-flex; align-items: center; justify-content: center; height: 38px; width: 38px; border-radius: 11px; background: var(--brand-primary); color: #fff; font-weight: 800; font-size: .8rem; flex-shrink: 0; }
        .pub-brand__text { display: flex; flex-direction: column; min-width: 0; line-height: 1.2; }
        .pub-brand__name { font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 46vw; }
        .pub-brand__sub { font-size: .76rem; color: var(--pub-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 46vw; }
        .pub-nav { display: flex; align-items: center; gap: 4px; }
        .pub-nav a { padding: 8px 12px; font-size: .92rem; font-weight: 500; color: var(--pub-muted); text-decoration: none; border-radius: 9px; }
        .pub-nav a:hover { color: var(--pub-text); background: var(--pub-soft); }
        .pub-nav a.is-active { color: var(--brand-primary); }
        .pub-actions { display: flex; align-items: center; gap: 14px; }
        .pub-link-quiet { font-size: .88rem; font-weight: 600; color: var(--pub-muted); text-decoration: none; padding: 6px 2px; }
        .pub-link-quiet:hover { color: var(--pub-text); text-decoration: underline; }

        /* Buttons */
        .pub-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; font-size: .92rem; font-weight: 600; padding: 10px 18px; border-radius: 11px; border: 1px solid transparent; text-decoration: none; cursor: pointer; transition: filter .15s, background .15s, border-color .15s; }
        .pub-btn--primary { background: var(--brand-primary); color: #fff; }
        .pub-btn--primary:hover { filter: brightness(.93); }
        .pub-btn--ghost { background: #fff; color: var(--pub-text); border-color: var(--pub-border); }
        .pub-btn--ghost:hover { border-color: #cbd5e1; background: var(--pub-soft); }
        .pub-btn--sm { padding: 7px 13px; font-size: .85rem; }

        /* Mobile nav toggle (CSS only) */
        .pub-burger { display: none; }
        @media (max-width: 860px) {
            .pub-nav, .pub-actions { display: none; }
            .pub-burger { display: inline-flex; }
            #pub-menu-toggle:checked ~ .pub-mobile { display: block; }
            #pub-menu-toggle:checked ~ .pub-header__bar .pub-burger__icon--open { display: none; }
            #pub-menu-toggle:checked ~ .pub-header__bar .pub-burger__icon--close { display: block; }
        }
        .pub-mobile { display: none; border-top: 1px solid var(--pub-border); padding: 10px 0 16px; }
        .pub-mobile a { display: block; padding: 12px 6px; text-decoration: none; color: var(--pub-text); border-radius: 8px; font-weight: 500; }
        .pub-mobile a:hover { background: var(--pub-soft); }
        .pub-mobile__actions { display: flex; flex-direction: column; gap: 8px; margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--pub-border); }
        .pub-mobile__secondary { text-align: center; }
        label.pub-burger { cursor: pointer; align-items: center; justify-content: center; width: 44px; height: 44px; padding: 0; border: 1px solid var(--pub-border); border-radius: 9px; background: #fff; color: var(--pub-text); }
        label.pub-burger svg { width: 20px; height: 20px; }
        .pub-burger__icon--close { display: none; }
        #pub-menu-toggle { display: none; }

        /* Sections */
        main { display: block; }
        .pub-section { padding: 56px 0; }
        .pub-section--soft { background: var(--pub-soft); }
        .pub-section__head { max-width: 720px; margin-bottom: 28px; }
        .pub-eyebrow { display: inline-block; font-size: .76rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--brand-primary); margin-bottom: 10px; }
        h1 { font-size: clamp(1.9rem, 4vw, 2.8rem); font-weight: 800; letter-spacing: -.02em; margin: 0 0 14px; line-height: 1.15; }
        h2 { font-size: clamp(1.4rem, 3vw, 1.9rem); font-weight: 700; letter-spacing: -.01em; margin: 0 0 12px; }
        h3 { font-size: 1.1rem; font-weight: 700; margin: 0 0 6px; }
        p { margin: 0 0 14px; }
        .pub-lead { font-size: 1.1rem; color: var(--pub-muted); }

        /* Hero */
        .pub-hero { padding: 56px 0 48px; }
        .pub-hero__grid { display: grid; grid-template-columns: 1fr; gap: 32px; align-items: center; }
        @media (min-width: 860px) { .pub-hero__grid { grid-template-columns: 1.15fr .85fr; } }
        .pub-hero__cta { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 22px; }
        .pub-chip { display: inline-flex; align-items: center; gap: 6px; font-size: .8rem; font-weight: 600; padding: 5px 12px; border-radius: 999px; background: color-mix(in srgb, var(--brand-primary) 10%, white); color: var(--brand-primary); }

        /* Hero — panel de identidad de comunidad (sin cifras/citas inventadas, ver DESIGN.md §26) */
        .pub-hero__community { background: var(--surface-warm, #faf8f4); border: 1px solid var(--pub-border); border-radius: var(--pub-radius); padding: 28px; }
        .pub-hero__community-icon { display: inline-flex; align-items: center; justify-content: center; width: 44px; height: 44px; border-radius: 12px; background: color-mix(in srgb, var(--brand-warm, #d87552) 16%, white); color: var(--brand-warm, #d87552); margin-bottom: 16px; }
        .pub-hero__community-icon svg { width: 22px; height: 22px; }
        .pub-hero__community-label { display: block; font-size: .76rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--brand-primary); margin-bottom: 10px; }
        .pub-hero__community-text { font-size: 1.05rem; font-weight: 600; color: var(--pub-text); margin: 0 0 18px; line-height: 1.45; }
        .pub-hero__community-list { list-style: none; margin: 0; padding: 14px 0 0; display: grid; gap: 10px; border-top: 1px solid var(--pub-border); }
        .pub-hero__community-list li { display: flex; align-items: center; gap: 9px; font-size: .88rem; font-weight: 500; color: var(--pub-muted); }
        .pub-hero__community-list svg { width: 16px; height: 16px; color: var(--brand-primary); flex-shrink: 0; }

        /* Cards grid */
        .pub-grid { display: grid; grid-template-columns: 1fr; gap: 18px; }
        @media (min-width: 640px) { .pub-grid--2 { grid-template-columns: repeat(2, 1fr); } }
        @media (min-width: 860px) { .pub-grid--3 { grid-template-columns: repeat(3, 1fr); } .pub-grid--4 { grid-template-columns: repeat(4, 1fr); } }
        .pub-grid--cards { grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); }
        .pub-grid--ann { grid-template-columns: repeat(auto-fit, minmax(280px, 360px)); justify-content: start; }
        .pub-card { background: #fff; border: 1px solid var(--pub-border); border-radius: var(--pub-radius); padding: 22px; box-shadow: var(--pub-shadow); }
        .pub-card__icon { display: inline-flex; align-items: center; justify-content: center; width: 42px; height: 42px; border-radius: 11px; background: color-mix(in srgb, var(--brand-primary) 12%, white); color: var(--brand-primary); font-size: 1.2rem; margin-bottom: 12px; }
        .pub-card p { color: var(--pub-muted); margin: 0; }
        .pub-card--quiet { background: var(--pub-soft); box-shadow: none; }

        /* Qué ofrecemos — bloque destacado + filas compactas (Home) */
        .pub-offer { display: grid; grid-template-columns: 1fr; gap: 20px; }
        @media (min-width: 860px) { .pub-offer { grid-template-columns: 1.1fr .9fr; align-items: stretch; } }
        .pub-offer__featured { background: #fff; border: 1px solid var(--pub-border); border-radius: var(--pub-radius); padding: 28px; box-shadow: var(--pub-shadow); }
        .pub-offer__featured-icon { display: inline-flex; align-items: center; justify-content: center; width: 48px; height: 48px; border-radius: 12px; background: color-mix(in srgb, var(--brand-primary) 12%, white); color: var(--brand-primary); margin-bottom: 16px; }
        .pub-offer__featured-icon svg { width: 24px; height: 24px; }
        .pub-offer__featured p { color: var(--pub-muted); }
        .pub-offer__list { display: flex; flex-direction: column; background: #fff; border: 1px solid var(--pub-border); border-radius: var(--pub-radius); box-shadow: var(--pub-shadow); overflow: hidden; }
        .pub-offer__row { display: flex; align-items: flex-start; gap: 14px; padding: 18px 20px; }
        .pub-offer__row + .pub-offer__row { border-top: 1px solid var(--pub-border); }
        .pub-offer__row-icon { flex: 0 0 auto; display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 10px; background: color-mix(in srgb, var(--brand-accent) 14%, white); color: var(--brand-accent); }
        .pub-offer__row-icon svg { width: 18px; height: 18px; }
        .pub-offer__row h3 { font-size: .98rem; margin: 0 0 3px; }
        .pub-offer__row p { margin: 0; font-size: .88rem; color: var(--pub-muted); }

        /* El AMPA — teaser editorial (Home) */
        .pub-about-teaser__grid { display: grid; grid-template-columns: 1fr; gap: 28px; align-items: center; }
        @media (min-width: 860px) { .pub-about-teaser__grid { grid-template-columns: .85fr 1.15fr; } }
        .pub-about-teaser__photo { aspect-ratio: 4 / 3; border: 1px dashed var(--pub-border); border-radius: var(--pub-radius); background: var(--pub-soft); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; color: var(--pub-muted); text-align: center; padding: 20px; }
        .pub-about-teaser__photo svg { width: 30px; height: 30px; }
        .pub-about-teaser__photo span { font-size: .8rem; max-width: 220px; }
        .pub-about-teaser__icon { display: inline-flex; align-items: center; justify-content: center; width: 44px; height: 44px; border-radius: 12px; background: color-mix(in srgb, var(--brand-primary) 12%, white); color: var(--brand-primary); margin-bottom: 16px; }
        .pub-about-teaser__icon svg { width: 22px; height: 22px; }
        .pub-about-teaser p { color: var(--pub-muted); font-size: 1.02rem; }

        /* Enlace con flecha — reutilizado en "Qué ofrecemos" y "El AMPA" (Home) */
        .pub-link-arrow { display: inline-flex; align-items: center; gap: 6px; font-weight: 600; color: var(--brand-primary); text-decoration: none; margin-top: 4px; }
        .pub-link-arrow:hover { text-decoration: underline; }

        /* Announcement list */
        .pub-ann { display: block; background: #fff; border: 1px solid var(--pub-border); border-radius: var(--pub-radius); padding: 20px 22px; box-shadow: var(--pub-shadow); text-decoration: none; transition: border-color .15s, box-shadow .15s; }
        .pub-ann:hover { border-color: color-mix(in srgb, var(--brand-primary) 40%, var(--pub-border)); }
        .pub-ann__meta { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 8px; }
        .pub-ann__date { font-size: .8rem; color: var(--pub-muted); }
        .pub-ann__title { font-size: 1.15rem; font-weight: 700; margin: 0 0 6px; }
        .pub-ann__summary { color: var(--pub-muted); margin: 0; }
        .pub-pin { font-size: .72rem; font-weight: 700; color: var(--brand-accent); background: color-mix(in srgb, var(--brand-accent) 12%, white); padding: 3px 9px; border-radius: 999px; }

        /* Anuncios — destacado + filas editoriales compactas (Home) */
        .pub-ann-featured { display: block; background: var(--surface-warm, #faf8f4); border: 1px solid var(--pub-border); border-radius: var(--pub-radius); padding: 24px 26px; text-decoration: none; margin-bottom: 20px; }
        .pub-ann-featured:hover .pub-ann-featured__title { text-decoration: underline; }
        .pub-ann-featured__meta { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
        .pub-ann-featured__pin { display: inline-flex; align-items: center; gap: 5px; font-size: .72rem; font-weight: 700; color: var(--brand-primary); text-transform: uppercase; letter-spacing: .04em; }
        .pub-ann-featured__pin svg { width: 14px; height: 14px; color: var(--brand-warm); }
        .pub-ann-featured__date { font-size: .8rem; color: var(--pub-muted); }
        .pub-ann-featured__title { font-size: 1.25rem; font-weight: 700; margin: 0 0 6px; color: var(--pub-text); }
        .pub-ann-featured__summary { margin: 0; color: var(--pub-muted); }
        .pub-ann-rows { display: flex; flex-direction: column; background: #fff; border: 1px solid var(--pub-border); border-radius: var(--pub-radius); overflow: hidden; }
        .pub-ann-row { display: flex; align-items: baseline; gap: 14px; padding: 16px 20px; text-decoration: none; color: inherit; }
        .pub-ann-row + .pub-ann-row { border-top: 1px solid var(--pub-border); }
        .pub-ann-row:hover .pub-ann-row__title { text-decoration: underline; }
        .pub-ann-row__date { flex: 0 0 auto; font-size: .78rem; color: var(--pub-muted); min-width: 9ch; }
        .pub-ann-row__title { font-weight: 600; font-size: .96rem; }

        /* Article */
        .pub-article { max-width: 760px; }
        .pub-article__content { white-space: pre-wrap; font-size: 1.02rem; color: #374151; }

        /* Prose */
        .pub-prose { max-width: 760px; }
        .pub-prose p { color: #374151; }
        .pub-prose ul { color: #374151; padding-left: 20px; }

        /* Empty state */
        .pub-empty { background: #fff; border: 1px dashed var(--pub-border); border-radius: var(--pub-radius); padding: 40px 20px; text-align: center; color: var(--pub-muted); }

        /* Back link */
        .pub-back { display: inline-flex; align-items: center; gap: 6px; color: var(--brand-primary); text-decoration: none; font-weight: 600; font-size: .9rem; margin-bottom: 16px; }
        .pub-back:hover { text-decoration: underline; }

        /* CTA banner */
        .pub-cta { background: linear-gradient(135deg, var(--brand-primary), color-mix(in srgb, var(--brand-primary) 70%, var(--brand-accent))); color: #fff; border-radius: var(--pub-radius); padding: 44px 36px; text-align: center; box-shadow: 0 20px 45px -20px color-mix(in srgb, var(--brand-primary) 55%, transparent); }
        .pub-cta h2 { color: #fff; }
        .pub-cta p { color: rgba(255,255,255,.85); }
        .pub-cta .pub-btn--ghost { background: rgba(255,255,255,.12); color: #fff; border-color: rgba(255,255,255,.35); }
        .pub-cta .pub-btn--primary { background: #fff; color: var(--brand-primary); }
        .pub-cta--flat { background: var(--surface-warm, #faf8f4); color: var(--pub-text); box-shadow: none; border: 1px solid var(--pub-border); }
        .pub-cta--flat h2 { color: var(--pub-text); }
        .pub-cta--flat p { color: var(--pub-muted); }
        .pub-cta--flat .pub-link-quiet { color: var(--pub-muted); }
        .pub-cta--flat .pub-btn--primary { background: var(--brand-primary); color: #fff; }

        /* Footer */
        .pub-footer { border-top: 1px solid var(--pub-border); border-bottom: 3px solid var(--brand-warm, #d87552); background: var(--pub-soft); padding: 36px 0; margin-top: 24px; }
        .pub-footer__grid { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 20px; }
        .pub-footer__links { display: flex; flex-wrap: wrap; gap: 16px; }
        .pub-footer__links a { color: var(--pub-muted); text-decoration: none; font-size: .9rem; }
        .pub-footer__links a:hover { color: var(--pub-text); }
        .pub-footer__copy { font-size: .82rem; color: var(--pub-muted); margin-top: 16px; }
    </style>
</head>
<body>
    @php
        $ampaName = $branding['ampa_name'] ?? 'AMPA';
        $schoolName = $branding['school_name'] ?? '';
        $ampaLogo = ($branding['ampa_logo_path'] ?? null)
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($branding['ampa_logo_path'])
            : null;
    @endphp

    <header class="pub-header">
        <div class="pub-container">
            <input type="checkbox" id="pub-menu-toggle">
            <div class="pub-header__bar">
                <a href="{{ route('public.home') }}" class="pub-brand">
                    @if($ampaLogo)
                        <img src="{{ $ampaLogo }}" alt="Logo {{ $ampaName }}" class="pub-brand__logo">
                    @else
                        <span class="pub-brand__badge">{{ mb_strtoupper(mb_substr($ampaName, 0, 2)) }}</span>
                    @endif
                    <span class="pub-brand__text">
                        <span class="pub-brand__name">{{ $ampaName }}</span>
                        @if($schoolName)<span class="pub-brand__sub">{{ $schoolName }}</span>@endif
                    </span>
                </a>

                <nav class="pub-nav">
                    <a href="{{ route('public.home') }}" @class(['is-active' => request()->routeIs('public.home')])>Inicio</a>
                    <a href="{{ route('public.about') }}" @class(['is-active' => request()->routeIs('public.about')])>El AMPA</a>
                    <a href="{{ route('public.extracurriculars') }}" @class(['is-active' => request()->routeIs('public.extracurriculars')])>Extraescolares</a>
                    <a href="{{ route('public.announcements.index') }}" @class(['is-active' => request()->routeIs('public.announcements.*')])>Anuncios</a>
                    <a href="{{ route('public.contact') }}" @class(['is-active' => request()->routeIs('public.contact')])>Contacto</a>
                </nav>

                <div class="pub-actions">
                    <a href="{{ url('/admin') }}" class="pub-link-quiet">Gestión AMPA</a>
                    <a href="{{ url('/familia/login') }}" class="pub-btn pub-btn--primary pub-btn--sm">Acceso familias</a>
                </div>

                <label for="pub-menu-toggle" class="pub-burger" aria-label="Abrir menú">
                    <svg class="pub-burger__icon--open" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M3 6h14M3 10h14M3 14h14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                    <svg class="pub-burger__icon--close" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M5 5l10 10M15 5L5 15" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                </label>
            </div>

            <div class="pub-mobile">
                <a href="{{ route('public.home') }}">Inicio</a>
                <a href="{{ route('public.about') }}">El AMPA</a>
                <a href="{{ route('public.extracurriculars') }}">Extraescolares</a>
                <a href="{{ route('public.announcements.index') }}">Anuncios</a>
                <a href="{{ route('public.contact') }}">Contacto</a>
                <div class="pub-mobile__actions">
                    <a href="{{ url('/familia/login') }}" class="pub-btn pub-btn--primary pub-btn--sm">Acceso familias</a>
                    <a href="{{ url('/admin') }}" class="pub-link-quiet pub-mobile__secondary">Gestión AMPA</a>
                </div>
            </div>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <footer class="pub-footer">
        <div class="pub-container">
            <div class="pub-footer__grid">
                <div>
                    <div class="pub-brand__name">{{ $ampaName }}</div>
                    @if($schoolName)<div class="pub-brand__sub">{{ $schoolName }}</div>@endif
                </div>
                <div class="pub-footer__links">
                    <a href="{{ route('public.about') }}">El AMPA</a>
                    <a href="{{ route('public.extracurriculars') }}">Extraescolares</a>
                    <a href="{{ route('public.announcements.index') }}">Anuncios</a>
                    <a href="{{ route('public.contact') }}">Contacto</a>
                    <a href="{{ url('/familia/login') }}">Acceso familias</a>
                </div>
            </div>
            <div class="pub-footer__copy">© {{ date('Y') }} {{ $ampaName }} — Zona pública</div>
        </div>
    </footer>
</body>
</html>
