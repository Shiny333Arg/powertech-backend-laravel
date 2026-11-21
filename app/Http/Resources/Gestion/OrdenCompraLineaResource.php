<?php

namespace App\Http\Resources\Gestion;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un renglon del detalle de un pedido a proveedor.
 *
 * <b>No confundir con CompraLineaResource</b>, que es de la tienda: ese es un
 * renglon de lo que un CLIENTE compro —una venta—, con precio de venta e IVA.
 * Este es lo que la empresa le pide a un proveedor, con costo de reposicion y
 * cantidades recibidas. Son dos documentos distintos que en castellano se
 * llaman parecido, y por eso este vive bajo Resources\Gestion.
 *
 * <b>proveedor es a quien se le pide ESTE renglon, y es un snapshot.</b> Se
 * copio del producto al guardar y no vuelve a mirarse: si manana al producto le
 * cambian el proveedor, el pedido viejo tiene que seguir diciendo a quien se le
 * pidio. Por eso se serializa el de la linea y no el del producto.
 *
 * @mixin \App\Models\OrdenCompraLinea
 */
class OrdenCompraLineaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'producto' => $this->producto ? [
                'id'     => $this->producto->id,
                'codigo' => $this->producto->codigo,
                'nombre' => $this->producto->nombre,
                'marca'  => $this->producto->marca,
            ] : null,

            // 'activo' viaja porque el formulario de edicion lo muestra: un
            // borrador cuyo renglon quedo apuntando a un proveedor dado de baja
            // no se puede guardar —OrdenCompraRequest lo rechaza— y la pantalla
            // lo marca para que se vea antes de intentarlo. El snapshot de la
            // linea trae el proveedor completo (ver SirveElPedido::recargar()),
            // asi que no hace falta ningun select nuevo.
            'proveedor' => $this->proveedor ? [
                'id'           => $this->proveedor->id,
                'razon_social' => $this->proveedor->razon_social,
                'activo'       => (bool) $this->proveedor->activo,
            ] : null,

            // ----------------------------------------------------------
            // Cantidades
            // ----------------------------------------------------------
            //
            // 'pendiente' y 'excedente' son accessors del modelo y no se
            // calculan acá: el pendiente nunca es negativo y el excedente
            // aparece cuando el proveedor manda de mas, que se acepta a
            // proposito —la mercaderia esta en el deposito—. Repetir esas dos
            // cuentas en el Resource seria tener dos definiciones de lo mismo.
            'cantidad_pedida'   => $this->cantidad_pedida,
            'cantidad_recibida' => $this->cantidad_recibida,
            'pendiente'         => $this->pendiente,
            'excedente'         => $this->excedente,

            // ----------------------------------------------------------
            // Importes
            // ----------------------------------------------------------
            //
            // El costo es el del catalogo al momento de cargar el pedido: una
            // ESTIMACION para que el administrativo vea cuanto pesa, no el
            // precio que va a facturar el proveedor. El subtotal es el accessor
            // del modelo: lo pedido por ese costo, sin mirar lo recibido.
            'costo_unitario'    => (float) $this->costo_unitario,
            'subtotal_estimado' => round($this->subtotal_estimado, 2),
        ];
    }
}
