<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * I.1 comprobantes — venta y comprobante NO son la misma cosa:
 *   - un presupuesto es una venta sin comprobante;
 *   - una venta puede generar factura y luego nota de credito;
 *   - el CAE, su vencimiento y la numeracion autorizada son del comprobante.
 *
 * El indice unico (tipo, punto de venta, numero) hace cumplir a nivel de motor
 * la unicidad correlativa que exige la normativa.
 *
 * Se emite al acreditarse el pago, NO atado a la salida del stock: en una
 * preventa la factura se emite semanas antes de que llegue la mercaderia.
 * Inmutable una vez con CAE: toda correccion es una nota de credito.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comprobantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->restrictOnDelete();
            $table->foreignId('tipo_comprobante_id')->constrained('tipos_comprobante')->restrictOnDelete();
            $table->foreignId('punto_venta_id')->constrained('puntos_venta')->restrictOnDelete();
            $table->unsignedInteger('numero');

            // Encadena la nota de credito con su factura de origen.
            $table->foreignId('comprobante_asociado_id')->nullable()
                  ->constrained('comprobantes')->restrictOnDelete();

            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->restrictOnDelete();

            // Se COPIAN del cliente al emitir: son los datos informados al
            // organismo y no pueden cambiar si el cliente actualiza su ficha.
            $table->foreignId('tipo_documento_id')->constrained('tipos_documento')->restrictOnDelete();
            $table->string('nro_doc', 20);
            $table->foreignId('condicion_iva_id')->constrained('condiciones_iva')->restrictOnDelete();

            $table->dateTime('fecha_emision');
            $table->decimal('neto', 12, 2);
            $table->decimal('iva', 12, 2);
            $table->decimal('total', 12, 2);

            $table->string('cae', 14)->nullable();
            $table->date('cae_vencimiento')->nullable();
            $table->enum('estado', ['pendiente', 'autorizado', 'rechazado'])->default('pendiente');
            $table->string('motivo', 255)->nullable();
            $table->timestamps();

            $table->unique(['tipo_comprobante_id', 'punto_venta_id', 'numero'], 'comprobantes_correlativo_unique');
            $table->index(['venta_id', 'estado']);
            $table->index('fecha_emision');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comprobantes');
    }
};
