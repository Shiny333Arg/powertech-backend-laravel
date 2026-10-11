<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

/** Destino del enlace de "olvidé mi contraseña": elegir la nueva. */
class NewPasswordController extends Controller
{
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

        $credenciales = [
            'email'                 => $request->string('email')->trim()->lower()->value(),
            'password'              => $request->input('password'),
            'password_confirmation' => $request->input('password_confirmation'),
            'token'                 => $request->input('token'),
        ];

        // Una cuenta desactivada despues de pedir el enlace no puede usarlo.
        if (User::where('email', $credenciales['email'])->where('activo', false)->exists()) {
            return $this->enlaceInvalido($request);
        }

        $status = Password::reset($credenciales, function (User $user, string $password) {
            $user->forceFill([
                'password'       => $password, // el cast 'hashed' la hashea
                'remember_token' => Str::random(60),
                // Recibir el mail demuestra que el correo es suyo.
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();

            event(new PasswordReset($user));
        });

        return $status === Password::PASSWORD_RESET
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
