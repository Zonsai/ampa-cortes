@extends('familia.layouts.app')

@section('title', 'Acceso familias')

@section('content')
<div class="family-auth">
    <div class="family-page-header" style="text-align: center;">
        <h1>Acceso para familias</h1>
        <p>Portal del AMPA — zona privada de familias</p>
    </div>

    <div class="family-card">
        <div class="family-card__body">
            @if($errors->any())
                <div class="family-flash family-flash--error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('familia.login.submit') }}" novalidate>
                @csrf

                <div class="family-field">
                    <label for="email" class="family-label">Correo electrónico</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        placeholder="correo@ejemplo.com"
                        class="family-input @error('email') family-input--error @enderror">
                </div>

                <div class="family-field">
                    <label for="password" class="family-label">Contraseña</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        class="family-input">
                </div>

                <div class="family-field">
                    <label class="family-check">
                        <input type="checkbox" name="remember">
                        Recordarme
                    </label>
                </div>

                <button type="submit" class="family-btn family-btn--primary family-btn--block">Entrar</button>
            </form>
        </div>
    </div>

    <p class="family-auth__hint">¿Problemas de acceso? Contacta con el AMPA.</p>
</div>
@endsection
