<?php

namespace App\Http\Middleware;

use App\Actions\RevocarTokenDeAcceso;
use App\Enums\AmbitoRol;
use App\Models\User;
use App\Support\SesionGestion;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * En cada request a /gestion/*: la sesion web y el token que la origino siguen
 * siendo coherentes, o la sesion se cierra.
 *
 * <b>El problema que resuelve.</b> La sesion Blade y el token son dos
 * credenciales con relojes distintos: la sesion muere por 120 minutos de
 * inactividad, el token por 8 horas de antiguedad. Sin este control, una sesion
 * viva con un token muerto le serviria al empleado una pagina cuyas llamadas a
 * la API van a fallar todas con 401; y el requisito 8 de la consigna pide que
 * las paginas entregadas por Laravel no incluyan datos privados que eludan la
 * proteccion de la API. Lo mas limpio es no servir la pagina.
 *
 * <b>No hay mecanismo nativo que cubra esto.</b> Lo evaluado y descartado:
 * Laravel\Sanctum\Http\Middleware\AuthenticateSession e
 * Illuminate\Session\Middleware\AuthenticateSession (alias 'auth.session')
 * invalidan la sesion cuando cambia el hash de la contraseña y no saben nada de
 * tokens; un Gate se evalua por accion y no puede invalidar la sesion; y no hay
 * un evento por request del que colgarse para cortar.
 *
 * <b>Por que la vigencia se compara a mano.</b> Ni
 * PersonalAccessToken::findToken() ni can() miran expires_at: eso solo lo hace
 * Guard::isValidAccessToken(), que corre en el guard 'sanctum' y no en una ruta
 * web. Sin el chequeo 4 de abajo, lo unico que terminaria cortando a un token
 * vencido en el panel seria que el cron de 'sanctum:prune-expired' ya hubiera
 * borrado la fila, o sea que la seguridad del panel dependeria de la frecuencia
 * de una tarea programada.
 *
 * Va DESPUES de 'auth' porque necesita usuario, y despues de 'ambito:gestion'
 * porque si la cuenta no es de gestion VerificarAmbito ya la redirige a su
 * ambito y no tiene sentido exigirle un token de gestion.
 */
final class VerificarTokenDeSesionGestion
{
    public function __construct(private readonly RevocarTokenDeAcceso $revocar) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 1. Hay usuario autenticado por sesion. Defensivo: 'auth' ya lo
        //    garantizo, pero deja el middleware autocontenido para los tests.
        if (! $user instanceof User) {
            return $this->cerrar($request, null, 'SESION_SIN_USUARIO');
        }

        // 2. La sesion referencia un token. Falta en cualquier sesion abierta
        //    antes de F2, y en la del empleado que entro por el login del
        //    storefront.
        $id = SesionGestion::tokenId($request->session());

        if ($id === null) {
            return $this->cerrar($request, $user, 'SESION_SIN_TOKEN');
        }

        // 3. El token sigue existiendo. No esta si lo revoco otro proceso
        //    —otro dispositivo, una baja de empleado— o si lo levanto el prune.
        $token = PersonalAccessToken::find($id);

        if (! $token) {
            return $this->cerrar($request, $user, 'TOKEN_INEXISTENTE');
        }

        // 4. No vencio. Imprescindible: nadie mas lo mira en el lado web.
        if ($token->expires_at !== null && $token->expires_at->isPast()) {
            return $this->cerrar($request, $user, 'TOKEN_VENCIDO', $token);
        }

        // 5. El token es de ESTE usuario. Si no, NO se lo revoca: borrarlo
        //    seria quitarle la credencial a un tercero.
        if ($token->tokenable_type !== User::class || (int) $token->tokenable_id !== $user->getKey()) {
            return $this->cerrar($request, $user, 'TOKEN_DE_OTRO_USUARIO');
        }

        // 6. La cuenta sigue activa. VerificarCuentaActiva ya lo cubre en el
        //    grupo 'web'; el chequeo aca es barato y no depende de ese orden.
        if (! $user->activo) {
            return $this->cerrar($request, $user, 'CUENTA_INACTIVA', $token);
        }

        // 7. El token corresponde al ambito de gestion y tiene la ability.
        if ($user->ambitoRol() !== AmbitoRol::Gestion || ! $token->can(config('gestion.ability'))) {
            return $this->cerrar($request, $user, 'AMBITO_INCORRECTO', $token);
        }

        return $next($request);
    }

    /**
     * Cierra la sesion y manda al ingreso del panel.
     *
     * <b>La respuesta se bifurca, y no es opcional.</b> Dentro de /gestion/*
     * ya hay AJAX en produccion —gestion.buscar.productos y
     * gestion.buscar.clientes, que el formulario de ventas y las dos vistas de
     * promociones consumen con Accept: application/json—. Si esto contestara
     * 302, ese fetch seguiria la redireccion y trataria de parsear el HTML del
     * login: el autocompletado se romperia en silencio.
     *
     * El token se revoca solo cuando se recibe, o sea cuando se comprobo que
     * es de este usuario (chequeos 4, 6 y 7). En el caso 5 se omite a
     * proposito.
     */
    private function cerrar(
        Request $request,
        ?User $user,
        string $codigo,
        ?PersonalAccessToken $token = null,
    ): Response {
        if ($user !== null && $token !== null) {
            ($this->revocar)($user, $token->getKey());
        }

        SesionGestion::olvidar($request->session());

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Tu sesión dejó de ser válida. Volvé a ingresar.',
                'codigo'  => 'SESION_GESTION_INVALIDA',
                'motivo'  => $codigo,
            ], 401);
        }

        return redirect()->route('gestion.login')->withErrors([
            'email' => 'Tu sesión dejó de ser válida. Volvé a ingresar.',
        ]);
    }
}
