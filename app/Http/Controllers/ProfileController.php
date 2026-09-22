<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\RegistroClienteRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\CondicionIva;
use App\Models\TipoDocumento;
use App\Models\Venta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
 */
class ProfileController extends Controller
{
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
            'documentoBloqueado' => Venta::where('cliente_id', $cliente->id)->exists(),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user    = $request->user();
        $cliente = $request->cliente();

        $cambioElCorreo = DB::transaction(function () use ($request, $user, $cliente) {
            $user->fill($request->datosDelUsuario());

            $cambioElCorreo = $user->isDirty('email');

            if ($cambioElCorreo) {
                $user->email_verified_at = null;
            }

            $user->save();
            $cliente->update($request->datosDelCliente());

            return $cambioElCorreo;
        });

        // El correo nuevo tiene que confirmarse antes de poder comprar.
        if ($cambioElCorreo) {
            $user->sendEmailVerificationNotification();
        }

        return redirect()
            ->route('profile.edit')
            ->with('status', $cambioElCorreo ? 'verification-link-sent' : 'profile-updated');
    }
}
