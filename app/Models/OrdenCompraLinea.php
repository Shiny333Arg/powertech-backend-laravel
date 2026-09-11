<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * E.3 orden_compra_lineas. `cantidad_recibida` permite recepciones parciales
 * —lo normal en el rubro— sin necesitar una tabla de recepciones aparte.
 * Cada recepcion impacta como un movimiento de tipo `compra` y suma
 * unicamente al stock fisico.
 */
class OrdenCompraLinea extends Model
{
    protected $table = 'orden_compra_lineas';

    public $timestamps = false;

    protected $fillable = [
        'orden_compra_id', 'producto_id', 'cantidad_pedida',
        'cantidad_recibida', 'costo_unitario',
    ];

    protected function casts(): array
    {
        return [
            'cantidad_pedida'   => 'integer',
            'cantidad_recibida' => 'integer',
            'costo_unitario'    => 'decimal:2',
        ];
    }

    public function ordenCompra(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_compra_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function pendienteDeRecibir(): int
    {
        return max(0, $this->cantidad_pedida - $this->cantidad_recibida);
    }
}
