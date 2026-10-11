<?php

namespace App\Http\Controllers\gestion;

use App\Enums\CanalPedido;
use App\Enums\TipoDireccion;
use App\Http\Requests\ProveedorRequest;
use App\Models\CondicionIva;
use App\Models\Proveedor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * CRUD de proveedores, sin delete.
 *
 * El proveedor esta referenciado por productos y por ordenes de compra, asi que
 * no se elimina nunca: la unica baja es activo = false. La ruta destroy no
 * existe, y el guard del modelo rechazaria el delete() igual.
 *
 * Los proveedores NO son usuarios: no tienen login ni interactuan con el
 * sistema. Por eso acá no hay nada de credenciales.
 */
class ProveedorController extends Controller
{
    public function __construct()
    {
        // Descomentar cuando exista el login:
        $this->authorizeResource(Proveedor::class, 'proveedor');
    }

    public function index(Request $request): View
    {
        $proveedores = Proveedor::query()
            ->withCount([
                // Cuantos productos dependen de este proveedor para reponerse.
                'productos',
                // Ordenes todavia capaces de sumar stock: es lo que hay que
                // mirar antes de dar de baja a alguien.
                'ordenesCompra as ordenes_abiertas_count' => fn ($query) => $query->abiertas(),
            ])
            ->when($request->filled('q'), function ($query) use ($request) {
                $busqueda = $request->string('q')->trim()->value();
                $soloDigitos = preg_replace('/\D/', '', $busqueda);

                $query->where(function ($sub) use ($busqueda, $soloDigitos) {
                    $sub->where('razon_social', 'like', "%{$busqueda}%")
                        ->orWhere('contacto', 'like', "%{$busqueda}%");

                    // Permite buscar el CUIT escrito con guiones aunque en la
                    // columna este guardado sin ellos.
                    if ($soloDigitos !== '') {
                        $sub->orWhere('cuit', 'like', "%{$soloDigitos}%");
                    }
                });
            })
            ->when($request->filled('canal_pedido'), fn ($query) => $query->where('canal_pedido', $request->query('canal_pedido')))
            ->when($request->query('estado') === 'activos', fn ($query) => $query->activos())
            ->when($request->query('estado') === 'inactivos', fn ($query) => $query->inactivos())
            ->orderBy('razon_social')
            ->paginate(10)
            ->withQueryString();

        return view('gestion.proveedors.index', [
            'proveedores' => $proveedores,
            'canales'     => CanalPedido::opciones(),
            'filtros'     => $request->only('q', 'canal_pedido', 'estado'),
        ]);
    }

    public function create(): View
    {
        return view('gestion.proveedors.create', [
            'proveedor' => new Proveedor([
                'activo'       => true,
                'canal_pedido' => CanalPedido::Email->value,
            ]),
            'direccion' => null,
        ] + $this->opcionesDeFormulario());
    }

    /**
     * Alta.
     *
     * Proveedor y domicilio en una sola transaccion: un proveedor guardado con
     * el domicilio a medias obliga a completarlo despues sin que nada avise.
     */
    public function store(ProveedorRequest $request): RedirectResponse
    {
        $proveedor = DB::transaction(function () use ($request) {
            $proveedor = Proveedor::create($request->datosDelProveedor());

            $this->guardarDireccion($proveedor, $request->datosDeLaDireccion());

            return $proveedor;
        });

        return redirect()
            ->route('gestion.proveedores.index')
            ->with('exito', "Se creó el proveedor «{$proveedor->razon_social}».");
    }

    public function edit(Proveedor $proveedor): View
    {
        return view('gestion.proveedors.edit', [
            'proveedor' => $proveedor,
            'direccion' => $proveedor->direcciones()->where('tipo', TipoDireccion::Fiscal->value)->first(),
        ] + $this->opcionesDeFormulario());
    }

    public function update(ProveedorRequest $request, Proveedor $proveedor): RedirectResponse
    {
        DB::transaction(function () use ($request, $proveedor) {
            $proveedor->update($request->datosDelProveedor());

            $this->guardarDireccion($proveedor, $request->datosDeLaDireccion());
        });

        return redirect()
            ->route('gestion.proveedors.index')
            ->with('exito', "Se guardaron los cambios en «{$proveedor->razon_social}».");
    }

    /**
     * Baja logica.
     *
     * No bloquea aunque tenga productos u ordenes abiertas: la baja es
     * reversible y el proveedor sigue nombrando las ordenes que ya emitio. Lo
     * que sí hace es avisar, porque la consecuencia no es obvia: los productos
     * que dependen de este proveedor dejan de generar orden automatica y su
     * faltante pasa a ser invisible hasta que alguien lo note.
     */
    public function darDeBaja(Proveedor $proveedor): RedirectResponse
    {
        $proveedor->darDeBaja();

        $productosEnReposicion = $proveedor->productos()
            ->activos()
            ->where('reposicion_automatica', true)
            ->count();

        $aviso = "«{$proveedor->razon_social}» salió de operación.";

        if ($productosEnReposicion > 0) {
            $aviso .= " {$productosEnReposicion} producto(s) dependían de él para reponerse: asignales otro proveedor.";
        }

        $ordenesAbiertas = $proveedor->ordenesCompra()->abiertas()->count();

        if ($ordenesAbiertas > 0) {
            $aviso .= " Quedan {$ordenesAbiertas} orden(es) de compra abierta(s), que siguen su curso.";
        }

        return back()->with('exito', $aviso);
    }

    public function reactivar(Proveedor $proveedor): RedirectResponse
    {
        $proveedor->reactivar();

        return back()->with('exito', "«{$proveedor->razon_social}» vuelve a estar en operación.");
    }

    /**
     * Un unico domicilio fiscal por proveedor.
     *
     * La tabla admite varios —es la misma que usan clientes, que sí necesitan
     * varias direcciones de envio—, pero para un proveedor alcanza con uno y
     * un CRUD anidado seria ruido. updateOrCreate sobre el tipo fiscal
     * reemplaza el existente en lugar de acumular.
     */
    private function guardarDireccion(Proveedor $proveedor, ?array $datos): void
    {
        if ($datos === null) {
            return; // no cargaron domicilio: no se toca el que pudiera existir
        }

        $proveedor->direcciones()->updateOrCreate(
            ['tipo' => TipoDireccion::Fiscal->value],
            $datos + ['es_predeterminada' => true],
        );
    }

    /** @return array<string, mixed> */
    private function opcionesDeFormulario(): array
    {
        return [
            'condiciones' => CondicionIva::orderBy('nombre')->get(['id', 'nombre']),
            'canales'     => CanalPedido::opciones(),
        ];
    }
}
