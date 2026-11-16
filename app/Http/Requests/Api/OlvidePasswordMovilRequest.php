<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Paso 1 de "olvide mi contraseña" desde la app: pedir el enlace.
 *
 * <b>Las mismas dos reglas que la web.</b>
 * Auth\PasswordResetLinkController valida inline `['email' => ['required',
 * 'email']]`; aca van en un FormRequest, que es lo que usa el resto de la
 * API, pero son las mismas dos. Nada de `exists:users,email`: esa regla
 * convertiria el formulario en un verificador de correos, que es
 * precisamente lo que el mecanismo evita.
 *
 * <b>Endpoint publico, sin token.</b> Quien olvido la contraseña no puede
 * iniciar sesion, asi que no hay Sanctum que lo identifique. La proteccion
 * no es el token: es el `throttle:6,1` de la ruta (el mismo numero que
 * `password.email` en routes/auth.php) mas el throttle propio del broker de
 * 60 segundos por correo (config/auth.php, `passwords.users.throttle`).
 */
class OlvidePasswordMovilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Igual que LoginMovilRequest: el correo entra normalizado para que el
     * teclado del telefono —que suele mandar la primera letra en mayuscula—
     * no sea la diferencia entre encontrar la cuenta y no encontrarla.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => Str::lower(trim((string) $this->input('email'))),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
        ];
    }

    public function correo(): string
    {
        return $this->validated('email');
    }
}
