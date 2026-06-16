<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFamiliaRole
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return redirect()->route('familia.login');
        }

        if (! $request->user()->hasRole('familia')) {
            abort(403);
        }

        if (! $request->user()->is_active) {
            return redirect()->route('familia.login')
                ->with('error', 'Tu cuenta está desactivada. Contacta con el AMPA.');
        }

        if ($request->user()->guardian === null || $request->user()->guardian->family_id === null) {
            return redirect()->route('familia.login')
                ->with('error', 'Tu cuenta no está vinculada a ninguna familia. Contacta con el AMPA.');
        }

        // HasOneThrough respects soft deletes; returns null if the family was deleted.
        if ($request->user()->family === null) {
            return redirect()->route('familia.login')
                ->with('error', 'Tu familia no está disponible actualmente. Contacta con el AMPA.');
        }

        return $next($request);
    }
}
