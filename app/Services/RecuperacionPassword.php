<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Contraseña olvidada: el mecanismo, en un solo lugar.
 *
 * <b>Por que existe esta clase.</b> El circuito ya funcionaba en la web
 * (Auth\PasswordResetLinkController y Auth\NewPasswordController), pero la
 * logica estaba escrita dentro de metodos que devuelven RedirectResponse: no
 * se pueden llamar desde la API sin arrastrar la redireccion y la sesion. La
 * alternativa era copiar las cuatro reglas al controlador movil, y esa copia
 * es exactamente el problema que documenta LoginMovilRequest: dos lugares que
 * hay que acordarse de cambiar juntos.
 *
 * Aca no hay logica nueva. Son las mismas llamadas al broker de Laravel que
 * ya hacia la web, movidas un nivel para abajo. Los dos controladores Blade
 * siguen validando y redirigiendo igual que antes; lo unico que cambio es de
 * donde sale el `Password::sendResetLink()` y el `Password::reset()`.
 *
 * <b>Las cuatro reglas del mecanismo, que ahora comparten los dos canales:</b>
 *
 *   1. A una cuenta dada de baja no se le manda el enlace, porque no podria
 *      ingresar igual.
 *   2. La respuesta de "pedi el enlace" es SIEMPRE la misma, exista o no el
 *      correo. Si fuera distinta, el formulario serviria para averiguar quien
 *      es cliente o empleado de PowerTech.
 *   3. Una cuenta desactivada DESPUES de pedir el enlace no puede usarlo.
 *   4. Correo inexistente y token vencido dan el mismo resultado, por la
 *      misma razon que el punto 2.
 *
 * <b>El token es el del broker y no se toca.</b> Vive hasheado en
 * `password_reset_tokens`, vence a los 60 minutos y se consume al usarse
 * (ver config/auth.php: `passwords.users.expire` y `passwords.users.throttle`).
 * Nada de eso se reimplementa: `Password::reset()` valida vigencia y lo borra
 * solo cuando el cambio sale bien, que es lo que lo hace de un solo uso.
 *
 * <b>Lo que esta clase NO hace, a proposito:</b> no inicia sesion, no emite
 * token de Sanctum y no devuelve nada que identifique al usuario. Recuperar
 * la contraseña termina en la pantalla de ingreso, en los dos canales.
 */
class RecuperacionPassword
{
    /**
     * Paso 1: manda el correo con el enlace, si corresponde mandarlo.
     *
     * No devuelve nada y es deliberado. El estado del broker
     * —`RESET_LINK_SENT`, `INVALID_USER`, `RESET_THROTTLED`— distingue casos
     * que quien llama NO tiene que poder distinguir: si el controlador
     * pudiera leerlo, tarde o temprano alguien escribiria un mensaje distinto
     * para cada uno y el formulario pasaria a confirmar correos. Que no haya
     * valor de retorno cierra esa puerta.
     *
     * El enlace del correo apunta a la ruta web `password.reset`, que es la
     * que ya existia. No cambia segun el canal que lo pidio: el mismo correo
     * sirve para el navegador y para la app, y la app sabe leerlo.
     */
    public function enviarEnlace(string $email): void
    {
        $email = $this->normalizarCorreo($email);

        // Regla 1. El `where activo` va aca y no en el provider porque
        // `sendResetLink` no acepta credenciales extra sin volver a buscar al
        // usuario: es la misma consulta que hacia PasswordResetLinkController.
        if (! User::where('email', $email)->where('activo', true)->exists()) {
            return;
        }

        Password::sendResetLink(['email' => $email]);
    }

    /**
     * Paso 2: valida el token y escribe la contraseña nueva.
     *
     * Devuelve el status del broker tal cual, sin interpretarlo: quien llama
     * decide como contarlo (la web redirige, la API tira un 422). Lo unico
     * que se garantiza es que un token malo y un correo inexistente dan el
     * MISMO status, para no filtrar la diferencia.
     *
     * @param  array{email: string, password: string, password_confirmation?: string, token: string}  $credenciales
     * @return string Una de las constantes de `Password` (`PASSWORD_RESET`,
     *                `INVALID_TOKEN`, `INVALID_USER`).
     */
    public function restablecer(array $credenciales): string
    {
        $credenciales['email'] = $this->normalizarCorreo($credenciales['email'] ?? '');

        // Regla 3. Se contesta con INVALID_TOKEN y no con un status propio
        // para que la cuenta de baja sea indistinguible del enlace vencido.
        if (User::where('email', $credenciales['email'])->where('activo', false)->exists()) {
            return Password::INVALID_TOKEN;
        }

        return Password::reset($credenciales, function (User $user, string $password) {
            $user->forceFill([
                'password'       => $password, // el cast 'hashed' del modelo la hashea
                'remember_token' => Str::random(60),

                // Recibir el mail demuestra que el correo es suyo. Si todavia
                // no estaba verificado, queda verificado aca: es lo que ya
                // hacia la web y lo que evita que el cliente recupere la
                // contraseña y siga sin poder usar el carrito.
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();

            event(new PasswordReset($user));
        });
    }

    /** ¿El status que devolvio [restablecer] significa que salio bien? */
    public function fueRestablecida(string $status): bool
    {
        return $status === Password::PASSWORD_RESET;
    }

    /**
     * Mismo criterio que el resto del proyecto: el correo se guarda y se
     * busca en minusculas y sin espacios al borde. Importa mas de lo que
     * parece: `password_reset_tokens` tiene el correo como clave primaria,
     * asi que "Juan@x.com" y "juan@x.com" serian dos filas distintas y el
     * token quedaria guardado bajo una y buscado bajo la otra.
     */
    public function normalizarCorreo(?string $email): string
    {
        return Str::lower(trim((string) $email));
    }
}
