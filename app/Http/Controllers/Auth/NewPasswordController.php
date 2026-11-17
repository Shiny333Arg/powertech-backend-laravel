<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\RecuperacionPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

/**
 * Destino del enlace de "olvidé mi contraseña": elegir la nueva.
 *
 * <b>El mecanismo se mudo a App\Services\RecuperacionPassword</b> para que la
 * API movil pueda usar el mismo y no una copia. Esta pantalla sigue haciendo
 * exactamente lo de antes: las mismas tres reglas de validacion, el mismo
 * rechazo de la cuenta dada de baja, la misma redireccion al login y el mismo
 * mensaje de error cuando el enlace no sirve. Lo que se fue al servicio es el
 * cuerpo del `Password::reset()` —el guardado de la contraseña, el
 * `remember_token`, la verificacion del correo y el evento—, que es
 * justamente lo que la API necesitaba reutilizar.
 */
class NewPasswordController extends Controller
{
    public function __construct(
        private readonly RecuperacionPassword $recuperacion,
    ) {
    }

    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = $this->recuperacion->restablecer([
            'email'                 => $request->input('email'),
            'password'              => $request->input('password'),
            'password_confirmation' => $request->input('password_confirmation'),
            'token'                 => $request->input('token'),
        ]);

        return $this->recuperacion->fueRestablecida($status)
            ? redirect()->route('login')->with('status', __($status))
            : $this->enlaceInvalido($request);
    }

    /**
     * Correo inexistente y token vencido dan el mismo mensaje, por la misma
     * razon que en PasswordResetLinkController.
     */
    private function enlaceInvalido(Request $request): RedirectResponse
    {
        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __(Password::INVALID_TOKEN)]);
    }
}
