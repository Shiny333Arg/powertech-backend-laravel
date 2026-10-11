<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AmbitoRol;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Login y logout unicos para empleados y clientes. Despues de ingresar, cada
 * uno va al inicio de su ambito.
 */
class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        // Si antes de loguearse intento abrir una pagina protegida, se lo
        // devuelve ahi, pero solo si esa pagina es de su ambito: un cliente
        // que tipeo /gestion no tiene que terminar rebotando contra un 403.
        $intended = $request->session()->pull('url.intended');

        if ($intended && AmbitoRol::deUrl($intended) === $user->ambitoRol()) {
            return redirect()->to($intended);
        }

        return redirect()->route($user->rutaInicio());
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
