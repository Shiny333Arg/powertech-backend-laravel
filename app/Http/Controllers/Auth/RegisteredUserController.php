<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AmbitoRol;
use App\Enums\ProveedorAuth;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegistroClienteRequest;
use App\Models\CondicionIva;
use App\Models\Rol;
use App\Models\TipoDocumento;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

/**
 * Registro de CLIENTES de la tienda. Los empleados no se registran: los da de
 * alta un administrativo (EmpleadoController) y reciben una invitacion.
 *
 * Crea dos filas en una transaccion: el user (credenciales) y el cliente
 * (datos fiscales para facturar).
 */
class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register', [
            // codigo_afip: la vista preselecciona DNI y consumidor final.
            'tiposDocumento'  => TipoDocumento::whereIn('codigo_afip', RegistroClienteRequest::TIPOS_DOCUMENTO_CLIENTE)
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'codigo_afip']),
            'condicionesIva'  => CondicionIva::orderBy('nombre')->get(['id', 'nombre', 'codigo_afip']),
        ]);
    }

    public function store(RegistroClienteRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create($request->datosDelUsuario() + [
                'rol_id'         => $this->idRolCliente(),
                'password'       => $request->validated('password'), // el cast 'hashed' la hashea
                'proveedor_auth' => ProveedorAuth::Local->value,
                'activo'         => true,
            ]);

            $user->cliente()->create($request->datosDelCliente() + ['activo' => true]);

            return $user;
        });

        // Dispara el mail de verificacion (Laravel lo escucha solo, porque
        // User implementa MustVerifyEmail).
        event(new Registered($user));

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->route('verification.notice');
    }

    private function idRolCliente(): int
    {
        return Rol::where('ambito', AmbitoRol::Tienda->value)
            ->where('nombre', 'cliente')
            ->value('id')
            ?? throw new RuntimeException('No existe el rol cliente. Corré RolSeeder.');
    }
}
