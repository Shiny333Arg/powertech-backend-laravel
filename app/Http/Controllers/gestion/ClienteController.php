<?php

namespace App\Http\Controllers\gestion;

use App\Enums\AmbitoRol;
use App\Enums\ProveedorAuth;
use App\Http\Requests\ClienteRequest;
use App\Models\Cliente;
use App\Models\CondicionIva;
use App\Models\Rol;
use App\Models\TipoDocumento;
use App\Models\User;
use App\Notifications\InvitacionCliente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Throwable;

/**
 * El cliente tiene que ser una entidad y no un texto libre dentro de la venta:
 * sin tipo y numero de documento y condicion frente al IVA no se puede
 * facturar.
 *
 * El alta escribe sobre DOS tablas: users (con que correo entra a la tienda) y
 * clientes (a quien se le factura). Es la misma cuenta que saldria de
 * registrarse solo en la tienda, nada mas que cargada por un empleado.
 *
 * La contraseña no pasa nunca por aca. El cliente nace sin ella y recibe un
 * enlace para elegirla (InvitacionCliente); aceptarlo tambien le verifica el
 * correo. Mientras tanto la cuenta ya existe y el mostrador puede venderle,
 * cobrarle y facturarle: la verificacion solo hace falta para comprar en la
 * tienda online, que es donde vive el middleware 'verified'.
 *
 * <b>No hay destroy().</b> El cliente solo se da de baja: esta referenciado
 * por ventas y comprobantes emitidos. `baja()` y `reactivar()` son la misma
 * operacion en los dos sentidos.
 */
class ClienteController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Cliente::class);

        $filtros = $request->validate([
            'q'      => ['nullable', 'string', 'max:100'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $clientes = Cliente::query()
            ->with(['tipoDocumento:id,nombre', 'condicionIva:id,nombre'])
            ->when($filtros['q'] ?? null, fn ($q, $texto) => $q->where(
                fn ($sub) => $sub->where('razon_social', 'like', "%{$texto}%")
                    ->orWhere('nro_doc', 'like', "%{$texto}%")
            ))
            // Las pantallas de gestion necesitan ver lo dado de baja para
            // poder reactivarlo: por eso el filtro es explicito y no un
            // global scope.
            ->when(isset($filtros['activo']), fn ($q) => $q->where('activo', (bool) $filtros['activo']))
            ->orderBy('razon_social')
            ->paginate(25)
            ->withQueryString();

        return view('gestion.clientes.index', compact('clientes', 'filtros'));
    }

    public function create(): View
    {
        Gate::authorize('create', Cliente::class);

        return view('gestion.clientes.create', $this->catalogos());
    }

    /**
     * Alta.
     *
     * Las dos filas en una transaccion: un user sin su cliente es una cuenta
     * a la que no se le puede facturar, y un cliente sin su user es el cliente
     * de mostrador viejo, que es justamente lo que esta pantalla ya no crea.
     *
     * El mail se manda DESPUES de la transaccion: si el envio falla, el alta
     * queda hecha y la invitacion se reenvia desde la ficha. Que no se le
     * pueda avisar no es razon para perder el cliente, sobre todo si hay una
     * venta esperando para cargarse a su nombre.
     */
    public function store(ClienteRequest $request): RedirectResponse
    {
        Gate::authorize('create', Cliente::class);

        $cliente = DB::transaction(function () use ($request) {
            $usuario = User::create($request->datosDelUsuario() + [
                'rol_id'         => $this->rolDeCliente(),
                'password'       => null,
                'proveedor_auth' => ProveedorAuth::Local->value,
                'activo'         => true,
            ]);

            return $usuario->cliente()->create($request->datosDelCliente() + ['activo' => true]);
        });

        $usuario = $cliente->user;

        $respuesta = redirect()
            ->route('gestion.clientes.show', $cliente)
            ->with('status', "Cliente «{$cliente->razon_social}» creado. Ya se le puede vender y facturar.");

        return $this->enviarInvitacion($usuario)
            ? $respuesta
            : $respuesta->withErrors(['estado' => "El cliente quedó creado, pero no se pudo enviar el correo a "
                ."{$usuario->email}. Reenviá la invitación desde su ficha."]);
    }

    public function show(Cliente $cliente): View
    {
        Gate::authorize('view', $cliente);

        $cliente->load([
            'tipoDocumento',
            'condicionIva',
            'direcciones',
            // password no se trae: la base calcula si la invitacion sigue
            // pendiente y devuelve un booleano (ver User::invitacionPendiente).
            // Sin el alias, el modelo llega sin la columna y la daria por nula,
            // o sea pendiente, para todos.
            'user' => fn ($query) => $query
                ->select(['id', 'email', 'nombres', 'apellido', 'activo', 'email_verified_at'])
                ->selectRaw('password IS NULL AS invitacion_pendiente'),
        ]);

        return view('gestion.clientes.show', [
            'cliente' => $cliente,
            'ventas'  => $cliente->ventas()->latest('fecha')->limit(10)->get(),
        ]);
    }

    public function edit(Cliente $cliente): View
    {
        Gate::authorize('update', $cliente);

        return view('gestion.clientes.edit', [
            'cliente' => $cliente,
            'usuario' => $cliente->user,
        ] + $this->catalogos());
    }

    /**
     * Edicion.
     *
     * Si le cambian el correo, la cuenta deja de estar verificada y el cliente
     * tiene que confirmar la casilla nueva: si no, un empleado podria mudarle
     * la cuenta a una direccion que no es suya y seguir pareciendo verificada.
     * No le corta la operatoria de mostrador, solo la compra online.
     */
    public function update(ClienteRequest $request, Cliente $cliente): RedirectResponse
    {
        Gate::authorize('update', $cliente);

        $usuario = $cliente->user;

        $cambioElCorreo = DB::transaction(function () use ($request, $cliente, $usuario) {
            $cliente->update($request->datosDelCliente());

            if ($usuario === null) {
                return false;
            }

            $usuario->fill($request->datosDelUsuario());

            $cambioElCorreo = $usuario->isDirty('email');

            if ($cambioElCorreo) {
                $usuario->email_verified_at = null;
            }

            $usuario->save();

            return $cambioElCorreo;
        });

        // Si todavia no eligio su contraseña, el enlace viejo apunta a la
        // casilla anterior: lo que hay que mandar es otra invitacion, no una
        // verificacion que no lo deja entrar a ningun lado.
        if ($cambioElCorreo) {
            $usuario->password === null
                ? $this->enviarInvitacion($usuario)
                : $usuario->sendEmailVerificationNotification();
        }

        return redirect()
            ->route('gestion.clientes.show', $cliente)
            ->with('status', $cambioElCorreo
                ? "Cliente actualizado. Le mandamos un correo a {$usuario->email} para que confirme la casilla nueva."
                : 'Cliente actualizado.');
    }

    /**
     * Reenvia el enlace para elegir la contraseña.
     *
     * Solo sirve mientras la cuenta no tenga una: una vez elegida, el camino
     * es "¿Olvidaste tu contraseña?" como cualquier usuario.
     */
    public function reenviarInvitacion(Cliente $cliente): RedirectResponse
    {
        Gate::authorize('update', $cliente);

        $usuario = $cliente->user;

        if ($usuario === null || ! $usuario->invitacionPendiente()) {
            return back()->withErrors(['estado' => 'Este cliente ya activó su cuenta o no tiene una.']);
        }

        return $this->enviarInvitacion($usuario)
            ? back()->with('status', "Le reenviamos la invitación a {$usuario->email}.")
            : back()->withErrors(['estado' => 'No se pudo enviar el correo. Probá de nuevo en un rato.']);
    }

    /**
     * Baja logica. Saca al cliente de la operacion de mostrador; sus compras
     * anteriores lo siguen nombrando.
     *
     * No toca el `activo` de su `user`: cortarle el acceso a la tienda es una
     * decision distinta, y por eso son dos columnas.
     */
    public function baja(Cliente $cliente): RedirectResponse
    {
        Gate::authorize('baja', $cliente);

        $cliente->update(['activo' => false]);

        return back()->with('status', 'Cliente dado de baja.');
    }

    public function reactivar(Cliente $cliente): RedirectResponse
    {
        Gate::authorize('baja', $cliente);

        $cliente->update(['activo' => true]);

        return back()->with('status', 'Cliente reactivado.');
    }

    /**
     * Un fallo del servidor de correo no tiene que tirar abajo el alta: se
     * registra en el log y el empleado ve un aviso para reintentar.
     */
    private function enviarInvitacion(User $usuario): bool
    {
        try {
            $usuario->notify(new InvitacionCliente());

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }

    /**
     * El unico rol de ambito tienda. No se elige en el formulario: un cliente
     * creado desde gestion tiene exactamente los mismos permisos que uno que
     * se registro solo.
     */
    private function rolDeCliente(): int
    {
        return Rol::where('nombre', 'cliente')
            ->where('ambito', AmbitoRol::Tienda->value)
            ->value('id') ?? throw new \RuntimeException(
                'No existe el rol "cliente". Corré RolSeeder.'
            );
    }

    /**
     * @return array<string, mixed>
     */
    private function catalogos(): array
    {
        return [
            'tiposDocumento' => TipoDocumento::orderBy('nombre')->get(),
            'condicionesIva' => CondicionIva::orderBy('nombre')->get(),
        ];
    }
}