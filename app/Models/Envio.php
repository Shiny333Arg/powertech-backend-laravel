<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 1:1 opcional con la venta: modo_entrega = retiro significa que no hay fila.
 *
 * La direccion se COPIA, no se referencia a direcciones: un envio ya despachado
 * no puede cambiar de destino porque el cliente edito su domicilio en el perfil.
 */
class Envio extends Model
{
    protected $table = 'envios';

    protected $fillable = [
        'venta_id', 'calle', 'numero', 'piso_depto', 'codigo_postal',
        'localidad', 'provincia', 'costo', 'proveedor_envio',
        'envio_externo_id', 'tracking_url', 'estado', 'fecha_despacho',
    ];

    protected $casts = [
        'costo'          => 'decimal:2',
        'fecha_despacho' => 'datetime',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }
}
