<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * H.2 venta_lineas — unidad sobre la que se calcula el compromiso de stock:
 * cada linea genera su movimiento de `reserva` al confirmarse y de `entrega`
 * al salir la mercaderia. Alcanza con que un solo producto falte para que toda
 * la venta quede en `pendiente_stock`.
 *
 * Se congelan descripcion, precio y alicuota: si manana el producto se renombra
 * o cambia su alicuota, el documento emitido debe seguir mostrando lo emitido.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venta_lineas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->restrictOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();

            $table->string('descripcion', 150);
            $table->unsignedInteger('cantidad');
            // Habilita la devolucion parcial y acota cuantas unidades puede
            // reingresar al fisico una nota de credito.
            $table->unsignedInteger('cantidad_devuelta')->default(0);

            $table->decimal('precio_unitario', 12, 2);
            $table->decimal('descuento_promocion', 12, 2)->default(0);
            $table->decimal('alicuota_iva', 4, 2);
            // Congelado: permite el margen real aunque el costo cambie despues.
            $table->decimal('costo_unitario', 12, 2)->nullable();
            $table->decimal('neto', 12, 2);
            $table->decimal('iva', 12, 2);
            $table->decimal('total', 12, 2);

            $table->index('producto_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venta_lineas');
    }
};
