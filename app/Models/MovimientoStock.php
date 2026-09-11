<?php

namespace App\Models;

use App\Enums\TipoMovimientoStock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

/**
 * D.1 movimientos_stock. Fuente de verdad para auditar y reconstruir el
 * stock: guardar solo el numero no permite explicar por que paso de 8 a 7.
 *
 * TABLA APPEND-ONLY: sin updated_at y sin borrado. Un error se corrige con un
 * movimiento inverso, nunca editando el original.
 *
 * Dos columnas de delta y no una, porque la entrega mueve los dos saldos en
 * el mismo instante y con una sola columna habria que partir el evento en dos
 * filas y confiar en que siempre se escriban juntas.
 */
class MovimientoStock extends Model
{
    protected $table = 'movimientos_stock';

    public const UPDATED_AT = null;   // append-only

    protected $fillable = [
        'producto_id', 'tipo', 'cantidad_fisico', 'cantidad_comprometido',
        'stock_fisico_resultante', 'stock_comprometido_resultante',
        'origen_type', 'origen_id', 'usuario_id', 'motivo',
    ];

    protected function casts(): array
    {
        return [
            'tipo'                          => TipoMovimientoStock::class,
            'cantidad_fisico'               => 'integer',
            'cantidad_comprometido'         => 'integer',
            'stock_fisico_resultante'       => 'integer',
            'stock_comprometido_resultante' => 'integer',
            'created_at'                    => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Append-only: la guarda cierra el camino que la base no puede cerrar
        // sola, porque a nivel motor la fila es una fila comun.
        static::updating(fn () => throw new LogicException(
            'Los movimientos de stock no se editan. Registra un movimiento inverso.'
        ));

        static::deleting(fn () => throw new LogicException(
            'Los movimientos de stock no se borran. Registra un movimiento inverso.'
        ));

        static::creating(function (self $m) {
            if ($m->tipo === TipoMovimientoStock::AJUSTE && blank($m->motivo)) {
                throw new LogicException('El ajuste de inventario exige un motivo.');
            }
        });
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /** ventas | ordenes_compra | comprobantes, o null si es ajuste manual. */
    public function origen(): MorphTo
    {
        return $this->morphTo();
    }
}
