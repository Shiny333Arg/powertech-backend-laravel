<?php

namespace App\Http\Controllers\gestion;

use App\Enums\EstadoOrdenCompra;
use App\Enums\OrigenOrdenCompra;
use App\Exceptions\TransicionNoPermitidaException;
use App\Http\Requests\OrdenCompraRequest;
use App\Models\OrdenCompra;
use App\Models\OrdenCompraLinea;
use App\Models\Producto;
use App\Services\OrdenCompraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pantalla de ordenes de compra, sin destroy.
 *
 * La orden no se elimina: lo que en otro CRUD seria la D es 'cancelar', que
 * conserva el numero, el historial y —lo importante para el circuito— vuelve a
 * habilitar la reposicion automatica de sus productos.
 *
 * <b>Este controlador no arma ordenes.</b> No crea lineas, no calcula totales,
 * no manda correos y no toca estados a mano: todo eso vive en
 * OrdenCompraService, que es el mismo que usa el restock automatico. Que los
 * dos caminos pasen por el mismo servicio es lo que garantiza que una orden
 * manual y una automatica sean el mismo documento.
 *
 * <b>El alta ya no empieza eligiendo proveedor.</b> El formulario acepta
 * cualquier producto del catalogo y el destinatario sale del detalle. Si hay
 * varios proveedores, el pedido se reparte en una orden por cada uno al
 * aprobarse, y cada proveedor recibe por correo solamente sus renglones.
 */
class OrdenCompraController extends Controller
{
    public function __construct(
        private readonly OrdenCompraService $ordenes,
    ) {
        $this->authorizeResource(OrdenCompra::class, 'orden');
    }

    /**
     * El listado muestra pedidos, no repartos.
     *
     * Una hija ya esta representada por su madre: mostrar las dos convertiria
     * un pedido de tres proveedores en cuatro filas y el administrativo no
     * sabria cual tiene que aprobar. Las hijas se ven en el detalle de su
     * pedido, que es donde se envian y se reciben.
     */
    public function index(Request $request): View
    {
        $ordenes = OrdenCompra::query()
            ->raiz()
            // Las dos relaciones de proveedor que mira proveedoresInvolucrados():
            // las hijas si el pedido ya se repartio, las lineas si sigue en
            // borrador. Cargarlas acá convierte un N+1 por fila en dos consultas.
            ->with([
                'proveedor:id,razon_social',
                'hijas:id,orden_padre_id,proveedor_id,estado',
                'hijas.proveedor:id,razon_social',
                'lineas:id,orden_compra_id,proveedor_id',
                'lineas.proveedor:id,razon_social',
            ])
            ->withCount('lineas')
            ->when($request->filled('q'), function ($query) use ($request) {
                $busqueda = $request->string('q')->trim()->value();

                $query->where(function ($sub) use ($busqueda) {
                    $sub->where('numero', 'like', "%{$busqueda}%")
                        ->orWhereHas('proveedor', fn ($p) => $p->where('razon_social', 'like', "%{$busqueda}%"))
                        // Un pedido repartido no tiene proveedor propio: se lo
                        // busca por el de cualquiera de sus ordenes.
                        ->orWhereHas('hijas.proveedor', fn ($p) => $p->where('razon_social', 'like', "%{$busqueda}%"));
                });
            })
            ->when($request->filled('estado'), fn ($query) => $query->where('estado', $request->query('estado')))
            ->when($request->filled('origen'), fn ($query) => $query->where('origen', $request->query('origen')))
            // Pendientes de aprobacion primero: es lo que el administrativo
            // entra a hacer. Ordenar por fecha dejaria lo accionable mezclado
            // con lo que ya siguio su curso.
            ->orderByRaw("FIELD(estado, 'borrador') DESC")
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('gestion.compras.index', [
            'ordenes'  => $ordenes,
            'estados'  => EstadoOrdenCompra::cases(),
            'origenes' => OrigenOrdenCompra::cases(),
            'filtros'  => $request->only('q', 'estado', 'origen'),
        ]);
    }

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

    public function create(): View
    {
        return view('gestion.compras.create', [
            'orden'           => new OrdenCompra(['estado' => EstadoOrdenCompra::Borrador]),
            'lineasIniciales' => $this->lineasIniciales(null),
        ]);
    }

    public function store(OrdenCompraRequest $request): RedirectResponse
    {
        $orden = $this->ordenes->crear(
            $request->lineas(),
            $request->user(),
            $request->validated('observaciones'),
        );

        $aviso = $orden->esMadre()
            ? "Se creó el pedido {$orden->numero} en borrador, con productos de "
                .$orden->proveedoresDelDetalle()->count()
                .' proveedores. Al aprobarlo se emite una orden para cada uno.'
            : "Se creó el pedido {$orden->numero} en borrador. Falta aprobarlo para poder enviarlo.";

        return redirect()
            ->route('gestion.compras.show', $orden)
            ->with('exito', $aviso);
    }

    public function edit(OrdenCompra $orden): View
    {
        // Una orden ya aprobada o enviada no se edita. Se avisa acá y no en la
        // vista para que el enlace directo tampoco entre.
        abort_unless($orden->esEditable(), 403, 'Solo se puede editar una orden en borrador.');

        $orden->load(['proveedor', 'lineas.producto', 'lineas.proveedor']);

        return view('gestion.compras.edit', [
            'orden'           => $orden,
            'lineasIniciales' => $this->lineasIniciales($orden),
        ]);
    }

    public function update(OrdenCompraRequest $request, OrdenCompra $orden): RedirectResponse
    {
        $this->ordenes->actualizar($orden, $request->lineas(), $request->validated('observaciones'));

        return redirect()
            ->route('gestion.compras.show', $orden)
            ->with('exito', "Se actualizó el detalle del pedido {$orden->numero}.");
    }

    // ------------------------------------------------------------------
    // Transiciones
    // ------------------------------------------------------------------

    /**
     * Requerimiento explicito: ninguna orden sale sin que un administrativo la
     * mire. Es tambien el momento en que un pedido mezclado se reparte en una
     * orden por proveedor: la firma que queda registrada es esta.
     */
    public function aprobar(Request $request, OrdenCompra $orden): RedirectResponse
    {
        $this->authorize('aprobar', $orden);

        try {
            $this->ordenes->aprobar($orden, $request->user());
        } catch (TransicionNoPermitidaException $error) {
            return back()->with('error', $error->getMessage());
        }

        $hijas = $orden->hijas()->count();

        return back()->with('exito', $hijas > 0
            ? "Se aprobó el pedido {$orden->numero} y se emitieron {$hijas} órdenes, una por proveedor. Ya se las puede enviar."
            : "Se aprobó el pedido {$orden->numero}. Ya se le puede enviar al proveedor.");
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
    // Auxiliares
    // ------------------------------------------------------------------

    /**
     * Los renglones con los que arranca el formulario, listos para el JSON que
     * lee el script.
     *
     * <b>old() gana sobre lo guardado.</b> Si la validacion rechazo el
     * formulario, lo que el usuario cargo no se puede perder, y old() solo trae
     * ids y cantidades: el nombre, la marca y el proveedor hay que volver a
     * buscarlos. Se hace acá y no en la vista porque una consulta dentro de un
     * partial es una consulta que nadie encuentra despues.
     *
     * @return list<array<string, mixed>>
     */
    private function lineasIniciales(?OrdenCompra $orden): array
    {
        $viejas = collect(old('lineas'));

        if ($viejas->isNotEmpty()) {
            $productos = Producto::with('proveedor:id,razon_social,activo')
                ->findMany($viejas->pluck('producto_id')->filter()->all())
                ->keyBy('id');

            return $viejas
                ->map(function ($linea) use ($productos) {
                    $producto = $productos->get((int) ($linea['producto_id'] ?? 0));

                    return $producto === null
                        ? null
                        : $this->renglon($producto, (int) ($linea['cantidad_pedida'] ?? 1));
                })
                ->filter()
                ->values()
                ->all();
        }

        if ($orden === null || ! $orden->exists) {
            return [];
        }

        $orden->loadMissing(['lineas.producto.proveedor', 'lineas.proveedor']);

        return $orden->lineas
            ->map(function (OrdenCompraLinea $linea) {
                if ($linea->producto === null) {
                    return null;
                }

                // El proveedor de la linea manda sobre el del producto: es el
                // snapshot de a quien se le pidio, y puede no ser el que el
                // producto tiene hoy.
                return $this->renglon($linea->producto, $linea->cantidad_pedida, $linea->proveedor);
            })
            ->filter()
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    private function renglon(Producto $producto, int $cantidad, $proveedor = null): array
    {
        $proveedor ??= $producto->proveedor;

        return [
            'producto_id' => $producto->id,
            'codigo'      => $producto->codigo,
            'nombre'      => $producto->nombre,
            'marca'       => $producto->marca,
            'costo'       => $producto->costo === null ? null : (float) $producto->costo,
            'cantidad'    => max(1, $cantidad),
            'proveedor'   => $proveedor === null ? null : [
                'id'           => $proveedor->id,
                'razon_social' => $proveedor->razon_social,
                'activo'       => (bool) $proveedor->activo,
            ],
        ];
    }
}
