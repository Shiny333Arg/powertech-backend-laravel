<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Routing\Pipeline;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autentica POST /api/v1/auth/logout en dos modos excluyentes.
 *
 * Existe para que haya <b>un solo endpoint de cierre de sesion</b> en todo el
 * sistema, que es lo que pide el requisito 6 de la consigna: "ambos clientes
 * deberan utilizar los mismos endpoints para ... cerrar sesion". Flutter llega
 * con Bearer; el panel llega con la cookie de sesion. Una sola ruta, un solo
 * controlador, una sola regla de revocacion.
 *
 * <b>Modo Bearer (Flutter).</b> Nada de sesion, nada de CSRF, guard 'sanctum'.
 * Es exactamente el comportamiento que tenia la ruta antes de F2.
 *
 * <b>Modo cookie (panel).</b> Se arma un sub-pipeline con EncryptCookies,
 * AddQueuedCookiesToResponse, StartSession y ValidateCsrfToken, y se autentica
 * con el guard 'web'. El patron no es un invento: es el mismo que usa
 * Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful, que
 * tambien instancia un Illuminate\Routing\Pipeline adentro de un middleware
 * para correr una lista condicional. La diferencia es el criterio: Sanctum
 * mira Referer/Origin contra config('sanctum.stateful'), y aca se mira la
 * presencia de la cabecera Authorization, que es determinista —el Referer se
 * puede quitar y no sirve para decidir seguridad—.
 *
 * <b>Por que el CSRF tiene que ser condicional y no fijo en la ruta.</b> En
 * modo cookie es obligatorio: sin el, cualquier sitio podria postear y
 * revocarle la credencial al empleado. En modo Bearer es imposible, porque
 * VerifyCsrfToken::tokensMatch() hace $request->session()->token() y sin sesion
 * iniciada eso revienta; y Flutter, que no manda ni cookie ni _token, recibiria
 * 419. Poner ValidateCsrfToken suelto en la ruta rompe uno de los dos clientes
 * si o si.
 *
 * <b>Un Bearer invalido NO cae en la sesion web.</b> La decision es excluyente:
 * si hay cabecera Authorization se usa 'sanctum' y nada mas. Un token vencido o
 * inventado termina en 401, nunca en "bueno, probemos con la cookie". Por eso
 * la autenticacion se resuelve aca y no con auth:sanctum,web, que si haria ese
 * fallback.
 *
 * Nota sobre la cuenta de baja: esta ruta NO lleva 'api.activo' a proposito.
 * Un empleado al que acaban de desactivar tiene que poder cerrar su sesion; el
 * acceso al panel ya se lo corta VerificarCuentaActiva en el grupo 'web'.
 */
final class AutenticarCierreDeSesion
{
    public function __construct(private readonly Application $app) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->bearerToken() !== null) {
            return $this->autenticarCon('sanctum', $request, $next);
        }

        return (new Pipeline($this->app))
            ->send($request)
            ->through([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ValidateCsrfToken::class,
            ])
            ->then(fn (Request $request) => $this->autenticarCon('web', $request, $next));
    }

    /**
     * Resuelve el usuario con UN guard y fija ese guard como el de la peticion,
     * igual que hace Illuminate\Auth\Middleware\Authenticate.
     */
    private function autenticarCon(string $guard, Request $request, Closure $next): Response
    {
        if (Auth::guard($guard)->user() === null) {
            return response()->json([
                'message' => 'No hay una sesión abierta para cerrar.',
                'codigo'  => 'TOKEN_AUSENTE',
            ], 401);
        }

        Auth::shouldUse($guard);

        return $next($request);
    }
}
