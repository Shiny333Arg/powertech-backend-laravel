<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

/**
 * "Olvidé mi contraseña", para empleados y clientes.
 *
 * La respuesta es SIEMPRE la misma, exista o no el correo, este o no activa
 * la cuenta: si fuera distinta, este formulario serviria para averiguar quien
 * es cliente o empleado de PowerTech.
 */
class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = $request->string('email')->trim()->lower()->value();

        // A una cuenta dada de baja no se le manda el enlace: no podria
        // ingresar igual.
        if (User::where('email', $email)->where('activo', true)->exists()) {
            Password::sendResetLink(['email' => $email]);
        }

        return back()->with('status', __(Password::RESET_LINK_SENT));
    }
}
