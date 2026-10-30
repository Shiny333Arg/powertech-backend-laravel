<?php

namespace App\Services;

use App\Enums\EstadoCarrito;
use App\Models\Carrito;
use App\Models\CarritoLinea;
use App\Models\Producto;
use App\Models\User;
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
    /**
     * El carrito activo del cliente, creandolo si todavia no tiene uno.
     *
     * Un cliente tiene como mucho un carrito activo: los demas quedaron en
     * 'convertido' o 'abandonado'. Se toma el mas reciente por las dudas de
     * que una carrera haya dejado dos.
     */
    public function activoDe(User $user): Carrito
    {
        $carrito = Carrito::query()
            ->activo()
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
    }

    /**
     * Saca el producto del carrito.
     *
     * La linea SI se borra fisicamente: no es un documento, no tiene historia
     * que preservar y nada la referencia. Es la diferencia con venta_lineas.
     */
    public function quitar(CarritoLinea $linea): void
    {
        $linea->delete();
    }

    /** Unidades totales en el carrito activo. Alimenta el contador del header. */
    public function cantidadDeItems(?User $user): int
    {
        if (! $user || ! $user->esCliente()) {
            return 0;
        }

        return (int) CarritoLinea::query()
            ->whereIn('carrito_id', Carrito::query()
                ->select('id')
                ->activo()
                ->where('user_id', $user->id))
            ->sum('cantidad');
    }
}
