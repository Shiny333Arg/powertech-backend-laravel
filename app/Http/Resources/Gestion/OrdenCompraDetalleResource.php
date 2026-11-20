<?php

namespace App\Http\Resources\Gestion;

use App\Models\OrdenCompra;
use App\Models\Proveedor;
use App\Support\FormaDeOrden;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * El pedido a proveedor completo: renglones, proveedores, cantidades e
 * importes. Equivale a resources/views/gestion/compras/show.blade.php.
 *
 * <b>Trae todo lo que la pantalla muestra y nada mas</b>, en una sola llamada:
 * no hay un segundo GET para los renglones ni para las ordenes del reparto. Es
 * el mismo criterio que CompraDetalleResource en la tienda, y el motivo por el
 * que el listado tiene su propia clase: la grilla no muestra renglones y
 * traerlos multiplicaria por quince el peso de cada pagina.
 *
 * <b>Lo que NO sale de aca todavia: el historial de recepciones.</b> Las
 * entradas de mercaderia son movimientos de stock con origen en la orden
 * —OrdenCompra::recepciones()—, porque el modelo de datos decidio no tener
 * tabla de recepciones. Lo que si sale es cuanto se recibio y cuanto falta de
 * cada renglon, que son columnas del pedido. El historial de movimientos entra
 * con el endpoint de recepcion, en E.2.
 *
 * <b>Tampoco salen</b> usuario_creo_id ni usuario_aprobo_id pelados —van como
 * objeto con el nombre, que es lo que la pantalla muestra— ni proveedor_id ni
 * orden_padre_id, que estan representados por 'proveedor' y 'padre'.
 *
 * Exige cargados: 'proveedor', 'lineas.producto', 'lineas.proveedor',
 * 'usuarioCreo', 'usuarioAprobo', 'hijas.proveedor' y 'padre'. Son los mismos
 * que carga el show del panel.
 *
 * @mixin \App\Models\OrdenCompra
 */
class OrdenCompraDetalleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'     => $this->id,
            'numero' => $this->numero,

            'forma' => FormaDeOrden::de($this->resource),

            'estado'          => $this->estado?->value,
            'estado_etiqueta' => $this->estado?->etiqueta(),

            'origen'          => $this->origen?->value,
            'origen_etiqueta' => $this->origen?->etiqueta(),

            // ----------------------------------------------------------
            // Destinatarios
            // ----------------------------------------------------------
            //
            // Mismo criterio que en el listado: 'proveedor' es el destinatario
            // propio —null en una madre— y 'proveedores' es a quienes apunta el
            // pedido. En un borrador todavia sin repartir, 'proveedores' sale
            // de los renglones: es lo que deja ver a quienes se les va a emitir
            // antes de aprobar.
            'proveedor' => $this->proveedor ? [
                'id'           => $this->proveedor->id,
                'razon_social' => $this->proveedor->razon_social,
                'email'        => $this->proveedor->email,
                'contacto'     => $this->proveedor->contacto,
                'telefono'     => $this->proveedor->telefono,
            ] : null,

            'proveedores' => $this->proveedoresInvolucrados()
                ->map(fn (Proveedor $proveedor) => [
                    'id'           => $proveedor->id,
                    'razon_social' => $proveedor->razon_social,
                ])
                ->all(),

            // ----------------------------------------------------------
            // Importes
            // ----------------------------------------------------------
            //
            // El total es la suma de los subtotales de los renglones, calculada
            // y persistida por el modelo. Viaja el valor guardado y no una suma
            // hecha acá: si alguna vez discrepan, el documento manda y la
            // diferencia es un dato, no algo que el Resource deba tapar.
            'total_estimado' => (float) $this->total_estimado,

            'fecha_aprobacion'       => $this->fecha_aprobacion?->toIso8601String(),
            'fecha_envio'            => $this->fecha_envio?->toIso8601String(),
            'fecha_entrega_estimada' => $this->fecha_entrega_estimada?->toDateString(),
            'creada_el'              => $this->created_at?->toIso8601String(),

            'observaciones' => $this->observaciones,

            // ----------------------------------------------------------
            // Renglones
            // ----------------------------------------------------------

            'lineas_count' => $this->lineas->count(),

            'lineas' => OrdenCompraLineaResource::collection($this->lineas)->toArray($request),

            // Los tres totales de unidades juntos, porque la pregunta del
            // deposito es siempre la misma: cuanto falta. Los calcula el modelo
            // sobre las lineas cargadas; 'pendientes' nunca es negativo, aunque
            // el proveedor haya mandado de mas en algun renglon.
            'unidades' => [
                'pedidas'    => (int) $this->lineas->sum('cantidad_pedida'),
                'recibidas'  => $this->unidadesRecibidas(),
                'pendientes' => $this->unidadesPendientes(),
            ],

            // ----------------------------------------------------------
            // Quienes
            // ----------------------------------------------------------
            //
            // usuario_creo queda en null en los pedidos automaticos, y no es un
            // dato faltante: no los creo nadie, los creo el sistema por
            // reposicion de stock. usuario_aprobo es null mientras el pedido
            // sea borrador.
            'usuario_creo' => $this->usuarioCreo ? [
                'id'     => $this->usuarioCreo->id,
                'nombre' => $this->usuarioCreo->nombre_completo,
            ] : null,

            'usuario_aprobo' => $this->usuarioAprobo ? [
                'id'     => $this->usuarioAprobo->id,
                'nombre' => $this->usuarioAprobo->nombre_completo,
            ] : null,

            // ----------------------------------------------------------
            // Reparto
            // ----------------------------------------------------------
            //
            // Las ordenes que esta madre emitio, una por proveedor. Van en
            // forma compacta y no con este mismo Resource: cada hija tiene su
            // propio endpoint de detalle, y anidarlo entero arrastraria otra vez
            // los renglones —que son una copia de los de la madre— y abriria la
            // puerta a una recursion sin fondo.
            //
            // Vacio en un pedido simple y en una hija: el reparto solo existe
            // cuando una madre fue aprobada.
            'hijas' => $this->hijas
                ->map(fn (OrdenCompra $hija) => [
                    'id'              => $hija->id,
                    'numero'          => $hija->numero,
                    'estado'          => $hija->estado?->value,
                    'estado_etiqueta' => $hija->estado?->etiqueta(),
                    'total_estimado'  => (float) $hija->total_estimado,
                    'proveedor'       => $hija->proveedor ? [
                        'id'           => $hija->proveedor->id,
                        'razon_social' => $hija->proveedor->razon_social,
                    ] : null,
                ])
                ->values()
                ->all(),

            // El pedido del que salio esta orden, si salio de uno: es lo que
            // deja volver desde una hija al documento que alguien aprobo.
            'padre' => $this->padre ? [
                'id'     => $this->padre->id,
                'numero' => $this->padre->numero,
            ] : null,
        ];
    }
}
