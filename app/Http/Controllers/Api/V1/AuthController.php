<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AmbitoRol;
use App\Enums\ProveedorAuth;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginMovilRequest;
use App\Http\Requests\Auth\RegistroClienteRequest;
use App\Http\Resources\UsuarioResource;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
     * La validacion de la credencial, el throttle y el rechazo de cuentas de
     * baja viven en LoginMovilRequest, que comparte criterio y contador con
     * el login web.
     */
    public function login(LoginMovilRequest $request): JsonResponse
    {
        $user = $request->usuarioValidado();

        $this->exigirCuentaDeTienda($user);

        return $this->respuestaConToken($user, $request->nombreDelDispositivo());
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

        return $this->respuestaConToken(
            $user,
            $dispositivo !== '' ? $dispositivo : 'App PowerTech',
            201,
        );
    }

    // ------------------------------------------------------------------
    // Sesion en curso
    // ------------------------------------------------------------------

    /** El usuario del token, con sus datos fiscales. */
    public function yo(Request $request): UsuarioResource
    {
        return new UsuarioResource($this->conDatosFiscales($request->user()));
    }

    /**
     * Cierra la sesion de ESTE dispositivo.
     *
     * Borra el token con el que llego el request, no todos: cerrar sesion en
     * el telefono no tiene por que cerrarla en la tablet. Para una baja de
     * cuenta —donde si hay que revocar todo— el lugar es la pantalla de
     * gestion, no esta.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Cerraste sesión en este dispositivo.',
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
     * La vigencia se pasa POR TOKEN y no por config('sanctum.expiration'):
     * ver el comentario de config/movil.php. La ability acotada deja cerrado
     * por defecto cualquier endpoint futuro de otro ambito.
     */
    private function respuestaConToken(User $user, string $dispositivo, int $status = 200): JsonResponse
    {
        $token = $user->createToken(
            $dispositivo,
            [config('movil.ability')],
            now()->addDays((int) config('movil.token_dias')),
        );

        return response()->json([
            'token' => $token->plainTextToken,

            // Para que la app sepa cuando va a tener que pedir login de nuevo
            // en lugar de descubrirlo con un 401 en medio de una compra.
            'expira_el' => $token->accessToken->expires_at?->toIso8601String(),

            'usuario' => new UsuarioResource($this->conDatosFiscales($user)),
        ], $status);
    }

    /**
     * Una cuenta de gestion no entra a la app de la tienda.
     *
     * Sale como error de validacion sobre 'email' y no como 403 para que la
     * app tenga una sola forma de error en la pantalla de login y pueda
     * mostrar el mensaje debajo del campo, que es donde el usuario lo busca.
     *
     * @throws ValidationException
     */
    private function exigirCuentaDeTienda(User $user): void
    {
        if ($user->esCliente()) {
            return;
        }

        throw ValidationException::withMessages([
            'email' => 'Esta cuenta es de gestión y no tiene acceso a la aplicación. '
                .'Ingresá desde el sitio web.',
        ]);
    }

    /** Evita el N+1 de UsuarioResource, que toca las dos relaciones del cliente. */
    private function conDatosFiscales(User $user): User
    {
        return $user->loadMissing([
            'cliente.tipoDocumento',
            'cliente.condicionIva',
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