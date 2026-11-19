<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AmbitoRol;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\SesionGestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Ingreso y cierre de sesion de la WEB.
 *
 * <b>Dos puertas de entrada desde F2, una por publico.</b>
 *
 *   /login          storefront (clientes). POST tradicional, sin JavaScript,
 *                   sin emitir ningun token. No cambio nada respecto de la
 *                   Etapa 2.
 *
 *   /gestion/login  panel (empleados). El formulario envia las credenciales por
 *                   JS a POST /api/v1/auth/login y despues intercambia el token
 *                   por sesion en POST /gestion/sesion.
 *
 * Son dos puertas y no un formulario que se adapte porque el ambito no se puede
 * conocer antes de validar las credenciales —hay un solo juego de
 * credenciales—. Separar la entrada es lo unico que permite que el panel ingrese
 * por la API, como exige el requisito 6, sin obligar al storefront a depender de
 * JavaScript: ese circuito la Etapa 3 no pide tocarlo.
 */
class AuthenticatedSessionController extends Controller
{
    /** Ingreso del storefront. */
    public function create(): View
    {
        return view('auth.login');
    }

    /** Ingreso del panel. Misma tarjeta, mismo diseño, otro mecanismo. */
    public function createGestion(): View
    {
        return view('auth.login-gestion');
    }

    /**
     * Ingreso tradicional del storefront.
     *
     * <b>Una cuenta de gestion no abre sesion por aca.</b> Si lo hiciera, el
     * empleado entraria al panel sin token y el middleware de coherencia lo
     * sacaria en el primer request. Se lo deriva a su puerta, que valida las
     * credenciales contra la API y emite el token. El costo es que tiene que
     * tipearlas una vez mas; a cambio, el panel entra por el endpoint
     * compartido. La alternativa —emitir el token aca— es justamente la que
     * incumple el requisito 6.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = $request->user();

        if ($user->ambitoRol() === AmbitoRol::Gestion) {

            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('gestion.login')->withErrors([
                'email' => 'El panel de gestión tiene su propio ingreso. Entrá por acá.',
            ]);
        }

        $request->session()->regenerate();

        // Si antes de loguearse intento abrir una pagina protegida, se lo
        // devuelve ahi, pero solo si esa pagina es de su ambito: un cliente
        // que tipeo /gestion no tiene que terminar rebotando contra un 403.
        $intended = $request->session()->pull('url.intended');

        if ($intended && AmbitoRol::deUrl($intended) === $user->ambitoRol()) {
            return redirect()->to($intended);
        }

        return redirect()->route($user->rutaInicio());
    }

    /**
     * Cierra la sesion WEB. <b>No toca tokens.</b>
     *
     * Desde F2 la revocacion vive en un solo lugar del sistema, el endpoint
     * compartido POST /api/v1/auth/logout, que atiende a Flutter por Bearer y al
     * panel por cookie. Que esta ruta tambien borrara el token la convertiria en
     * un segundo mecanismo de revocacion, que es exactamente lo que el
     * requisito 6 no admite.
     *
     * Sigue existiendo porque el storefront la necesita: sus clientes se
     * autentican por sesion y no tienen token que revocar. Un empleado no
     * deberia llegar aca —su boton de salir apunta al endpoint compartido—, pero
     * si llega con una pestaña vieja, se le limpian las claves de la sesion y se
     * lo manda a su propia puerta. El token queda vivo hasta vencer, y el panel
     * no se abre sin sesion: el middleware de coherencia exige las dos cosas.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $esDeGestion = $request->user()?->ambitoRol() === AmbitoRol::Gestion;

        SesionGestion::olvidar($request->session());

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route($esDeGestion ? 'gestion.login' : 'login');
    }
}
