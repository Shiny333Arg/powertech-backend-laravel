<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cierra la sesion de un usuario dado de baja mientras estaba logueado.
 *
 * El login ya rechaza cuentas inactivas, pero una sesion abierta ANTES de la
 * baja seguiria viva hasta que expire. Se aplica a todo el grupo 'web' (ver
 * bootstrap/app.php), asi que el corte es inmediato en el proximo clic.
 */
class VerificarCuentaActiva
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->activo) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['email' => __('auth.inactive')]);
        }

        return $next($request);
    }
}
