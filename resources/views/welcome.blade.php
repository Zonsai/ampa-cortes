<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $branding['ampa_name'] ?? 'AMPA' }} — Portal</title>
    <style>
        :root {
            --brand-primary: {{ $branding['primary_color'] ?? '#4f46e5' }};
            --brand-accent: {{ $branding['accent_color'] ?? '#0f766e' }};
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f8fafc;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            box-shadow: 0 4px 24px 0 rgba(0,0,0,.06);
            max-width: 420px;
            width: 100%;
            padding: 2.5rem 2rem;
            text-align: center;
        }
        .logo-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .75rem;
            margin-bottom: 1.25rem;
        }
        .logo-img {
            height: 48px;
            width: auto;
            object-fit: contain;
        }
        .logo-initials {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--brand-primary);
            color: #fff;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: .05em;
            flex-shrink: 0;
        }
        .badge {
            display: inline-block;
            background: #eff6ff;
            color: var(--brand-primary);
            font-size: .75rem;
            font-weight: 600;
            letter-spacing: .05em;
            text-transform: uppercase;
            padding: .25rem .75rem;
            border-radius: 9999px;
            margin-bottom: 1.25rem;
        }
        h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.3;
            margin-bottom: .5rem;
        }
        .school-name {
            font-size: .9rem;
            color: #64748b;
            margin-bottom: 1.5rem;
        }
        .subtitle {
            font-size: .9375rem;
            color: #64748b;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        .btn-group {
            display: flex;
            flex-direction: column;
            gap: .75rem;
        }
        .btn {
            display: inline-block;
            padding: .75rem 1.5rem;
            border-radius: .5rem;
            font-size: .9375rem;
            font-weight: 600;
            text-decoration: none;
            transition: background .15s, box-shadow .15s;
        }
        .btn-primary {
            background: var(--brand-primary);
            color: #ffffff;
            border: 1px solid var(--brand-primary);
        }
        .btn-primary:hover { filter: brightness(.9); }
        .btn-secondary {
            background: #ffffff;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        .btn-secondary:hover { background: #f9fafb; }
        .footer {
            margin-top: 2.5rem;
            font-size: .8125rem;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="card">
        @php
            $ampaLogo = $branding['ampa_logo_path'] ? \Illuminate\Support\Facades\Storage::disk('public')->url($branding['ampa_logo_path']) : null;
            $schoolLogo = $branding['school_logo_path'] ? \Illuminate\Support\Facades\Storage::disk('public')->url($branding['school_logo_path']) : null;
            $ampaName = $branding['ampa_name'] ?? 'AMPA';
            $schoolName = $branding['school_name'] ?? '';
        @endphp

        <div class="logo-wrap">
            @if($ampaLogo)
                <img src="{{ $ampaLogo }}" alt="Logo {{ $ampaName }}" class="logo-img">
            @else
                <span class="logo-initials" aria-hidden="true">
                    {{ mb_strtoupper(mb_substr($ampaName, 0, 2)) }}
                </span>
            @endif

            @if($schoolLogo)
                <img src="{{ $schoolLogo }}" alt="Logo {{ $schoolName }}" class="logo-img">
            @endif
        </div>

        <div class="badge">AMPA</div>
        <h1>{{ $ampaName }}</h1>
        @if($schoolName)
            <p class="school-name">{{ $schoolName }}</p>
        @endif
        <p class="subtitle">Portal de gestión del AMPA y zona de familias</p>

        <div class="btn-group">
            @auth
                @if(auth()->user()->hasRole('familia'))
                    <a href="{{ url('/familia') }}" class="btn btn-primary">Ir a mi zona familiar</a>
                @else
                    <a href="{{ url('/admin') }}" class="btn btn-primary">Ir al panel de gestión</a>
                @endif
            @else
                <a href="{{ url('/familia/login') }}" class="btn btn-primary">Acceso familias</a>
                <a href="{{ url('/admin') }}" class="btn btn-secondary">Acceso gestión AMPA</a>
            @endauth
        </div>
    </div>

    <p class="footer">{{ $ampaName }} &mdash; Zona privada</p>
</body>
</html>
