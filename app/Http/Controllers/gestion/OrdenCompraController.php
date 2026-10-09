<?php

namespace App\Http\Controllers\gestion;

use App\Enums\EstadoOrdenCompra;
use App\Enums\OrigenOrdenCompra;
use App\Exceptions\TransicionNoPermitidaException;
use App\Http\Requests\OrdenCompraRequest;
use App\Models\OrdenCompra;
use App\Models\Producto;
use App\Models\Proveedor;
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
 */
class OrdenCompraController extends Controller
{
    public function __construct(
        private readonly OrdenCompraService $ordenes,
    ) {
        // Descomentar junto con el resto de los authorizeResource del proyecto:
        $this->authorizeResource(OrdenCompra::class, 'orden');
    }

    public function index(Request $request): View
    {
        $ordenes = OrdenCompra::query()
            ->with('proveedor:id,razon_social')
            ->withCount('lineas')
            ->when($request->filled('q'), function ($query) use ($request) {
                $busqueda = $request->string('q')->trim()->value();

                $query->where(function ($sub) use ($busqueda) {
                    $sub->where('numero', 'like', "%{$busqueda}%")
                        ->orWhereHas('proveedor', fn ($p) => $p->where('razon_social', 'like', "%{$busqueda}%"));
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
        $orden->load(['proveedor', 'lineas.producto', 'usuarioCreo', 'usuarioAprobo']);

        return view('gestion.compras.show', ['orden' => $orden]);
    }

    public function create(Request $request): View
    {
        return view('gestion.compras.create', [
            'orden'       => new OrdenCompra(['estado' => EstadoOrdenCompra::Borrador]),
            'proveedores' => Proveedor::activos()->orderBy('razon_social')->get(['id', 'razon_social']),
            'proveedor'   => $request->filled('proveedor')
                ? Proveedor::find($request->query('proveedor'))
                : null,
            'productos'   => $this->productosDe($request->query('proveedor')),
        ]);
    }

    public function store(OrdenCompraRequest $request): RedirectResponse
    {
        $proveedor = Proveedor::findOrFail($request->validated('proveedor_id'));

        $orden = $this->ordenes->crear(
            $proveedor,
            $request->lineas(),
            $request->user(),
            $request->validated('observaciones'),
        );

        return redirect()
            ->route('gestion.compras.show', $orden)
            ->with('exito', "Se creó el pedido {$orden->numero} en borrador. Falta aprobarlo para poder enviarlo.");
    }

    public function edit(OrdenCompra $orden): View
    {
        // Una orden ya aprobada o enviada no se edita. Se avisa acá y no en la
        // vista para que el enlace directo tampoco entre.
        abort_unless($orden->esEditable(), 403, 'Solo se puede editar una orden en borrador.');

        $orden->load(['proveedor', 'lineas.producto']);

        return view('gestion.compras.edit', [
            'orden'     => $orden,
            'proveedor' => $orden->proveedor,
            'productos' => $this->productosDe($orden->proveedor_id),
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

    /** Requerimiento explicito: ninguna orden sale sin que un administrativo la mire. */
    public function aprobar(Request $request, OrdenCompra $orden): RedirectResponse
    {
        $this->authorize('aprobar', $orden);

        try {
            $this->ordenes->aprobar($orden, $request->user());
        } catch (TransicionNoPermitidaException $error) {
            return back()->with('error', $error->getMessage());
        }

        return back()->with('exito', "Se aprobó el pedido {$orden->numero}. Ya se le puede enviar al proveedor.");
    }

    /**
     * Manda el remito por correo.
     *
     * El error de envio se muestra en pantalla en vez de propagarse: lo tipico
     * es un proveedor sin casilla cargada o con canal distinto de email, y eso
     * se arregla en la ficha del proveedor, no reintentando.
     */
    public function enviar(Request $request, OrdenCompra $orden): RedirectResponse
    {
        $this->authorize('enviar', $orden);

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

    /**
     * Cancelar es lo que vuelve a habilitar la reposicion de estos productos:
     * mientras la orden este abierta, el restock los da por pedidos.
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

    /** El desplegable de productos del formulario: solo los de ese proveedor. */
    private function productosDe(int|string|null $proveedorId)
    {
        if (blank($proveedorId)) {
            return collect();
        }

        return Producto::query()
            ->activos()
            ->where('proveedor_id', $proveedorId)
            ->orderBy('nombre')
            ->get(['id', 'codigo', 'nombre', 'costo', 'stock_disponible', 'punto_reposicion']);
    }
}
