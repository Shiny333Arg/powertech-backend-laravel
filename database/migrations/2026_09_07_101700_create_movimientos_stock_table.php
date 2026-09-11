<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * D.1 movimientos_stock — fuente de verdad para auditar y reconstruir el stock.
 * Tabla APPEND-ONLY: sin updated_at y sin borrado. Un error se corrige con un
 * movimiento inverso, nunca editando el original.
 *
 * Dos columnas de delta y no una, porque la ENTREGA mueve los dos valores en el
 * mismo instante (-fisico, -comprometido). Con una sola columna habria que
 * partir el evento en dos filas y confiar en que siempre se escriban juntas.
 *
 * Deltas por tipo (cantidad_fisico / cantidad_comprometido):
 *   inicial    +q /  0     compra      +q /  0
 *   reserva     0 / +q     entrega     -q / -q
 *   liberacion  0 / -q     devolucion  +q /  0
 *   ajuste     +-q /  0
 *
 * NO existe un movimiento de tipo `pago`: acreditar solo emite el comprobante.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();

            $table->enum('tipo', [
                'inicial', 'compra', 'reserva',
                'entrega', 'liberacion', 'devolucion', 'ajuste',
            ]);

            // Deltas con signo: un movimiento resta tanto como suma.
            $table->integer('cantidad_fisico')->default(0);
            $table->integer('cantidad_comprometido')->default(0);

            // Saldo posterior al movimiento. UNSIGNED como las columnas que
            // reflejan: el historico hereda el mismo invariante.
            $table->unsignedInteger('stock_fisico_resultante');
            $table->unsignedInteger('stock_comprometido_resultante');

            // Documento que causo el movimiento:
            // ventas | ordenes_compra | comprobantes | ajuste manual (null).
            $table->nullableMorphs('origen');

            $table->foreignId('usuario_id')->nullable()->constrained('users')->restrictOnDelete();
            // Obligatorio para `ajuste`; en el resto lo explica el documento origen.
            $table->string('motivo', 255)->nullable();

            // Solo created_at: append-only.
            $table->timestamp('created_at')->useCurrent();

            // Reconstruccion del saldo y analisis de consumo.
            $table->index(['producto_id', 'created_at']);
            $table->index(['tipo', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_stock');
    }
};
