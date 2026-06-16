<?php

namespace App\Http\Controllers\Familia;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check() && Auth::user()->hasRole('familia')) {
            return redirect()->route('familia.dashboard');
        }

        return view('familia.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Credenciales incorrectas. Verifica tu correo y contraseña.'])
                ->onlyInput('email');
        }

        if (! Auth::user()->hasRole('familia')) {
            Auth::logout();

            return back()
                ->withErrors(['email' => 'Esta cuenta no tiene acceso a la zona de familias.'])
                ->onlyInput('email');
        }

        if (! Auth::user()->is_active) {
            Auth::logout();

            return back()
                ->withErrors(['email' => 'Esta cuenta está desactivada. Contacta con el AMPA.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('familia.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('familia.login');
    }
}
