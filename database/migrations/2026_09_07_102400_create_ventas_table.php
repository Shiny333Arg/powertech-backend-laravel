<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * H.1 ventas — documento comercial. Mostrador y online comparten tabla y se
 * diferencian por `canal`.
 *
 * IMPACTO EN STOCK POR TRANSICION
 *   alta presupuesto        sin efecto
 *   -> confirmada           mov. reserva     (+comprometido, haya stock o no)
 *   -> pagada               SIN EFECTO. Solo emite el comprobante
 *   -> pendiente_stock      sin efecto (preventa facturada, sigue comprometida)
 *   -> despachada / entregada  mov. entrega  (-fisico, -comprometido)
 *   -> cancelada / vencida  mov. liberacion  (-comprometido)
 *   -> devuelta(_parcial)   mov. devolucion  (+fisico), desde la nota de credito
 *
 * El cobro no mueve stock. El fisico baja cuando la mercaderia sale del
 * deposito. Al acreditarse el pago: hay fisico -> en_preparacion,
 * no hay -> pendiente_stock.
 *
 * LA VENTA NO SE ELIMINA — no lleva `deleted_at`, y la omision es la decision.
 * Su fin de vida ya esta modelado como estado: cancelada, vencida,
 * devuelta_parcial o devuelta, y cada uno dice algo distinto que un
 * `deleted_at` no diria. Tener la columna seria peor que no tenerla: un
 * $venta->delete() distraido haria desaparecer de las pantallas un documento
 * facturado, con su comprobante autorizado colgando de una venta que Eloquent
 * ya no devuelve. El borrado fisico lo rechazan las FK RESTRICT de
 * venta_lineas, pagos, comprobantes y envios; el caso que las FK no cubren
 * —una venta recien creada y todavia sin lineas— lo ataja la guarda del
 * evento `deleting` en el modelo Venta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            // CORRELATIVO INTERNO del documento comercial, tomado de la serie
            // 'venta' de `numeradores` dentro de la misma transaccion que
            // inserta la fila (ver Numerador::siguiente).
            //
            // Una sola serie compartida por mostrador y online: son la misma
            // entidad, y dos series obligarian a decir "la venta 1204 de
            // mostrador" cada vez que alguien nombra un numero.
            //
            // Se asigna en el alta, presupuesto incluido, y no cambia nunca:
            // el documento que nace como presupuesto 1204 se valida, se
            // confirma, se cobra y se entrega siendo 1204.
            //
            // NO es la numeracion fiscal. Esa es `comprobantes.numero`,
            // correlativa por (tipo, punto de venta) y autorizada por ARCA.
            $table->unsignedInteger('numero')->unique();
            $table->enum('canal', ['mostrador', 'online']);

            // Nullable: consumidor final anonimo en mostrador.
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->restrictOnDelete();
            // Nullable: en la venta online no hay vendedor.
            $table->foreignId('usuario_id')->nullable()->constrained('users')->restrictOnDelete();

            $table->enum('estado', [
                'presupuesto', 'confirmada', 'pagada',
                'pendiente_stock', 'en_preparacion',
                'despachada', 'lista_retiro', 'entregada',
                'devuelta_parcial', 'devuelta',
                'vencida', 'cancelada',
            ])->default('presupuesto');

            $table->enum('modo_entrega', ['retiro', 'envio'])->default('retiro');

            // Acota la vigencia del precio ofertado; un job marca `vencida`.
            $table->date('validez_hasta')->nullable();
            // Quien habilito el cobro del presupuesto. Sin esto, la validacion
            // existe en el proceso pero no en los datos.
            $table->foreignId('usuario_valido_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->dateTime('fecha_validacion')->nullable();

            $table->decimal('subtotal', 12, 2)->default(0);
            // Unica columna de descuento: se elimino el descuento manual.
            $table->decimal('descuento_promociones', 12, 2)->default(0);
            $table->decimal('costo_envio', 12, 2)->default(0);
            // Se guardan porque la factura los exige discriminados y
            // recalcularlos sobre precios redondeados no siempre reproduce
            // el importe autorizado.
            $table->decimal('neto_gravado', 12, 2)->default(0);
            $table->decimal('total_iva', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            $table->text('observaciones')->nullable();
            $table->dateTime('fecha');
            $table->timestamps();
            // SIN softDeletes(), y es la decision: una venta no se elimina de
            // ninguna forma. Su fin de vida es un estado. Ver el bloque de
            // comentario del encabezado.

            $table->index(['usuario_id', 'fecha']);   // historial del vendedor
            $table->index(['estado', 'fecha']);       // cola de pendiente_stock por antiguedad
            $table->index(['canal', 'fecha']);
            $table->index(['cliente_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
