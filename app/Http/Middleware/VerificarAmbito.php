<?php

namespace App\Http\Middleware;

use App\Enums\AmbitoRol;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Separa las dos aplicaciones: 'ambito:gestion' deja pasar solo a empleados y
 * 'ambito:tienda' solo a clientes. Va DESPUES de 'auth'.
 *
 * Es necesaria porque empleados y clientes comparten la tabla users, el guard
 * y la pantalla de login: sin este control, un cliente logueado podria abrir
 * cualquier URL de /gestion.
 *
 * Al que no corresponde se lo manda al inicio de su propio ambito en lugar de
 * mostrarle un 403: casi siempre es alguien que siguio un enlace equivocado.
 */
class VerificarAmbito
{
    public function handle(Request $request, Closure $next, string $ambito): Response
    {
        $user = $request->user();

        if ($user && $user->ambitoRol() !== AmbitoRol::from($ambito)) {
            return redirect()->route($user->rutaInicio());
        }

        return $next($request);
    }
}
