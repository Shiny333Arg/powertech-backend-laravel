<?php

namespace App\Models;

use App\Enums\EstadoOrdenCompra;
use App\Enums\OrigenOrdenCompra;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * No se elimina nunca. Su fin de vida es un estado —'recibida' o 'cancelada'—,
 * igual que el de una venta. La FK de sus lineas es CASCADE, pero eso es la red
 * para un borrado por SQL directo, no una invitacion a usar delete().
 */
class OrdenCompra extends Model
{
    protected $table = 'ordenes_compra';

    protected $fillable = [
        'numero', 'proveedor_id', 'estado', 'origen', 'total_estimado',
        'usuario_creo_id', 'usuario_aprobo_id', 'fecha_aprobacion',
        'fecha_envio', 'fecha_entrega_estimada', 'observaciones',
    ];

    protected $casts = [
        'estado'                 => EstadoOrdenCompra::class,
        'origen'                 => OrigenOrdenCompra::class,
        'total_estimado'         => 'decimal:2',
        'fecha_aprobacion'       => 'datetime',
        'fecha_envio'            => 'datetime',
        'fecha_entrega_estimada' => 'date',
    ];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function lineas(): HasMany
    {
        return $this->hasMany(OrdenCompraLinea::class, 'orden_compra_id');
    }

    public function usuarioCreo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_creo_id');
    }

    public function usuarioAprobo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_aprobo_id');
    }

    public function movimientosStock(): MorphMany
    {
        return $this->morphMany(MovimientoStock::class, 'origen');
    }

    // ------------------------------------------------------------------
    // Scopes
    // ------------------------------------------------------------------

    /** Antes de generar una orden automatica: el faltante ya puede estar pedido. */
    public function scopeAbiertas(Builder $query): Builder
    {
        return $query->whereIn('estado', EstadoOrdenCompra::abiertas());
    }

    public function scopePendientesDeAprobacion(Builder $query): Builder
    {
        return $query->where('estado', EstadoOrdenCompra::Borrador->value);
    }

    /** Las que ya no van a mover stock: cerradas para siempre. */
    public function scopeCerradas(Builder $query): Builder
    {
        return $query->whereIn('estado', EstadoOrdenCompra::cerradas());
    }

    /** ¿Este producto ya esta pedido? Es la pregunta del restock automatico. */
    public function scopeConProducto(Builder $query, int $productoId): Builder
    {
        return $query->whereHas('lineas', fn (Builder $linea) => $linea->where('producto_id', $productoId));
    }

    public function scopeAutomaticas(Builder $query): Builder
    {
        return $query->where('origen', OrigenOrdenCompra::Automatica->value);
    }

    // ------------------------------------------------------------------
    // Estado
    // ------------------------------------------------------------------

    public function esBorrador(): bool
    {
        return $this->estado === EstadoOrdenCompra::Borrador;
    }

    /** Solo el borrador se edita: despues, lo aprobo alguien o lo recibio el proveedor. */
    public function esEditable(): bool
    {
        return $this->estado->permiteEdicion();
    }

    public function estaAbierta(): bool
    {
        return $this->estado->esAbierta();
    }

    /** Mientras quede algo por recibir, la orden sigue en juego. */
    public function unidadesPendientes(): int
    {
        return $this->lineas->sum(fn (OrdenCompraLinea $linea) => $linea->pendiente);
    }

    // ------------------------------------------------------------------
    // Lineas
    // ------------------------------------------------------------------

    /**
     * El renglon de un producto en esta orden, o null si no esta.
     *
     * Consulta fresca y no la relacion cargada a proposito: la usan el alta
     * automatica y la ampliacion, las dos dentro de una transaccion, donde una
     * coleccion en memoria puede estar desactualizada respecto de lo que otro
     * worker acaba de escribir.
     */
    public function lineaDe(int $productoId): ?OrdenCompraLinea
    {
        return $this->lineas()->where('producto_id', $productoId)->first();
    }

    /**
     * Los renglones cuyo pendiente ya no alcanza para sacar al producto de su
     * punto de reposicion.
     *
     * Pasa cuando se siguio vendiendo despues de aprobar el pedido: el faltante
     * crecio y el documento ya estaba cerrado. Recibirlo completo no va a
     * destrabar las preventas, y conviene saberlo antes de que llegue.
     *
     * @return \Illuminate\Support\Collection<int, OrdenCompraLinea>
     */
    public function lineasInsuficientes()
    {
        return $this->lineas->reject(fn (OrdenCompraLinea $linea) => $linea->cubreElFaltante());
    }

    // ------------------------------------------------------------------
    // Totales
    // ------------------------------------------------------------------

    /**
     * Recalcula el total desde las lineas y lo persiste.
     *
     * Se recalcula entero, nunca se acumula: un total que se va sumando de a
     * un renglon se desincroniza en la primera edicion o en el primer renglon
     * borrado, y nadie lo nota hasta que el administrativo compara contra la
     * factura del proveedor.
     *
     * Es una estimacion por definicion —usa el costo del catalogo, no el
     * precio real que va a facturar el proveedor— y por eso la columna se
     * llama total_estimado.
     */
    public function recalcularTotalEstimado(): static
    {
        $total = $this->lineas()
            ->selectRaw('COALESCE(SUM(cantidad_pedida * costo_unitario), 0) as total')
            ->value('total');

        $this->forceFill(['total_estimado' => $total])->save();

        return $this;
    }
}
