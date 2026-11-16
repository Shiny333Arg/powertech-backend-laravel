<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\RegistroClienteRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\CondicionIva;
use App\Models\TipoDocumento;
use App\Services\PerfilService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Perfil del CLIENTE de la tienda: sus datos personales y fiscales.
 *
 * Los empleados no pasan por aca (las rutas llevan 'ambito:tienda'): lo unico
 * que pueden cambiar de su cuenta es la contraseña, desde "Mi cuenta".
 *
 * Diferencias con el ProfileController de Breeze:
 *  - Escribe en dos tablas (users y clientes) en una transaccion.
 *  - No tiene destroy: un usuario no se elimina nunca.
 *
 * <b>La escritura vive en PerfilService.</b> Estaba inline en update() y la
 * necesita igual Api\V1\PerfilController: son dos tablas en una transaccion y
 * la baja de la verificacion al cambiar el correo. Copiada en los dos
 * controladores, la web y la app harian cosas distintas con el mismo
 * formulario en cuanto se agregue un campo.
 */
class ProfileController extends Controller
{
    public function __construct(
        private readonly PerfilService $perfil,
    ) {
    }

    public function edit(Request $request): View
    {
        $user = $request->user();

        $cliente = $user->cliente;

        abort_if($cliente === null, 404);

        return view('cliente.perfil.edit', [
            'user'           => $user,
            'cliente'        => $cliente,
            'tiposDocumento' => TipoDocumento::whereIn('codigo_afip', RegistroClienteRequest::TIPOS_DOCUMENTO_CLIENTE)
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'codigo_afip']),
            'condicionesIva' => CondicionIva::orderBy('nombre')->get(['id', 'nombre', 'codigo_afip']),
            // Misma regla que ProfileUpdateRequest: con compras, el documento queda fijo.
            'documentoBloqueado' => $this->perfil->documentoBloqueado($cliente),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $cambioElCorreo = $this->perfil->actualizar(
            $request->user(),
            $request->cliente(),
            $request->datosDelUsuario(),
            $request->datosDelCliente(),
        );

        return redirect()
            ->route('profile.edit')
            // El correo nuevo tiene que confirmarse antes de poder comprar.
            ->with('status', $cambioElCorreo ? 'verification-link-sent' : 'profile-updated');
    }
}
