<?php

namespace App\Http\Resources;

use App\Models\CarritoLinea;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una linea del carrito, ya valuada.
 *
 * <b>Envuelve el arreglo que arma CarritoService::resumen(), no el modelo
 * CarritoLinea.</b> La linea en la base tiene tres datos —carrito, producto y
 * cantidad— y ninguno es un importe: el precio no se guarda a proposito. Todo
 * lo que la app necesita mostrar (unitario, descuento, subtotal, preventa)
 * sale del calculo, asi que el resource recibe el calculo. Colgarlo del
 * modelo obligaria a recalcular adentro del resource, uno por uno, y a
 * repetir la consulta de promociones por cada linea.
 *
 * <b>Dos preguntas distintas sobre preventa, y las dos viajan.</b>
 * `en_preventa` es del PRODUCTO: no queda ninguna unidad libre, y es el mismo
 * campo que devuelve el catalogo para ese producto. `unidades_en_preventa` es
 * de la LINEA: cuantas de las que el cliente pidio no estan hoy en el
 * deposito. Un producto con 3 disponibles y una linea de 5 tiene
 * `en_preventa: false` y `unidades_en_preventa: 2`. La app muestra cosas
 * distintas con cada una y no tiene que derivar ninguna.
 *
 * <b>Una linea no disponible viaja igual.</b> Si el producto se despublico
 * mientras estaba en el carrito, llega con `disponible: false` e importes en
 * cero, y no suma al total. Ocultarla dejaria al cliente sin forma de
 * quitarla y sin entender por que el total no cierra con lo que ve.
 */
class CarritoLineaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $datos */
        $datos = $this->resource;

        /** @var CarritoLinea $linea */
        $linea = $datos['linea'];

        return [
            'id'          => $linea->id,
            'producto_id' => $linea->producto_id,
            'cantidad'    => $linea->cantidad,

            // Si el producto desaparecio de la base —no deberia: lo impide
            // ProtegeContraBorrado— la linea sigue siendo valida y quitable.
            'producto' => $linea->producto
                ? new ProductoResource($linea->producto)
                : null,

            'disponible' => $datos['disponible'],

            // Por que no se puede comprar, en texto listo para mostrar. null
            // cuando si se puede: la app pregunta por `disponible`, no por
            // esto.
            'motivo_no_disponible' => $datos['disponible']
                ? null
                : 'Este producto ya no está disponible en la tienda.',

            'stock_disponible'     => $datos['stock_disponible'],
            'en_preventa'          => $datos['en_preventa'],
            'unidades_en_preventa' => $datos['unidades_en_preventa'],

            'precio_unitario'       => $datos['precio_unitario'],
            'precio_unitario_final' => $datos['precio_unitario_final'],
            'tiene_descuento'       => $datos['porcentaje_descuento'] > 0,
            'porcentaje_descuento'  => $datos['porcentaje_descuento'],

            'subtotal' => $datos['subtotal'],
        ];
    }
}
