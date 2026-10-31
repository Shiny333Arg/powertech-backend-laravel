<?php

namespace App\Services;

use App\Enums\CanalPedido;
use App\Enums\EstadoOrdenCompra;
use App\Enums\OrigenOrdenCompra;
use App\Events\OrdenCompraCancelada;
use App\Exceptions\TransicionNoPermitidaException;
use App\Mail\RemitoDePedido;
use App\Models\Numerador;
use App\Models\OrdenCompra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

/**
 * Sabe COMO se arma, se aprueba y se envia una orden de compra. No decide
 * cuando: eso es ReposicionService para las automaticas y el administrativo
 * para las manuales.
 *
 * <b>Un pedido puede mezclar proveedores; una orden no.</b> El formulario deja
 * cargar lo que haga falta sin pensar a quien se le compra. Lo que decide la
 * forma del documento es el detalle, no una eleccion previa: si todas las
 * lineas son del mismo proveedor, la orden tiene destinatario y se comporta
 * como siempre; si hay varios, proveedor_id queda en NULL y al aprobarla se
 * emite una orden hija por proveedor. Las hijas son las que se envian y se
 * reciben; la madre es el pedido que el administrativo cargo y aprobo.
 *
 * El reparto pasa al APROBAR y no al guardar porque hasta ahi el detalle se
 * sigue tocando: repartir antes obligaria a rehacer las hijas en cada edicion,
 * y una orden que se rehace no es un documento.
 *
 * <b>Canal de pedido: solo email.</b> El pedido se le hace llegar al proveedor
 * como un remito adjunto en un correo. No hay integracion con portales ni
 * registro de pedidos telefonicos: si el proveedor no tiene canal 'email' y
 * una casilla cargada, la orden se aprueba pero no se puede enviar, y el
 * sistema lo dice en vez de marcarla enviada sin que haya salido nada.
 *
 * <b>Reglas que hace cumplir</b>
 *  - El numero sale de la serie 'orden_compra' de numeradores, con la fila
 *    bloqueada y DENTRO de la transaccion que inserta la orden. Un ROLLBACK
 *    devuelve el numero. Las hijas NO consumen la serie: se numeran como
 *    OC-0000042/1, /2, /3, porque son el reparto de un pedido y no pedidos
 *    distintos, y asi la serie sigue contando documentos cargados.
 *  - Las automaticas y las manuales comparten todo salvo el campo 'origen':
 *    distinguirlas es la unica forma de medir si los umbrales estan calibrados.
 *  - El detalle se modifica solo en borrador. Aprobada o enviada, el documento
 *    esta cerrado: es lo que alguien autorizo y lo que el proveedor recibio.
 *  - Una orden no se elimina. Su fin de vida es 'cancelada' o 'recibida', igual
 *    que una venta.
 *  - total_estimado se recalcula siempre desde las lineas, nunca se acumula a
 *    mano: un total que se suma incrementalmente se desincroniza en la primera
 *    edicion.
 */
class OrdenCompraService
{
    private const SERIE = 'orden_compra';

    // ------------------------------------------------------------------
    // Alta automatica
    // ------------------------------------------------------------------

    /**
     * Deja pedido un producto: lo agrega al borrador abierto del proveedor, o
     * abre uno nuevo si no hay.
     *
     * <b>Por que agrupar por proveedor.</b> Cuando entra una venta grande,
     * ocho productos del mismo proveedor cruzan el umbral en el mismo segundo.
     * Una orden por producto le deja al administrativo ocho documentos para
     * aprobar y al proveedor ocho correos por el mismo envio. Agrupar en el
     * borrador que ya existe convierte eso en un pedido con ocho renglones.
     *
     * Solo se agrupa contra un BORRADOR: una orden ya aprobada o enviada es un
     * documento cerrado y meterle un renglon cambiaria lo que el administrativo
     * aprobo y lo que el proveedor recibio.
     *
     * Lo que genera el automatico es siempre mono-proveedor: entra por aca con
     * un producto, y borradorAbiertoDe() solo encuentra ordenes que ya tienen
     * destinatario. Un pedido mezclado que el administrativo este armando a
     * mano tiene proveedor_id NULL y por eso nunca se le agrega nada de forma
     * automatica, que es lo correcto: es un documento que alguien esta
     * escribiendo.
     */
    public function pedir(Producto $producto, int $cantidad, OrigenOrdenCompra $origen = OrigenOrdenCompra::Automatica): OrdenCompra
    {
        $proveedor = $producto->proveedor;

        if ($proveedor === null) {
            throw new RuntimeException(
                "«{$producto->nombre}» tiene reposición automática pero no tiene proveedor asignado."
            );
        }

        // Lock por PROVEEDOR, no por producto: el que ReposicionService ya tomo
        // impide dos evaluaciones del mismo producto, pero no dos productos
        // distintos del mismo proveedor buscando su borrador a la vez. Sin
        // este, los dos encuentran "no hay borrador" y abren uno cada uno.
        $lock = Cache::lock("orden-compra:proveedor:{$proveedor->getKey()}", 30);

        return $lock->block(10, function () use ($producto, $proveedor, $cantidad, $origen) {
            return DB::transaction(function () use ($producto, $proveedor, $cantidad, $origen) {
                $orden = $this->borradorAbiertoDe($proveedor)
                    ?? $this->nuevoBorrador($proveedor, $origen);

                $this->agregarLinea($orden, $producto, $cantidad);

                return $orden->fresh('lineas.producto');
            });
        });
    }

    /**
     * Sube la cantidad pedida de un producto en un borrador, hasta cubrir el
     * faltante de ahora.
     *
     * <b>Por que hace falta.</b> Existir un pedido no significa que alcance. El
     * faltante crece con cada venta, y entre la primera que cruzo el umbral y
     * el momento en que alguien aprueba pueden pasar horas y varias ventas mas.
     * Sin esto, el pedido queda congelado en el faltante del primer minuto: la
     * mercaderia llega, el disponible sigue negativo y las preventas que
     * esperaban esa entrega no se destraban.
     *
     * <b>Solo sube.</b> Si el administrativo puso una cantidad mayor a mano es
     * una decision suya y el automatico no la pisa. Y si el faltante se redujo
     * —una devolucion, una venta cancelada— recortar el pedido seria discutirle
     * al que ya lo reviso.
     *
     * Devuelve la orden si cambio algo, o null si ya cubria el faltante. Ese
     * null es lo que mantiene idempotente al barrido.
     */
    public function ampliar(OrdenCompra $orden, Producto $producto, int $cantidad): ?OrdenCompra
    {
        if (! $orden->esBorrador()) {
            throw TransicionNoPermitidaException::porque(
                "El pedido {$orden->numero} ya no es un borrador: su detalle no se modifica."
            );
        }

        // Mismo lock que pedir(), y por el mismo motivo: dos productos del
        // mismo proveedor pueden estar tocando este borrador a la vez. Una
        // madre no llega aca —no la encuentra borradorAbiertoDe()— asi que la
        // clave nunca es nula.
        $lock = Cache::lock("orden-compra:proveedor:{$orden->proveedor_id}", 30);

        return $lock->block(10, function () use ($orden, $producto, $cantidad) {
            return DB::transaction(function () use ($orden, $producto, $cantidad) {
                $linea = $orden->lineaDe($producto->getKey());

                // El producto esta en otra orden abierta, no en esta. No
                // deberia pasar —ReposicionService busca la orden que lo
                // contiene— pero no es motivo para inventarle un renglon a un
                // pedido que no lo tenia.
                if ($linea === null) {
                    return null;
                }

                if ($cantidad <= $linea->cantidad_pedida) {
                    return null;
                }

                $linea->update(['cantidad_pedida' => $cantidad]);

                $orden->recalcularTotalEstimado();

                return $orden->fresh('lineas.producto');
            });
        });
    }

    /**
     * El borrador vigente del proveedor, si lo hay. Solo uno a la vez.
     *
     * emitidas() es redundante con el where de proveedor_id, pero deja dicho lo
     * que importa: un pedido mezclado que alguien esta armando no es candidato
     * a que el automatico le agregue renglones.
     */
    public function borradorAbiertoDe(Proveedor $proveedor): ?OrdenCompra
    {
        return OrdenCompra::query()
            ->where('proveedor_id', $proveedor->getKey())
            ->where('estado', EstadoOrdenCompra::Borrador->value)
            ->lockForUpdate()
            ->latest('id')
            ->first();
    }

    // ------------------------------------------------------------------
    // Alta manual
    // ------------------------------------------------------------------

    /**
     * Alta manual desde la pantalla de compras.
     *
     * No recibe proveedor: el destinatario sale del detalle. Con un solo
     * proveedor queda seteado y el pedido es una orden comun; con varios queda
     * en NULL y se reparte al aprobarse.
     *
     * @param  list<array{producto_id:int, cantidad_pedida:int, costo_unitario?:float|string|null}>  $lineas
     */
    public function crear(array $lineas, User $usuario, ?string $observaciones = null): OrdenCompra
    {
        return DB::transaction(function () use ($lineas, $usuario, $observaciones) {
            $orden = $this->nuevoBorrador(null, OrigenOrdenCompra::Manual, $usuario, $observaciones);

            $this->reemplazarLineas($orden, $lineas);

            return $orden->fresh('lineas.producto');
        });
    }

    /**
     * Edicion del detalle. Solo en borrador.
     *
     * Una orden aprobada ya paso por la decision del administrativo y una
     * enviada ya esta en manos del proveedor: cambiarles el detalle seria
     * reescribir lo que otro leyo. Lo que se hace en ese caso es cancelarla y
     * emitir otra.
     *
     * El proveedor puede cambiar acá, y no es una excepcion a la regla vieja de
     * «el destinatario no se cambia»: lo que cambia es el detalle, y el
     * destinatario es una consecuencia. Sacarle a un pedido de dos proveedores
     * todos los renglones de uno lo convierte en una orden comun dirigida al
     * otro, que es exactamente lo que quedo pedido.
     *
     * @param  list<array{producto_id:int, cantidad_pedida:int, costo_unitario?:float|string|null}>  $lineas
     */
    public function actualizar(OrdenCompra $orden, array $lineas, ?string $observaciones = null): OrdenCompra
    {
        if (! $orden->esEditable()) {
            throw TransicionNoPermitidaException::porque(
                'Solo se puede editar el detalle de una orden de compra en borrador.'
            );
        }

        return DB::transaction(function () use ($orden, $lineas, $observaciones) {
            $orden->lineas()->delete();

            $this->reemplazarLineas($orden, $lineas);

            if ($observaciones !== null) {
                $orden->observaciones = $observaciones;
                $orden->save();
            }

            return $orden->fresh('lineas.producto');
        });
    }

    // ------------------------------------------------------------------
    // Transiciones
    // ------------------------------------------------------------------

    /**
     * Aprobacion del administrativo. Es un requerimiento explicito: ninguna
     * orden sale sin que una persona la haya mirado.
     *
     * Deja registro de quien y cuando, que es el punto de pedirla.
     *
     * Aprobar es tambien lo que CONGELA las cantidades: a partir de acá la
     * reposicion automatica ya no las amplia, porque el documento paso a ser
     * lo que esta persona autorizo.
     *
     * <b>Y es donde se reparte.</b> Un pedido mezclado emite acá una orden por
     * proveedor, cada una ya aprobada: la decision que el requerimiento pide
     * que quede registrada es esta, y pedirle al administrativo que vuelva a
     * aprobar tres documentos que acaba de autorizar seria pedirle la misma
     * firma cuatro veces.
     *
     * <b>Y es donde se limpia lo que este pedido deja sin sentido.</b> Ver
     * anularBorradoresSuperados().
     *
     * Devuelve un array y no la orden sola porque la pantalla tiene dos cosas
     * que contar: que se aprobo, y que borradores se dieron de baja en el mismo
     * acto. Mismo criterio que enviarTodas(). La orden que se recibio queda
     * actualizada en memoria, asi que el llamador la sigue teniendo.
     *
     * @return array{orden: OrdenCompra, anulados: list<string>}
     */
    public function aprobar(OrdenCompra $orden, User $usuario): array
    {
        $this->exigirTransicion($orden, EstadoOrdenCompra::Aprobada);

        if ($orden->lineas()->count() === 0) {
            throw TransicionNoPermitidaException::porque(
                'La orden no tiene renglones: no hay nada que aprobar.'
            );
        }

        return DB::transaction(function () use ($orden, $usuario) {
            $hijas = $orden->esMadre()
                ? $this->repartirPorProveedor($orden, $usuario)
                : collect();

            $orden->fill([
                'estado'            => EstadoOrdenCompra::Aprobada,
                'usuario_aprobo_id' => $usuario->id,
                'fecha_aprobacion'  => now(),
                // En una madre la entrega es la del proveedor mas lento: el
                // pedido esta completo cuando llego el ultimo.
                'fecha_entrega_estimada' => $orden->esMadre()
                    ? $hijas->pluck('fecha_entrega_estimada')->filter()->max()
                    : ($orden->fecha_entrega_estimada ?? $this->entregaEstimada($orden->proveedor)),
            ])->save();

            // DESPUES del save, y el orden no es casual: anular dispara
            // ReponerPorCancelacion, que vuelve a evaluar cada producto. Si
            // esta orden todavia no figurara como aprobada, esa evaluacion no
            // encontraria ninguna orden abierta cubriendo el faltante y
            // recrearia el borrador que acabamos de dar de baja.
            $anulados = $this->anularBorradoresSuperados($orden, $usuario);

            return ['orden' => $orden, 'anulados' => $anulados];
        });
    }

    /**
     * Da de baja los borradores automaticos que este pedido deja sin sentido.
     *
     * <b>El problema que resuelve.</b> El restock arma borradores solo. Si el
     * administrativo arma a mano un pedido que cubre esos mismos productos —que
     * es lo que hace el boton de sugerencias del alta— los borradores quedan
     * ahi, pidiendo algo que ya se pidio. Nadie los va a aprobar, nadie los va
     * a cancelar, y el listado se llena de documentos que no significan nada.
     * Peor: si alguien los aprueba por error, el proveedor recibe dos pedidos
     * por el mismo faltante.
     *
     * <b>Solo automaticas, y solo si quedan COMPLETAMENTE cubiertas.</b> Un
     * borrador manual es alguien escribiendo y no se toca. Y un borrador de
     * ocho renglones del que este pedido cubre cinco no se anula: los tres que
     * quedan no los reemplazo nadie, y tirarlos seria perder pedidos legitimos.
     * La unidad que se superpone es el renglon; el documento se da de baja solo
     * cuando ya no le queda nada propio.
     *
     * <b>La cantidad no se compara.</b> Alcanza con que el producto este en el
     * pedido aprobado. Si la cantidad quedo corta, lo dice
     * OrdenCompraLinea::cubreElFaltante() sobre la orden que sobrevive, que es
     * el mecanismo que ya existe para eso; chequearlo tambien acá seria tener
     * dos definiciones de lo mismo.
     *
     * <b>Se le atribuye al que aprobo</b>, a diferencia del alta automatica,
     * donde usuario_creo_id queda en null. No es inconsistencia: nadie decidio
     * crear esos borradores, pero esta persona si decidio aprobar un pedido que
     * los reemplaza. La baja es consecuencia directa de su firma.
     *
     * @return list<string> los numeros de los pedidos que se dieron de baja
     */
    private function anularBorradoresSuperados(OrdenCompra $aprobada, User $usuario): array
    {
        $cubiertos = $aprobada->lineas()
            ->pluck('producto_id')
            ->map(fn ($id) => (int) $id);

        if ($cubiertos->isEmpty()) {
            return [];
        }

        $borradores = OrdenCompra::query()
            ->automaticas()
            ->where('estado', EstadoOrdenCompra::Borrador->value)
            ->whereKeyNot($aprobada->getKey())
            ->whereHas('lineas', fn (Builder $linea) => $linea->whereIn('producto_id', $cubiertos))
            ->with('lineas:id,orden_compra_id,producto_id')
            ->get();

        $anulados = [];

        foreach ($borradores as $borrador) {
            $propios = $borrador->lineas
                ->pluck('producto_id')
                ->map(fn ($id) => (int) $id);

            // Le queda algo que este pedido no cubre: sigue en pie.
            if ($propios->diff($cubiertos)->isNotEmpty()) {
                continue;
            }

            $this->cancelar(
                $borrador,
                $usuario,
                "su contenido quedó cubierto por el pedido {$aprobada->numero}",
            );

            $anulados[] = $borrador->numero;
        }

        return $anulados;
    }

    /**
     * Envia el remito al proveedor por correo y marca la orden como enviada.
     *
     * <b>El correo se manda ANTES de cambiar el estado, y de forma sincronica.</b>
     * Encolarlo dejaria la orden en 'enviada' con el mail todavia sin salir, y
     * si el envio despues falla nadie se entera: el administrativo ve una orden
     * enviada que el proveedor nunca recibio. Si el correo no sale, esto
     * explota y la orden se queda en 'aprobada', que es la verdad.
     *
     * El riesgo espejo —el correo sale y el UPDATE falla— existe, pero deja la
     * orden reenviable, que es el lado seguro del error.
     *
     * Una madre no se envia: no tiene a quien. Lo que se manda son sus hijas,
     * una por una, con enviarTodas().
     */
    public function enviar(OrdenCompra $orden, User $usuario): OrdenCompra
    {
        if ($orden->esMadre()) {
            throw TransicionNoPermitidaException::porque(
                "El pedido {$orden->numero} va a varios proveedores: se envía la orden de cada uno, no el pedido entero."
            );
        }

        $this->exigirTransicion($orden, EstadoOrdenCompra::Enviada);

        $orden->loadMissing(['proveedor', 'lineas.producto']);

        $this->exigirCanalEmail($orden->proveedor);

        Mail::to($orden->proveedor->email)->send(new RemitoDePedido($orden));

        $orden->fill([
            'estado'      => EstadoOrdenCompra::Enviada,
            'fecha_envio' => now(),
        ])->save();

        $orden->padre?->sincronizarDesdeHijas();

        return $orden;
    }

    /**
     * Manda el remito de cada hija que todavia no salio.
     *
     * <b>Un proveedor por vez, y el fallo de uno no arrastra a los otros.</b>
     * Es el motivo por el que el reparto existe: con un solo documento para
     * tres destinatarios, un proveedor sin casilla cargada dejaria el pedido
     * entero sin enviar, y reintentar le mandaria el remito dos veces a los
     * que si lo recibieron. Acá cada orden lleva su propio estado, asi que
     * reintentar solo alcanza a las que fallaron.
     *
     * No tira excepcion: devuelve que salio y que no, porque lo tipico es un
     * proveedor mal configurado y eso se arregla en su ficha, no reintentando.
     *
     * @return array{enviadas: \Illuminate\Support\Collection<int, OrdenCompra>, fallidas: array<string, string>}
     */
    public function enviarTodas(OrdenCompra $madre, User $usuario): array
    {
        $enviadas = collect();
        $fallidas = [];

        $pendientes = $madre->hijas()
            ->with('proveedor')
            ->where('estado', EstadoOrdenCompra::Aprobada->value)
            ->get();

        foreach ($pendientes as $hija) {
            try {
                $enviadas->push($this->enviar($hija, $usuario));
            } catch (TransicionNoPermitidaException $error) {
                $fallidas[$hija->proveedor->razon_social] = $error->getMessage();
            } catch (\Throwable $error) {
                report($error);

                $fallidas[$hija->proveedor->razon_social] =
                    'No se pudo enviar el correo. El pedido sigue aprobado y se puede reintentar.';
            }
        }

        $madre->sincronizarDesdeHijas();

        return ['enviadas' => $enviadas, 'fallidas' => $fallidas];
    }

    /**
     * Cancela la orden. No la borra: la orden no se elimina nunca.
     *
     * Cancelar es tambien lo que <b>vuelve a habilitar la reposicion</b> de sus
     * productos: mientras la orden este abierta, ReposicionService los da por
     * pedidos y no genera nada. En cuanto pasa a cancelada, el faltante vuelve
     * a estar descubierto, y el evento que se emite acá lo hace pedir en el
     * acto por la cantidad que haga falta en ese momento.
     *
     * <b>El evento no es un lujo.</b> Cancelar no mueve stock —la mercaderia
     * nunca llego— asi que el listener reactivo no se entera, y el barrido
     * recien pasa a las 7 de la manana. Sin el aviso, un producto en preventa
     * con clientes que ya pagaron se queda esperando toda la noche sin que nada
     * lo pida.
     *
     * Cancelar una madre cancela a sus hijas: el pedido completo se da de baja.
     * Cancelar una hija sola es legitimo —un proveedor que avisa que no tiene
     * stock— y deja al resto del pedido en pie.
     */
    public function cancelar(OrdenCompra $orden, User $usuario, string $motivo): OrdenCompra
    {
        $this->exigirTransicion($orden, EstadoOrdenCompra::Cancelada);

        return DB::transaction(function () use ($orden, $usuario, $motivo) {
            if ($orden->esMadre()) {
                foreach ($orden->hijas as $hija) {
                    if ($hija->estado->puedeTransicionarA(EstadoOrdenCompra::Cancelada)) {
                        $this->cancelar($hija, $usuario, "Se canceló el pedido {$orden->numero}: {$motivo}");
                    }
                }
            }

            $orden->fill([
                'estado'        => EstadoOrdenCompra::Cancelada,
                'observaciones' => $this->anotar($orden->observaciones, "Cancelada por {$usuario->usuario}: {$motivo}"),
            ])->save();

            // Por evento y no llamando a ReposicionService: ese servicio ya depende
            // de este —le pide que arme los pedidos— y al revés seria una
            // dependencia circular. El mismo patron que MovimientoStockRegistrado.
            //
            // Una madre ya repartida no lo emite: sus hijas lo hicieron una por
            // una y el mismo faltante no se pide dos veces.
            if (! $orden->esMadre() || $orden->hijas()->doesntExist()) {
                OrdenCompraCancelada::dispatch($orden);
            }

            $orden->padre?->sincronizarDesdeHijas();

            return $orden;
        });
    }

    // ------------------------------------------------------------------
    // Reparto
    // ------------------------------------------------------------------

    /**
     * Emite una orden aprobada por cada proveedor que aparece en el detalle.
     *
     * Las hijas copian las lineas en vez de llevarselas: la madre tiene que
     * seguir mostrando lo que se pidio, y sobre todo tiene que seguir contando
     * para el restock mientras esta en borrador, que es cuando todavia no hay
     * hijas que cubran ese faltante.
     *
     * El numero es derivado —OC-0000042/1— y no sale del numerador: son el
     * reparto de un pedido, no pedidos distintos. Asi la serie sigue contando
     * documentos cargados y el numero dice de donde viene cada orden sin tener
     * que abrirla.
     *
     * @return \Illuminate\Support\Collection<int, OrdenCompra>
     */
    private function repartirPorProveedor(OrdenCompra $madre, User $usuario): Collection
    {
        $madre->loadMissing('lineas');

        $grupos = $madre->lineas->groupBy('proveedor_id');

        if ($grupos->keys()->contains(null) || $grupos->keys()->contains('')) {
            throw TransicionNoPermitidaException::porque(
                'Hay renglones sin proveedor: no hay a quién emitirles la orden. Asignales un proveedor desde la ficha del producto.'
            );
        }

        $proveedores = Proveedor::findMany($grupos->keys()->all())->keyBy('id');

        return $grupos
            ->values()
            ->map(function (Collection $lineas, int $i) use ($madre, $proveedores, $usuario) {
                $proveedor = $proveedores->get((int) $lineas->first()->proveedor_id);

                $hija = OrdenCompra::create([
                    'numero'                 => $madre->numero.'/'.($i + 1),
                    'orden_padre_id'         => $madre->getKey(),
                    'proveedor_id'           => $proveedor->getKey(),
                    'estado'                 => EstadoOrdenCompra::Aprobada,
                    'origen'                 => $madre->origen,
                    'total_estimado'         => 0,
                    'usuario_creo_id'        => $madre->usuario_creo_id,
                    'usuario_aprobo_id'      => $usuario->id,
                    'fecha_aprobacion'       => now(),
                    'fecha_entrega_estimada' => $this->entregaEstimada($proveedor),
                    'observaciones'          => "Emitida desde el pedido {$madre->numero}.",
                ]);

                foreach ($lineas as $linea) {
                    $hija->lineas()->create([
                        'producto_id'     => $linea->producto_id,
                        'proveedor_id'    => $linea->proveedor_id,
                        'cantidad_pedida' => $linea->cantidad_pedida,
                        'costo_unitario'  => $linea->costo_unitario,
                    ]);
                }

                return $hija->recalcularTotalEstimado();
            });
    }

    // ------------------------------------------------------------------
    // Construccion
    // ------------------------------------------------------------------

    private function nuevoBorrador(
        ?Proveedor $proveedor,
        OrigenOrdenCompra $origen,
        ?User $usuario = null,
        ?string $observaciones = null,
    ): OrdenCompra {
        // usuario_creo_id queda en null en las automaticas: no las creo nadie,
        // las creo el sistema. Falsear el usuario del vendedor cuya venta
        // disparo el faltante seria atribuirle una decision que no tomo.
        $usuario ??= $origen === OrigenOrdenCompra::Manual ? auth()->user() : null;

        return OrdenCompra::create([
            'numero'                 => Numerador::proximoFormateado(self::SERIE),
            // NULL en el alta manual: lo escribe sincronizarProveedor() cuando
            // el detalle dice a quien se le compra.
            'proveedor_id'           => $proveedor?->getKey(),
            'estado'                 => EstadoOrdenCompra::Borrador,
            'origen'                 => $origen,
            'total_estimado'         => 0,
            'usuario_creo_id'        => $usuario?->id,
            'fecha_entrega_estimada' => $proveedor ? $this->entregaEstimada($proveedor) : null,
            'observaciones'          => $observaciones ?? ($origen === OrigenOrdenCompra::Automatica
                ? 'Generada automáticamente por reposición de stock.'
                : null),
        ]);
    }

    /**
     * Suma un producto al borrador.
     *
     * La rama que acumula es defensa, no el camino normal: el crecimiento del
     * faltante sobre un producto que ya esta en el pedido lo maneja ampliar().
     * Acá cubre el caso de que alguien llame a pedir() con un producto que el
     * borrador ya tiene, para que el proveedor no reciba un remito con el mismo
     * item en dos renglones.
     */
    public function agregarLinea(OrdenCompra $orden, Producto $producto, int $cantidad): void
    {
        $linea = $orden->lineaDe($producto->getKey());

        if ($linea !== null) {
            $linea->increment('cantidad_pedida', $cantidad);
        } else {
            $orden->lineas()->create([
                'producto_id'     => $producto->getKey(),
                'proveedor_id'    => $producto->proveedor_id,
                'cantidad_pedida' => $cantidad,
                // El costo del catalogo es una ESTIMACION para que el
                // administrativo tenga una referencia al aprobar. El precio
                // real lo fija la factura del proveedor al recibir.
                'costo_unitario'  => $producto->costo ?? 0,
            ]);
        }

        $orden->recalcularTotalEstimado();
        $orden->sincronizarProveedor();
    }

    /** @param  list<array{producto_id:int, cantidad_pedida:int, costo_unitario?:float|string|null}>  $lineas */
    private function reemplazarLineas(OrdenCompra $orden, array $lineas): void
    {
        $productos = Producto::whereIn('id', array_column($lineas, 'producto_id'))->get()->keyBy('id');

        foreach ($lineas as $fila) {
            $producto = $productos->get((int) $fila['producto_id']);

            if ($producto === null) {
                throw new RuntimeException("El producto #{$fila['producto_id']} no existe.");
            }

            $orden->lineas()->create([
                'producto_id' => $producto->id,
                // Snapshot: a quien se le pide ESTE renglon. Hoy sale del
                // producto; cuando un item tenga varios proveedores, de la
                // eleccion del operador, sin que cambie nada mas.
                'proveedor_id'    => $producto->proveedor_id,
                'cantidad_pedida' => (int) $fila['cantidad_pedida'],
                'costo_unitario'  => $fila['costo_unitario'] ?? $producto->costo ?? 0,
            ]);
        }

        $orden->recalcularTotalEstimado();
        $orden->sincronizarProveedor();
    }

    // ------------------------------------------------------------------
    // Auxiliares
    // ------------------------------------------------------------------

    /**
     * El unico canal soportado es el correo. Se verifica acá y no en la vista
     * porque el comando programado tambien puede llegar a enviar.
     */
    private function exigirCanalEmail(Proveedor $proveedor): void
    {
        if ($proveedor->canal_pedido !== CanalPedido::Email) {
            throw TransicionNoPermitidaException::porque(
                "«{$proveedor->razon_social}» tiene canal de pedido «{$proveedor->canal_pedido->etiqueta()}». "
                .'Los pedidos se envían por correo: cambiá el canal del proveedor a email.'
            );
        }

        if (blank($proveedor->email)) {
            throw TransicionNoPermitidaException::porque(
                "«{$proveedor->razon_social}» no tiene correo cargado y el remito se envía por email."
            );
        }
    }

    private function entregaEstimada(Proveedor $proveedor): ?string
    {
        return $proveedor->plazo_entrega_dias
            ? now()->addDays($proveedor->plazo_entrega_dias)->toDateString()
            : null;
    }

    private function exigirTransicion(OrdenCompra $orden, EstadoOrdenCompra $destino): void
    {
        if (! $orden->estado->puedeTransicionarA($destino)) {
            throw TransicionNoPermitidaException::entre($orden->estado, $destino);
        }
    }

    private function anotar(?string $observaciones, string $nota): string
    {
        $sello = now()->format('d/m/Y H:i');

        return trim(($observaciones ?? '')."\n[{$sello}] {$nota}");
    }
}
