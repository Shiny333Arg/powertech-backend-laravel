<?php

namespace App\Models;

use App\Enums\CanalVenta;
use App\Enums\EstadoVenta;
use App\Enums\ModoEntrega;
use App\Models\Concerns\ProtegeContraBorrado;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * El documento comercial. Mostrador y online comparten la tabla y se
 * diferencian por canal.
 *
 * SIN columna activo, a proposito: la venta no se da de baja ni se elimina.
 * Su fin de vida es un estado —cancelada, vencida, devuelta_parcial,
 * devuelta— y cada uno dice algo distinto que un flag no diria.
 *
 * numero es el correlativo INTERNO, unico global, asignado en el alta
 * (incluido el presupuesto) y fijo de por vida. No tiene ninguna relacion con
 * comprobantes.numero, que es el correlativo fiscal que autoriza ARCA.
 */
class Venta extends Model
{
    use ProtegeContraBorrado;

    protected $table = 'ventas';

    protected $fillable = [
        'numero', 'canal', 'cliente_id', 'usuario_id', 'estado', 'modo_entrega',
        'validez_hasta', 'usuario_valido_id', 'fecha_validacion',
        'subtotal', 'descuento_promociones', 'costo_envio',
        'neto_gravado', 'total_iva', 'total', 'observaciones', 'fecha',
    ];

    protected $casts = [
        'numero'                => 'integer',
        'canal'                 => CanalVenta::class,
        'estado'                => EstadoVenta::class,
        'modo_entrega'          => ModoEntrega::class,
        'validez_hasta'         => 'date',
        'fecha_validacion'      => 'datetime',
        'subtotal'              => 'decimal:2',
        'descuento_promociones' => 'decimal:2',
        'costo_envio'           => 'decimal:2',
        'neto_gravado'          => 'decimal:2',
        'total_iva'             => 'decimal:2',
        'total'                 => 'decimal:2',
        'fecha'                 => 'datetime',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /** Vendedor. Null en la venta online. */
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

    /** 0 (presupuesto), 1 (factura) o N (factura + nota de credito). */
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

    /** Cola del deposito: quien pago antes se entrega antes. */
    public function scopePendientesDeStock(Builder $query): Builder
    {
        return $query->where('estado', EstadoVenta::PendienteStock->value)->orderBy('fecha');
    }

    public function scopeDelCanal(Builder $query, CanalVenta $canal): Builder
    {
        return $query->where('canal', $canal->value);
    }

    public function esPresupuesto(): bool
    {
        return $this->estado === EstadoVenta::Presupuesto;
    }

    /** El circuito de estados vive en EstadoVenta; acá solo se consulta. */
    public function puedeTransicionarA(EstadoVenta $destino): bool
    {
        return $this->estado->puedeTransicionarA($destino);
    }

    public function comprometeStock(): bool
    {
        return $this->estado->comprometeStock();
    }

    public function estaValidada(): bool
    {
        return $this->fecha_validacion !== null;
    }

    public function numeroFormateado(int $digitos = 7): string
    {
        return 'V-'.str_pad((string) $this->numero, $digitos, '0', STR_PAD_LEFT);
    }

    protected function mensajeNoEliminable(): string
    {
        return 'Una venta no se elimina ni se da de baja: cancelala o devolvela.';
    }
}
