<?php

namespace App\Http\Middleware;

use App\Actions\RevocarTokenDeAcceso;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * La cuenta se dio de baja despues de emitir el token.
 *
 * <b>Es el unico caso de la API que revoca</b>, y por eso contesta 401 y no
 * 403: la credencial dejo de valer, no es que no alcance. Ver la regla completa
 * en VerificarAmbitoApi.
 *
 * El borrado pasa por RevocarTokenDeAcceso, que es el unico lugar del sistema
 * donde se borra un token, y esta acotado a los tokens del propio usuario. De
 * paso, el instanceof evita el 500 que produciria un TransientToken, que no
 * implementa delete(): con config('sanctum.guard') vacio no deberia aparecer
 * nunca, pero el chequeo es gratis y no depende de ese valor.
 */
class VerificarCuentaActivaApi
{
    public function __construct(private readonly RevocarTokenDeAcceso $revocar) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->activo) {

            $token = $user->currentAccessToken();

            if ($token instanceof PersonalAccessToken) {
                ($this->revocar)($user, $token->getKey());
            }

            return response()->json([
                'message' => __('auth.inactive'),
                'codigo'  => 'CUENTA_INACTIVA',
            ], 401);
        }

        return $next($request);
    }
}
