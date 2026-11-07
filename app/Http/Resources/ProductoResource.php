<?php

namespace App\Http\Resources;

use App\Support\UrlPublica;
use App\Support\VisitanteTienda;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Producto en una lista: vitrina, resultados de busqueda, relacionados.
 * Equivale a home/partials/tarjeta-producto.blade.php.
 *
 * <b>El precio solo viaja si quien pregunta puede verlo.</b> La decision la
 * toma VisitanteTienda y no este archivo: aca solo se consulta. Es la segunda
 * capa —la primera es que para un invitado el precio con promociones ni
 * siquiera se calcula, asi que no existe en memoria— y esta puesta para que
 * un cambio futuro en el controlador no abra el dato por descuido.
 *
 * <b>Los campos se enumeran uno por uno, nunca toArray() del modelo.</b> Un
 * volcado completo expondria `precio`, `costo`, `stock_fisico` y
 * `stock_comprometido` de una sola vez. `costo` en particular no lo ve nadie
 * por esta API: es informacion de la empresa, no del cliente, y no aparece ni
 * para un cliente autenticado.
 *
 * <b>El stock SI lo ve el invitado.</b> No es una omision: la ficha web le
 * muestra la cantidad exacta de unidades disponibles por requerimiento
 * explicito (3.5). Lo unico reservado es el precio.
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
        $disponible = (int) $this->stock_disponible;

        $datos = [
            'id'     => $this->id,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'marca'  => $this->marca,

            // null cuando no hay imagen: la app pone su propio placeholder.
            'imagen_url' => UrlPublica::deStorage($this->imagen),

            'destacado' => (bool) $this->destacado,

            // Disponible <= 0 no oculta el producto: lo marca como preventa,
            // que esta admitida (Requerimientos 3.5 y 5.1).
            'stock_disponible' => $disponible,
            'en_preventa'      => $disponible <= 0,

            // Se emite directo y no con whenLoaded(): este resource se anida
            // dentro de ProductoDetalleResource, y ahi la resolucion pasa por
            // toArray() en lugar de resolve(), que es el que descarta los
            // MissingValue. Un whenLoaded sin filtrar terminaria serializando
            // un objeto vacio en 'categoria'. Todas las consultas de
            // BuscadorCatalogo traen la categoria con with(), asi que esto no
            // introduce un N+1.
            'categoria' => $this->categoria ? [
                'id'     => $this->categoria->id,
                'nombre' => $this->categoria->nombre,
            ] : null,
        ];

        return $datos + $this->bloqueDePrecio($request);
    }

    /**
     * Los cuatro campos de precio, o ninguno.
     *
     * Van todos juntos a proposito: mostrar `precio_final` sin `precio`, o el
     * porcentaje de descuento sin los importes, sigue siendo informacion de
     * precio. O se entrega el bloque completo o no se entrega nada.
     *
     * `precio_final` lo deja BuscadorCatalogo::anotarPrecios() como atributo
     * transitorio. Si no esta —producto sin promociones vigentes— el final es
     * el de lista.
     *
     * @return array<string, mixed>
     */
    protected function bloqueDePrecio(Request $request): array
    {
        if (! VisitanteTienda::de($request)->puedeVerPrecios()) {
            return [];
        }

        $lista = (float) $this->precio;
        $final = (float) ($this->precio_final ?? $lista);

        $hayDescuento = $final < $lista && $lista > 0;

        return [
            'precio'          => round($lista, 2),
            'precio_final'    => round($final, 2),
            'tiene_descuento' => $hayDescuento,

            // Mismo calculo que item/show.blade.php, para que la app y la web
            // no muestren dos porcentajes distintos del mismo descuento.
            'porcentaje_descuento' => $hayDescuento
                ? (int) round(100 - ($final * 100 / $lista))
                : 0,
        ];
    }
}