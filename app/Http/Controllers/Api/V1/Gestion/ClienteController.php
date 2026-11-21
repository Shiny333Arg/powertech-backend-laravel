<?php

namespace App\Http\Controllers\Api\V1\Gestion;

use App\Enums\ProveedorAuth;
use App\Http\Controllers\Controller;
use App\Http\Requests\ClienteRequest;
use App\Http\Resources\Gestion\ClienteResource;
use App\Http\Resources\Gestion\ClienteVentaResource;
use App\Models\Cliente;
use App\Models\User;
use App\Support\CuentaDeCliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * Clientes, por API.
 *
 * <b>Es la misma operación que la del panel Blade, con JSON en lugar de
 * vistas.</b> Mismo modelo, misma ClientePolicy, el mismo ClienteRequest —que a
 * su vez comparte sus reglas con el registro de la tienda por el trait
 * ReglasDatosCliente— y el mismo App\Support\CuentaDeCliente para el rol y la
 * invitación. Lo único propio de este archivo es la forma de la respuesta: acá
 * no hay ninguna regla de negocio "de la API".
 *
 * <b>El alta escribe sobre DOS tablas:</b> `users` (con qué correo entra a la
 * tienda) y `clientes` (a quién se le factura). Es la misma cuenta que saldría
 * de registrarse solo en la tienda, nada más que cargada por un empleado.
 *
 * La contraseña no pasa nunca por acá. El cliente nace sin ella y recibe un
 * enlace firmado para elegirla (InvitacionCliente); aceptarlo también le
 * verifica el correo. Mientras tanto la cuenta ya existe y el mostrador puede
 * venderle, cobrarle y facturarle: la verificación sólo hace falta para comprar
 * en la tienda online.
 *
 * <b>Sin destroy, igual que el controlador del panel: un cliente NO se elimina
 * nunca.</b> Está referenciado por ventas y por comprobantes emitidos, y esos
 * comprobantes son historia fiscal que no puede quedar sin sujeto. La ruta
 * DELETE no existe, ClientePolicy::delete() devuelve false siempre y el guard
 * del modelo (ProtegeContraBorrado) es la segunda capa. La única baja es
 * activo = false, por PATCH .../estado.
 *
 * <b>La autorización está en las rutas, no en el constructor.</b> A diferencia
 * del controlador web: ver el comentario de routes/api_gestion.php.
 */
class ClienteController extends Controller
{
    /**
     * Tamaño de página por defecto y tope.
     *
     * 25 es el mismo `paginate(25)` que tenía el listado del panel. El tope
     * está para que nadie pida 10.000 filas.
     */
    private const POR_PAGINA = 25;

    private const POR_PAGINA_MAX = 100;

    /** Las compras que mostraba la ficha por página: `paginate(10)`. */
    private const VENTAS_POR_PAGINA = 10;

    /**
     * GET /api/v1/gestion/clientes
     *
     * Los mismos dos filtros del listado del panel y con los mismos nombres:
     * búsqueda libre sobre razón social y documento, y situación.
     *
     * <b>`activo` es un booleano opcional de tres estados</b> y no el
     * `estado=todos|activos|inactivos` de proveedores: así lo nombra el
     * formulario de esta pantalla desde siempre, y cambiarlo rompería las URL
     * filtradas que alguien tenga guardadas. Sin el parámetro se muestran
     * activos e inactivos, porque es la pantalla desde donde se reactiva un
     * cliente dado de baja: ese criterio ahora vive acá, con el mismo default
     * que tenía el controlador.
     *
     * No trae la cuenta ni las direcciones: la grilla no las muestra. Ver el
     * Resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filtros = $request->validate([
            'q'          => ['nullable', 'string', 'max:100'],
            'activo'     => ['nullable', 'boolean'],
            'por_pagina' => ['nullable', 'integer', 'min:1', 'max:'.self::POR_PAGINA_MAX],
        ]);

        $busqueda = $filtros['q'] ?? null;

        $clientes = Cliente::query()
            // Las dos únicas relaciones que serializa el listado, acotadas a
            // las columnas que salen: sin esto, 25 clientes son 50 consultas
            // más. El codigo_afip entra porque lo emite el Resource —es lo que
            // distingue un CUIT de un DNI sin comparar nombres—, y traerlo
            // cuesta una columna.
            ->with([
                'tipoDocumento:id,nombre,codigo_afip',
                'condicionIva:id,nombre,codigo_afip',
            ])
            ->when($busqueda, fn ($query, $texto) => $query->where(
                fn ($sub) => $sub->where('razon_social', 'like', "%{$texto}%")
                    ->orWhere('nro_doc', 'like', "%{$texto}%")
            ))
            // Las pantallas de gestión necesitan ver lo dado de baja para poder
            // reactivarlo: por eso el filtro es explícito y no un global scope.
            ->when(isset($filtros['activo']), fn ($query) => $query->where('activo', (bool) $filtros['activo']))
            ->orderBy('razon_social')
            ->paginate($filtros['por_pagina'] ?? self::POR_PAGINA)
            ->withQueryString();

        // Los filtros REALMENTE aplicados, misma convención que el resto de la
        // API de gestión: la pantalla no tiene que adivinar si el backend
        // aceptó lo que mandó.
        return ClienteResource::collection($clientes)->additional([
            'filtros' => [
                'q'      => $busqueda,
                'activo' => isset($filtros['activo']) ? (bool) $filtros['activo'] : null,
            ],
        ]);
    }

    /**
     * GET /api/v1/gestion/clientes/{cliente}
     *
     * Lo que pinta la ficha: datos fiscales, contacto, estado de la cuenta en
     * la tienda y direcciones. El historial de compras va aparte, porque se
     * pagina solo.
     */
    public function show(Cliente $cliente): ClienteResource
    {
        return new ClienteResource($this->recargar($cliente));
    }

    /**
     * GET /api/v1/gestion/clientes/{cliente}/ventas
     *
     * El historial de compras de la ficha, paginado de diez en diez como antes.
     *
     * <b>Va bajo 'cliente.ver' y no bajo 'venta.ver'</b>, que es el permiso del
     * módulo de ventas. No es un descuido: es el permiso con el que el panel ya
     * mostraba esta tabla —la ficha entera se autoriza con `view` sobre el
     * cliente— y cambiarlo acá le sacaría al vendedor y al cajero una vista que
     * hoy tienen. El detalle de cada venta sigue exigiendo lo suyo: el enlace
     * va a /gestion/ventas/{venta}, que pide 'venta.ver' como siempre.
     */
    public function ventas(Cliente $cliente): AnonymousResourceCollection
    {
        $ventas = $cliente->ventas()
            ->latest('fecha')
            ->latest('id')
            ->paginate(self::VENTAS_POR_PAGINA)
            ->withQueryString();

        return ClienteVentaResource::collection($ventas);
    }

    /**
     * POST /api/v1/gestion/clientes — 201 con el recurso creado.
     *
     * <b>Las dos filas en una transacción:</b> un user sin su cliente es una
     * cuenta a la que no se le puede facturar, y un cliente sin su user es el
     * cliente de mostrador viejo, que es justamente lo que esta pantalla ya no
     * crea.
     *
     * <b>El correo se manda DESPUÉS de la transacción.</b> Si el envío falla, el
     * alta queda hecha y la invitación se reenvía desde la ficha; que no se le
     * pueda avisar no es razón para perder el cliente. El 201 lo dice en
     * 'invitacion.enviada' en lugar de tirar el alta abajo, que es el
     * equivalente del `withErrors(['estado' => ...])` que devolvía el panel
     * sobre un alta ya consumada.
     *
     * <b>Nace activo</b>, como en el panel: el estado tiene su endpoint y su
     * permiso (cliente.baja), y el cuerpo no lo decide.
     */
    public function store(ClienteRequest $request): JsonResponse
    {
        $cliente = DB::transaction(function () use ($request) {
            $usuario = User::create($request->datosDelUsuario() + [
                'rol_id'         => CuentaDeCliente::rol(),
                'password'       => null,
                'proveedor_auth' => ProveedorAuth::Local->value,
                'activo'         => true,
            ]);

            return $usuario->cliente()->create($request->datosDelCliente() + ['activo' => true]);
        });

        $enviada = CuentaDeCliente::invitar($cliente->user);

        return (new ClienteResource($this->recargar($cliente)))
            ->additional(['invitacion' => ['enviada' => $enviada]])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * PUT /api/v1/gestion/clientes/{cliente} — reemplazo completo.
     *
     * El cuerpo reemplaza el recurso, que es lo que ClienteRequest ya validaba
     * para el formulario web, incluidas la unicidad del correo y la del
     * documento ignorando al propio cliente.
     *
     * <b>Si le cambian el correo, la cuenta deja de estar verificada</b> y el
     * cliente tiene que confirmar la casilla nueva: si no, un empleado podría
     * mudarle la cuenta a una dirección que no es suya y que siguiera pareciendo
     * verificada. No le corta la operatoria de mostrador, sólo la compra online.
     *
     * Y si todavía no eligió su contraseña, el enlace viejo apunta a la casilla
     * anterior: lo que hay que mandar es otra invitación, no una verificación
     * que no lo deja entrar a ningún lado. Las dos reglas son las del panel,
     * textuales.
     *
     * <b>El estado NO se toca desde acá aunque venga en el cuerpo sin
     * querer</b>: tiene su endpoint y su permiso, y mezclarlos dejaría que
     * alguien con 'cliente.editar' diera de baja. Es la misma regla que en
     * categorías, productos y proveedores. Tampoco se toca el `activo` del user:
     * cortarle el acceso a la tienda es otra decisión y no tiene pantalla acá.
     */
    public function update(ClienteRequest $request, Cliente $cliente): ClienteResource
    {
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

        if ($cambioElCorreo) {
            $usuario->password === null
                ? CuentaDeCliente::invitar($usuario)
                : $usuario->sendEmailVerificationNotification();
        }

        // La pantalla escribe su propio aviso con este dato, igual que hace con
        // 'advertencias' en la baja del proveedor: la API manda el hecho, el
        // texto es presentación.
        return (new ClienteResource($this->recargar($cliente)))
            ->additional(['correo' => ['cambio' => $cambioElCorreo]]);
    }

    /**
     * Relee el cliente antes de serializarlo.
     *
     * En el alta hace falta porque las columnas que el cuerpo no trajo —y que
     * tienen default en la tabla— todavía no existen como atributo, y la
     * respuesta las emitiría con el valor equivocado.
     *
     * <b>`password` no se trae: la base calcula si la invitación sigue
     * pendiente y devuelve un booleano.</b> Sin el alias, el modelo llega sin la
     * columna y la daría por nula —o sea pendiente— para todos. Es el mismo
     * `selectRaw` que hacía el controlador web.
     */
    private function recargar(Cliente $cliente): Cliente
    {
        return $cliente->refresh()->load([
            'tipoDocumento',
            'condicionIva',
            'direcciones',
            'user' => fn ($query) => $query
                ->select(['id', 'email', 'nombres', 'apellido', 'activo', 'email_verified_at'])
                ->selectRaw('password IS NULL AS invitacion_pendiente'),
        ]);
    }
}
