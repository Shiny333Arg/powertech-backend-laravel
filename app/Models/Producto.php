<?php

namespace App\Models;

use App\Models\Concerns\NoSeBorraFisicamente;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;

/**
 * C.2 productos. Tabla central del sistema.
 *
 * TRES VALORES DE STOCK
 *   stock_fisico       unidades realmente en deposito. UNSIGNED, nunca < 0.
 *   stock_comprometido vendidas y pendientes de entrega, incluida la reserva
 *                      de preventa. UNSIGNED, nunca < 0.
 *   stock_disponible   COLUMNA GENERADA STORED = fisico - comprometido.
 *                      Solo lectura, indexada, y el unico que puede ser < 0.
 *
 * Ninguno de los tres se escribe desde aca. Comprometer, liberar y entregar
 * se hacen con UPDATE relativo dentro de la transaccion de la venta (ver
 * StockService), nunca leyendo el valor y reescribiendolo desde PHP: con un
 * SELECT previo, dos ventas simultaneas del mismo producto se pisan y una de
 * las dos reservas desaparece.
 *
 * UN PRODUCTO NO SE ELIMINA NUNCA. Solo baja logica: lo referencian lineas
 * de venta, de comprobante, de orden de compra y el historico de stock.
 */
class Producto extends Model
{
    use SoftDeletes, NoSeBorraFisicamente;

    protected $table = 'productos';

    /**
     * `stock_disponible` no esta en la lista, y su ausencia es deliberada:
     * es una columna generada por el motor. Tampoco estan `stock_fisico` ni
     * `stock_comprometido`, porque solo deben moverse por un movimiento de
     * stock registrado, jamas por un update suelto desde un formulario.
     */
    protected $fillable = [
        'categoria_id', 'alicuota_iva_id', 'proveedor_id',
        'codigo', 'nombre', 'marca', 'descripcion', 'imagen',
        'precio', 'costo',
        'punto_reposicion', 'cantidad_reposicion', 'reposicion_automatica',
        'peso_gramos', 'destacado', 'publicado_app', 'activo',
    ];

    protected function casts(): array
    {
        return [
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
    }

    /**
     * Cinturon y tiradores sobre la columna generada.
     *
     * El motor ya rechaza la escritura con ERROR 1906, pero ese error aparece
     * recien al hacer flush y no dice de donde salio. Este mutador falla en el
     * momento exacto en que alguien intenta asignarla.
     *
     * La hidratacion desde la base no pasa por los mutadores (usa
     * setRawAttributes), asi que leer el modelo sigue funcionando normal.
     */
    public function setStockDisponibleAttribute($value): void
    {
        throw new LogicException(
            'stock_disponible es una columna generada: se deriva de stock_fisico '
            . 'menos stock_comprometido y no se escribe. Registra un movimiento '
            . 'de stock en su lugar.'
        );
    }

    // ---------------------------------------------------------------
    // Relaciones
    // ---------------------------------------------------------------

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

    public function ordenCompraLineas(): HasMany
    {
        return $this->hasMany(OrdenCompraLinea::class, 'producto_id');
    }

    public function promociones(): BelongsToMany
    {
        return $this->belongsToMany(
            Promocion::class, 'promocion_productos', 'producto_id', 'promocion_id'
        );
    }

    // ---------------------------------------------------------------
    // Scopes
    // ---------------------------------------------------------------

    /** Vitrina de la tienda. */
    public function scopePublicados(Builder $q): Builder
    {
        return $q->where('publicado_app', true)->where('activo', true);
    }

    /** Con unidades libres. Usa el indice de la columna generada. */
    public function scopeConDisponibilidad(Builder $q): Builder
    {
        return $q->where('stock_disponible', '>', 0);
    }

    /** Dashboard de reposicion: incluye los que estan en negativo. */
    public function scopeBajoPuntoReposicion(Builder $q): Builder
    {
        return $q->whereColumn('stock_disponible', '<', 'punto_reposicion');
    }

    public function scopeConReposicionAutomatica(Builder $q): Builder
    {
        return $q->where('reposicion_automatica', true)->whereNotNull('proveedor_id');
    }

    // ---------------------------------------------------------------
    // Consultas de negocio
    // ---------------------------------------------------------------

    /** Lo unico que se le muestra al cliente y al vendedor al vender. */
    public function hayDisponibilidadInmediata(int $cantidad = 1): bool
    {
        return $this->stock_disponible >= $cantidad;
    }

    /** El disponible negativo mide cuantas unidades se deben a clientes. */
    public function estaEnPreventa(): bool
    {
        return $this->stock_disponible < 0;
    }

    /** La entrega exige mercaderia real en el deposito. */
    public function puedeEntregar(int $cantidad): bool
    {
        return $this->stock_fisico >= $cantidad
            && $this->stock_comprometido >= $cantidad;
    }

    /** Cantidad a pedir: cubre el faltante hasta el punto mas la reposicion. */
    public function cantidadASolicitar(): int
    {
        return max(0, $this->punto_reposicion - $this->stock_disponible)
            + $this->cantidad_reposicion;
    }

    /**
     * Verificacion de consistencia del job de conciliacion: los saldos
     * cacheados tienen que coincidir siempre con la suma del historico.
     */
    public function saldosCoincidenConHistorico(): bool
    {
        $sumas = $this->movimientosStock()
            ->selectRaw('COALESCE(SUM(cantidad_fisico), 0) AS f, COALESCE(SUM(cantidad_comprometido), 0) AS c')
            ->first();

        return (int) $sumas->f === $this->stock_fisico
            && (int) $sumas->c === $this->stock_comprometido;
    }
}
