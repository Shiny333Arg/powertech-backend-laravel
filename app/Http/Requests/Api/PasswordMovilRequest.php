<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Cambio de la propia contraseña desde la app.
 *
 * Mismas reglas que Auth\PasswordController, que es el que atiende el segundo
 * formulario de la pantalla de perfil en la web. Alla estan escritas inline
 * con validateWithBag() —porque el formulario convive con el de datos y
 * necesita su propia bolsa de errores— y aca van en un FormRequest, que es lo
 * que usa el resto de la API. Las reglas son las mismas tres.
 *
 * <b>`current_password:sanctum`, con el guard escrito.</b> Esto es lo unico
 * que no se puede copiar tal cual de la web, y el motivo no se nota hasta que
 * falla: la regla `current_password` sin parametro resuelve contra el guard
 * POR DEFECTO, que en este proyecto es `web`. En una peticion de la API no
 * hay sesion web, asi que ese guard contesta "invitado" y la regla rechaza la
 * contraseña correcta. El mensaje que ve el usuario seria "La contraseña
 * actual no es correcta" sobre una contraseña que si lo es, y no habria forma
 * de darse cuenta mirando el formulario.
 *
 * <b>El usuario de Google no tiene contraseña que confirmar.</b> `users.password`
 * es nullable: quien se registro con Google todavia no eligio una, y la
 * primera que elija la crea. Es la misma condicion que evalua
 * PasswordController.
 *
 * <b>Los tokens NO se revocan al cambiar la contraseña</b>, igual que la web
 * no cierra las otras sesiones. Si se quisiera cambiar eso, hay que cambiarlo
 * en los dos lados a la vez; hacerlo solo en la API dejaria a la app
 * comportandose distinto de la web frente a la misma accion.
 */
class PasswordMovilRequest extends FormRequest
{
    /**
     * El middleware `auth:sanctum` ya garantizo que hay usuario, y la
     * contraseña que se cambia es siempre la suya: no hay id en el cuerpo ni
     * en la URL.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'current_password' => $this->user()->password === null
                ? ['nullable']
                : ['required', 'current_password:sanctum'],

            // Password::defaults() no esta configurado en AppServiceProvider,
            // asi que hoy es minimo 8 caracteres. Se usa igual y no el
            // literal: el dia que el proyecto endurezca la politica, la API
            // acompaña sola.
            'password' => ['required', Password::defaults(), 'confirmed'],
        ];
    }

    /**
     * Los mensajes salen de lang/es/validation.php, que ya traduce
     * `current_password` y `confirmed`. No hace falta escribir ninguno.
     */
    public function nuevaPassword(): string
    {
        return $this->validated('password');
    }
}
