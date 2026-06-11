@extends('familia.layouts.app')

@section('title', 'Acceso familias')

@section('content')
<div class="max-w-md mx-auto">
    <h1 class="text-2xl font-bold text-gray-900 mb-2">Acceso para familias</h1>
    <p class="text-gray-500 text-sm mb-6">Portal del AMPA — zona privada de familias</p>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">

        @if($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-red-800 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('familia.login.submit') }}" novalidate>
            @csrf

            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                    Correo electrónico
                </label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('email') border-red-400 @enderror"
                    placeholder="correo@ejemplo.com"
                >
            </div>

            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                    Contraseña
                </label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                >
            </div>

            <div class="flex items-center justify-between mb-6">
                <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded border-gray-300 text-indigo-600">
                    Recordarme
                </label>
            </div>

            <button
                type="submit"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg text-sm transition-colors"
            >
                Entrar
            </button>
        </form>
    </div>

    <p class="mt-4 text-center text-xs text-gray-400">
        ¿Problemas de acceso? Contacta con el AMPA.
    </p>
</div>
@endsection
