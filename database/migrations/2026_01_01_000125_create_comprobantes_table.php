<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comprobantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas');
            $table->foreignId('tipo_comprobante_id')->constrained('tipos_comprobante');
            $table->foreignId('punto_venta_id')->constrained('puntos_venta');
            // Correlativo por tipo + punto de venta, lo lleva ARCA
            $table->integer('numero');
            // Factura de origen de una nota de credito/debito
            $table->foreignId('comprobante_asociado_id')->nullable()->constrained('comprobantes');

            // Datos del receptor congelados al momento de emitir
            $table->foreignId('cliente_id')->nullable()->constrained('clientes');
            $table->foreignId('tipo_documento_id')->constrained('tipos_documento');
            $table->string('nro_doc', 20);
            $table->foreignId('condicion_iva_id')->constrained('condiciones_iva');

            $table->dateTime('fecha_emision');
            $table->decimal('neto', 12, 2);
            $table->decimal('iva', 12, 2);
            $table->decimal('total', 12, 2);

            $table->string('cae', 14)->nullable();
            $table->date('cae_vencimiento')->nullable();
            $table->enum('estado', ['pendiente', 'autorizado', 'rechazado'])->default('pendiente');
            // Motivo de la nota de credito o del rechazo
            $table->string('motivo', 255)->nullable();
            $table->timestamps();

            $table->unique(
                ['tipo_comprobante_id', 'punto_venta_id', 'numero'],
                'comprobantes_correlativo_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comprobantes');
    }
};
