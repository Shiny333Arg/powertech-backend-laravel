<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')
                ->constrained('productos')
                ->restrictOnDelete();
            $table->enum('tipo', [
                'inicial',
                'compra',
                'reserva',
                'entrega',
                'liberacion',
                'devolucion',
                'ajuste',
            ]);

            // Deltas CON signo: un movimiento resta tanto como suma.
            $table->integer('cantidad_fisico')->default(0);
            $table->integer('cantidad_comprometido')->default(0);

            // Saldo posterior al movimiento. UNSIGNED como las columnas que reflejan:
            // el historico hereda el mismo invariante.
            $table->unsignedInteger('stock_fisico_resultante');
            $table->unsignedInteger('stock_comprometido_resultante');

            // Documento que causo el movimiento: ventas | ordenes_compra |
            // comprobantes | ajuste manual. Sin FK, por polimorfica.
            $table->string('origen_type', 100)->nullable();
            $table->unsignedBigInteger('origen_id')->nullable();

            $table->foreignId('usuario_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
            $table->string('motivo', 255)->nullable(); // obligatorio para 'ajuste'

            // append-only: sin updated_at. Un error se corrige con un movimiento inverso.
            $table->timestamp('created_at')->useCurrent();

            $table->index(['producto_id', 'created_at']);
            $table->index(['origen_type', 'origen_id'], 'movimientos_stock_origen_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_stock');
    }
};
