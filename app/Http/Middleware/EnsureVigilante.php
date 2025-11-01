<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureVigilante
{
    /**
     * Si no hay sesión de 'vigilante' redirige al login (o a donde prefieras).
     */
    public function handle(Request $request, Closure $next)
    {
        // Si el usuario autenticado es un "vigilante" vía Auth -> permitir
        if (auth()->check() && (auth()->user()->is_vigilante ?? false)) {
            return $next($request);
        }

        // Si existe session('vigilante') (login manejado por vigilanteController) -> permitir
        if (session()->has('vigilante') || session()->has('vigilante_id')) {
            return $next($request);
        }

        // No autenticado como vigilante -> redirigir al formulario de login (usuario)
        return redirect()->route('login');
    }
}
