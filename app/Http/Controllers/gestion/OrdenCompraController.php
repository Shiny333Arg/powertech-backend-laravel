<?php

namespace App\Http\Controllers\gestion;

use App\Enums\EstadoOrdenCompra;
use App\Enums\OrigenOrdenCompra;
use App\Exceptions\TransicionNoPermitidaException;
use App\Models\OrdenCompra;
use App\Models\User;
use App\Services\OrdenCompraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pantallas de pedidos a proveedores. <b>Ya no es un CRUD completo.</b>
 *
 * El listado, el alta y la edición pasaron a la API en la primera entrega de la
 * migración del módulo, igual que en categorías, productos, proveedores y
 * clientes: la página entrega la estructura y los datos los trae y los escribe
 * el JavaScript de cada pantalla contra /api/v1/gestion/compras.
 *
 * store(), update() y sugerencias() se eliminaron junto con sus rutas —y con
 * ellos los cuatro helpers privados que existían sólo para servirlas:
 * lineasIniciales(), renglon(), omitido() y productosYaPedidos()—. Existían
 * para alimentar formularios Blade, y mientras existieran eran una vía
 * alternativa para ejecutar operaciones de negocio desde controladores web, que
 * es lo que el requisito 3 de la Etapa 3 pide cerrar. Lo que hacía cada una
 * está mapeado a su endpoint en el comentario del bloque de compras de
 * routes/web.php.
 *
 * <b>La lógica de las sugerencias no se duplicó: se mudó.</b> Vive ahora en
 * OrdenCompraController::sugerencias() de la API, con la misma consulta y la
 * misma cuenta de ReposicionService. Hay una sola implementación y cambió de
 * lugar.
 *
 * <b>Las tres transiciones siguen acá, a propósito.</b> aprobar(), enviar() y
 * cancelar() se operan desde los formularios del detalle —show.blade.php—, que
 * es la segunda entrega de la migración. Sus endpoints ya existen desde E.2.a;
 * lo que todavía no se migró es la pantalla que los invoca, y retirar estos
 * métodos antes dejaría el detalle sin poder operar.
 *
 * <b>Este controlador no arma ordenes.</b> No crea lineas, no calcula totales,
 * no manda correos y no toca estados a mano: todo eso vive en
 * OrdenCompraService, que es el mismo que usa el restock automatico y el mismo
 * que invoca la API. Que los tres caminos pasen por el mismo servicio es lo que
 * garantiza que una orden manual, una automatica y una cargada desde la app
 * sean el mismo documento.
 *
 * La orden no se elimina: lo que en otro CRUD seria la D es 'cancelar', que
 * conserva el numero, el historial y —lo importante para el circuito— vuelve a
 * habilitar la reposicion automatica de sus productos.
 */
class OrdenCompraController extends Controller
{
    public function __construct(
        private readonly OrdenCompraService $ordenes,
    ) {
        $this->authorizeResource(OrdenCompra::class, 'orden');
    }

    /**
     * Listado de pedidos.
     *
     * <b>La página ya no trae los pedidos.</b> El listado, los tres filtros y
     * el paginado los resuelve resources/js/gestion/compras/index.js contra
     * GET /api/v1/gestion/compras, con los filtros viajando en la URL para que
     * un listado filtrado se pueda seguir guardando y compartiendo.
     *
     * El endpoint mantiene el criterio de la grilla: muestra pedidos y no
     * repartos —sólo órdenes raíz—, y pone los borradores primero, que es lo
     * que el administrativo entra a hacer.
     *
     * Del servidor bajan tres cosas, y ninguna es un dato de negocio: los dos
     * enums de los filtros, el color del badge de cada estado —que lo define
     * EstadoOrdenCompra::color() y así el panel y el JavaScript no tienen dos
     * paletas— y los permisos del rol, que deciden qué botones se dibujan y no
     * qué se puede hacer. Eso lo sigue exigiendo la API en cada llamada.
     */
    public function index(Request $request): View
    {
        return view('gestion.compras.index', [
            'estados'  => EstadoOrdenCompra::cases(),
            'origenes' => OrigenOrdenCompra::cases(),
            'colores'  => EstadoOrdenCompra::colores(),
            'permisos' => $this->permisosDe($request->user()),
        ]);
    }

    /**
     * El detalle del pedido.
     *
     * <b>Sigue sirviendo los datos desde Blade</b>: es la pantalla de la
     * segunda entrega, donde viven aprobar, enviar, cancelar y cerrar con
     * faltante, y migrarla a medias dejaría la mitad de los botones sin poder
     * operar. Su endpoint —GET /api/v1/gestion/compras/{orden}— ya existe desde
     * E.1 y es el que consume el resto de las pantallas migradas.
     */
    public function show(OrdenCompra $orden): View
    {
        // Los proveedores van completos y no con select acotado: el detalle
        // muestra canal de pedido, contacto y telefono de cada destinatario, y
        // un canal_pedido que no se trajo es indistinguible de uno mal
        // configurado —dispara el cartel de «no recibe pedidos por correo»
        // sobre un proveedor que esta perfecto—.
        $orden->load([
            'proveedor',
            'lineas.producto',
            'lineas.proveedor',
            'usuarioCreo',
            'usuarioAprobo',
            'hijas.proveedor',
            'hijas.lineas',
            'padre:id,numero',
        ]);

        return view('gestion.compras.show', ['orden' => $orden]);
    }

    /**
     * El formulario de alta, vacío.
     *
     * Ya no se precarga un OrdenCompra en blanco ni los renglones iniciales: el
     * pedido nace vacío y se arma en la pantalla. El estado tampoco es un dato
     * del formulario —todo pedido nace borrador, lo decide el servicio— y el
     * destinatario nunca lo fue: sale de los productos que se carguen.
     */
    public function create(): View
    {
        return view('gestion.compras.create');
    }

    /**
     * El formulario de edición del detalle.
     *
     * Entrega el id y nada más: los renglones, las observaciones y a quiénes se
     * les va a pedir los trae la pantalla de
     * GET /api/v1/gestion/compras/{orden}. El binding de ruta sigue sirviendo
     * para dos cosas que no se pueden delegar al JavaScript: el 404 de un
     * pedido que no existe y la autorización de authorizeResource(), que sobre
     * `edit` consulta OrdenCompraPolicy::update() y por lo tanto también el
     * estado.
     */
    public function edit(OrdenCompra $orden): View
    {
        // Una orden ya aprobada o enviada no se edita. Se avisa acá y no en la
        // vista para que el enlace directo tampoco entre.
        abort_unless($orden->esEditable(), 403, 'Solo se puede editar una orden en borrador.');

        return view('gestion.compras.edit', ['orden' => $orden]);
    }

    // ------------------------------------------------------------------
    // Transiciones — segunda entrega de la migración
    // ------------------------------------------------------------------

    /**
     * Requerimiento explicito: ninguna orden sale sin que un administrativo la
     * mire. Es tambien el momento en que un pedido mezclado se reparte en una
     * orden por proveedor: la firma que queda registrada es esta.
     *
     * Y es donde se dan de baja los borradores automaticos que este pedido deja
     * sin sentido. El servicio devuelve cuales fueron para poder decirlo: una
     * anulacion silenciosa es indistinguible de un documento que desaparecio.
     */
    public function aprobar(Request $request, OrdenCompra $orden): RedirectResponse
    {
        $this->authorize('aprobar', $orden);

        try {
            ['anulados' => $anulados] = $this->ordenes->aprobar($orden, $request->user());
        } catch (TransicionNoPermitidaException $error) {
            return back()->with('error', $error->getMessage());
        }

        $hijas = $orden->hijas()->count();

        $aviso = $hijas > 0
            ? "Se aprobó el pedido {$orden->numero} y se emitieron {$hijas} órdenes, una por proveedor. Ya se las puede enviar."
            : "Se aprobó el pedido {$orden->numero}. Ya se le puede enviar al proveedor.";

        if ($anulados !== []) {
            $aviso .= count($anulados) === 1
                ? ' Se canceló el pedido automático '.$anulados[0].', que pedía lo mismo.'
                : ' Se cancelaron los pedidos automáticos '.implode(', ', $anulados).', que pedían lo mismo.';
        }

        return back()->with('exito', $aviso);
    }

    /**
     * Manda el remito por correo.
     *
     * El error de envio se muestra en pantalla en vez de propagarse: lo tipico
     * es un proveedor sin casilla cargada o con canal distinto de email, y eso
     * se arregla en la ficha del proveedor, no reintentando.
     *
     * Sobre un pedido repartido manda el remito de cada proveedor por separado
     * y <b>un fallo no arrastra a los demas</b>: el que no salio queda en
     * 'aprobada' y se reintenta solo, sin volver a molestar a los que ya
     * recibieron el suyo.
     */
    public function enviar(Request $request, OrdenCompra $orden): RedirectResponse
    {
        $this->authorize('enviar', $orden);

        if ($orden->esMadre()) {
            return $this->enviarRepartido($request, $orden);
        }

        try {
            $this->ordenes->enviar($orden, $request->user());
        } catch (TransicionNoPermitidaException $error) {
            return back()->with('error', $error->getMessage());
        } catch (\Throwable $error) {
            report($error);

            return back()->with(
                'error',
                "No se pudo enviar el correo a «{$orden->proveedor->razon_social}». "
                .'El pedido sigue aprobado y se puede reintentar.'
            );
        }

        return back()->with(
            'exito',
            "Se envió el remito del pedido {$orden->numero} a {$orden->proveedor->email}."
        );
    }

    private function enviarRepartido(Request $request, OrdenCompra $orden): RedirectResponse
    {
        $resultado = $this->ordenes->enviarTodas($orden, $request->user());

        $enviadas = $resultado['enviadas'];
        $fallidas = $resultado['fallidas'];

        if ($enviadas->isEmpty() && $fallidas === []) {
            return back()->with('error', "El pedido {$orden->numero} no tiene órdenes pendientes de envío.");
        }

        // Las dos cosas a la vez: salieron dos de tres. Decir solo «se envió»
        // dejaria al administrativo creyendo que el tercer proveedor tambien
        // recibio el suyo.
        if ($fallidas !== []) {
            $detalle = collect($fallidas)
                ->map(fn (string $motivo, string $proveedor) => "{$proveedor}: {$motivo}")
                ->implode(' ');

            return back()
                ->with('exito', $enviadas->isEmpty() ? null : "Se enviaron {$enviadas->count()} de las órdenes del pedido {$orden->numero}.")
                ->with('error', 'No salieron todas. '.$detalle);
        }

        return back()->with(
            'exito',
            "Se envió el remito del pedido {$orden->numero} a los {$enviadas->count()} proveedores."
        );
    }

    /**
     * Cancelar es lo que vuelve a habilitar la reposicion de estos productos:
     * mientras la orden este abierta, el restock los da por pedidos.
     *
     * Sobre un pedido repartido cancela todas sus ordenes. Para dar de baja la
     * de un solo proveedor —el que avisa que no tiene stock— se cancela esa
     * orden y el resto del pedido sigue en pie.
     */
    public function cancelar(Request $request, OrdenCompra $orden): RedirectResponse
    {
        $this->authorize('cancelar', $orden);

        $datos = $request->validate(
            ['motivo' => ['required', 'string', 'max:255']],
            ['motivo.required' => 'Escribí por qué se cancela el pedido.'],
        );

        try {
            $this->ordenes->cancelar($orden, $request->user(), $datos['motivo']);
        } catch (TransicionNoPermitidaException $error) {
            return back()->with('error', $error->getMessage());
        }

        return back()->with(
            'exito',
            "Se canceló el pedido {$orden->numero}. Sus productos vuelven a entrar en la reposición automática."
        );
    }

    // ------------------------------------------------------------------
    // Auxiliares de la pantalla
    // ------------------------------------------------------------------

    /**
     * Qué puede hacer este empleado con los pedidos.
     *
     * Se le pregunta a la MISMA OrdenCompraPolicy que autoriza la API. 'crear'
     * no mira ninguna fila; 'ver' tampoco, porque view() decide por el permiso.
     * Así las claves de permiso siguen escritas en un solo lugar y no se
     * repiten en la vista ni en el JavaScript.
     *
     * <b>Las cuatro transiciones no entran acá.</b> Dependen del estado de cada
     * pedido —y aprobar, además, de quién lo creó—, así que no hay un booleano
     * por pantalla que las represente: se deciden pedido por pedido en el
     * detalle, que es donde viven sus botones.
     *
     * @return array<string, bool>
     */
    private function permisosDe(User $empleado): array
    {
        return [
            'crear' => $empleado->can('create', OrdenCompra::class),
        ];
    }
}
