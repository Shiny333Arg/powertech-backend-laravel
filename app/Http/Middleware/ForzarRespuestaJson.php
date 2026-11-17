<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fuerza `Accept: application/json` en todo el grupo 'api'.
 *
 * <b>Por que hace falta.</b> Laravel decide entre devolver JSON y redirigir
 * mirando `expectsJson()`, que depende de la cabecera Accept del cliente. Sin
 * esto, una peticion que se olvide de mandarla y caiga en una ruta protegida
 * recibe un 302 hacia /login —por el `redirectGuestsTo` de bootstrap/app.php—
 * en lugar de un 401. El cliente HTTP de Flutter sigue la redireccion sin
 * avisar y termina intentando parsear HTML: el error que se ve es un fallo de
 * decodificacion, no un "no estas autenticado", y cuesta un rato largo
 * encontrarlo.
 *
 * Se aplica SOLO al grupo 'api'. La web no lo ve y sigue redirigiendo igual
 * que siempre.
 */
class ForzarRespuestaJson
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}