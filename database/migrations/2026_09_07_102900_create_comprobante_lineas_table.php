<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * I.2 comprobante_lineas — no se reutiliza venta_lineas porque una nota de
 * credito parcial tiene lineas propias que no coinciden con las de la venta, y
 * porque el comprobante queda congelado con exactamente los importes que se
 * informaron al organismo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comprobante_lineas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comprobante_id')->constrained('comprobantes')->restrictOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->string('descripcion', 150);
            $table->unsignedInteger('cantidad');
            $table->decimal('precio_unitario', 12, 2);
            $table->decimal('alicuota_iva', 4, 2);
            $table->decimal('neto', 12, 2);
            $table->decimal('iva', 12, 2);
            $table->decimal('total', 12, 2);

            $table->index('producto_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comprobante_lineas');
    }
};
