<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Equivalente de VerificarCuentaActiva para la API.
 *
 * <b>Es un archivo aparte y no una rama dentro del middleware web.</b> El
 * criterio es el mismo —una cuenta dada de baja no sigue operando— pero las
 * dos respuestas no se parecen en nada: aquel cierra la sesion, invalida la
 * cookie y redirige al login; aca no hay sesion que cerrar, hay un token que
 * revocar. Mezclar las dos en un `if ($request->expectsJson())` obligaria a
 * tocar un archivo del circuito web para resolver un problema del movil.
 *
 * <b>Revoca solo el token con el que llego el request.</b> Dar de baja una
 * cuenta es una decision de un administrativo, y ahi corresponde borrarle
 * todos los tokens de una. Esto es la red de seguridad para el token que se
 * emitio ANTES de la baja y todavia no vencio: el corte es inmediato en la
 * proxima llamada, igual que en la web es inmediato en el proximo clic.
 *
 * <b>401 y no 403.</b> Despues de revocar, el token efectivamente no vale: la
 * app tiene que descartarlo y volver al login. Un 403 le diria que la
 * credencial sigue siendo buena pero le falta permiso, que no es el caso.
 */
class VerificarCuentaActivaApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->activo) {
            $user->currentAccessToken()?->delete();

            return response()->json([
                'message' => __('auth.inactive'),
            ], 401);
        }

        return $next($request);
    }
}