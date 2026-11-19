<?php

namespace App\Http\Resources\Gestion;

use App\Support\UrlPublica;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Producto visto desde el panel.
 *
 * <b>Es un archivo distinto del ProductoResource de la tienda, y es la
 * separacion que mas importa de toda la API de gestion.</b> Aquel oculta el
 * precio a los invitados y NO expone `costo`, `stock_fisico`,
 * `stock_comprometido` ni el proveedor: son datos de la empresa, no del
 * cliente. Este los necesita todos. Un resource compartido que decidiera por
 * request que campos salen terminaria, el dia que alguien se equivoque de
 * rama, publicando el costo de compra en la vitrina. Dos archivos no se
 * equivocan.
 *
 * Por eso tampoco se usa `toArray()` del modelo: los campos se enumeran uno
 * por uno, y lo que se agregue manana a la tabla no sale solo.
 *
 * Mismo cuerpo en el listado y en el detalle. Las tres relaciones las trae el
 * controlador con `with()`, asi que no hay N+1 ni forma distinta segun el
 * endpoint: la pantalla lee siempre las mismas claves.
 *
 * @mixin \App\Models\Producto
 */
class ProductoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'codigo'      => $this->codigo,
            'nombre'      => $this->nombre,
            'marca'       => $this->marca,
            'descripcion' => $this->descripcion,

            // null cuando no hay imagen: la pantalla pone su placeholder.
            // Misma conversion que usa la tienda, para que las dos interfaces
            // armen la URL igual.
            'imagen_url' => UrlPublica::deStorage($this->imagen),

            // Los dos importes. 'costo' es lo que la tienda nunca ve.
            'precio' => (float) $this->precio,
            'costo'  => $this->costo !== null ? (float) $this->costo : null,

            // Los tres saldos. 'stock_disponible' es columna generada
            // (fisico - comprometido): se lee, no se escribe.
            'stock_fisico'       => (int) $this->stock_fisico,
            'stock_comprometido' => (int) $this->stock_comprometido,
            'stock_disponible'   => (int) $this->stock_disponible,
            'en_preventa'        => $this->enPreventa(),

            'punto_reposicion'      => (int) $this->punto_reposicion,
            'cantidad_reposicion'   => (int) $this->cantidad_reposicion,
            'reposicion_automatica' => (bool) $this->reposicion_automatica,

            'peso_gramos' => $this->peso_gramos,

            // Las tres banderas de estado, que responden preguntas distintas:
            //   activo        ¿se puede operar con el? (ventas, compras)
            //   publicado_app ¿se ve en la tienda?
            //   destacado     lo escribe un job por volumen de venta; no se
            //                 edita a mano, y por eso ProductoRequest ni
            //                 siquiera acepta el campo. Viaja de solo lectura.
            'activo'        => (bool) $this->activo,
            'publicado_app' => (bool) $this->publicado_app,
            'destacado'     => (bool) $this->destacado,

            'categoria' => $this->categoria ? [
                'id'     => $this->categoria->id,
                'nombre' => $this->categoria->nombre,
            ] : null,

            'proveedor' => $this->proveedor ? [
                'id'           => $this->proveedor->id,
                'razon_social' => $this->proveedor->razon_social,
            ] : null,

            'alicuota_iva' => $this->alicuotaIva ? [
                'id'         => $this->alicuotaIva->id,
                'nombre'     => $this->alicuotaIva->nombre,
                'porcentaje' => (float) $this->alicuotaIva->porcentaje,
            ] : null,
        ];
    }
}
