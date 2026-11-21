<?php

namespace App\Http\Controllers\gestion;

use App\Models\Cliente;
use App\Models\CondicionIva;
use App\Models\TipoDocumento;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Las pantallas de clientes del panel. <b>Ya no es un CRUD.</b>
 *
 * Entrega las cuatro páginas Blade —listado, alta, ficha y edición— y nada más.
 * Los datos los trae y los modifica el JavaScript de cada pantalla contra
 * /api/v1/gestion/clientes, igual que en categorías, productos y proveedores.
 *
 * store(), update(), baja(), reactivar() y reenviarInvitacion() se eliminaron
 * junto con sus rutas: existían para servirlas, y mientras existieran eran una
 * vía alternativa para ejecutar operaciones de negocio desde controladores web,
 * que es lo que el requisito 3 de la Etapa 3 pide cerrar. Lo que hacía cada una
 * está mapeado a su endpoint en el comentario del bloque CLIENTES de
 * routes/web.php.
 *
 * Con ellas se fue también el grupo `gestion.direcciones.*` y su
 * DireccionController: esta ficha era su único consumidor —el proveedor y el
 * empleado mandan su domicilio anidado en el cuerpo de su propio formulario—,
 * así que las direcciones del cliente pasaron a colgar de
 * /api/v1/gestion/clientes/{cliente}/direcciones.
 *
 * <b>Las reglas no se movieron de lugar:</b> el alta y la edición siguen pasando
 * por el mismo ClienteRequest —que comparte sus reglas con el registro de la
 * tienda a través de ReglasDatosCliente—, la misma ClientePolicy y el mismo
 * DireccionRequest. Lo que cambió es quién los invoca.
 *
 * <b>Un cliente sigue siendo DOS filas</b>, `users` y `clientes`, y la
 * contraseña sigue sin pasar por ninguna pantalla: el cliente nace sin ella y
 * la elige desde el enlace que recibe por correo. Esas reglas viven ahora en
 * Api\V1\Gestion\ClienteController y en App\Support\CuentaDeCliente.
 *
 * El cliente está referenciado por ventas y por comprobantes emitidos, así que
 * no se elimina nunca: la única baja es activo = false, ahora por
 * PATCH .../estado.
 *
 * <b>Lo único que las páginas siguen trayendo de la base son los
 * desplegables</b> —tipos de documento y condiciones frente al IVA—: son el
 * catálogo de opciones del formulario, no los datos del cliente. Ninguno de los
 * dos tiene endpoint propio; crear uno pedía inventar también el permiso que lo
 * protege. Queda anotado como pendiente, igual que en proveedores.
 */
class ClienteController extends Controller
{
    /**
     * Listado de gestión.
     *
     * <b>La página ya no trae los clientes.</b> El listado, los dos filtros y el
     * paginado los resuelve resources/js/gestion/clientes/index.js contra
     * GET /api/v1/gestion/clientes, con los filtros viajando en la URL para que
     * un listado filtrado se pueda seguir guardando y compartiendo.
     *
     * Muestra activos e inactivos por defecto, a propósito: es la pantalla desde
     * donde se reactiva un cliente dado de baja. Ese criterio ahora vive en el
     * endpoint, que tiene el mismo default.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Cliente::class);

        return view('gestion.clientes.index', [
            'permisos' => $this->permisosDe($request->user()),
        ]);
    }

    /** El formulario de alta, vacío. */
    public function create(Request $request): View
    {
        Gate::authorize('create', Cliente::class);

        return view('gestion.clientes.create', $this->opcionesDeFormulario());
    }

    /**
     * La ficha del cliente.
     *
     * Entrega el id y los permisos del rol: los datos del cliente, el estado de
     * su cuenta, sus direcciones y su historial de compras los pide la pantalla
     * a la API. El binding de ruta sigue sirviendo para dos cosas que no se
     * pueden delegar al JavaScript: el 404 de un cliente que no existe y la
     * autorización de Gate::authorize().
     */
    public function show(Request $request, Cliente $cliente): View
    {
        Gate::authorize('view', $cliente);

        return view('gestion.clientes.show', [
            'cliente'  => $cliente,
            'permisos' => $this->permisosDe($request->user()),
        ]);
    }

    /**
     * El formulario de edición.
     *
     * Entrega el id y los desplegables. El bloque de la cuenta se muestra o se
     * esconde según la respuesta de la API y no según una variable de la vista:
     * el cliente de mostrador viejo (user_id nulo) no tiene nada de `users` que
     * editar, y eso lo dice el detalle.
     */
    public function edit(Request $request, Cliente $cliente): View
    {
        Gate::authorize('update', $cliente);

        return view('gestion.clientes.edit', [
            'cliente'  => $cliente,
            'permisos' => $this->permisosDe($request->user()),
        ] + $this->opcionesDeFormulario());
    }

    /**
     * Qué puede hacer este empleado con los clientes.
     *
     * Se le pregunta a la MISMA ClientePolicy que autoriza la API, con una
     * instancia vacía: la policy decide por el permiso del rol y no mira la
     * fila. Así las claves de permiso siguen escritas en un solo lugar y no se
     * repiten en la vista ni en el JavaScript.
     *
     * <b>Acá los botones por permiso no son decorativos.</b> Según la matriz de
     * PermisoSeeder, el vendedor y el cajero tienen ver, crear y editar y NO
     * tienen 'cliente.baja': con cualquiera de esos dos roles la columna de
     * acciones sale sin los botones de baja y reactivación, y el endpoint los
     * rechazaría igual con un 403.
     *
     * @return array<string, bool>
     */
    private function permisosDe(User $empleado): array
    {
        $cualquiera = new Cliente;

        return [
            'crear'  => $empleado->can('create', Cliente::class),
            'editar' => $empleado->can('update', $cualquiera),
            'baja'   => $empleado->can('baja', $cualquiera),
        ];
    }

    /** @return array<string, mixed> */
    private function opcionesDeFormulario(): array
    {
        return [
            'tiposDocumento' => TipoDocumento::orderBy('nombre')->get(),
            'condicionesIva' => CondicionIva::orderBy('nombre')->get(),
        ];
    }
}
