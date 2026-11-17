<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\RecuperacionPassword;
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
 *
 * <b>El mecanismo se mudo a App\Services\RecuperacionPassword</b> para que la
 * API movil pueda usar el mismo y no una copia. El comportamiento de esta
 * pantalla no cambio: valida igual, manda el mismo correo y vuelve con el
 * mismo `status`. Lo unico que se fue de aca es la consulta de la cuenta
 * activa y la llamada al broker, que ahora viven en el servicio.
 */
class PasswordResetLinkController extends Controller
{
    public function __construct(
        private readonly RecuperacionPassword $recuperacion,
    ) {
    }

    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // El servicio normaliza el correo, descarta las cuentas de baja y
        // manda el enlace. No devuelve nada: ver el comentario de
        // RecuperacionPassword::enviarEnlace().
        $this->recuperacion->enviarEnlace($request->input('email'));

        return back()->with('status', __(Password::RESET_LINK_SENT));
    }
}
