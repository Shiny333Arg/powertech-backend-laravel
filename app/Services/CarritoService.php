<?php

namespace App\Services;

use App\Enums\CanalVenta;
use App\Enums\EstadoCarrito;
use App\Models\Carrito;
use App\Models\CarritoLinea;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Operaciones sobre el carrito del cliente: agregar, cambiar cantidad y quitar.
 *
 * <b>El carrito NO compromete stock.</b> No hay reserva, no hay validacion
 * contra stock_disponible y no se escribe ningun movimiento. Se admite
 * preventa, asi que reservar no protegeria de nada, y un carrito que reserva
 * obliga a expirar y devolver unidades de carritos abandonados, que son la
 * mayoria: el resultado practico seria comprometido fantasma bloqueando la
 * vitrina. El compromiso nace con la venta confirmada, no aca.
 *
 * Tampoco se guarda precio en la linea: el carrito muestra siempre el precio
 * vigente con las promociones activas al momento de mirarlo. El precio se
 * congela recien al convertirse en venta.
 */
class CarritoService
{
    public function __construct(
        private readonly MotorPromociones $motor,
    ) {}

    /**
     * El carrito ABIERTO del cliente, creandolo si todavia no tiene uno.
     *
     * Abierto incluye `en_checkout`, no solo `activo`: durante el checkout el
     * carrito sigue siendo el del cliente. Buscarlo por `activo` a secas
     * devuelve vacio mientras esta pagando y hace que este metodo cree un
     * carrito nuevo, dejando el que tenia los productos huerfano. Esa fue
     * exactamente la causa de que el carrito se vaciara al entrar al checkout.
     *
     * Un cliente tiene como mucho un carrito abierto: los demas quedaron en
     * `convertido` o `abandonado`. Se toma el mas reciente por las dudas de
     * que una carrera haya dejado dos.
     */
    public function abiertoDe(User $user): Carrito
    {
        $carrito = Carrito::query()
            ->abierto()
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        if ($carrito) {
            return $carrito;
        }

        return Carrito::create([
            'user_id'       => $user->id,
            'session_token' => Str::random(64),
            'estado'        => EstadoCarrito::Activo,
        ]);
    }

    /**
     * Suma unidades del producto al carrito.
     *
     * Agregar dos veces el mismo producto SUMA cantidad en lugar de crear otra
     * fila: carrito_lineas tiene UK (carrito_id, producto_id). Por eso es
     * firstOrCreate + increment y no un create suelto.
     *
     * El increment es un UPDATE relativo y no un save() desde PHP: dos pestañas
     * del mismo cliente agregando a la vez tienen que serializarse en el lock
     * de fila, no pisarse.
     */
    public function agregar(Carrito $carrito, Producto $producto, int $cantidad): CarritoLinea
    {
        return DB::transaction(function () use ($carrito, $producto, $cantidad) {

            $this->volverAComprar($carrito);

            $linea = $carrito->lineas()->firstOrCreate(
                ['producto_id' => $producto->id],
                ['cantidad'    => 0],
            );

            $linea->increment('cantidad', $cantidad);

            return $linea->refresh();
        });
    }

    /**
     * Fija la cantidad de una linea.
     *
     * Cantidad 0 no es una cantidad valida: es quitar el producto. La decision
     * se toma aca y no en la vista para que el formulario de la ficha y el del
     * carrito se comporten igual.
     */
    public function cambiarCantidad(CarritoLinea $linea, int $cantidad): void
    {
        if ($cantidad <= 0) {
            $this->quitar($linea);

            return;
        }

        $linea->update(['cantidad' => $cantidad]);

        $this->volverAComprar($linea->carrito);
    }

    /**
     * Saca el producto del carrito.
     *
     * La linea SI se borra fisicamente: no es un documento, no tiene historia
     * que preservar y nada la referencia. Es la diferencia con venta_lineas.
     */
    public function quitar(CarritoLinea $linea): void
    {
        $carrito = $linea->carrito;

        $linea->delete();

        $this->volverAComprar($carrito);
    }

    /**
     * Saca todas las lineas de una.
     *
     * El carrito NO se borra ni se marca `abandonado`: se vacia. Borrarlo
     * obligaria a crear otro en la proxima llamada —con otro id, que la app
     * ya tenia en memoria— y `abandonado` es un estado final que no se
     * reabre, asi que dejaria al cliente sin carrito utilizable. Vaciar
     * conserva la identidad del carrito abierto, que es lo que la app
     * referencia.
     *
     * El delete va por la relacion y no fila por fila: es un solo DELETE.
     *
     * @return int cuantas lineas se quitaron
     */
    public function vaciar(Carrito $carrito): int
    {
        return DB::transaction(function () use ($carrito) {

            $quitadas = $carrito->lineas()->delete();

            $this->volverAComprar($carrito);

            return (int) $quitadas;
        });
    }

    /**
     * Devuelve el carrito de `en_checkout` a `activo`.
     *
     * Tocar el carrito significa que el cliente volvio a la vitrina: dejar el
     * estado en `en_checkout` ensuciaria el reporte de abandono en el
     * checkout, que es el que distingue a quien se fue de la pantalla de pago
     * de quien solo estaba mirando.
     */
    private function volverAComprar(?Carrito $carrito): void
    {
        if ($carrito && $carrito->estado === EstadoCarrito::EnCheckout) {
            $carrito->update(['estado' => EstadoCarrito::Activo]);
        }
    }

    /**
     * El carrito valuado con los precios y promociones VIGENTES ahora mismo.
     *
     * <b>Es el unico lugar donde se calcula el importe del carrito.</b> Nada
     * de lo que devuelve sale de la base: `carrito_lineas` no guarda precio a
     * proposito, asi que cada llamada vuelve a preguntarle al producto y al
     * MotorPromociones. Un cambio de precio o de promocion se refleja en la
     * proxima consulta sin que haya que tocar el carrito.
     *
     * <b>Todo el calculo intermedio es en centavos enteros.</b> El motor ya
     * trabaja asi; mantenerlo hasta el final evita que el redondeo de cada
     * linea se arrastre al total. La division por 100 ocurre una sola vez,
     * al armar el arreglo de salida.
     *
     * <b>Las lineas no disponibles NO suman.</b> Un producto despublicado o
     * dado de baja mientras estaba en el carrito viaja igual —el cliente
     * tiene que verlo para poder quitarlo— pero queda fuera de
     * `subtotal_lista`, `descuento_total` y `total`, porque esos tres son lo
     * que el checkout va a aceptar cobrar. CheckoutService::
     * exigirProductosVendibles() rechaza exactamente estas lineas, asi que un
     * total que las incluyera prometeria un importe imposible.
     *
     * `cantidad_items` sigue contando TODAS las unidades, disponibles o no:
     * es el numero del globito del carrito, y ahi el cliente espera ver lo
     * que metio, no lo que se puede cobrar.
     *
     * @return array{
     *     carrito: Carrito,
     *     lineas: Collection<int, array{
     *         linea: CarritoLinea,
     *         disponible: bool,
     *         stock_disponible: int,
     *         en_preventa: bool,
     *         unidades_en_preventa: int,
     *         precio_unitario: float,
     *         precio_unitario_final: float,
     *         porcentaje_descuento: int,
     *         subtotal: float
     *     }>,
     *     cantidad_items: int,
     *     subtotal_lista: float,
     *     descuento_total: float,
     *     total: float,
     *     tiene_preventa: bool,
     *     tiene_no_disponibles: bool
     * }
     */
    public function resumen(Carrito $carrito): array
    {
        $carrito->load(['lineas.producto.categoria:id,nombre']);

        $productos = $carrito->lineas
            ->pluck('producto')
            ->filter()
            ->keyBy('id');

        $promociones = $this->motor->paraProductos($productos, CanalVenta::Online);

        $listaCentavos = 0;
        $finalCentavos = 0;
        $items = 0;
        $hayPreventa = false;
        $hayNoDisponibles = false;

        $lineas = $carrito->lineas->map(function (CarritoLinea $linea) use (
            $promociones,
            &$listaCentavos,
            &$finalCentavos,
            &$items,
            &$hayPreventa,
            &$hayNoDisponibles,
        ): array {

            $producto = $linea->producto;

            // Mismo criterio que Producto::scopePublicados() y que
            // CheckoutService::exigirProductosVendibles(). Un producto nulo
            // solo se da si lo borraron de la base, que ProtegeContraBorrado
            // impide, pero la linea no puede romper la respuesta por eso.
            $disponible = $producto !== null
                && $producto->activo
                && $producto->publicado_app;

            $items += $linea->cantidad;

            if (! $disponible) {
                $hayNoDisponibles = true;

                return [
                    'linea' => $linea,
                    'disponible' => false,
                    'stock_disponible' => 0,
                    'en_preventa' => false,
                    'unidades_en_preventa' => 0,
                    'precio_unitario' => 0.0,
                    'precio_unitario_final' => 0.0,
                    'porcentaje_descuento' => 0,
                    'subtotal' => 0.0,
                ];
            }

            $precio = (int) round(((float) $producto->precio) * 100);
            $descuento = $promociones[$producto->id]['descuento'] ?? 0;
            $final = max(0, $precio - $descuento);

            $listaCentavos += $precio * $linea->cantidad;
            $finalCentavos += $final * $linea->cantidad;

            // Mismo atributo transitorio que deja
            // BuscadorCatalogo::anotarPrecios(). Sin esto, el ProductoResource
            // anidado en la linea publicaria `precio_final` igual al de lista
            // y la app veria dos precios distintos para el mismo producto: el
            // correcto en la linea y uno sin descuento adentro del producto.
            $producto->setAttribute('precio_final', round($final / 100, 2));

            $stock = (int) $producto->stock_disponible;

            // Cuantas de las unidades pedidas NO estan hoy en el deposito.
            // Es la pregunta de LINEA, distinta de `en_preventa`, que es la
            // del producto. Las dos viajan porque la app muestra cosas
            // distintas con cada una, y asi no tiene que calcular ninguna.
            $enPreventa = max(0, $linea->cantidad - max(0, $stock));

            if ($enPreventa > 0) {
                $hayPreventa = true;
            }

            return [
                'linea' => $linea,
                'disponible' => true,
                'stock_disponible' => $stock,
                'en_preventa' => $producto->enPreventa(),
                'unidades_en_preventa' => $enPreventa,

                'precio_unitario' => round($precio / 100, 2),
                'precio_unitario_final' => round($final / 100, 2),

                'porcentaje_descuento' => $precio > 0 && $final < $precio
                    ? (int) round(100 - ($final * 100 / $precio))
                    : 0,

                'subtotal' => round(($final * $linea->cantidad) / 100, 2),
            ];
        });

        return [
            'carrito' => $carrito,
            'lineas' => $lineas,
            'cantidad_items' => $items,
            'subtotal_lista' => round($listaCentavos / 100, 2),
            'descuento_total' => round(($listaCentavos - $finalCentavos) / 100, 2),
            'total' => round($finalCentavos / 100, 2),
            'tiene_preventa' => $hayPreventa,
            'tiene_no_disponibles' => $hayNoDisponibles,
        ];
    }

    /** Unidades totales en el carrito abierto. Alimenta el contador del header. */
    public function cantidadDeItems(?User $user): int
    {
        if (! $user || ! $user->esCliente()) {
            return 0;
        }

        return (int) CarritoLinea::query()
            ->whereIn('carrito_id', Carrito::query()
                ->select('id')
                ->abierto()
                ->where('user_id', $user->id))
            ->sum('cantidad');
    }
}
