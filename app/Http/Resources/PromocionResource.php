<?php

namespace App\Http\Resources;

use App\Support\UrlPublica;
use App\Support\VisitanteTienda;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Promocion vigente: el banner de la vitrina y el detalle de la ficha.
 *
 * <b>`valor` y `tipo` son datos de precio y se reservan.</b> No es exceso de
 * celo: TipoPromocion::PrecioEspecial guarda en `valor` el precio final del
 * producto, no un descuento. Publicarlo a un invitado filtraria el precio
 * exacto de todos los productos alcanzados por esa promocion, que es
 * justamente lo que el resto de la API evita. Como el tipo decide como leer
 * la columna, los dos campos viajan juntos o no viaja ninguno.
 *
 * Lo que SI ve el invitado es la parte editorial —nombre, descripcion,
 * banner, vigencia—, que es lo que hace falta para mostrar el carrusel y para
 * que el banner lleve al listado filtrado por ?promocion={id}.
 *
 * La descripcion es texto libre que carga un administrativo. Si alguien
 * escribe un importe ahi, lo esta publicando a proposito: es una decision
 * editorial, no una fuga del modelo.
 *
 * @mixin \App\Models\Promocion
 */
class PromocionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $datos = [
            'id'          => $this->id,
            'nombre'      => $this->nombre,
            'descripcion' => $this->descripcion,
            'banner_url'  => UrlPublica::deStorage($this->banner),

            'vigencia_desde' => $this->vigencia_desde?->toIso8601String(),
            'vigencia_hasta' => $this->vigencia_hasta?->toIso8601String(),
        ];

        if (! VisitanteTienda::de($request)->puedeVerPrecios()) {
            return $datos;
        }

        return $datos + [
            'tipo'           => $this->tipo?->value,
            'tipo_etiqueta'  => $this->tipo?->etiqueta(),
            'unidad'         => $this->tipo?->unidad(),
            'valor'          => $this->valor !== null ? round((float) $this->valor, 2) : null,
        ];
    }
}