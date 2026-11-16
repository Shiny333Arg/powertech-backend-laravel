<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\NuevaPasswordMovilRequest;
use App\Http\Requests\Api\OlvidePasswordMovilRequest;
use App\Services\RecuperacionPassword;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

/**
 * Contraseña olvidada, para la app movil. Dos endpoints publicos.
 *
 * <b>Separado de AuthController y no colgado de el.</b> Son operaciones
 * distintas y conviene que se vea: AuthController emite y revoca tokens de
 * Sanctum, y este controlador no emite ninguno. Quien recupera la contraseña
 * no inicia sesion: termina en la pantalla de ingreso, igual que en la web
 * (NewPasswordController redirige a `login`). Mezclarlos invitaria a que
 * alguien "mejore" el flujo devolviendo un token de paso, que es la falla
 * clasica de este circuito.
 *
 * <b>Reutiliza el mecanismo de la web, no lo reimplementa.</b> Las dos
 * acciones delegan en App\Services\RecuperacionPassword, que es la misma
 * clase que usan Auth\PasswordResetLinkController y
 * Auth\NewPasswordController. El token sigue siendo el del broker de
 * Laravel: hasheado en `password_reset_tokens`, vencimiento de 60 minutos y
 * de un solo uso (config/auth.php, `passwords.users`). Este controlador no
 * genera tokens, no los lee y no los valida: solo traduce el resultado a
 * JSON.
 *
 * <b>El correo que llega es el mismo en los dos canales</b> y su enlace
 * apunta a la ruta web `password.reset`. Eso no se cambio a proposito: un
 * enlace distinto segun quien lo pidio obligaria a tocar la notificacion
 * —que es compartida con los empleados, que no tienen app— y a sostener dos
 * formatos de correo. La app resuelve el otro extremo: sabe sacar el token
 * de ese enlace.
 */
class PasswordRecuperacionController extends Controller
{
    public function __construct(
        private readonly RecuperacionPassword $recuperacion,
    ) {
    }

    /**
     * `POST /api/v1/auth/password/olvide`
     *
     * <b>Siempre 200 y siempre el mismo texto.</b> Exista el correo o no,
     * este la cuenta activa o de baja, haya pedido el enlace hace diez
     * segundos o nunca: la respuesta no cambia. Es la regla del mecanismo
     * web y el motivo esta escrito en PasswordResetLinkController: una
     * respuesta distinta convertiria este endpoint —que es publico y no pide
     * token— en una forma de averiguar quien tiene cuenta en PowerTech.
     *
     * Lo unico que puede fallar es la validacion del formato del correo
     * (422) o el throttle de la ruta (429).
     */
    public function enviarEnlace(OlvidePasswordMovilRequest $request): JsonResponse
    {
        $this->recuperacion->enviarEnlace($request->correo());

        return response()->json([
            'message' => 'Si el correo corresponde a una cuenta de PowerTech, '
                .'te enviamos un enlace para elegir una contraseña nueva. '
                .'Revisá tu bandeja de entrada y la carpeta de spam.',
        ]);
    }

    /**
     * `POST /api/v1/auth/password/restablecer`
     *
     * <b>El fallo sale como 422 sobre el campo `email`</b>, no como 401 ni
     * 403. Dos razones:
     *
     *   La paridad con la web. reset-password.blade.php muestra "El enlace no
     *   es valido o ya vencio" debajo del campo de correo, con el atajo para
     *   pedir uno nuevo. La app hace lo mismo porque recibe el error en el
     *   mismo lugar.
     *
     *   El contrato de errores de la app. Un 401 significa "sesion vencida"
     *   y dispara el circuito de relogueo (ver ErrorSesionVencida en
     *   lib/core/errores.dart); un 403 significa "esta cuenta no entra". Las
     *   dos lecturas son falsas aca: no hay sesion que vencer y la cuenta
     *   puede estar perfecta. El 422 es lo que de verdad paso: el dato que
     *   mandaron no sirve.
     *
     * <b>Un token invalido, uno vencido, uno ya usado y un correo
     * inexistente dan la misma respuesta.</b> El servicio los colapsa en
     * INVALID_TOKEN antes de llegar aca, asi que este metodo no podria
     * distinguirlos ni queriendo.
     *
     * <b>No devuelve token ni usuario.</b> Despues de cambiar la contraseña
     * el cliente vuelve al login y entra con la nueva, que es lo que hace la
     * web. Devolver una sesion aca significaria que un enlace de correo
     * alcanza para quedar dentro de la app.
     *
     * @throws ValidationException
     */
    public function restablecer(NuevaPasswordMovilRequest $request): JsonResponse
    {
        $status = $this->recuperacion->restablecer($request->credenciales());

        if (! $this->recuperacion->fueRestablecida($status)) {
            // Mismo texto que la web: sale de lang/es/passwords.php, asi que
            // si algun dia se reescribe, los dos canales cambian juntos.
            throw ValidationException::withMessages([
                'email' => __(Password::INVALID_TOKEN),
            ]);
        }

        return response()->json([
            'message' => 'Listo, cambiamos tu contraseña. '
                .'Ya podés ingresar con la nueva.',
        ]);
    }
}
