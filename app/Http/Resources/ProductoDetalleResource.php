<?php

namespace App\Http\Resources;

use App\Models\Producto;
use App\Support\VisitanteTienda;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Ficha de producto. Equivale a item/show.blade.php.
 *
 * Agrega a la tarjeta lo que solo tiene sentido en el detalle: la descripcion
 * —donde viven las caracteristicas tecnicas, que no se modelan aparte—, las
 * promociones que lo alcanzan y los relacionados.
 *
 * <b>Las promociones aplicadas van junto con el precio o no van.</b> Nombrar
 * las promociones vigentes de un producto sin mostrar el importe parece
 * inofensivo, pero una promocion de tipo `precio_especial` guarda un PRECIO en
 * la columna `valor`: publicarla a un invitado seria filtrar exactamente el
 * dato que el resto de este codigo se ocupa de ocultar. Para el invitado la
 * lista llega vacia, igual que en la ficha web, donde el bloque entero esta
 * dentro de @auth.
 */
class ProductoDetalleResource extends ProductoResource
{
    /**
     * @param  Collection<int, \App\Models\Promocion>  $promociones
     * @param  Collection<int, Producto>  $relacionados
     */
    public function __construct(
        Producto $producto,
        private readonly Collection $promociones,
        private readonly Collection $relacionados,
    ) {
        parent::__construct($producto);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $puedeVerPrecios = VisitanteTienda::de($request)->puedeVerPrecios();

        return parent::toArray($request) + [
            'descripcion' => $this->descripcion,

            'promociones' => $puedeVerPrecios
                ? PromocionResource::collection($this->promociones)->toArray($request)
                : [],

            'relacionados' => ProductoResource::collection($this->relacionados)->toArray($request),
        ];
    }
}