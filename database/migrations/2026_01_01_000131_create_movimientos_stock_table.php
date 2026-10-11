<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Libro mayor de stock: append-only, nunca se edita ni se borra.
        // El stock de productos es siempre el resultante del ultimo movimiento.
        Schema::create('movimientos_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos');
            $table->enum('tipo', [
                'inicial',
                'compra',
                'reserva',
                'entrega',
                'liberacion',
                'devolucion',
                'ajuste',
            ]);
            // Deltas con signo: negativos en entregas y liberaciones
            $table->integer('cantidad_fisico')->default(0);
            $table->integer('cantidad_comprometido')->default(0);
            // Saldos despues de aplicar el movimiento
            $table->unsignedInteger('stock_fisico_resultante');
            $table->unsignedInteger('stock_comprometido_resultante');

            // Polimorfica: Venta, OrdenCompra o Comprobante que lo origino
            $table->string('origen_type', 100)->nullable();
            $table->unsignedBigInteger('origen_id')->nullable();

            $table->foreignId('usuario_id')->nullable()->constrained('users');
            $table->string('motivo', 255)->nullable();
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
