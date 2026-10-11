<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige una clave de permiso suelta: 'permiso:stock.ajustar'.
 *
 * Es el complemento de 'can:', no su reemplazo. Donde hay un modelo, 'can:'
 * es mejor: pasa por la policy, que ademas de mirar el permiso consulta el
 * estado del recurso —no se edita una venta ya cobrada ni una orden enviada,
 * tenga el permiso quien lo tenga—. Este middleware es para las rutas que no
 * tienen modelo que consultar: el listado de stock, los ajustes de inventario
 * y el autocompletado de la pantalla de venta.
 *
 * Va DESPUES de 'auth': si llegara un invitado, $user seria null y la
 * respuesta correcta es el login, no un 403.
 *
 * Acepta varias claves separadas por coma y alcanza con tener una:
 * 'permiso:stock.ver,stock.ajustar'.
 *
 * El 403 lo dibuja resources/views/errors/403.blade.php.
 */
class VerificarPermiso
{
    public function handle(Request $request, Closure $next, string ...$claves): Response
    {
        $user = $request->user();

        // Una sola clave por argumento o varias separadas por coma: las dos
        // formas terminan en la misma lista.
        $permitidas = collect($claves)
            ->flatMap(fn (string $clave) => explode(',', $clave))
            ->map(fn (string $clave) => trim($clave))
            ->filter();

        if (! $user || ! $permitidas->contains(fn (string $clave) => $user->tienePermiso($clave))) {
            abort(403, 'No tenés permiso para entrar acá.');
        }

        return $next($request);
    }
}
