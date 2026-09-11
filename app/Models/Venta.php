<?php

namespace App\Models;

use App\Enums\CanalVenta;
use App\Enums\EstadoVenta;
use App\Enums\ModoEntrega;
use App\Exceptions\BorradoNoPermitidoException;
use App\Exceptions\TransicionInvalidaException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * H.1 ventas. El documento comercial. Mostrador y online comparten la tabla
 * y se diferencian por `canal`.
 *
 * NO USA SoftDeletes, Y ES A PROPOSITO. Una venta no se elimina de ninguna
 * forma, ni siquiera logicamente. Su fin de vida ya esta modelado como
 * estado: cancelada, vencida, devuelta_parcial o devuelta. Tener deleted_at
 * seria peor que inutil: un $venta->delete() distraido haria desaparecer de
 * las pantallas un documento facturado, con su comprobante autorizado
 * colgando de una venta que Eloquent ya no devuelve.
 *
 * Al no tener el trait, delete() intenta un borrado fisico que las FK
 * RESTRICT de venta_lineas, pagos, comprobantes y envios rechazan. La guarda
 * de booted() lo ataja antes, para que el mensaje sea del dominio y no una
 * violacion de integridad. Y cubre el caso que la FK no puede cubrir: una
 * venta recien creada, todavia sin lineas.
 */
class Venta extends Model
{
    protected $table = 'ventas';

    /**
     * `numero` no esta en la lista: lo asigna Numerador::siguiente() dentro
     * de la transaccion, nunca un formulario. Tampoco `estado`, que solo
     * cambia por transicionarA().
     */
    protected $fillable = [
        'canal', 'cliente_id', 'usuario_id', 'modo_entrega', 'validez_hasta',
        'subtotal', 'descuento_promociones', 'costo_envio',
        'neto_gravado', 'total_iva', 'total', 'observaciones', 'fecha',
    ];

    protected function casts(): array
    {
        return [
            'canal'                 => CanalVenta::class,
            'estado'                => EstadoVenta::class,
            'modo_entrega'          => ModoEntrega::class,
            'validez_hasta'         => 'date',
            'fecha_validacion'      => 'datetime',
            'fecha'                 => 'datetime',
            'numero'                => 'integer',
            'subtotal'              => 'decimal:2',
            'descuento_promociones' => 'decimal:2',
            'costo_envio'           => 'decimal:2',
            'neto_gravado'          => 'decimal:2',
            'total_iva'             => 'decimal:2',
            'total'                 => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (self $venta) {
            throw BorradoNoPermitidoException::para(
                "la venta {$venta->numeroFormateado()}",
                'una venta no se elimina. Cancelala si todavia no se facturo, '
                . 'o emiti una nota de credito para devolverla.'
            );
        });
    }

    // ---------------------------------------------------------------
    // Relaciones
    // ---------------------------------------------------------------

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /** El vendedor. Null en la venta online. */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /** Quien habilito el cobro del presupuesto. */
    public function usuarioValido(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_valido_id');
    }

    public function lineas(): HasMany
    {
        return $this->hasMany(VentaLinea::class, 'venta_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'venta_id');
    }

    public function comprobantes(): HasMany
    {
        return $this->hasMany(Comprobante::class, 'venta_id');
    }

    public function envio(): HasOne
    {
        return $this->hasOne(Envio::class, 'venta_id');
    }

    public function promocionesAplicadas(): HasMany
    {
        return $this->hasMany(PromocionAplicada::class, 'venta_id');
    }

    public function movimientosStock(): MorphMany
    {
        return $this->morphMany(MovimientoStock::class, 'origen');
    }

    // ---------------------------------------------------------------
    // Numeracion interna
    // ---------------------------------------------------------------

    /**
     * El correlativo interno se muestra con prefijo. Sale de la serie
     * 'venta' de numeradores; la columna guarda el entero.
     */
    public function numeroFormateado(): string
    {
        return sprintf('V-%07d', $this->numero);
    }

    // ---------------------------------------------------------------
    // Estados
    // ---------------------------------------------------------------

    public function puedeTransicionarA(EstadoVenta $destino): bool
    {
        return $this->estado->puedeTransicionarA($destino);
    }

    /**
     * Cambia el estado validando la transicion. NO toca stock ni emite
     * comprobantes: eso lo orquesta el servicio de ventas, que envuelve esta
     * llamada, el movimiento de stock y la facturacion en una transaccion.
     * Separarlo evita que un cambio de estado silencioso mueva inventario.
     */
    public function transicionarA(EstadoVenta $destino): void
    {
        if (! $this->puedeTransicionarA($destino)) {
            throw TransicionInvalidaException::entre($this->estado, $destino);
        }

        $this->estado = $destino;
        $this->save();
    }

    /** El presupuesto no compromete stock ni emite comprobante. */
    public function esPresupuesto(): bool
    {
        return $this->estado->esPresupuesto();
    }

    public function estaFacturada(): bool
    {
        return $this->comprobantes()->where('estado', 'autorizado')->exists();
    }

    public function estaVencido(): bool
    {
        return $this->esPresupuesto()
            && $this->validez_hasta !== null
            && $this->validez_hasta->isPast();
    }

    /**
     * Al acreditarse el pago se decide el estado siguiente segun haya o no
     * mercaderia. Se compara contra el fisico, no contra el disponible: las
     * unidades de esta venta ya estan comprometidas por ella misma.
     */
    public function tieneStockParaPreparar(): bool
    {
        return ! $this->lineas()
            ->join('productos', 'productos.id', '=', 'venta_lineas.producto_id')
            ->whereColumn('productos.stock_fisico', '<', 'venta_lineas.cantidad')
            ->exists();
    }

    // ---------------------------------------------------------------
    // Scopes
    // ---------------------------------------------------------------

    /**
     * Cola de preventas facturadas esperando reposicion, por antiguedad:
     * quien pago primero se entrega primero. Usa el indice (estado, fecha).
     */
    public function scopePendientesDeStock(Builder $q): Builder
    {
        return $q->where('estado', EstadoVenta::PENDIENTE_STOCK)->orderBy('fecha');
    }

    public function scopeDelCanal(Builder $q, CanalVenta $canal): Builder
    {
        return $q->where('canal', $canal);
    }

    public function scopePresupuestosVencidos(Builder $q): Builder
    {
        return $q->where('estado', EstadoVenta::PRESUPUESTO)
                 ->whereDate('validez_hasta', '<', now());
    }
}
