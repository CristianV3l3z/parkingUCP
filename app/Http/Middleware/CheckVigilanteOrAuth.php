<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckVigilanteOrAuth
{
    /**
     * Allow if Laravel user is authenticated OR session('vigilante') exists.
     */
    public function handle(Request $request, Closure $next)
    {
        // 1) Si el guard de Laravel tiene un usuario, permitir
        if (Auth::check()) {
            return $next($request);
        }

        // 2) Si la sesión contiene 'vigilante', permitir
        if ($request->session()->has('vigilante')) {
            return $next($request);
        }

        // 3) Si no, redirigir a login (o a la ruta que uses)
        return redirect()->route('login');
    }
}
