<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portal Familias') — AMPA Cortés de Aragón</title>
    @if(!app()->environment('testing'))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-gray-50 text-gray-900">

    {{-- Cabecera --}}
    <header class="bg-white border-b border-gray-200 shadow-sm">
        <div class="max-w-4xl mx-auto px-4 py-3 flex items-center justify-between">
            <div>
                <span class="font-semibold text-gray-800">AMPA Cortés de Aragón</span>
                <span class="ml-2 text-sm text-gray-400 hidden sm:inline">Portal Familias</span>
                @auth
                    <span class="ml-2 text-sm text-gray-500">
                        Familia {{ auth()->user()->family?->name }}
                    </span>
                @endauth
            </div>
            @auth
                <form method="POST" action="{{ route('familia.logout') }}">
                    @csrf
                    <button type="submit"
                            class="text-sm text-gray-600 hover:text-red-600 transition-colors">
                        Cerrar sesión
                    </button>
                </form>
            @endauth
        </div>

        {{-- Navegación --}}
        @auth
        <nav class="max-w-4xl mx-auto px-4 border-t border-gray-100 flex gap-1 text-sm overflow-x-auto">
            <a href="{{ route('familia.dashboard') }}"
               class="py-2 px-3 whitespace-nowrap transition-colors @if(request()->routeIs('familia.dashboard')) font-semibold text-indigo-700 border-b-2 border-indigo-600 @else text-gray-600 hover:text-indigo-600 border-b-2 border-transparent @endif">
                Inicio
            </a>
            <a href="{{ route('familia.children') }}"
               class="py-2 px-3 whitespace-nowrap transition-colors @if(request()->routeIs('familia.children')) font-semibold text-indigo-700 border-b-2 border-indigo-600 @else text-gray-600 hover:text-indigo-600 border-b-2 border-transparent @endif">
                Mis hijos/as
            </a>
            <a href="{{ route('familia.activities.index') }}"
               class="py-2 px-3 whitespace-nowrap transition-colors @if(request()->routeIs('familia.activities.*')) font-semibold text-indigo-700 border-b-2 border-indigo-600 @else text-gray-600 hover:text-indigo-600 border-b-2 border-transparent @endif">
                Extraescolares
            </a>
            <a href="{{ route('familia.forms.index') }}"
               class="py-2 px-3 whitespace-nowrap transition-colors @if(request()->routeIs('familia.forms.*')) font-semibold text-indigo-700 border-b-2 border-indigo-600 @else text-gray-600 hover:text-indigo-600 border-b-2 border-transparent @endif">
                Formularios
            </a>
            @php
                $pendingConsentsCount = auth()->user()?->family
                    ? app(\App\Services\ConsentStatusService::class)->countPendingForFamily(auth()->user()->family)
                    : 0;
            @endphp
            <a href="{{ route('familia.consents.index') }}"
               class="py-2 px-3 whitespace-nowrap inline-flex items-center gap-1 transition-colors @if(request()->routeIs('familia.consents.*')) font-semibold text-indigo-700 border-b-2 border-indigo-600 @else text-gray-600 hover:text-indigo-600 border-b-2 border-transparent @endif">
                Consentimientos
                @if($pendingConsentsCount > 0)
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">{{ $pendingConsentsCount }}</span>
                @endif
            </a>
        </nav>
        @endauth
    </header>

    {{-- Mensajes flash --}}
    <div class="max-w-4xl mx-auto px-4 pt-4">
        @if(session('success'))
            <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-green-800 text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-red-800 text-sm">
                {{ session('error') }}
            </div>
        @endif
    </div>

    {{-- Contenido principal --}}
    <main class="max-w-4xl mx-auto px-4 py-6">
        @yield('content')
    </main>

</body>
</html>
