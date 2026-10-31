<?php

namespace App\Models;

use App\Enums\EstadoOrdenCompra;
use App\Models\Concerns\ProtegeContraBorrado;
use App\Models\Concerns\TieneBajaLogica;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Nunca se elimina: esta referenciado por venta_lineas, comprobante_lineas,
 * movimientos_stock y orden_compra_lineas, y todas esas filas son historia
 * que no puede quedar sin sujeto.
 *
 * stock_disponible es una columna GENERADA. NO figura en $fillable: es de solo
 * lectura y cualquier intento de escribirla es un error del motor. Los otros
 * dos saldos tampoco se escriben con save(): se mueven con UPDATE relativo
 * dentro de la transaccion de la venta (ver comentario al pie).
 */
class Producto extends Model
{
    use ProtegeContraBorrado;
    use TieneBajaLogica;

    protected $table = 'productos';

    protected $fillable = [
        'categoria_id',
        'alicuota_iva_id',
        'proveedor_id',
        'codigo',
        'nombre',
        'marca',
        'descripcion',
        'imagen',
        'precio',
        'costo',
        'stock_fisico',
        'stock_comprometido',
        'punto_reposicion',
        'cantidad_reposicion',
        'reposicion_automatica',
        'peso_gramos',
        'destacado',
        'publicado_app',
        'activo',
    ];

    protected $casts = [
        'precio'                => 'decimal:2',
        'costo'                 => 'decimal:2',
        'stock_fisico'          => 'integer',
        'stock_comprometido'    => 'integer',
        'stock_disponible'      => 'integer',
        'punto_reposicion'      => 'integer',
        'cantidad_reposicion'   => 'integer',
        'reposicion_automatica' => 'boolean',
        'peso_gramos'           => 'integer',
        'destacado'             => 'boolean',
        'publicado_app'         => 'boolean',
        'activo'                => 'boolean',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function alicuotaIva(): BelongsTo
    {
        return $this->belongsTo(AlicuotaIva::class, 'alicuota_iva_id');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function movimientosStock(): HasMany
    {
        return $this->hasMany(MovimientoStock::class, 'producto_id');
    }

    public function ventaLineas(): HasMany
    {
        return $this->hasMany(VentaLinea::class, 'producto_id');
    }

    public function comprobanteLineas(): HasMany
    {
        return $this->hasMany(ComprobanteLinea::class, 'producto_id');
    }

    public function ordenCompraLineas(): HasMany
    {
        return $this->hasMany(OrdenCompraLinea::class, 'producto_id');
    }

    public function carritoLineas(): HasMany
    {
        return $this->hasMany(CarritoLinea::class, 'producto_id');
    }

    public function promociones(): BelongsToMany
    {
        return $this->belongsToMany(Promocion::class, 'promocion_productos', 'producto_id', 'promocion_id');
    }

    /** Lo que se ve en la tienda: activo Y publicado. Son preguntas distintas. */
    public function scopePublicados(Builder $query): Builder
    {
        // Calificadas con qualifyColumn y no a mano: el scope se usa dentro de
        // consultas que hacen JOIN con categorias, que tambien tiene 'activo'.
        // Sin calificar, el motor corta por columna ambigua.
        return $query->where($query->qualifyColumn('activo'), true)
            ->where($query->qualifyColumn('publicado_app'), true);
    }

    public function scopeConDisponibilidad(Builder $query): Builder
    {
        return $query->where('stock_disponible', '>', 0);
    }

    /**
     * Alimenta el dashboard de reposicion y el barrido diario.
     *
     * Se compara contra el DISPONIBLE, no contra el fisico: contra el fisico,
     * un producto con todas sus unidades vendidas y pendientes de entrega
     * pareceria abastecido y nunca se repondria.
     *
     * <b>La comparacion es <=, no <.</b> "Quedan 5 y el punto de reposicion es
     * 5" ya es el momento de pedir: el umbral es el piso que no se quiere
     * cruzar, no un valor al que se pueda llegar y quedarse. El DER original
     * decia '<'; esto lo corrige, y hay que corregirlo tambien alla para que
     * no queden dos definiciones del mismo umbral.
     */
    public function scopeBajoPuntoReposicion(Builder $query): Builder
    {
        return $query->whereColumn('stock_disponible', '<=', 'punto_reposicion');
    }

    /** Los que el restock automatico mira. Sin esto, mirarlo es un where suelto. */
    public function scopeConReposicionAutomatica(Builder $query): Builder
    {
        return $query->where('reposicion_automatica', true);
    }

    /**
     * Reposicion automatica activada y sin proveedor: no hay a quien pedirle.
     *
     * ProductoRequest ya no deja guardar esa combinacion, pero se llega igual
     * por datos anteriores a esa validacion o porque el proveedor se dio de
     * baja despues. Es el agujero que hay que poder listar.
     */
    public function scopeSinProveedor(Builder $query): Builder
    {
        return $query->whereNull('proveedor_id');
    }

    /** Preventa: hay unidades comprometidas que todavia no estan en el deposito. */
    public function enPreventa(): bool
    {
        return $this->stock_disponible < 0;
    }

    public function necesitaReposicion(): bool
    {
        return $this->stock_disponible <= $this->punto_reposicion;
    }

    /**
     * La orden abierta que ya cubre este faltante, si existe.
     *
     * Devolver la orden en vez de un booleano es lo que permite que la pantalla
     * de stock muestre "ya pedido en OC-0000034" con enlace, en lugar de un
     * cartel generico que obliga a ir a buscarla.
     */
    public function ordenAbierta(): ?OrdenCompra
    {
        return OrdenCompra::query()
            ->whereIn('estado', EstadoOrdenCompra::abiertas())
            ->conProducto($this->getKey())
            ->latest('id')
            ->first();
    }
}

/*
 * Los saldos NO se mueven desde el modelo. Comprometer, liberar y entregar van
 * con UPDATE relativo dentro de la transaccion, nunca leyendo y reescribiendo
 * desde PHP, porque dos ventas simultaneas del mismo producto tienen que
 * serializarse en el lock de fila:
 *
 *   // comprometer: siempre valido, incluso en preventa
 *   Producto::whereKey($id)->update([
 *       'stock_comprometido' => DB::raw('stock_comprometido + '.(int) $cantidad),
 *   ]);
 *
 *   // entregar: condicionado al stock real. Cero filas afectadas = sin stock.
 *   $ok = Producto::whereKey($id)
 *       ->where('stock_fisico', '>=', $cantidad)
 *       ->where('stock_comprometido', '>=', $cantidad)
 *       ->update([
 *           'stock_fisico'       => DB::raw('stock_fisico - '.(int) $cantidad),
 *           'stock_comprometido' => DB::raw('stock_comprometido - '.(int) $cantidad),
 *       ]);
 *
 * Esto vive en un servicio de stock, no acá. Va como referencia.
 */
