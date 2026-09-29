<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** cantidad_recibida habilita recepciones parciales sin tabla de recepciones. */
class OrdenCompraLinea extends Model
{
    protected $table = 'orden_compra_lineas';

    protected $fillable = [
        'orden_compra_id', 'producto_id', 'cantidad_pedida',
        'cantidad_recibida', 'costo_unitario',
    ];

    protected $casts = [
        'cantidad_pedida'   => 'integer',
        'cantidad_recibida' => 'integer',
        'costo_unitario'    => 'decimal:2',
    ];

    public function ordenCompra(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_compra_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    /**
     * Lo que todavia se espera de este renglon.
     *
     * Pasa a ser un accessor y no un metodo para poder usarlo en las vistas y
     * en sum() sin parentesis, como el resto de los calculados del modelo. El
     * max(0) cubre la recepcion de mas: si el proveedor manda 12 sobre 10
     * pedidas, el pendiente es cero, no -2.
     */
    protected function pendiente(): Attribute
    {
        return Attribute::get(fn () => max(0, $this->cantidad_pedida - $this->cantidad_recibida));
    }

    /** El estimado de este renglon: lo pedido al costo del catalogo. */
    protected function subtotalEstimado(): Attribute
    {
        return Attribute::get(fn () => $this->cantidad_pedida * (float) $this->costo_unitario);
    }

    public function estaCompleta(): bool
    {
        return $this->pendiente === 0;
    }
}
