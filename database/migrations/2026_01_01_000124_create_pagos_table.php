<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')
                ->constrained('ventas')
                ->restrictOnDelete();
            $table->enum('metodo', ['efectivo', 'tarjeta', 'transferencia']);
            $table->decimal('monto', 12, 2);
            $table->decimal('comision', 12, 2)->nullable();
            $table->decimal('neto_acreditado', 12, 2)->nullable();
            $table->tinyInteger('cuotas')->nullable();
            $table->enum('estado', ['pendiente', 'acreditado', 'rechazado', 'devuelto'])
                ->default('pendiente');

            // Mercado Pago Checkout Pro
            $table->string('mp_preference_id', 60)->nullable(); // NO unico: varios intentos por preferencia
            $table->string('mp_payment_id', 30)->nullable()->unique(); // idempotencia del webhook
            $table->string('mp_merchant_order_id', 30)->nullable();
            $table->string('mp_status', 30)->nullable();
            $table->string('mp_status_detail', 60)->nullable();
            $table->string('mp_payment_type', 30)->nullable();

            $table->foreignId('usuario_id')  // null en la venta online: no hay cajero
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
            $table->dateTime('fecha');
            $table->timestamps();

            $table->index(['usuario_id', 'fecha']); // historial de cobros del cajero
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
