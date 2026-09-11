<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * J.1 envios. 1:1 opcional con ventas: modo_entrega = retiro significa que
 * no hay fila aca.
 *
 * La direccion se COPIA, no se referencia a `direcciones`. Un envio ya
 * despachado no puede cambiar de destino porque el cliente edito su
 * domicilio en el perfil: el documento logistico es tan inmutable como el
 * fiscal.
 */
class Envio extends Model
{
    protected $table = 'envios';

    protected $fillable = [
        'venta_id', 'calle', 'numero', 'piso_depto', 'codigo_postal',
        'localidad', 'provincia', 'costo', 'proveedor_envio',
        'envio_externo_id', 'tracking_url', 'estado', 'fecha_despacho',
    ];

    protected function casts(): array
    {
        return [
            'costo'          => 'decimal:2',
            'fecha_despacho' => 'datetime',
        ];
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function estaDespachado(): bool
    {
        return $this->fecha_despacho !== null;
    }

    /** Copia una direccion del cliente al documento, sin referenciarla. */
    public static function desdeDireccion(Direccion $d, int $ventaId, float $costo = 0): self
    {
        return new self([
            'venta_id'      => $ventaId,
            'calle'         => $d->calle,
            'numero'        => $d->numero,
            'piso_depto'    => $d->piso_depto,
            'codigo_postal' => $d->codigo_postal,
            'localidad'     => $d->localidad,
            'provincia'     => $d->provincia,
            'costo'         => $costo,
        ]);
    }
}
