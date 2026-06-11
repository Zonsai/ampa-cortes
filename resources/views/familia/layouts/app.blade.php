<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portal Familias') — AMPA</title>
    @if(!app()->environment('testing'))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-gray-50 text-gray-900">

    {{-- Cabecera --}}
    <header class="bg-white border-b border-gray-200 shadow-sm">
        <div class="max-w-4xl mx-auto px-4 py-3 flex items-center justify-between">
            <div>
                <span class="font-semibold text-gray-800">Portal Familias — AMPA</span>
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
        <nav class="max-w-4xl mx-auto px-4 pb-2 flex gap-4 text-sm">
            <a href="{{ route('familia.dashboard') }}"
               class="@if(request()->routeIs('familia.dashboard')) font-semibold text-indigo-700 @else text-gray-600 hover:text-indigo-600 @endif">
                Inicio
            </a>
            <a href="{{ route('familia.children') }}"
               class="@if(request()->routeIs('familia.children')) font-semibold text-indigo-700 @else text-gray-600 hover:text-indigo-600 @endif">
                Mis hijos/as
            </a>
            <a href="{{ route('familia.activities.index') }}"
               class="@if(request()->routeIs('familia.activities.*')) font-semibold text-indigo-700 @else text-gray-600 hover:text-indigo-600 @endif">
                Extraescolares
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
