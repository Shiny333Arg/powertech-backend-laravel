<?php

namespace App\Http\Controllers;

use App\Enums\CanalVenta;
use App\Http\Requests\CarritoLineaRequest;
use App\Models\CarritoLinea;
use App\Models\Producto;
use App\Services\CarritoService;
use App\Services\MotorPromociones;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Carrito del cliente en la tienda.
 *
 * Alcance de esta etapa: agregar, quitar y cambiar cantidad. El checkout vive
 * en CheckoutController.
 *
 * El carrito no toca stock en ninguna de sus formas: ver CarritoService.
 *
 * No hay carrito anonimo todavia. La tabla lo soporta —carritos.session_token
 * existe para eso— pero la ficha de producto no le ofrece comprar al invitado,
 * asi que las rutas van detras de 'auth'. El dia que se habilite, el unico
 * cambio es resolver el carrito por token de sesion en vez de por user_id.
 */
class CarritoController extends Controller
{
    public function __construct(private readonly CarritoService $carritos)
    {
    }

    /**
     * El carrito con los precios VIGENTES, no los del momento en que se agrego.
     *
     * Las promociones se resuelven de a todo el carrito en una sola consulta:
     * MotorPromociones recibe la coleccion completa indexada por id.
     */
    public function index(MotorPromociones $motor): View
    {
        $carrito = $this->carritos->abiertoDe(request()->user());

        $carrito->load(['lineas.producto.categoria:id,nombre']);

        $productos = $carrito->lineas
            ->pluck('producto')
            ->filter()
            ->keyBy('id');

        $promociones = $motor->paraProductos($productos, CanalVenta::Online);

        // Precio unitario ya descontado, por producto. El motor trabaja en
        // centavos: la division por 100 es el unico lugar donde se vuelve a
        // pesos, y se hace una sola vez.
        $precios = $productos->mapWithKeys(fn ($producto) => [
            $producto->id => (float) $producto->precio
                - (($promociones[$producto->id]['descuento'] ?? 0) / 100),
        ]);

        return view('carrito.index', [
            'carrito' => $carrito,
            'precios' => $precios,
            'total'   => $carrito->lineas->sum(
                fn ($linea) => ($precios[$linea->producto_id] ?? 0) * $linea->cantidad
            ),
        ]);
    }

    /** Agrega unidades. Si el producto ya estaba, suma. */
    public function store(CarritoLineaRequest $request): RedirectResponse
    {
        $producto = Producto::findOrFail($request->integer('producto_id'));

        $this->carritos->agregar(
            $this->carritos->abiertoDe($request->user()),
            $producto,
            $request->integer('cantidad'),
        );

        return redirect()
            ->route('cliente.carrito.index')
            ->with('exito', "Agregamos {$producto->nombre} a tu carrito.");
    }

    /** Fija la cantidad de una linea. Cantidad 0 equivale a quitarla. */
    public function update(CarritoLineaRequest $request, CarritoLinea $linea): RedirectResponse
    {
        $this->verificarPropiedad($linea);

        $this->carritos->cambiarCantidad($linea, $request->integer('cantidad'));

        return redirect()
            ->route('cliente.carrito.index')
            ->with('exito', 'Actualizamos tu carrito.');
    }

    public function destroy(CarritoLinea $linea): RedirectResponse
    {
        $this->verificarPropiedad($linea);

        $nombre = $linea->producto?->nombre ?? 'El producto';

        $this->carritos->quitar($linea);

        return redirect()
            ->route('cliente.carrito.index')
            ->with('exito', "Quitamos {$nombre} de tu carrito.");
    }

    /**
     * La linea tiene que ser del carrito abierto de QUIEN pide.
     *
     * Sin esto, el id de la URL alcanza para editar el carrito de otro cliente.
     * Es 404 y no 403 a proposito: una linea ajena no tiene por que existir
     * para este usuario.
     */
    private function verificarPropiedad(CarritoLinea $linea): void
    {
        abort_unless(
            $linea->carrito_id === $this->carritos->abiertoDe(request()->user())->id,
            404,
        );
    }
}
