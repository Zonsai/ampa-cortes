<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portal Familias') — {{ $branding['ampa_name'] ?? 'AMPA' }}</title>
    <style>
        :root {
            --brand-primary: {{ $branding['primary_color'] ?? '#4f46e5' }};
            --brand-accent: {{ $branding['accent_color'] ?? '#0f766e' }};
            --fam-bg: #f4f5f7;
            --fam-surface: #ffffff;
            --fam-border: #e5e7eb;
            --fam-text: #111827;
            --fam-muted: #6b7280;
            --fam-muted-2: #9ca3af;
            --fam-radius: 14px;
            --fam-radius-sm: 10px;
            --fam-shadow: 0 1px 2px rgba(16,24,40,.04), 0 1px 3px rgba(16,24,40,.05);
            --fam-shadow-hover: 0 6px 18px rgba(16,24,40,.09);
        }

        * { box-sizing: border-box; }

        body.family-body {
            margin: 0;
            min-height: 100vh;
            background: var(--fam-bg);
            color: var(--fam-text);
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
            line-height: 1.5;
        }

        .family-container { width: 100%; max-width: 1040px; margin: 0 auto; padding: 0 16px; }

        /* Header */
        .family-header { background: var(--fam-surface); border-bottom: 1px solid var(--fam-border); position: sticky; top: 0; z-index: 30; }
        .family-header__bar { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 0; }
        .family-brand { display: flex; align-items: center; gap: 10px; min-width: 0; text-decoration: none; color: inherit; border-radius: var(--fam-radius-sm); padding: 4px 6px; margin: -4px -6px; transition: background .15s; }
        .family-brand:hover { background: #f4f5f7; }
        .family-brand:focus-visible { outline: 2px solid var(--brand-primary); outline-offset: 2px; }
        .family-brand__logo { height: 34px; width: auto; object-fit: contain; }
        .family-brand__badge { display: inline-flex; align-items: center; justify-content: center; height: 34px; width: 34px; border-radius: 50%; background: var(--brand-primary); color: #fff; font-weight: 700; font-size: .72rem; flex: 0 0 auto; }
        .family-brand__text { min-width: 0; }
        .family-brand__name { font-weight: 600; line-height: 1.15; }
        .family-brand__sub { font-size: .8rem; color: var(--fam-muted); }
        .family-brand__sub--strong { color: var(--brand-primary); font-weight: 500; }
        .family-logout { background: none; border: none; cursor: pointer; font-size: .85rem; color: var(--fam-muted); padding: 6px 10px; border-radius: 8px; transition: color .15s, background .15s; }
        .family-logout:hover { color: #dc2626; background: #fef2f2; }

        /* Nav */
        .family-nav { display: flex; gap: 2px; overflow-x: auto; border-top: 1px solid #f1f2f4; scrollbar-width: none; }
        .family-nav::-webkit-scrollbar { display: none; }
        .family-nav a { position: relative; white-space: nowrap; padding: 11px 14px; font-size: .9rem; color: var(--fam-muted); text-decoration: none; border-bottom: 2px solid transparent; display: inline-flex; align-items: center; gap: 6px; transition: color .15s; }
        .family-nav a:hover { color: var(--fam-text); }
        .family-nav a.is-active { color: var(--brand-primary); border-bottom-color: var(--brand-primary); font-weight: 600; }
        .family-nav__count { display: inline-flex; align-items: center; justify-content: center; min-width: 18px; height: 18px; padding: 0 5px; border-radius: 999px; font-size: .7rem; font-weight: 700; background: var(--brand-primary); color: #fff; }

        /* Main */
        .family-main { padding: 22px 0 56px; }

        /* Flash */
        .family-flash { border-radius: var(--fam-radius-sm); padding: 12px 14px; font-size: .9rem; margin-bottom: 14px; border: 1px solid transparent; }
        .family-flash--success { background: #ecfdf3; border-color: #bbf7d0; color: #15803d; }
        .family-flash--error { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }

        /* Page header */
        .family-page-header { margin-bottom: 20px; }
        .family-page-header h1 { font-size: 1.5rem; font-weight: 700; margin: 0 0 4px; letter-spacing: -.01em; }
        .family-page-header p { margin: 0; color: var(--fam-muted); font-size: .9rem; }

        /* Chips */
        .family-chip { display: inline-flex; align-items: center; gap: 5px; font-size: .72rem; font-weight: 600; padding: 3px 9px; border-radius: 999px; background: #f3f4f6; color: var(--fam-muted); border: 1px solid transparent; white-space: nowrap; }
        .family-chip--member { background: #ecfdf3; color: #15803d; }
        .family-chip--ampa { background: #fffbeb; color: #b45309; border-color: #fde68a; }
        .family-chip--brand { background: #eef2ff; color: var(--brand-primary); }
        .family-chip--count { background: #eef2ff; color: var(--brand-primary); }

        /* Alerts */
        .family-alerts { margin-bottom: 22px; display: grid; gap: 10px; }
        .family-alert { display: flex; gap: 10px; border-radius: var(--fam-radius-sm); padding: 12px 14px; font-size: .88rem; line-height: 1.45; border: 1px solid transparent; }
        .family-alert a { color: inherit; font-weight: 600; }
        .family-alert--brand { background: #eef2ff; border-color: #c7d2fe; color: #3730a3; }
        .family-alert--info { background: #eff6ff; border-color: #bfdbfe; color: #1e40af; }
        .family-alert--warning { background: #fffbeb; border-color: #fde68a; color: #92400e; }
        .family-alert--success { background: #ecfdf3; border-color: #bbf7d0; color: #166534; }
        .family-alert--violet { background: #f5f3ff; border-color: #ddd6fe; color: #5b21b6; }

        /* Stats grid */
        .family-stats-grid { display: grid; grid-template-columns: 1fr; gap: 12px; margin-bottom: 26px; }
        @media (min-width: 560px) { .family-stats-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (min-width: 920px) { .family-stats-grid { grid-template-columns: repeat(4, 1fr); } }
        .family-stat-card { background: var(--fam-surface); border: 1px solid var(--fam-border); border-radius: var(--fam-radius); padding: 16px; box-shadow: var(--fam-shadow); }
        .family-stat-card__value { font-size: 1.9rem; font-weight: 700; line-height: 1; color: var(--fam-text); }
        .family-stat-card__label { font-size: .82rem; color: var(--fam-muted); margin-top: 7px; }
        .family-stat-card--muted .family-stat-card__value { color: var(--fam-muted-2); }
        .family-stat-card--brand .family-stat-card__value { color: var(--brand-primary); }
        .family-stat-card--success .family-stat-card__value { color: #16a34a; }
        .family-stat-card--warning .family-stat-card__value { color: #d97706; }

        /* Section */
        .family-section { margin-bottom: 28px; }
        .family-section__title { font-size: 1rem; font-weight: 600; margin: 0 0 12px; }

        /* Pending actions ("Pendiente de ti") */
        .family-pending-list { display: grid; gap: 10px; }
        .family-pending-item { display: flex; align-items: flex-start; gap: 12px; background: var(--fam-surface); border: 1px solid var(--fam-border); border-radius: var(--fam-radius-sm); padding: 13px 14px; box-shadow: var(--fam-shadow); }
        .family-pending-item__icon { flex: 0 0 auto; display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 9px; background: #eef2ff; color: var(--brand-primary); }
        .family-pending-item__icon svg { width: 18px; height: 18px; }
        .family-pending-item__body { flex: 1 1 auto; min-width: 0; }
        .family-pending-item__title { font-weight: 600; font-size: .92rem; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .family-pending-item__desc { font-size: .82rem; color: var(--fam-muted); margin-top: 3px; }
        .family-pending-item__cta { flex: 0 0 auto; }
        @media (max-width: 560px) {
            .family-pending-item { flex-wrap: wrap; }
            .family-pending-item__cta { width: 100%; }
            .family-pending-item__cta .family-btn { width: 100%; }
        }

        /* Card */
        .family-card { background: var(--fam-surface); border: 1px solid var(--fam-border); border-radius: var(--fam-radius); box-shadow: var(--fam-shadow); overflow: hidden; }
        .family-card-list > .family-card + .family-card { margin-top: 14px; }
        .family-card__header { padding: 14px 16px; border-bottom: 1px solid #f1f2f4; display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
        .family-card__title { font-weight: 600; }
        .family-card__sub { font-size: .82rem; color: var(--fam-muted); margin-top: 2px; }
        .family-card__body { padding: 14px 16px; }

        /* Rows / list */
        .family-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 16px; }
        .family-row + .family-row { border-top: 1px solid #f4f4f5; }
        .family-row__main { min-width: 0; }
        .family-row__title { font-weight: 500; font-size: .92rem; }
        .family-row__sub { font-size: .78rem; color: var(--fam-muted); margin-top: 2px; }
        .family-row__side { display: flex; align-items: center; gap: 10px; flex: 0 0 auto; }
        .family-row__side--stack { flex-direction: column; align-items: flex-end; gap: 4px; }

        /* Table */
        .family-table-wrap { overflow-x: auto; }
        .family-table { width: 100%; border-collapse: collapse; font-size: .88rem; }
        .family-table thead th { text-align: left; font-weight: 600; color: var(--fam-muted); background: #fafafa; padding: 9px 16px; border-bottom: 1px solid var(--fam-border); font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; }
        .family-table tbody td { padding: 12px 16px; border-bottom: 1px solid #f4f4f5; vertical-align: middle; }
        .family-table tbody tr:last-child td { border-bottom: none; }
        .family-table .cell-title { font-weight: 500; }
        .family-table .cell-sub { font-size: .76rem; color: var(--fam-muted); margin-top: 2px; }

        /* Status badges */
        .family-status-badge { display: inline-flex; align-items: center; gap: 5px; white-space: nowrap; font-size: .74rem; font-weight: 600; padding: 3px 10px; border-radius: 999px; line-height: 1.4; }
        .status-success { background: #dcfce7; color: #15803d; }
        .status-warning { background: #fef3c7; color: #b45309; }
        .status-info { background: #dbeafe; color: #1d4ed8; }
        .status-pending { background: #e0e7ff; color: #4338ca; }
        .status-danger { background: #fee2e2; color: #b91c1c; }
        .status-muted { background: #f3f4f6; color: #6b7280; }

        /* Buttons / links */
        .family-btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; font-size: .88rem; font-weight: 600; padding: 9px 16px; border-radius: 10px; border: 1px solid transparent; cursor: pointer; text-decoration: none; transition: filter .15s, background .15s, border-color .15s; }
        .family-btn--primary { background: var(--brand-primary); color: #fff; }
        .family-btn--primary:hover { filter: brightness(.93); }
        .family-btn--ghost { background: #fff; color: var(--fam-text); border-color: var(--fam-border); }
        .family-btn--ghost:hover { border-color: #cbd5e1; background: #f9fafb; }
        .family-btn--warning { background: #fef3c7; color: #92400e; }
        .family-btn--warning:hover { filter: brightness(.97); }
        .family-btn--block { width: 100%; }
        .family-btn--sm { padding: 6px 12px; font-size: .8rem; border-radius: 8px; }
        .family-link { color: var(--brand-primary); text-decoration: none; font-size: .85rem; font-weight: 500; }
        .family-link:hover { text-decoration: underline; }
        .family-link-danger { background: none; border: none; cursor: pointer; color: #ef4444; font-size: .78rem; padding: 0; text-decoration: none; font-family: inherit; }
        .family-link-danger:hover { color: #b91c1c; text-decoration: underline; }
        .family-back-link { display: inline-flex; align-items: center; gap: 4px; color: var(--brand-primary); text-decoration: none; font-size: .85rem; margin-bottom: 14px; }
        .family-back-link:hover { text-decoration: underline; }

        /* Quick-access action grid */
        .family-action-grid { display: grid; grid-template-columns: 1fr; gap: 12px; }
        @media (min-width: 560px) { .family-action-grid { grid-template-columns: repeat(2, 1fr); } }
        .family-resource-card { display: block; background: var(--fam-surface); border: 1px solid var(--fam-border); border-radius: var(--fam-radius); padding: 16px; box-shadow: var(--fam-shadow); text-decoration: none; color: inherit; transition: border-color .15s, box-shadow .15s; }
        .family-resource-card:hover { border-color: #c7d2fe; box-shadow: var(--fam-shadow-hover); }
        .family-resource-card__head { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
        .family-resource-card__title { font-weight: 600; }
        .family-resource-card:hover .family-resource-card__title { color: var(--brand-primary); }
        .family-resource-card__desc { font-size: .83rem; color: var(--fam-muted); margin-top: 5px; }

        /* Empty state */
        .family-empty-state { background: var(--fam-surface); border: 1px dashed #d6d9de; border-radius: var(--fam-radius); padding: 30px 20px; text-align: center; color: var(--fam-muted); }
        .family-empty-state__title { font-weight: 600; color: var(--fam-text); margin-bottom: 4px; }
        .family-empty-state p { margin: 0 auto; font-size: .88rem; max-width: 38ch; }
        .family-empty-state .family-btn { margin-top: 14px; }

        /* Activity enrollment status (index) */
        .family-enroll-list { margin-top: 12px; border-top: 1px solid #f1f2f4; padding-top: 10px; display: grid; gap: 6px; }
        .family-enroll-line { display: flex; align-items: center; justify-content: space-between; gap: 8px; font-size: .8rem; }
        .family-enroll-line__name { color: var(--fam-muted); }

        /* Child enrollment list */
        .family-child-enrollments { display: grid; gap: 0; }
        .family-child-enrollment { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 8px 0; }
        .family-child-enrollment + .family-child-enrollment { border-top: 1px solid #f4f4f5; }
        .family-child-enrollment__info { min-width: 0; }
        .family-child-enrollment__name { font-weight: 500; font-size: .9rem; display: block; }
        .family-child-enrollment__group { font-size: .78rem; color: var(--fam-muted); }
        .family-child-enrollment__status { display: flex; align-items: center; gap: 8px; flex: 0 0 auto; }

        /* Activity list (index) */
        .family-activity-meta { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 4px; }

        /* Group meta (activity detail) */
        .family-group-details { display: flex; flex-wrap: wrap; gap: 4px 14px; font-size: .82rem; color: var(--fam-muted); margin-top: 4px; }
        .family-group-detail { white-space: nowrap; }
        .family-group-info-grid { display: flex; flex-wrap: wrap; gap: 6px 20px; margin-bottom: 4px; }
        .family-group-info-item { display: flex; align-items: baseline; gap: 6px; font-size: .84rem; }
        .family-group-info-label { font-size: .7rem; font-weight: 600; color: var(--fam-muted-2); text-transform: uppercase; letter-spacing: .04em; }
        .family-group-info-value { color: var(--fam-text); }
        .family-group-students { margin-top: 12px; border-top: 1px solid #f1f2f4; padding-top: 4px; }
        .family-meta-list { display: flex; flex-wrap: wrap; gap: 4px 16px; font-size: .85rem; color: var(--fam-muted); margin-top: 6px; }
        .family-meta-list strong { color: var(--fam-text); font-weight: 600; }
        .family-spots { font-size: .78rem; color: var(--fam-muted-2); margin-top: 8px; }
        .family-spots .ok { color: #16a34a; font-weight: 600; }
        .family-spots .full { color: #ea580c; font-weight: 600; }

        /* Auth (login) */
        .family-auth { max-width: 420px; margin: 24px auto 0; }
        .family-auth__hint { margin-top: 16px; text-align: center; font-size: .78rem; color: var(--fam-muted-2); }

        /* Forms / inputs */
        .family-field { margin-bottom: 16px; }
        .family-field:last-child { margin-bottom: 0; }
        .family-label { display: block; font-size: .85rem; font-weight: 500; color: #374151; margin-bottom: 6px; }
        .family-label .req { color: #ef4444; margin-left: 2px; }
        .family-input { width: 100%; border: 1px solid #d1d5db; border-radius: 10px; padding: 9px 12px; font-size: .9rem; font-family: inherit; color: var(--fam-text); background: #fff; transition: border-color .15s, box-shadow .15s; }
        .family-input:focus { outline: none; border-color: var(--brand-primary); box-shadow: 0 0 0 3px color-mix(in srgb, var(--brand-primary) 18%, transparent); }
        .family-input--error { border-color: #f87171; }
        .family-check { display: inline-flex; align-items: center; gap: 8px; font-size: .85rem; color: var(--fam-muted); cursor: pointer; }
        .family-field-error { margin-top: 6px; font-size: .76rem; color: #dc2626; }

        /* Definition list (respuestas / detalle) */
        .family-dl__row { padding: 14px 16px; }
        .family-dl__row + .family-dl__row { border-top: 1px solid #f4f4f5; }
        .family-dl__term { font-size: .72rem; font-weight: 600; color: var(--fam-muted-2); text-transform: uppercase; letter-spacing: .04em; margin-bottom: 4px; }
        .family-dl__desc { font-size: .9rem; color: var(--fam-text); }
        .family-dl__desc--empty { color: var(--fam-muted-2); font-style: italic; }

        /* Detail meta rows (consent detail) */
        .family-meta-row + .family-meta-row { margin-top: 14px; }
        .family-meta-row__term { font-size: .72rem; font-weight: 600; color: var(--fam-muted-2); text-transform: uppercase; letter-spacing: .04em; }
        .family-meta-row__value { color: var(--fam-text); margin-top: 3px; font-size: .92rem; }
        .family-legal { font-size: .88rem; color: #374151; white-space: pre-wrap; line-height: 1.6; }

        /* History list */
        .family-history { list-style: none; margin: 0; padding: 0; display: grid; gap: 8px; }
        .family-history li { font-size: .84rem; color: var(--fam-muted); }
        .family-history time { color: var(--fam-muted-2); }

        /* Action row */
        .family-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 4px; }

        /* Extra button variants */
        .family-btn--danger { background: #fee2e2; color: #b91c1c; }
        .family-btn--danger:hover { filter: brightness(.97); }
        .family-btn--muted { background: #f3f4f6; color: #374151; }
        .family-btn--muted:hover { filter: brightness(.97); }

        /* Helpers */
        .fam-stack > * + * { margin-top: 14px; }
        .fam-row-wrap { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
        .fam-mt-1 { margin-top: 6px; }
        .fam-muted { color: var(--fam-muted); }
    </style>
    @if(!app()->environment('testing'))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="family-body">

    {{-- Cabecera --}}
    <header class="family-header">
        <div class="family-container">
            <div class="family-header__bar">
                @php
                    $ampaName = $branding['ampa_name'] ?? 'AMPA';
                    $ampaLogo = isset($branding['ampa_logo_path']) && $branding['ampa_logo_path']
                        ? \Illuminate\Support\Facades\Storage::disk('public')->url($branding['ampa_logo_path'])
                        : null;
                @endphp

                <a href="{{ route('familia.dashboard') }}" class="family-brand">
                    @if($ampaLogo)
                        <img src="{{ $ampaLogo }}" alt="Logo {{ $ampaName }}" class="family-brand__logo">
                    @else
                        <span class="family-brand__badge">{{ mb_strtoupper(mb_substr($ampaName, 0, 2)) }}</span>
                    @endif

                    <div class="family-brand__text">
                        <div class="family-brand__name">{{ $ampaName }}</div>
                        @auth
                            <div class="family-brand__sub">
                                Portal Familias
                                @if(auth()->user()->family?->name)
                                    · <span class="family-brand__sub--strong">{{ auth()->user()->family->name }}</span>
                                @endif
                            </div>
                        @else
                            <div class="family-brand__sub">Portal Familias</div>
                        @endauth
                    </div>
                </a>

                @auth
                    <form method="POST" action="{{ route('familia.logout') }}">
                        @csrf
                        <button type="submit" class="family-logout">Cerrar sesión</button>
                    </form>
                @endauth
            </div>

            {{-- Navegación --}}
            @auth
                @php
                    $pendingConsentsCount = auth()->user()?->family
                        ? app(\App\Services\ConsentStatusService::class)->countPendingForFamily(auth()->user()->family)
                        : 0;
                @endphp
                <nav class="family-nav">
                    <a href="{{ route('familia.dashboard') }}" class="@if(request()->routeIs('familia.dashboard')) is-active @endif">Inicio</a>
                    <a href="{{ route('familia.children') }}" class="@if(request()->routeIs('familia.children')) is-active @endif">Mis hijos/as</a>
                    <a href="{{ route('familia.activities.index') }}" class="@if(request()->routeIs('familia.activities.*')) is-active @endif">Extraescolares</a>
                    <a href="{{ route('familia.forms.index') }}" class="@if(request()->routeIs('familia.forms.*')) is-active @endif">Formularios</a>
                    <a href="{{ route('familia.consents.index') }}" class="@if(request()->routeIs('familia.consents.*')) is-active @endif">
                        Consentimientos
                        @if($pendingConsentsCount > 0)
                            <span class="family-nav__count">{{ $pendingConsentsCount }}</span>
                        @endif
                    </a>
                </nav>
            @endauth
        </div>
    </header>

    {{-- Contenido principal --}}
    <main class="family-main">
        <div class="family-container">
            @if(session('success'))
                <div class="family-flash family-flash--success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="family-flash family-flash--error">{{ session('error') }}</div>
            @endif

            @yield('content')
        </div>
    </main>

</body>
</html>
