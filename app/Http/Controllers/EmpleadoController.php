<?php

namespace App\Http\Controllers;

use App\Enums\AmbitoRol;
use App\Enums\ProveedorAuth;
use App\Enums\TipoDireccion;
use App\Http\Requests\EmpleadoRequest;
use App\Http\Requests\ResetearPasswordRequest;
use App\Models\Empleado;
use App\Models\Rol;
use App\Models\TipoDocumento;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * CRUD de empleados. Escribe sobre TRES tablas: users (credenciales),
 * empleados (datos personales y fiscales) y direcciones (domicilio).
 *
 * No existe un CRUD de 'users' suelto, a proposito: una fila de users sin su
 * ficha es una cuenta sin dueno, y el catalogo de permisos no tiene ninguna
 * clave 'usuario.*'. Los clientes se dan de alta solos en la tienda y tienen
 * su propio modulo.
 *
 * SIN destroy: un usuario no se elimina nunca.
 */
class EmpleadoController extends Controller
{
    public function __construct()
    {
        // Descomentar cuando exista el login:
        // $this->authorizeResource(Empleado::class, 'empleado');
    }

    public function index(Request $request): View
    {
        $empleados = Empleado::query()
            ->with(['user:id,usuario,email,nombres,apellido,rol_id,activo,forzar_cambio_password,ultimo_acceso_at', 'user.rol:id,nombre'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $busqueda = $request->string('q')->trim()->value();

                $query->where(function ($sub) use ($busqueda) {
                    $sub->where('legajo', 'like', "%{$busqueda}%")
                        ->orWhere('nro_doc', 'like', "%{$busqueda}%")
                        ->orWhereHas('user', fn($u) => $u
                            ->where('nombres', 'like', "%{$busqueda}%")
                            ->orWhere('apellido', 'like', "%{$busqueda}%")
                            ->orWhere('usuario', 'like', "%{$busqueda}%"));
                });
            })
            ->when($request->filled('rol_id'), fn($query) => $query
                ->whereHas('user', fn($u) => $u->where('rol_id', $request->integer('rol_id'))))
            // La baja del empleado vive en users.activo, no en empleados.
            ->when($request->query('estado') === 'activos', fn($query) => $query
                ->whereHas('user', fn($u) => $u->where('activo', true)))
            ->when($request->query('estado') === 'inactivos', fn($query) => $query
                ->whereHas('user', fn($u) => $u->where('activo', false)))
            ->orderBy('legajo')
            ->paginate(20)
            ->withQueryString();

        return view('user.index', [
            'empleados' => $empleados,
            'roles'     => $this->rolesDeGestion(),
            'filtros'   => $request->only('q', 'rol_id', 'estado'),
        ]);
    }

    public function create(): View
    {
        return view('user.create', [
            'empleado'  => new Empleado(['fecha_ingreso' => now()->toDateString()]),
            'usuario'   => new User(['activo' => true]),
            'direccion' => null,
        ] + $this->opcionesDeFormulario());
    }

    /**
     * Alta.
     *
     * Las tres filas en una transaccion: un user sin su empleado es una cuenta
     * que puede entrar al sistema sin que nadie sepa de quien es.
     *
     * Nace SIEMPRE con forzar_cambio_password: la contrasena que carga el
     * administrativo es provisoria por definicion, porque la conoce alguien
     * distinto del dueno de la cuenta.
     */
    public function store(EmpleadoRequest $request): RedirectResponse
    {
        $passwordProvisoria = $request->validated('password') ?: Str::random(12);

        $empleado = DB::transaction(function () use ($request, $passwordProvisoria) {
            $usuario = User::create($request->datosDelUsuario() + [
                'password'               => $passwordProvisoria, // el cast 'hashed' la hashea
                'proveedor_auth'         => ProveedorAuth::Local->value,
                'forzar_cambio_password' => true,
                'activo'                 => true,
            ]);

            $empleado = $usuario->empleado()->create($request->datosDelEmpleado());

            $this->guardarDireccion($empleado, $request->datosDeLaDireccion());

            return $empleado;
        });

        return redirect()
            ->route('user.index')
            ->with('exito', "Se dio de alta a {$empleado->user->apellido}, {$empleado->user->nombres}.")
            // Se muestra una sola vez: en la base queda hasheada y no hay forma
            // de recuperarla despues.
            ->with('password_provisoria', $passwordProvisoria);
    }

    public function edit(Empleado $empleado): View
    {
        return view('user.edit', [
            'empleado'  => $empleado,
            'usuario'   => $empleado->user,
            'direccion' => $empleado->direcciones()->where('tipo', TipoDireccion::Particular->value)->first(),
        ] + $this->opcionesDeFormulario());
    }

    /** No toca la contrasena: el request la rechaza y acá tampoco se escribe. */
    public function update(EmpleadoRequest $request, Empleado $empleado): RedirectResponse
    {
        DB::transaction(function () use ($request, $empleado) {
            $empleado->user->update($request->datosDelUsuario());
            $empleado->update($request->datosDelEmpleado());

            $this->guardarDireccion($empleado, $request->datosDeLaDireccion());
        });

        return redirect()
            ->route('user.index')
            ->with('exito', 'Se guardaron los cambios del empleado.');
    }

    /**
     * Baja logica.
     *
     * Toca dos columnas: users.activo corta el acceso y empleados.fecha_baja
     * deja asentado desde cuando ya no trabaja. El legajo se conserva, y las
     * ventas, cobros y movimientos que registro lo siguen nombrando.
     */
    public function darDeBaja(Empleado $empleado): RedirectResponse
    {
        if ($error = $this->motivoParaNoDarDeBaja($empleado)) {
            return back()->with('error', $error);
        }

        DB::transaction(function () use ($empleado) {
            $empleado->user->darDeBaja();
            $empleado->update(['fecha_baja' => now()->toDateString()]);
        });

        return back()->with('exito', "{$empleado->user->apellido} ya no tiene acceso al sistema. Su historial queda intacto.");
    }

    public function reactivar(Empleado $empleado): RedirectResponse
    {
        DB::transaction(function () use ($empleado) {
            $empleado->user->reactivar();
            $empleado->update(['fecha_baja' => null]);
        });

        return back()->with('exito', "{$empleado->user->apellido} vuelve a tener acceso al sistema.");
    }

    /**
     * Circuito de contrasena olvidada. No hay autogestion: el empleado avisa a
     * un administrativo y el administrativo le asigna una provisoria.
     *
     * OJO con una diferencia respecto del texto del requerimiento, que habla
     * solo de "activar un flag": el flag por si solo no devuelve el acceso a
     * alguien que olvido la clave, porque seguiria sin poder entrar. Por eso
     * acá se asignan las dos cosas, clave nueva y flag.
     */
    public function resetearPassword(ResetearPasswordRequest $request, Empleado $empleado): RedirectResponse
    {
        $passwordProvisoria = $request->validated('password') ?: Str::random(12);

        $empleado->user->update([
            'password'               => $passwordProvisoria,
            'forzar_cambio_password' => true,
        ]);

        return back()
            ->with('exito', "Se asignó una contraseña provisoria a {$empleado->user->usuario}. En el próximo ingreso va a tener que cambiarla.")
            ->with('password_provisoria', $passwordProvisoria);
    }

    /**
     * Dos casos en los que la baja dejaria el sistema en un estado del que no
     * se sale desde la pantalla.
     */
    private function motivoParaNoDarDeBaja(Empleado $empleado): ?string
    {
        if (auth()->id() !== null && auth()->id() === $empleado->user_id) {
            return 'No podés darte de baja a vos mismo. Pedíselo a otro administrador.';
        }

        $esAdministrador = $empleado->user->rol?->nombre === 'administrador';

        if ($esAdministrador && $this->administradoresActivos() <= 1) {
            return 'Es el único administrador activo. Si lo das de baja, nadie va a poder administrar el sistema.';
        }

        return null;
    }

    private function administradoresActivos(): int
    {
        return User::where('activo', true)
            ->whereHas('rol', fn($query) => $query->where('nombre', 'administrador'))
            ->count();
    }

    /** Un solo domicilio particular por empleado, igual que en proveedores. */
    private function guardarDireccion(Empleado $empleado, ?array $datos): void
    {
        if ($datos === null) {
            return;
        }

        $empleado->direcciones()->updateOrCreate(
            ['tipo' => TipoDireccion::Particular->value],
            $datos + ['es_predeterminada' => true],
        );
    }

    /** @return array<string, mixed> */
    private function opcionesDeFormulario(): array
    {
        return [
            'roles'           => $this->rolesDeGestion(),
            'tiposDocumento'  => TipoDocumento::orderBy('nombre')->get(['id', 'nombre']),
        ];
    }

    /** El rol cliente no se ofrece: pertenece al ambito tienda. */
    private function rolesDeGestion()
    {
        return Rol::where('ambito', AmbitoRol::Gestion->value)
            ->where('nombre', '!=', 'administrador')
            ->orderBy('nombre')
            ->get(['id', 'nombre']);
    }
}
