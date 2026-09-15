<?php

namespace App\Models;

use App\Enums\TipoMovimientoStock;
use App\Models\Concerns\ProtegeContraBorrado;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Tabla append-only: sin updated_at y sin borrado. Un error se corrige con un
 * movimiento inverso, nunca editando el original.
 *
 * Dos columnas de delta y no una: la entrega descuenta fisico y libera
 * comprometido en el mismo instante, y tiene que ser una sola fila.
 */
class MovimientoStock extends Model
{
    use ProtegeContraBorrado;

    public const UPDATED_AT = null;

    protected $table = 'movimientos_stock';

    protected $fillable = [
        'producto_id', 'tipo', 'cantidad_fisico', 'cantidad_comprometido',
        'stock_fisico_resultante', 'stock_comprometido_resultante',
        'origen_type', 'origen_id', 'usuario_id', 'motivo',
    ];

    protected $casts = [
        'tipo'                          => TipoMovimientoStock::class,
        'cantidad_fisico'               => 'integer',
        'cantidad_comprometido'         => 'integer',
        'stock_fisico_resultante'       => 'integer',
        'stock_comprometido_resultante' => 'integer',
        'created_at'                    => 'datetime',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /** ventas | ordenes_compra | comprobantes | ajuste manual (null) */
    public function origen(): MorphTo
    {
        return $this->morphTo();
    }

    protected function mensajeNoEliminable(): string
    {
        return 'Los movimientos de stock no se borran. Registrá un movimiento inverso.';
    }
}
