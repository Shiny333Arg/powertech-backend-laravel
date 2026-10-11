<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

/**
 * Cambio de la propia contraseña, para empleados y clientes. Es lo unico que
 * un empleado puede modificar de su cuenta ("Mi cuenta" en gestion).
 */
class PasswordController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validateWithBag('updatePassword', [
            // Un cliente que se registro con Google no tiene contraseña que
            // confirmar: la primera que elige la crea.
            'current_password' => $user->password === null
                ? ['nullable']
                : ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        // El cast 'hashed' del modelo la hashea.
        $user->update(['password' => $validated['password']]);

        return back()->with('status', 'password-updated');
    }
}
