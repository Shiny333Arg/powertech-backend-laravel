<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Aceptacion de una invitacion: el usuario elige su contraseña y entra.
 *
 * Sirve para los dos ambitos. El empleado la recibe al ser dado de alta
 * (InvitacionEmpleado) y el cliente cargado desde el mostrador tambien
 * (InvitacionCliente): en los dos casos la cuenta nace sin contraseña y el
 * enlace es lo unico que la activa. Lo que cambia es el texto del correo y a
 * donde cae al entrar, y de eso se encarga rutaInicio().
 *
 * El enlace es una URL firmada con vencimiento. Es de un solo uso sin
 * necesitar ninguna tabla: solo se acepta mientras la cuenta no tenga
 * contraseña, y aceptarla le asigna una.
 *
 * Aceptar tambien VERIFICA el correo, y es el punto de todo el mecanismo: el
 * enlace solo pudo haber llegado a esa casilla, asi que completarlo prueba
 * que la direccion que cargo el empleado es real.
 *
 * La firma se valida aca y no con el middleware 'signed' para poder mostrar
 * un mensaje util en lugar de un 403 generico. El formulario de la vista
 * tiene que enviarse a la MISMA URL firmada: action="{{ request()->fullUrl() }}".
 */
class InvitacionController extends Controller
{
    public function show(Request $request, User $user): View|RedirectResponse
    {
        return $this->rechazo($request, $user)
            ?? view('auth.aceptar-invitacion', ['user' => $user]);
    }

    public function store(Request $request, User $user): RedirectResponse
    {
        if ($rechazo = $this->rechazo($request, $user)) {
            return $rechazo;
        }

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->forceFill([
            'password'          => $validated['password'], // el cast 'hashed' la hashea
            'email_verified_at' => $user->email_verified_at ?? now(), // el enlace llego a su correo
        ])->save();

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()
            ->route($user->rutaInicio())
            ->with('exito', "¡Bienvenido, {$user->nombres}! Tu cuenta ya está activa.");
    }

    /**
     * null si la invitacion se puede aceptar; si no, la redireccion al login
     * con el motivo. "Ya la usaste" no es un error: es un aviso.
     *
     * No se mira el ambito del rol: la invitacion vale para cualquier cuenta
     * que todavia no tenga contraseña, y esa condicion ya es la que la hace
     * de un solo uso.
     */
    private function rechazo(Request $request, User $user): ?RedirectResponse
    {
        $login = redirect()->route('login');

        if (! $request->hasValidSignature()) {
            return $login->with('error', 'El enlace de invitación venció o no es válido. Pedí que te lo '
                .'reenvíen, o usá "¿Olvidaste tu contraseña?" con tu correo.');
        }

        if (! $user->activo) {
            return $login->with('error', 'Esta invitación no está disponible.');
        }

        if ($user->password !== null) {
            return $login->with('info', 'Esta invitación ya fue utilizada. Ingresá con tu correo y tu contraseña.');
        }

        return null;
    }
}