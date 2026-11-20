<?php

namespace App\Http\Resources\Gestion;

use App\Models\Proveedor;
use App\Support\FormaDeOrden;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un pedido a proveedor en el listado. Equivale a una fila de
 * resources/views/gestion/compras/index.blade.php.
 *
 * <b>No confundir con CompraResource</b>, que es de la tienda: ese es una
 * COMPRA DEL CLIENTE —una venta, con su pago y su seguimiento—. Este es una
 * orden de compra a un proveedor. Los dos documentos se llaman «compra» en
 * castellano y no tienen nada que ver; este vive bajo Resources\Gestion y no se
 * publica en ningun endpoint de la tienda.
 *
 * <b>Sin renglones, igual que la grilla del panel.</b> El detalle lo sirve
 * OrdenCompraDetalleResource. Son dos clases y no una con whenLoaded porque el
 * listado carga 'lineas' con un select acotado —solo lo que necesita para saber
 * a quienes apunta el pedido—, y una sola clase tendria que publicar renglones
 * a los que les faltan columnas. Es la misma division que CompraResource y
 * CompraDetalleResource en la tienda.
 *
 * <b>La forma del documento es lo primero que hay que leer.</b> Un pedido puede
 * ser simple, madre o hija, y la diferencia la decide proveedor_id (ver
 * OrdenCompra). No es un campo de la tabla: se deriva, y viaja explicito porque
 * es lo que le dice a la pantalla si el destinatario es uno o varios, y si lo
 * que esta mirando es un pedido o el reparto de otro.
 *
 * <b>Exige relaciones cargadas.</b> 'proveedores' sale de
 * OrdenCompra::proveedoresInvolucrados(), que mira las hijas si el pedido ya se
 * repartio y las lineas si todavia es un borrador: sin 'hijas.proveedor' y
 * 'lineas.proveedor' cargadas es un N+1 por fila. Las carga el controlador.
 *
 * @mixin \App\Models\OrdenCompra
 */
class OrdenCompraResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'     => $this->id,
            'numero' => $this->numero,

            // Derivada de proveedor_id y orden_padre_id, con los mismos metodos
            // que usa el panel. Se publica porque decide que puede hacer la
            // pantalla: una madre no se envia ni se recibe —lo hacen sus
            // hijas—, y una hija no se aprueba, porque ya nacio aprobada.
            'forma' => FormaDeOrden::de($this->resource),

            'estado'          => $this->estado?->value,
            'estado_etiqueta' => $this->estado?->etiqueta(),

            // Manual o automatica por reposicion. Distinguirlas es la unica
            // forma de medir si los umbrales del restock estan bien calibrados.
            'origen'          => $this->origen?->value,
            'origen_etiqueta' => $this->origen?->etiqueta(),

            // ----------------------------------------------------------
            // Destinatarios
            // ----------------------------------------------------------
            //
            // 'proveedor' es el destinatario propio: null en una madre, que no
            // tiene uno. 'proveedores' es a quienes apunta el pedido, y en una
            // madre son varios. En un pedido simple los dos dicen lo mismo, a
            // proposito: asi la pantalla puede leer siempre 'proveedores' sin
            // preguntar de que forma es el documento.
            'proveedor' => $this->proveedor ? [
                'id'           => $this->proveedor->id,
                'razon_social' => $this->proveedor->razon_social,
            ] : null,

            'proveedores' => $this->proveedoresInvolucrados()
                ->map(fn (Proveedor $proveedor) => [
                    'id'           => $proveedor->id,
                    'razon_social' => $proveedor->razon_social,
                ])
                ->all(),

            // Estimado por definicion: sale del costo del catalogo, no de lo
            // que va a facturar el proveedor, y no se recalcula al recibir.
            //
            // <b>No se suma entre ordenes.</b> Una madre y sus hijas tienen el
            // mismo detalle, asi que un total agregado sobre un listado cuenta
            // un pedido repartido dos veces. Por eso la coleccion no publica
            // ninguna suma: el que la necesite tiene que filtrar antes con
            // raiz() o con emitidas().
            'total_estimado' => (float) $this->total_estimado,

            // Las tres quedan en null mientras el pedido es borrador: la
            // aprobacion y el envio no pasaron, y la entrega estimada se calcula
            // al aprobar, con el plazo de cada proveedor.
            'fecha_aprobacion'       => $this->fecha_aprobacion?->toIso8601String(),
            'fecha_envio'            => $this->fecha_envio?->toIso8601String(),
            'fecha_entrega_estimada' => $this->fecha_entrega_estimada?->toDateString(),
            'creada_el'              => $this->created_at?->toIso8601String(),

            // Cuantos renglones tiene, sin traerlos: es lo que muestra la
            // grilla. El detalle de cada uno esta en GET /compras/{orden}.
            'lineas_count' => $this->whenCounted('lineas'),
        ];
    }
}
