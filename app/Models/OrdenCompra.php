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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * No se elimina nunca. Su fin de vida es un estado —'recibida' o 'cancelada'—,
 * igual que el de una venta. La FK de sus lineas es CASCADE, pero eso es la red
 * para un borrado por SQL directo, no una invitacion a usar delete().
 *
 * <b>Una orden puede ser de tres formas, y la diferencia es proveedor_id.</b>
 *
 *   Simple   proveedor_id seteado, orden_padre_id null. Es lo que habia hasta
 *            ahora y lo que genera siempre la reposicion automatica: un pedido
 *            dirigido a una empresa. Se aprueba, se envia y se recibe sola.
 *
 *   Madre    proveedor_id NULL. El administrativo cargo productos de varios
 *            proveedores en un solo documento. No se envia ni se recibe: al
 *            aprobarla emite una hija por proveedor y queda como el pedido que
 *            el usuario ve. Conserva sus lineas —son lo que se pidio— asi que
 *            el restock la cuenta como pedido vigente desde que es borrador.
 *
 *   Hija     proveedor_id seteado, orden_padre_id apuntando a la madre. Es una
 *            orden real: tiene su numero, su remito y su recepcion. Nace
 *            aprobada, porque la aprobacion de la madre es la decision que el
 *            requerimiento pide que quede registrada.
 *
 * Que la diferencia sea una columna y no un tipo es a proposito: una orden de
 * un solo proveedor cargada desde el formulario nuevo es indistinguible de una
 * de antes, y nada del circuito necesita preguntar de que forma es.
 */
class OrdenCompra extends Model
{
    protected $table = 'ordenes_compra';

    protected $fillable = [
        'numero', 'orden_padre_id', 'proveedor_id', 'estado', 'origen', 'total_estimado',
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

    /** La madre de la que salio esta orden, si salio de una. */
    public function padre(): BelongsTo
    {
        return $this->belongsTo(self::class, 'orden_padre_id');
    }

    /** Las ordenes por proveedor que emitio esta, al aprobarse. */
    public function hijas(): HasMany
    {
        return $this->hasMany(self::class, 'orden_padre_id');
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

    /**
     * ¿Este producto ya esta pedido? Es la pregunta del restock automatico.
     *
     * <b>Una madre ya repartida no cuenta, y es importante que no cuente.</b>
     * Sus lineas son una copia de las que tienen las hijas: quien puede cumplir
     * el pedido es la hija, no ella. Si contara, cancelar la orden de UN
     * proveedor no destrabaria nada —el evento de cancelacion dispara una nueva
     * evaluacion, esa evaluacion encontraria a la madre todavia abierta con ese
     * producto adentro, y concluiria que el faltante ya esta pedido—. El
     * producto no se volveria a pedir nunca.
     *
     * Una madre en BORRADOR si cuenta, y por el motivo opuesto: todavia no hay
     * hijas que cubran ese faltante, y lo que el administrativo esta cargando a
     * mano es un pedido real. Sin esto, el automatico abriria un pedido por
     * algo que alguien ya puso en pantalla.
     */
    public function scopeConProducto(Builder $query, int $productoId): Builder
    {
        return $query
            ->whereHas('lineas', fn (Builder $linea) => $linea->where('producto_id', $productoId))
            ->where(fn (Builder $sub) => $sub
                ->whereNotNull('proveedor_id')
                ->orWhere('estado', EstadoOrdenCompra::Borrador->value));
    }

    public function scopeAutomaticas(Builder $query): Builder
    {
        return $query->where('origen', OrigenOrdenCompra::Automatica->value);
    }

    /**
     * Lo que va en el listado: pedidos, no sus repartos.
     *
     * Una hija ya esta representada por su madre. Mostrar las dos convertiria
     * un pedido de tres proveedores en cuatro filas y el administrativo no
     * sabria cual tiene que aprobar.
     */
    public function scopeRaiz(Builder $query): Builder
    {
        return $query->whereNull('orden_padre_id');
    }

    /**
     * Las que tienen destinatario: simples e hijas, nunca una madre.
     *
     * Es el filtro de cualquier pregunta que termine en un correo o en una
     * entrega de mercaderia. Para saber si un faltante ya esta pedido NO se usa
     * este scope: ahi la madre cuenta, porque sus lineas son el pedido.
     */
    public function scopeEmitidas(Builder $query): Builder
    {
        return $query->whereNotNull('proveedor_id');
    }

    // ------------------------------------------------------------------
    // Forma
    // ------------------------------------------------------------------

    /** Pedido mezclado: se reparte en una orden por proveedor al aprobarse. */
    public function esMadre(): bool
    {
        return $this->proveedor_id === null;
    }

    public function esHija(): bool
    {
        return $this->orden_padre_id !== null;
    }

    /** Tiene un destinatario, asi que se le puede mandar un remito. */
    public function esEnviable(): bool
    {
        return ! $this->esMadre();
    }

    /**
     * ¿Queda algun remito por mandar?
     *
     * En una orden con destinatario la pregunta la contesta el estado y la
     * policy. En una madre no: lo que sale son los remitos de sus hijas, y
     * puede que ya hayan salido todos, o que queden las dos que fallaron la
     * vez pasada. Es lo que decide si el boton «enviar» tiene sentido.
     */
    public function tieneAlgoQueEnviar(): bool
    {
        if (! $this->esMadre()) {
            return true;
        }

        return $this->hijas()
            ->where('estado', EstadoOrdenCompra::Aprobada->value)
            ->exists();
    }

    /**
     * Los proveedores a los que apunta este pedido.
     *
     * Sale de las hijas si ya se repartio, y de las lineas si todavia es un
     * borrador: antes de aprobar no hay hijas, pero el usuario igual tiene que
     * ver a quienes se les va a emitir.
     *
     * Conviene tener cargadas 'hijas.proveedor' y 'lineas.proveedor' antes de
     * llamarlo en un listado, o es un N+1.
     *
     * @return \Illuminate\Support\Collection<int, Proveedor>
     */
    public function proveedoresInvolucrados(): Collection
    {
        if ($this->proveedor !== null) {
            return collect([$this->proveedor]);
        }

        $origen = $this->hijas->isNotEmpty() ? $this->hijas : $this->lineas;

        return $origen
            ->map(fn ($fila) => $fila->proveedor)
            ->filter()
            ->unique('id')
            ->sortBy('razon_social')
            ->values();
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

    /**
     * Se le puede cargar una entrega de mercaderia.
     *
     * Una madre nunca: la mercaderia la manda cada proveedor contra su propia
     * orden, y cargarla sobre el pedido entero no diria cual de los tres
     * cumplio.
     */
    public function esRecibible(): bool
    {
        return ! $this->esMadre() && $this->estado->permiteRecepcion();
    }

    /**
     * Quedo a medio recibir y se puede dar por terminada.
     *
     * Es el caso del proveedor que manda 8 de 10 y avisa que el resto no viene.
     * Sin una accion explicita de cierre, la orden se queda en
     * 'recibida_parcial' para siempre, que es un estado ABIERTO: el restock la
     * cuenta como pedido vigente y nunca vuelve a pedir esos productos.
     *
     * Una madre no, por el mismo motivo que no se recibe: quien puede dar por
     * terminado lo que falta es el proveedor que lo debe, y el que avisa que el
     * resto no viene avisa por lo suyo. Cerrar el pedido entero lo marcaria
     * recibido con los otros proveedores todavia debiendo.
     *
     * El estado 'recibida_parcial' de una madre no es una decision: es el
     * resumen de sus hijas que escribe sincronizarDesdeHijas().
     */
    public function esCerrable(): bool
    {
        return ! $this->esMadre() && $this->estado === EstadoOrdenCompra::RecibidaParcial;
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

    /**
     * Le pone a la madre el estado que resume a sus hijas.
     *
     * <b>Por que hace falta.</b> La madre se aprueba una vez y despues no
     * vuelve a moverse sola: lo que avanza son las hijas, cada una a su ritmo.
     * Sin esto, el listado muestra «aprobada» un pedido cuyos tres proveedores
     * ya entregaron todo.
     *
     * El resumen es el del renglon mas atrasado, no el del mas adelantado: un
     * pedido con dos proveedores entregados y uno sin enviar esta a medio
     * camino, no terminado. Las canceladas se ignoran salvo que no quede
     * ninguna otra.
     *
     * Escribe con forceFill a proposito: no es una transicion que alguien pide,
     * es el reflejo de transiciones que ya pasaron, asi que no corresponde
     * pasarla por puedeTransicionarA().
     *
     * <b>No es solo cosmetico.</b> El estado de la madre decide si el pedido
     * esta ABIERTO, y una orden abierta que contiene un producto es lo que hace
     * que el restock no lo vuelva a pedir. Una madre que se quedara en
     * «aprobada» con sus tres proveedores ya entregados bloquearia la
     * reposicion de esos productos para siempre.
     *
     * Por eso va dentro de una transaccion que bloquea la fila: dos depositos
     * cargando entregas de dos proveedores del mismo pedido a la vez se
     * serializan acá, y el ultimo en entrar lee el estado que dejo el primero.
     * Sin el lock, los dos pueden leer el mismo acumulado viejo y el resumen
     * queda atrasado una entrega.
     */
    public function sincronizarDesdeHijas(): static
    {
        if (! $this->esMadre()) {
            return $this;
        }

        return DB::transaction(function () {
            // El lock va primero y sobre la madre, no sobre las hijas: bloquear
            // las hijas chocaria con la transaccion de la recepcion, que las
            // tiene tomadas. Esta lectura ademas fija el snapshot despues de
            // adquirir el lock, asi que las hijas se leen ya actualizadas.
            self::whereKey($this->getKey())->lockForUpdate()->first();

            $hijas = $this->hijas()->with('lineas')->get();

            if ($hijas->isEmpty()) {
                return $this;
            }

            $vivas = $hijas->reject(fn (self $hija) => $hija->estado === EstadoOrdenCompra::Cancelada);

            if ($vivas->isEmpty()) {
                $this->forceFill(['estado' => EstadoOrdenCompra::Cancelada])->save();

                return $this;
            }

            // El resumen es el del renglon mas atrasado: un pedido con dos
            // proveedores entregados y uno sin enviar esta a medio camino.
            $estado = match (true) {
                $vivas->every(fn (self $h) => $h->estado === EstadoOrdenCompra::Recibida)
                    => EstadoOrdenCompra::Recibida,

                $vivas->contains(fn (self $h) => $h->unidadesRecibidas() > 0)
                    => EstadoOrdenCompra::RecibidaParcial,

                $vivas->every(fn (self $h) => $h->estado === EstadoOrdenCompra::Enviada)
                    => EstadoOrdenCompra::Enviada,

                default => EstadoOrdenCompra::Aprobada,
            };

            if ($estado !== $this->estado) {
                $this->forceFill(['estado' => $estado])->save();
            }

            return $this;
        });
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

    /**
     * Los ids de proveedor que aparecen en el detalle.
     *
     * Uno solo significa que el pedido tiene destinatario y se comporta como
     * siempre. Varios, que al aprobarlo se reparte.
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    public function proveedoresDelDetalle(): Collection
    {
        return $this->lineas()
            ->whereNotNull('proveedor_id')
            ->distinct()
            ->pluck('proveedor_id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    /**
     * Deja proveedor_id acorde al detalle: el proveedor si hay uno solo, NULL
     * si hay varios.
     *
     * <b>Es lo que hace que no haya que decidir nada al crear.</b> El
     * formulario acepta la mezcla siempre; la forma del documento sale de lo
     * que termino teniendo adentro. Un pedido que empieza mezclado y al que le
     * sacan renglones hasta dejar un solo proveedor vuelve a ser una orden
     * comun, y el restock vuelve a encontrarlo con borradorAbiertoDe().
     *
     * Solo tiene sentido mientras el detalle se pueda tocar: despues de
     * aprobar, la forma ya esta decidida y las hijas emitidas.
     */
    public function sincronizarProveedor(): static
    {
        $proveedores = $this->proveedoresDelDetalle();

        $this->forceFill([
            'proveedor_id' => $proveedores->count() === 1 ? $proveedores->first() : null,
        ])->save();

        return $this;
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
     *
     * <b>Ojo al sumar total_estimado de varias ordenes:</b> una madre y sus
     * hijas tienen el mismo detalle, asi que un SUM sobre toda la tabla cuenta
     * un pedido repartido dos veces. Cualquier reporte de montos comprados
     * tiene que filtrar con emitidas() o con raiz(), nunca sin filtro.
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
