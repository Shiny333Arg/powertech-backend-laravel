<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * Paso 2 de "olvide mi contraseña" desde la app: elegir la nueva.
 *
 * <b>Las mismas tres reglas que Auth\NewPasswordController</b>, que es el que
 * atiende el formulario de reset-password.blade.php. Alla estan escritas
 * inline y aca van en un FormRequest, como el resto de la API, pero son
 * identicas: token obligatorio, correo valido, y contraseña confirmada con
 * `Password::defaults()`.
 *
 * <b>Por que `Password::defaults()` y no `min:8` literal.</b> Mismo motivo
 * que PasswordMovilRequest: hoy `defaults()` no esta configurado en
 * AppServiceProvider y equivale a 8 caracteres, pero el dia que el proyecto
 * endurezca la politica, los tres canales —web, cambio de contraseña de la
 * app y esta pantalla— la acompañan solos. Si aca hubiera un literal, este
 * seria el que se queda viejo.
 *
 * <b>El token NO se valida aca.</b> Esta clase solo comprueba que haya
 * llegado algo. Si es el token correcto, si todavia no vencio y si no fue
 * usado ya, lo decide el broker de Laravel dentro de
 * RecuperacionPassword::restablecer(). Intentar adelantarlo con una regla
 * `exists:password_reset_tokens,token` no funcionaria —el token se guarda
 * hasheado— y ademas filtraria la diferencia entre "token invalido" y
 * "correo inexistente", que el mecanismo mantiene indistinguibles a
 * proposito.
 *
 * <b>Endpoint publico, sin token de Sanctum.</b> El token que viaja en el
 * cuerpo es el del correo: es de un solo uso, vence en 60 minutos y no sirve
 * para autenticar ninguna otra llamada. No se guarda en el telefono ni se
 * trata como credencial de sesion.
 */
class NuevaPasswordMovilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => Str::lower(trim((string) $this->input('email'))),
            'token' => trim((string) $this->input('token')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'token'    => ['required', 'string'],
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * Las cuatro claves que espera el broker. `password_confirmation` viaja
     * aunque el broker no la mire: asi el arreglo es el mismo que arma
     * Auth\NewPasswordController y las dos llamadas al servicio son
     * comparables de un vistazo.
     *
     * @return array{email: string, password: string, password_confirmation: string, token: string}
     */
    public function credenciales(): array
    {
        return [
            'email'                 => $this->validated('email'),
            'password'              => $this->validated('password'),
            'password_confirmation' => (string) $this->input('password_confirmation'),
            'token'                 => $this->validated('token'),
        ];
    }

    /**
     * Los mensajes de `required`, `email` y `confirmed` ya estan traducidos
     * en lang/es/validation.php. El unico texto propio es el del token, que
     * por defecto diria "El campo token es obligatorio": en la app ese campo
     * no se escribe a mano, se pega del correo, y el usuario tiene que
     * entender que lo que falta es el enlace.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'token.required' => 'Falta el enlace de recuperación. Pegá el enlace que te llegó por correo o pedí uno nuevo.',
        ];
    }
}
