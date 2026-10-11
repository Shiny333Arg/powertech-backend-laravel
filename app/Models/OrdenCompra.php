<?php

namespace App\Models;

use App\Enums\EstadoOrdenCompra;
use App\Enums\OrigenOrdenCompra;
use App\Enums\TipoMovimientoStock;
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

    /**
     * Las entradas de mercaderia contra esta orden, de la mas nueva a la mas
     * vieja.
     *
     * Es el historial de recepciones, y existe sin tabla de recepciones porque
     * cada entrada ya es un movimiento de stock con su fecha, su autor y el
     * remito del proveedor en 'motivo'. Agregar una tabla seria guardar dos
     * veces lo mismo.
     */
    public function recepciones(): MorphMany
    {
        return $this->movimientosStock()
            ->where('tipo', TipoMovimientoStock::Compra->value)
            ->latest('created_at');
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

    /** Lo que el deposito tiene para recibir: enviadas y recibidas a medias. */
    public function scopeEnRecepcion(Builder $query): Builder
    {
        return $query->whereIn('estado', [
            EstadoOrdenCompra::Enviada->value,
            EstadoOrdenCompra::RecibidaParcial->value,
        ]);
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

    /** Se le puede cargar una entrega de mercaderia. */
    public function esRecibible(): bool
    {
        return $this->estado->permiteRecepcion();
    }

    /**
     * Quedo a medio recibir y se puede dar por terminada.
     *
     * Es el caso del proveedor que manda 8 de 10 y avisa que el resto no viene.
     * Sin una accion explicita de cierre, la orden se queda en
     * 'recibida_parcial' para siempre, que es un estado ABIERTO: el restock la
     * cuenta como pedido vigente y nunca vuelve a pedir esos productos.
     */
    public function esCerrable(): bool
    {
        return $this->estado === EstadoOrdenCompra::RecibidaParcial;
    }

    /** Mientras quede algo por recibir, la orden sigue en juego. */
    public function unidadesPendientes(): int
    {
        return $this->lineas->sum(fn (OrdenCompraLinea $linea) => $linea->pendiente);
    }

    public function unidadesRecibidas(): int
    {
        return $this->lineas->sum('cantidad_recibida');
    }

    /** Todos los renglones estan servidos. */
    public function estaCompleta(): bool
    {
        return $this->lineas->every(fn (OrdenCompraLinea $linea) => $linea->estaCompleta());
    }

    /** Llego algo de mas en algun renglon. */
    public function tieneExcedentes(): bool
    {
        return $this->lineas->contains(fn (OrdenCompraLinea $linea) => $linea->tieneExcedente());
    }

    /**
     * El estado que le corresponde despues de cargar una entrega.
     *
     * Se calcula desde las lineas y no desde lo que acaba de entrar: lo que
     * define si la orden esta servida es el acumulado de 'cantidad_recibida',
     * no el tamaño de esta entrega. Exige las lineas frescas.
     */
    public function estadoTrasRecepcion(): EstadoOrdenCompra
    {
        return $this->estaCompleta()
            ? EstadoOrdenCompra::Recibida
            : EstadoOrdenCompra::RecibidaParcial;
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
     * llama total_estimado. <b>No se recalcula al recibir</b>, ni siquiera
     * cuando llega de mas: lo que estima es el pedido, no la entrega.
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
