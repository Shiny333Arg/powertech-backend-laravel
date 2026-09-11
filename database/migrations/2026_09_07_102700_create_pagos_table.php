<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * H.3 pagos — un booleano de "cobrada" no dice como, cuando ni en cuantas
 * partes se cobro. Habilita pago mixto, pagos parciales y el arqueo del cajero.
 *
 * mp_payment_id UNICO es lo que da IDEMPOTENCIA: Mercado Pago reenvia la misma
 * notificacion varias veces y sin ese indice se acredita el mismo pago dos veces
 * y se emiten dos comprobantes por la misma venta -> dos numeros fiscales
 * quemados que despues hay que anular con notas de credito.
 *
 * mp_preference_id NO es unico: una preferencia puede generar varios intentos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->restrictOnDelete();
            $table->enum('metodo', ['efectivo', 'tarjeta', 'transferencia']);
            $table->decimal('monto', 12, 2);
            // Lo que cobra el cliente no es lo que entra a la cuenta.
            $table->decimal('comision', 12, 2)->nullable();
            $table->decimal('neto_acreditado', 12, 2)->nullable();
            $table->unsignedTinyInteger('cuotas')->nullable();
            $table->enum('estado', ['pendiente', 'acreditado', 'rechazado', 'devuelto'])
                  ->default('pendiente');

            $table->string('mp_preference_id', 60)->nullable()->index();
            $table->string('mp_payment_id', 30)->nullable()->unique();
            $table->string('mp_merchant_order_id', 30)->nullable();
            $table->string('mp_status', 30)->nullable();
            $table->string('mp_status_detail', 60)->nullable();
            // Medio que MP informa realmente; puede no coincidir con `metodo`.
            $table->string('mp_payment_type', 30)->nullable();

            // Null en la venta online: no hay cajero.
            $table->foreignId('usuario_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->dateTime('fecha');

            $table->index(['usuario_id', 'fecha']);   // historial de cobros del cajero
            $table->index(['venta_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
