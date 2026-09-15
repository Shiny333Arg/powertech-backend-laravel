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
            $table->foreignId('venta_id')
                ->constrained('ventas')
                ->restrictOnDelete();
            $table->foreignId('tipo_comprobante_id')
                ->constrained('tipos_comprobante')
                ->restrictOnDelete();
            $table->foreignId('punto_venta_id')
                ->constrained('puntos_venta')
                ->restrictOnDelete();

            // Correlativo FISCAL, por (tipo, punto de venta). Lo autoriza ARCA.
            $table->integer('numero');

            $table->foreignId('comprobante_asociado_id') // nota de credito -> factura
                ->nullable()
                ->constrained('comprobantes')
                ->restrictOnDelete();

            $table->foreignId('cliente_id')
                ->nullable()
                ->constrained('clientes')
                ->restrictOnDelete();

            // Datos fiscales copiados al emitir: no cambian si el cliente edita su ficha.
            $table->foreignId('tipo_documento_id')
                ->constrained('tipos_documento')
                ->restrictOnDelete();
            $table->string('nro_doc', 20);
            $table->foreignId('condicion_iva_id')
                ->constrained('condiciones_iva')
                ->restrictOnDelete();

            $table->dateTime('fecha_emision');
            $table->decimal('neto', 12, 2);
            $table->decimal('iva', 12, 2);
            $table->decimal('total', 12, 2);

            $table->string('cae', 14)->nullable();
            $table->date('cae_vencimiento')->nullable();
            $table->enum('estado', ['pendiente', 'autorizado', 'rechazado'])->default('pendiente');
            $table->string('motivo', 255)->nullable();
            $table->timestamps();

            // Unicidad correlativa que exige la normativa, hecha cumplir por el motor.
            $table->unique(['tipo_comprobante_id', 'punto_venta_id', 'numero'], 'comprobantes_correlativo_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comprobantes');
    }
};
