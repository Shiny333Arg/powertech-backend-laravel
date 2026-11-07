<?php

namespace App\Http\Requests\Api;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Login de la app. Hermano de Auth\LoginRequest, no su reemplazo.
 *
 * <b>Por que esta duplicado en lugar de reutilizar aquel.</b>
 * LoginRequest::authenticate() termina en Auth::login(), que abre una sesion
 * y escribe una cookie: es exactamente lo que la API no tiene que hacer. Aca
 * la credencial se valida con Auth::validate() —que no loguea a nadie— y se
 * devuelve el usuario para que el controlador le emita un token.
 *
 * <b>El resto del criterio es el mismo a proposito:</b> mismo mensaje
 * generico ante credenciales incorrectas, y la cuenta dada de baja se informa
 * recien DESPUES de validar la contraseña, para no revelarle a un tercero si
 * el correo existe.
 *
 * <b>El contador de intentos se COMPARTE con el login web.</b> Es la misma
 * clave (correo + IP) y no una propia, porque dos contadores separados le
 * darian a un atacante el doble de intentos por minuto con solo alternar de
 * canal. El costo es que cinco fallos en la app tambien frenan el login web
 * de ese correo desde esa IP durante un minuto, que es el comportamiento
 * correcto.
 */
class LoginMovilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

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
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],

            // Con que nombre queda registrado el token. Lo manda la app
            // (modelo y sistema operativo) y es lo que permitiria, mas
            // adelante, una pantalla de "sesiones activas" con revocacion
            // individual. Opcional: si no viene, se usa un nombre generico.
            'dispositivo' => ['nullable', 'string', 'max:120'],
        ];
    }

    /**
     * Valida la credencial SIN abrir sesion y devuelve el usuario.
     *
     * @throws ValidationException
     */
    public function usuarioValidado(): User
    {
        $this->asegurarQueNoEstaBloqueado();

        // El 'activo' => true viaja como credencial extra: el provider lo
        // agrega como where y una cuenta de baja ni siquiera se recupera.
        if (! Auth::validate($this->only('email', 'password') + ['activo' => true])) {
            RateLimiter::hit($this->claveDeThrottle());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        /** @var User $user */
        $user = Auth::getLastAttempted();

        if (! $user->activo) {
            throw ValidationException::withMessages([
                'email' => trans('auth.inactive'),
            ]);
        }

        RateLimiter::clear($this->claveDeThrottle());

        return $user;
    }

    /** Nombre con el que se registra el token. */
    public function nombreDelDispositivo(): string
    {
        $nombre = trim((string) $this->input('dispositivo'));

        return $nombre !== '' ? $nombre : 'App PowerTech';
    }

    /**
     * @throws ValidationException
     */
    private function asegurarQueNoEstaBloqueado(): void
    {
        if (! RateLimiter::tooManyAttempts($this->claveDeThrottle(), 5)) {
            return;
        }

        event(new Lockout($this));

        $segundos = RateLimiter::availableIn($this->claveDeThrottle());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $segundos,
                'minutes' => ceil($segundos / 60),
            ]),
        ]);
    }

    /**
     * Misma clave que Auth\LoginRequest::throttleKey(): un contador por
     * correo e IP, compartido entre la web y la app. Si aquella cambia, esta
     * tiene que cambiar igual o el contador deja de ser uno solo.
     */
    private function claveDeThrottle(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}