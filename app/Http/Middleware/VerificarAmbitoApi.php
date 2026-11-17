<?php

namespace App\Http\Middleware;

use App\Enums\AmbitoRol;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Equivalente de VerificarAmbito para la API. Va DESPUES de 'auth:sanctum'.
 *
 * <b>La diferencia con el web no es el criterio sino la respuesta.</b> Alla,
 * al que se equivoco de aplicacion se lo redirige al inicio de la suya,
 * porque casi siempre es alguien que siguio un enlace equivocado. Aca no hay
 * a donde redirigir: la app de la tienda no tiene pantallas de gestion y el
 * que llega con una cuenta administrativa no se equivoco de enlace, se
 * equivoco de aplicacion.
 *
 * <b>Es la segunda linea, no la primera.</b> AuthController::login() ya
 * rechaza a las cuentas de gestion antes de emitir token, que es donde el
 * mensaje puede ser util ("ingresa desde el sitio web"). Esto cubre el caso
 * de un token emitido mientras la cuenta era de tienda y usado despues de
 * que le cambiaran el rol.
 *
 * Por eso tambien revoca el token: si el rol cambio, ese token ya no
 * corresponde a esta aplicacion y no tiene sentido dejarlo vivo hasta que
 * venza.
 */
class VerificarAmbitoApi
{
    public function handle(Request $request, Closure $next, string $ambito): Response
    {
        $user = $request->user();

        if ($user && $user->ambitoRol() !== AmbitoRol::from($ambito)) {
            $user->currentAccessToken()?->delete();

            return response()->json([
                'message' => 'Esta cuenta no tiene acceso a la aplicación de la tienda.',
            ], 403);
        }

        return $next($request);
    }
}