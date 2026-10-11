<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venta_lineas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas');
            $table->foreignId('producto_id')->constrained('productos');
            // Se congela el nombre del producto al momento de la venta
            $table->string('descripcion', 150);
            $table->integer('cantidad');
            $table->integer('cantidad_devuelta')->default(0);
            $table->decimal('precio_unitario', 12, 2);
            $table->decimal('descuento_promocion', 12, 2)->default(0);
            $table->decimal('alicuota_iva', 4, 2);
            // Costo congelado: permite calcular el margen historico
            $table->decimal('costo_unitario', 12, 2)->nullable();
            $table->decimal('neto', 12, 2);
            $table->decimal('iva', 12, 2);
            $table->decimal('total', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venta_lineas');
    }
};
