<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\RevocarTokenDeAcceso;
use App\Enums\AmbitoRol;
use App\Enums\ProveedorAuth;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginMovilRequest;
use App\Http\Requests\Auth\RegistroClienteRequest;
use App\Http\Resources\UsuarioResource;
use App\Models\Rol;
use App\Models\User;
use App\Support\SesionGestion;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use RuntimeException;

/**
 * Autenticacion de la app movil con Sanctum. Tokens Bearer, sin sesion.
 *
 * <b>Corre en paralelo al login web y no lo reemplaza.</b> Las rutas de
 * routes/auth.php siguen intactas: aquellas abren sesion con cookie, estas
 * emiten un token. Un cliente puede tener las dos cosas a la vez y son
 * independientes —cerrar sesion en el navegador no mata el token del
 * telefono, y al reves tampoco—.
 *
 * <b>Que NO hay y es deliberado:</b>
 *
 *   Refresh tokens. El token vive lo que dice config('movil.token_dias') y
 *   cuando vence el cliente vuelve a ingresar. Sanctum no los trae y
 *   construir el circuito a mano (rotacion, blacklist, deteccion de reuso)
 *   es mas superficie de la que esta etapa justifica.
 *
 *   Acceso para cuentas de gestion. El empleado tiene el sitio web; la app es
 *   de la tienda. Se corta aca, antes de emitir token, y otra vez en el
 *   middleware por si el rol cambia despues.
 *
 * El alta de cliente duplica la transaccion de RegisteredUserController a
 * proposito: se eligio no tocar la autenticacion web en esta etapa. <b>Si esa
 * transaccion cambia alla, hay que cambiarla aca.</b> Es el costo asumido de
 * la decision.
 */
class AuthController extends Controller
{
    // ------------------------------------------------------------------
    // Emision de token
    // ------------------------------------------------------------------

    /**
     * Ingreso con correo y contraseña. Devuelve el token y el usuario.
     *
     * <b>Es el unico lugar del sistema donde se validan credenciales.</b> Lo
     * usan Flutter y el ingreso del panel administrativo, que es lo que pide el
     * requisito 6 de la consigna: "ambos clientes deberan utilizar los mismos
     * endpoints para ... iniciar sesion".
     *
     * La validacion de la credencial, el throttle y el rechazo de cuentas de
     * baja viven en LoginMovilRequest, que comparte criterio y contador con
     * el login web.
     */
    public function login(LoginMovilRequest $request): JsonResponse
    {
        $user = $request->usuarioValidado();

        $ambito = $this->ambitoDelIngreso($request, $user);

        return $this->respuestaConToken($user, $request->nombreDelDispositivo(), $ambito);
    }

    /**
     * Alta de un cliente desde la app. Crea user + cliente y emite token.
     *
     * Reutiliza RegistroClienteRequest sin modificarlo: es validacion pura
     * —no arma vistas ni redirige— asi que funciona igual en la API, y un
     * fallo sale como 422 con el detalle por campo.
     *
     * <b>El token se emite aunque el correo no este verificado.</b> Sin token
     * la app no puede ni mostrar el perfil ni pedir el reenvio del correo, y
     * el cliente quedaria en una pantalla sin salida. Lo que el correo sin
     * verificar bloquea es el carrito y el checkout, igual que en la web.
     */
    public function registro(RegistroClienteRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request) {

            $user = User::create($request->datosDelUsuario() + [
                'rol_id'         => $this->idRolCliente(),
                'password'       => $request->validated('password'), // el cast 'hashed' la hashea
                'proveedor_auth' => ProveedorAuth::Local->value,
                'activo'         => true,
            ]);

            $user->cliente()->create($request->datosDelCliente() + ['activo' => true]);

            return $user;
        });

        // Dispara el mail de verificacion, igual que el registro web.
        event(new Registered($user));

        $dispositivo = trim((string) $request->input('dispositivo'));

        // El registro publico es solo de clientes: el ambito no se negocia.
        return $this->respuestaConToken(
            $user,
            $dispositivo !== '' ? $dispositivo : 'App PowerTech',
            AmbitoRol::Tienda,
            201,
        );
    }

    // ------------------------------------------------------------------
    // Sesion en curso
    // ------------------------------------------------------------------

    /**
     * El usuario autenticado, su rol y —en gestion— sus permisos.
     *
     * <b>Un solo endpoint para los dos clientes.</b> Requisito 6: "ambos
     * clientes deberan utilizar los mismos endpoints para ... consultar el
     * usuario autenticado y sus permisos". Por eso la ruta salio del grupo
     * 'api.ambito:tienda' y no lleva filtro de ability.
     *
     * <b>Lee de la base en cada llamada, sin cache.</b> El requisito 7 exige
     * que los cambios de permisos se reflejen en las solicitudes posteriores
     * aunque el usuario conserve un token valido. Lo que devuelve esto sirve
     * para pintar la interfaz, nunca para autorizar: eso lo siguen resolviendo
     * los middleware 'permiso' y las policies, por request.
     */
    public function yo(Request $request): UsuarioResource
    {
        return new UsuarioResource($this->conDatosFiscales($request->user()));
    }

    /**
     * Cierra la sesion del acceso actual. Unico endpoint de revocacion.
     *
     * <b>Atiende a los dos clientes y por eso tiene dos modos.</b> El de
     * AutenticarCierreDeSesion decide cual: con cabecera Authorization, Flutter
     * y el token del request; sin ella, el panel y el token que guardo la
     * sesion. No hay ninguna otra ruta en el sistema capaz de borrar un token.
     *
     * <b>Modo Bearer.</b> Borra el token con el que llego el request, no todos:
     * cerrar sesion en el telefono no tiene por que cerrarla en la tablet. Para
     * una baja de cuenta —donde si hay que revocar todo— el lugar es la
     * pantalla de gestion.
     *
     * <b>Modo cookie.</b> Revoca y despues destruye la sesion, en ese orden y
     * en la misma operacion. El orden es la garantia: si la revocacion falla, la
     * excepcion corta antes del logout y la sesion sigue abierta, asi que el
     * empleado nunca queda afuera con el token vivo —puede reintentar—. Que la
     * fila ya no estuviera no es un fallo sino exito idempotente, y la accion
     * lo trata asi.
     *
     * El instanceof es el discriminador y no una precaucion vaga:
     * currentAccessToken() devuelve lo que puso withAccessToken(), que solo
     * llama el guard de Sanctum. Autenticado por 'web' es null. De paso elimina
     * el 500 que produciria un TransientToken, que no implementa delete().
     */
    public function logout(Request $request, RevocarTokenDeAcceso $revocar): JsonResponse
    {
        $user  = $request->user();
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $revocar($user, $token->getKey());

            return response()->json([
                'message' => 'Cerraste sesión en este dispositivo.',
            ]);
        }

        $id = SesionGestion::tokenId($request->session());

        if ($id !== null) {
            $revocar($user, $id);
        }

        SesionGestion::olvidar($request->session());

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // JSON y no una redireccion: el requisito 5 prohibe que un endpoint
        // devuelva vistas Blade o redirija al login. El destino viaja como dato
        // para que lo use el JS; sin JS el navegador muestra este JSON y el
        // empleado navega a mano, degradado pero correcto —salio y el token
        // quedo revocado—.
        return response()->json([
            'message'     => 'Cerraste sesión.',
            'redirigir_a' => route('gestion.login'),
        ]);
    }

    /**
     * Reenvia el correo de verificacion.
     *
     * Existe porque el circuito de verificacion es web: el enlace firmado
     * abre en el navegador. La app no puede verificar por su cuenta, pero si
     * tiene que poder pedir el correo de nuevo, que es el caso real de quien
     * se registro y lo borro sin querer.
     */
    public function reenviarVerificacion(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Tu correo ya está verificado.',
            ]);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Te reenviamos el correo de verificación. Revisá tu bandeja de entrada.',
        ]);
    }

    // ------------------------------------------------------------------
    // Auxiliares
    // ------------------------------------------------------------------

    /**
     * Emite el token y arma la respuesta de ingreso.
     *
     * La vigencia y la ability salen del ambito, y se pasan POR TOKEN y no por
     * config('sanctum.expiration'): ese valor es global y sobrescribiria el
     * expires_at de todos los tokens. Ver el comentario de config/sanctum.php.
     *
     *   tienda   ability 'tienda'    30 dias   config/movil.php
     *   gestion  ability 'gestion'    8 horas  config/gestion.php
     *
     * La ability acotada es lo que deja cerrado por defecto el ambito ajeno:
     * 'abilities:tienda' en routes/api.php rechaza un token de gestion sin
     * tener que enumerar que no puede hacer.
     */
    private function respuestaConToken(
        User $user,
        string $dispositivo,
        AmbitoRol $ambito,
        int $status = 200,
    ): JsonResponse {
        [$ability, $vence] = $ambito === AmbitoRol::Gestion
            ? [config('gestion.ability'), now()->addHours((int) config('gestion.token_horas'))]
            : [config('movil.ability'), now()->addDays((int) config('movil.token_dias'))];

        $token = $user->createToken($dispositivo, [$ability], $vence);

        return response()->json([
            'token' => $token->plainTextToken,

            // Para que la app sepa cuando va a tener que pedir login de nuevo
            // en lugar de descubrirlo con un 401 en medio de una compra.
            'expira_el' => $token->accessToken->expires_at?->toIso8601String(),

            // El cliente no adivina a donde va: el panel lo usa para decidir si
            // hace el intercambio por sesion o si esta en la puerta equivocada.
            'ambito' => $ambito->value,

            'usuario' => new UsuarioResource($this->conDatosFiscales($user)),
        ], $status);
    }

    /**
     * Que ambito corresponde a este ingreso.
     *
     * <b>Ausente:</b> se deriva del rol, despues de validar las credenciales.
     * Es lo que manda el ingreso del panel, que no puede saber de antemano si
     * quien escribe es empleado o cliente —hay un solo juego de credenciales—.
     *
     * <b>Presente:</b> tiene que coincidir con el rol. Es lo que manda Flutter,
     * con 'tienda' fijo, y es la puerta que antes cerraba
     * exigirCuentaDeTienda(): una cuenta de gestion que pide 'tienda' se
     * rechaza y NO se emite token.
     *
     * Derivarlo del rol no abre ninguna escalada. Un empleado que omita el
     * parametro obtiene un token 'gestion' —el mismo que obtendria entrando al
     * panel, y que por su ability no abre ningun endpoint de tienda—; un cliente
     * que lo omita obtiene 'tienda'. No hay combinacion que entregue un ambito
     * que el rol no corresponda.
     *
     * El rechazo sale como error de validacion sobre 'email' y no como 403 para
     * que la app tenga una sola forma de error en la pantalla de login y pueda
     * mostrar el mensaje debajo del campo, que es donde el usuario lo busca. El
     * 403 esta reservado para el USO de un token ya emitido.
     *
     * @throws ValidationException
     */
    private function ambitoDelIngreso(LoginMovilRequest $request, User $user): AmbitoRol
    {
        $delRol = $user->ambitoRol();

        if ($delRol === null) {
            throw ValidationException::withMessages([
                'email' => 'Esta cuenta no tiene un rol asignado. Contactá a la administración.',
            ]);
        }

        $pedido = $request->ambitoSolicitado();

        if ($pedido === null || $pedido === $delRol) {
            return $delRol;
        }

        throw ValidationException::withMessages([
            'email' => $pedido === AmbitoRol::Tienda
                ? 'Esta cuenta es de gestión y no tiene acceso a la aplicación. Ingresá desde el sitio web.'
                : 'Esta cuenta no tiene acceso al panel de gestión.',
        ]);
    }

    /**
     * Evita el N+1 de UsuarioResource, que toca las dos relaciones del cliente
     * y —desde F2— el rol con sus permisos.
     */
    private function conDatosFiscales(User $user): User
    {
        return $user->loadMissing([
            'cliente.tipoDocumento',
            'cliente.condicionIva',
            'rol.permisos',
        ]);
    }

    /** Mismo criterio que RegisteredUserController: el rol cliente del ambito tienda. */
    private function idRolCliente(): int
    {
        return Rol::where('ambito', AmbitoRol::Tienda->value)
            ->where('nombre', 'cliente')
            ->value('id')
            ?? throw new RuntimeException('No existe el rol cliente. Corré RolSeeder.');
    }
}