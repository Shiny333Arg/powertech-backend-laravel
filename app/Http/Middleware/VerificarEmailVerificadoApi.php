<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Equivalente de 'verified' para la API. Va DESPUES de 'auth:sanctum'.
 *
 * <b>Por que no se usa el 'verified' nativo.</b> Funcionar, funciona: con
 * ForzarRespuestaJson delante, EnsureEmailIsVerified corta con 403 en lugar
 * de redirigir. El problema es lo que manda en el cuerpo, que es el mensaje
 * por defecto del framework, en ingles y sin nada que la app pueda mirar
 * aparte del texto. Distinguir "tu correo no esta verificado" de "esta cuenta
 * no es de la tienda" —los dos 403— obligaria a Flutter a comparar cadenas.
 *
 * Por eso la respuesta lleva `codigo`: es el campo del que la app cuelga la
 * decision de mostrar la pantalla de verificacion, con su boton de reenvio,
 * en lugar de un error generico.
 *
 * <b>El token NO se revoca</b>, y es la diferencia con los otros dos
 * middlewares de este grupo. Alla la credencial dejo de valer: la cuenta se
 * dio de baja o cambio de ambito, y no hay vuelta atras desde la app. Aca la
 * credencial es perfectamente buena y le falta un paso al usuario, que lo
 * resuelve tocando el enlace del correo sin volver a iniciar sesion. Revocar
 * seria echarlo de la app por no haber abierto todavia el mail.
 *
 * <b>El criterio es el mismo que el de la web.</b> routes/web.php exige
 * 'verified' en el bloque del carrito y en el del checkout, y no en "mis
 * compras". Esta clase existe para que la app aplique esa misma regla, no una
 * propia: si manana se decide que el carrito no la necesita, se cambia en los
 * dos lados a la vez.
 */
class VerificarEmailVerificadoApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $verificado = $user instanceof MustVerifyEmail
            ? $user->hasVerifiedEmail()
            : $user !== null;

        if (! $verificado) {
            return response()->json([
                'message' => 'Necesitás confirmar tu correo para usar el carrito. '
                    .'Te mandamos un enlace cuando te registraste.',
                'codigo' => 'email_no_verificado',
            ], 403);
        }

        return $next($request);
    }
}
