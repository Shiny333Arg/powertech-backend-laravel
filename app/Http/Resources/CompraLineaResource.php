<?php

namespace App\Http\Resources;

use App\Support\UrlPublica;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una linea del pedido, tal como se emitio.
 *
 * <b>Todo sale de `venta_lineas` y nada del catalogo.</b> Descripcion, precio
 * unitario, descuento y total estan congelados en la linea: es lo que se le
 * cobro. Si el producto se renombro, cambio de precio o salio de una
 * promocion despues, el pedido tiene que seguir mostrando lo que el cliente
 * compro. Lo UNICO que se lee del producto vivo es la imagen y si todavia se
 * puede abrir su ficha, que son datos de navegacion y no del documento.
 *
 * <b>`costo_unitario` no viaja nunca.</b> Es el costo de la empresa, esta en
 * la misma fila y no lo ve ningun cliente por esta API. Tampoco viajan
 * `alicuota_iva`, `neto` ni `iva`: son el desglose fiscal, que el cliente lee
 * en la factura y no en esta pantalla. El IVA total del pedido si viaja, en
 * los importes de la compra.
 *
 * <b>`producto_enlazable` lo decide el backend.</b> Un producto despublicado
 * o dado de baja sigue apareciendo en el pedido —se lo vendieron— pero su
 * ficha devuelve 404. Es la misma condicion que usa compras/show.blade.php
 * para decidir si la descripcion es un enlace o un texto suelto, resuelta
 * aca para que la app no tenga que conocer las reglas de publicacion del
 * catalogo.
 *
 * @mixin \App\Models\VentaLinea
 */
class CompraLineaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $producto = $this->producto;

        return [
            'id' => $this->id,

            // Null si la linea quedo sin producto. No deberia pasar —lo impide
            // ProtegeContraBorrado— pero la linea es valida igual: el
            // documento no depende de que el producto siga existiendo.
            'producto_id' => $this->producto_id,

            'descripcion' => $this->descripcion,

            'cantidad' => $this->cantidad,

            // Mayor a cero cuando hubo una devolucion sobre esta linea. La
            // app muestra la chapita; el total de la linea no cambia, porque
            // la devolucion se documenta con una nota de credito aparte.
            'cantidad_devuelta' => $this->cantidad_devuelta,

            'precio_unitario' => round((float) $this->precio_unitario, 2),

            // Lo que descontaron las promociones sobre esta linea, ya
            // aplicado dentro de `total`. Viaja para poder mostrar el ahorro
            // sin recalcularlo.
            'descuento_promocion' => round((float) $this->descuento_promocion, 2),

            'total' => round((float) $this->total, 2),

            // null cuando no hay imagen: la app pone su propio placeholder.
            // Misma regla que el catalogo, ver UrlPublica.
            'imagen_url' => UrlPublica::deStorage($producto?->imagen),

            'producto_enlazable' => (bool) ($producto
                && $producto->activo
                && $producto->publicado_app),
        ];
    }
}
