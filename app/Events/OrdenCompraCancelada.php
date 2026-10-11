<?php

namespace App\Events;

use App\Models\OrdenCompra;
use App\Models\Producto;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * Una orden de compra dejo de estar abierta por decision de una persona.
 *
 * <b>Por que existe este evento y no una llamada directa.</b> Cancelar deja el
 * faltante de sus productos otra vez descubierto, y quien sabe que hacer con
 * eso es ReposicionService. Pero ReposicionService ya depende de
 * OrdenCompraService —le pide que arme los pedidos—, asi que llamarlo desde
 * cancelar() seria una dependencia circular. El evento corta el ciclo: el
 * servicio de compras anuncia un hecho y no le importa quien escucha.
 *
 * Es tambien el enganche natural para lo que venga despues: avisarle al
 * proveedor, notificar al vendedor cuya preventa dependia de esta orden,
 * registrar la cancelacion en un tablero de proveedores incumplidores. Nada de
 * eso obliga a volver a tocar OrdenCompraService.
 *
 * <b>Cancelar es el unico cierre que libera el faltante.</b> 'recibida' tambien
 * cierra la orden, pero porque la mercaderia entro: ahi el stock ya subio y el
 * movimiento de tipo 'compra' dispara la evaluacion por su cuenta. Acá no entro
 * nada, y sin este aviso nadie se enteraria.
 */
class OrdenCompraCancelada
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly OrdenCompra $orden,
    ) {}

    /**
     * Los productos que esta orden estaba cubriendo.
     *
     * Las lineas NO se borran al cancelar —la orden conserva su detalle, que es
     * parte de su historia— asi que siguen estando para consultar.
     *
     * unique() porque un pedido no deberia tener el mismo producto en dos
     * renglones, pero evaluarlo dos veces no aporta nada y toma el lock dos
     * veces.
     *
     * @return Collection<int, Producto>
     */
    public function productos(): Collection
    {
        return $this->orden->lineas()
            ->with('producto')
            ->get()
            ->pluck('producto')
            ->filter()
            ->unique('id')
            ->values();
    }
}
