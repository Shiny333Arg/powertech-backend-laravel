<?php

namespace App\Http\Middleware;

use App\Enums\AmbitoRol;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * El equivalente de 'ambito' para tokens: separa las dos aplicaciones.
 *
 * <b>Rechaza, NO revoca.</b> Es la diferencia con la version anterior y es una
 * decision de politica, no un detalle: un token de gestion que golpea un
 * endpoint de tienda es un error de programacion del cliente, no una credencial
 * comprometida. Borrarlo dejaba al empleado sin acceso al panel por haber
 * pedido mal una URL, y encima de forma irreversible —el token se borra, no se
 * rechaza—.
 *
 * La regla general, que vale para toda la API:
 *
 *   401 = la credencial ya no sirve   -> el cliente la descarta y pide ingreso
 *   403 = la credencial es buena pero no alcanza -> el cliente la CONSERVA
 *
 * De ahi que el unico caso que revoca sea la cuenta de baja
 * (VerificarCuentaActivaApi, 401) y que ambito y ability contesten 403 sin
 * tocar la fila.
 *
 * El 'codigo' estable es lo que le permite a Flutter distinguir sin comparar
 * textos. Sin el, la app interpretaba ciertos 403 como perdida de acceso y
 * descartaba el token.
 */
class VerificarAmbitoApi
{
    public function handle(Request $request, Closure $next, string $ambito): Response
    {
        $user = $request->user();

        if ($user && $user->ambitoRol() !== AmbitoRol::from($ambito)) {
            return response()->json([
                'message' => $ambito === AmbitoRol::Tienda->value
                    ? 'Esta cuenta no tiene acceso a la aplicación de la tienda.'
                    : 'Esta cuenta no tiene acceso al panel de gestión.',
                'codigo' => 'AMBITO_INCORRECTO',
            ], 403);
        }

        return $next($request);
    }
}
