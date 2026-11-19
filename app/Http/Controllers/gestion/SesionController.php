<?php

namespace App\Http\Controllers\gestion;

use App\Actions\RevocarTokenDeAcceso;
use App\Enums\AmbitoRol;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\SesionGestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Intercambia un token de gestion por una sesion Blade.
 *
 * Es el paso que hace funcionar la navegacion tradicional: el token tiene que
 * sobrevivir a la recarga completa de pagina sin persistirse en el navegador, y
 * para eso lo guarda la sesion del servidor. Ver App\Support\SesionGestion.
 *
 * <b>Esta ruta NO acepta credenciales.</b> Ni correo, ni contraseña, ni id de
 * usuario: lo unico que recibe es un Bearer y el token CSRF. Las credenciales
 * viajan a un solo lugar en todo el sistema, POST /api/v1/auth/login, que es lo
 * que pide el requisito 6 de la consigna.
 *
 * <b>Esta declarada FUERA del grupo prefix('gestion').</b> La URL lleva el
 * prefijo pero la ruta no pertenece al grupo, porque ese grupo tiene 'auth' y
 * en este momento todavia no hay sesion: estando adentro, 'auth' correria
 * primero y redirigiria al login devolviendole HTML al AJAX. Pertenece al grupo
 * 'web', que es lo que le da sesion y CSRF.
 *
 * <b>No usa auth:sanctum.</b> Con config('sanctum.guard') vacio el riesgo no
 * existe, pero el guard podria resolver primero la sesion del grupo 'web' e
 * ignorar el Bearer de la cabecera. La verificacion explicita de abajo cierra
 * el caso sin depender de un valor de configuracion, y de paso tiene que
 * comparar expires_at a mano: ni findToken() ni can() lo miran.
 */
final class SesionController extends Controller
{
    public function __construct(private readonly RevocarTokenDeAcceso $revocar) {}

    public function store(Request $request): JsonResponse
    {
        $enClaro = $request->bearerToken();

        if ($enClaro === null) {
            return $this->rechazar('No llegó ningún token.', 'TOKEN_AUSENTE', 401);
        }

        $token = PersonalAccessToken::findToken($enClaro);

        if (! $token) {
            return $this->rechazar('El token no es válido.', 'TOKEN_INVALIDO', 401);
        }

        if ($token->expires_at !== null && $token->expires_at->isPast()) {
            return $this->rechazar('El token venció. Volvé a ingresar.', 'TOKEN_INVALIDO', 401);
        }

        $user = $token->tokenable;

        if (! $user instanceof User) {
            return $this->rechazar('El token no es válido.', 'TOKEN_INVALIDO', 401);
        }

        // Cuenta de baja: 401 y SE REVOCA. Es la unica causa de rechazo que
        // revoca, segun la politica acordada: la credencial dejo de valer, no
        // es que no alcance.
        if (! $user->activo) {
            ($this->revocar)($user, $token->getKey());

            return $this->rechazar(__('auth.inactive'), 'CUENTA_INACTIVA', 401);
        }

        // Ambito o ability equivocados: 403 y NO se revoca. El token sigue
        // sirviendo para lo que fue emitido; el error es del cliente.
        if ($user->ambitoRol() !== AmbitoRol::Gestion) {
            return $this->rechazar(
                'Esta cuenta no tiene acceso al panel de gestión.',
                'AMBITO_INCORRECTO',
                403,
            );
        }

        if (! $token->can(config('gestion.ability'))) {
            return $this->rechazar(
                'Este token no tiene permiso para abrir el panel de gestión.',
                'ABILITY_INSUFICIENTE',
                403,
            );
        }

        $this->revocarElSuperado($request, $user, $token);

        Auth::guard('web')->login($user);

        // Rota el id de sesion —cierra la fijacion de sesion— y, de paso, el
        // token CSRF: Store::regenerate() llama a regenerateToken(). Los datos
        // se migran, asi que url.intended sobrevive.
        $request->session()->regenerate();

        SesionGestion::guardar($request->session(), $enClaro, $token);

        return response()->json([
            'message'     => 'Sesión abierta.',
            'redirigir_a' => $this->destino($request, $user),
        ]);
    }

    /**
     * Revoca el token que esta MISMA sesion traia, si habia uno.
     *
     * Es el caso frecuente —el empleado vuelve a entrar en el mismo navegador
     * sin cerrar sesion— y el unico que no depende de que algo falle, asi que
     * es donde mas rinde cerrar el huerfano.
     *
     * <b>Solo si el token anterior es del mismo usuario.</b> Si el empleado A
     * inicia sesion sobre la sesion abierta de B, el token referenciado es de
     * B y revocarlo lo echaria de su propia sesion en otra maquina. En ese caso
     * se descarta la referencia y nada mas.
     *
     * No toca otras sesiones ni otros dispositivos: alcanza unicamente al id
     * que llevaba esta sesion. La consigna aclara que usar el mismo mecanismo
     * "no significa compartir un unico token entre dispositivos o interfaces".
     */
    private function revocarElSuperado(Request $request, User $user, PersonalAccessToken $nuevo): void
    {
        $anterior = SesionGestion::tokenId($request->session());

        if ($anterior === null || $anterior === (int) $nuevo->getKey()) {
            return;
        }

        $previo = PersonalAccessToken::find($anterior);

        if (! $previo
            || $previo->tokenable_type !== User::class
            || (int) $previo->tokenable_id !== $user->getKey()) {
            return;
        }

        ($this->revocar)($user, $anterior);
    }

    /**
     * A donde va el navegador despues del intercambio.
     *
     * Respeta url.intended con el mismo criterio que
     * AuthenticatedSessionController::store(): se vuelve a la pagina que el
     * empleado quiso abrir, pero solo si es de su ambito.
     */
    private function destino(Request $request, User $user): string
    {
        $intended = $request->session()->pull('url.intended');

        if (is_string($intended) && $intended !== '' && AmbitoRol::deUrl($intended) === AmbitoRol::Gestion) {
            return $intended;
        }

        return route($user->rutaInicio());
    }

    private function rechazar(string $mensaje, string $codigo, int $estado): JsonResponse
    {
        return response()->json([
            'message' => $mensaje,
            'codigo'  => $codigo,
        ], $estado);
    }
}
