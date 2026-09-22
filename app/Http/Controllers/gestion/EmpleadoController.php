<?php

namespace App\Http\Controllers\gestion;

use App\Enums\AmbitoRol;
use App\Enums\ProveedorAuth;
use App\Enums\TipoDireccion;
use App\Http\Requests\EmpleadoRequest;
use App\Models\Empleado;
use App\Models\Rol;
use App\Models\TipoDocumento;
use App\Models\User;
use App\Notifications\InvitacionEmpleado;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

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
 *
 * La contraseña no pasa nunca por este controlador. El empleado nace sin
 * ella y recibe por correo un enlace para elegirla (InvitacionEmpleado). Si
 * despues la olvida, usa "¿Olvidaste tu contraseña?" como cualquier usuario.
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
            ->with([
                // password no se trae al listado; la base calcula si la
                // invitacion sigue pendiente (ver User::invitacionPendiente).
                'user' => fn ($query) => $query
                    ->select(['id', 'email', 'nombres', 'apellido', 'rol_id', 'activo', 'ultimo_acceso_at'])
                    ->selectRaw('password IS NULL AS invitacion_pendiente'),
                'user.rol:id,nombre',
            ])
            ->when($request->filled('q'), function ($query) use ($request) {
                $busqueda = $request->string('q')->trim()->value();

                $query->where(function ($sub) use ($busqueda) {
                    $sub->where('legajo', 'like', "%{$busqueda}%")
                        ->orWhere('nro_doc', 'like', "%{$busqueda}%")
                        ->orWhereHas('user', fn($u) => $u
                            ->where('nombres', 'like', "%{$busqueda}%")
                            ->orWhere('apellido', 'like', "%{$busqueda}%")
                            ->orWhere('email', 'like', "%{$busqueda}%"));
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

        return view('gestion.user.index', [
            'empleados' => $empleados,
            'roles'     => $this->rolesDeGestion(),
            'filtros'   => $request->only('q', 'rol_id', 'estado'),
        ]);
    }

    public function create(): View
    {
        return view('gestion.user.create', [
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
     * Nace SIN contraseña: no puede ingresar hasta que abra el enlace de la
     * invitacion y elija la suya. Asi nadie mas que el la conoce, y completar
     * el enlace demuestra que el correo que cargo el administrativo es real.
     *
     * El mail se manda DESPUES de la transaccion: si el envio falla, el alta
     * queda hecha y la invitacion se puede reenviar desde la ficha.
     */
    public function store(EmpleadoRequest $request): RedirectResponse
    {
        $empleado = DB::transaction(function () use ($request) {
            $usuario = User::create($request->datosDelUsuario() + [
                'password'       => null,
                'proveedor_auth' => ProveedorAuth::Local->value,
                'activo'         => true,
            ]);

            $empleado = $usuario->empleado()->create($request->datosDelEmpleado());

            $this->guardarDireccion($empleado, $request->datosDeLaDireccion());

            return $empleado;
        });

        $usuario   = $empleado->user;
        $respuesta = redirect()
            ->route('gestion.empleados.index')
            ->with('exito', "Se dio de alta a {$usuario->apellido}, {$usuario->nombres}.");

        return $this->enviarInvitacion($usuario)
            ? $respuesta->with('info', "Le enviamos a {$usuario->email} el enlace para elegir su contraseña.")
            : $respuesta->with('error', 'El empleado quedó creado, pero no se pudo enviar el correo de invitación. '
                .'Usá "Reenviar invitación" desde su ficha.');
    }

    public function edit(Empleado $empleado): View
    {
        return view('gestion.user.edit', [
            'empleado'  => $empleado,
            'usuario'   => $empleado->user,
            'direccion' => $empleado->direcciones()->where('tipo', TipoDireccion::Particular->value)->first(),
        ] + $this->opcionesDeFormulario());
    }

    /**
     * No toca la contraseña: el request la rechaza y acá tampoco se escribe.
     *
     * Si cambia el correo, deja de estar verificado. Y si la invitacion seguia
     * pendiente, se reenvia a la direccion nueva: la anterior probablemente
     * estaba mal escrita, que es la razon mas comun para corregirla.
     */
    public function update(EmpleadoRequest $request, Empleado $empleado): RedirectResponse
    {
        $usuario = $empleado->user;

        $cambioElCorreo = DB::transaction(function () use ($request, $empleado, $usuario) {
            $usuario->fill($request->datosDelUsuario());

            $cambioElCorreo = $usuario->isDirty('email');

            if ($cambioElCorreo) {
                $usuario->email_verified_at = null;
            }

            $usuario->save();
            $empleado->update($request->datosDelEmpleado());

            $this->guardarDireccion($empleado, $request->datosDeLaDireccion());

            return $cambioElCorreo;
        });

        $respuesta = redirect()
            ->route('gestion.empleados.index')
            ->with('exito', 'Se guardaron los cambios del empleado.');

        if ($cambioElCorreo && $usuario->invitacionPendiente()) {
            return $this->enviarInvitacion($usuario)
                ? $respuesta->with('info', "Le reenviamos la invitación a {$usuario->email}.")
                : $respuesta->with('error', 'No se pudo reenviar la invitación al correo nuevo. Probá con "Reenviar invitación".');
        }

        return $respuesta;
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
     * Para el empleado al que no le llego el correo o al que se le vencio el
     * enlace. Solo tiene sentido mientras no haya elegido su contraseña: si ya
     * la eligio y la olvido, el camino es "¿Olvidaste tu contraseña?".
     */
    public function reenviarInvitacion(Empleado $empleado): RedirectResponse
    {
        $usuario = $empleado->user;

        if (! $usuario->activo) {
            return back()->with('error', 'El empleado está dado de baja. Reactivalo antes de reenviar la invitación.');
        }

        if (! $usuario->invitacionPendiente()) {
            return back()->with('error', "{$usuario->nombres} ya activó su cuenta. Si olvidó la contraseña, "
                .'puede recuperarla con "¿Olvidaste tu contraseña?" en la pantalla de ingreso.');
        }

        return $this->enviarInvitacion($usuario)
            ? back()->with('exito', "Le reenviamos la invitación a {$usuario->email}.")
            : back()->with('error', 'No se pudo enviar el correo. Revisá la dirección y volvé a intentar.');
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

    /**
     * Un fallo del servidor de correo no tiene que tirar abajo el alta: se
     * registra en el log y el administrativo ve un aviso para reintentar.
     */
    private function enviarInvitacion(User $usuario): bool
    {
        try {
            $usuario->notify(new InvitacionEmpleado());

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
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
