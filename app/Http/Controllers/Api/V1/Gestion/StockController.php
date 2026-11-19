<?php

namespace App\Http\Controllers\Api\V1\Gestion;

use App\Enums\TipoMovimientoStock;
use App\Http\Controllers\Controller;
use App\Http\Resources\Gestion\MovimientoStockResource;
use App\Http\Resources\Gestion\ProductoResource;
use App\Models\MovimientoStock;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/**
 * Consulta de stock: el kardex global y la situacion de un producto.
 *
 * Las dos son de lectura. El unico endpoint que escribe inventario es
 * `Acciones\AjustarStockController`, y esta aparte por eso.
 *
 * <b>Mismos filtros que las dos pantallas del panel</b>, con los mismos
 * nombres —`producto_id`, `tipo`, `desde`, `hasta`— para que las dos
 * interfaces se expliquen igual. Con una diferencia deliberada: aca `tipo` se
 * valida contra el enum. En el panel es un `<select>` armado con
 * `TipoMovimientoStock::cases()`, asi que un valor invalido no puede salir del
 * formulario; en la API el cliente manda lo que quiere, y un filtro mal escrito
 * tiene que contestar 422 y no una lista vacia que parezca «no hay
 * movimientos».
 *
 * <b>`producto_id` solo se acepta en el kardex global.</b> En el de un producto
 * el filtro lo impone la ruta; aceptarlo por query string permitiria pedir los
 * movimientos de otro producto. Es la misma precaucion que tomo la adenda de F1
 * en el panel.
 */
class StockController extends Controller
{
    private const POR_PAGINA = 20;

    private const POR_PAGINA_MAX = 100;

    /**
     * GET /api/v1/gestion/stock/movimientos
     *
     * El kardex completo, de todos los productos, del mas reciente al mas
     * viejo. Es el extracto que se usa para auditar: «quien movio esto y
     * cuando».
     */
    public function movimientos(Request $request): AnonymousResourceCollection
    {
        $filtros = $request->validate($this->reglasDeFiltros(incluyeProducto: true));

        $movimientos = $this->aplicarFiltros(
            MovimientoStock::query()->with(['producto:id,codigo,nombre', 'usuario:id,nombres,apellido']),
            $filtros,
        )
            ->latest('id')
            ->paginate($filtros['por_pagina'] ?? self::POR_PAGINA)
            ->withQueryString();

        return MovimientoStockResource::collection($movimientos)
            ->additional(['filtros' => $this->filtrosAplicados($filtros, incluyeProducto: true)]);
    }

    /**
     * GET /api/v1/gestion/productos/{producto}/stock
     *
     * La situacion de un producto y su historico, en una sola respuesta:
     * cuanto hay ahora y por que.
     *
     * La situacion va en `producto`, al lado de `data`, con el mismo
     * `ProductoResource` que devuelven los endpoints del catalogo. Reutilizarlo
     * —en vez de escribir un resource «de stock» con tres campos— es lo que
     * garantiza que los saldos se lean igual en las dos pantallas, y de paso
     * trae el punto de reposicion y `en_preventa`, que es justo lo que se mira
     * al decidir un ajuste.
     */
    public function show(Request $request, Producto $producto): AnonymousResourceCollection
    {
        $filtros = $request->validate($this->reglasDeFiltros(incluyeProducto: false));

        $movimientos = $this->aplicarFiltros(
            $producto->movimientosStock()->with('usuario:id,nombres,apellido'),
            $filtros,
        )
            ->latest('id')
            ->paginate($filtros['por_pagina'] ?? self::POR_PAGINA)
            ->withQueryString();

        return MovimientoStockResource::collection($movimientos)->additional([
            'producto' => new ProductoResource(
                $producto->load(['categoria:id,nombre', 'proveedor:id,razon_social', 'alicuotaIva:id,nombre,porcentaje'])
            ),
            'filtros' => $this->filtrosAplicados($filtros, incluyeProducto: false),
        ]);
    }

    /**
     * Reglas de los filtros, compartidas por los dos endpoints.
     *
     * Misma forma que las del panel, mas `por_pagina`, que en la web la decide
     * la pantalla y aca la decide el cliente.
     *
     * @return array<string, array<int, mixed>>
     */
    private function reglasDeFiltros(bool $incluyeProducto): array
    {
        $reglas = [
            'tipo'       => ['nullable', Rule::enum(TipoMovimientoStock::class)],
            'desde'      => ['nullable', 'date'],
            'hasta'      => ['nullable', 'date', 'after_or_equal:desde'],
            'por_pagina' => ['nullable', 'integer', 'min:1', 'max:'.self::POR_PAGINA_MAX],
        ];

        if (! $incluyeProducto) {
            return $reglas;
        }

        return ['producto_id' => ['nullable', 'integer']] + $reglas;
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function aplicarFiltros(Builder|HasMany $consulta, array $filtros): Builder|HasMany
    {
        return $consulta
            ->when($filtros['producto_id'] ?? null, fn ($q, $p) => $q->where('producto_id', $p))
            ->when($filtros['tipo'] ?? null, fn ($q, $t) => $q->where('tipo', $t))
            ->when($filtros['desde'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filtros['hasta'] ?? null, fn ($q, $h) => $q->whereDate('created_at', '<=', $h));
    }

    /**
     * Los filtros realmente aplicados, para que la pantalla no tenga que
     * adivinar si el backend aceptó lo que mandó. Misma convencion que el
     * resto de los listados de la API de gestion.
     *
     * @param  array<string, mixed>  $filtros
     * @return array<string, mixed>
     */
    private function filtrosAplicados(array $filtros, bool $incluyeProducto): array
    {
        $aplicados = [
            'tipo'  => $filtros['tipo'] ?? null,
            'desde' => $filtros['desde'] ?? null,
            'hasta' => $filtros['hasta'] ?? null,
        ];

        if (! $incluyeProducto) {
            return $aplicados;
        }

        // Entero y no la cadena que llega por query string: el cliente compara
        // este eco contra el id que mando, y '1' !== 1.
        return [
            'producto_id' => isset($filtros['producto_id']) ? (int) $filtros['producto_id'] : null,
        ] + $aplicados;
    }
}
