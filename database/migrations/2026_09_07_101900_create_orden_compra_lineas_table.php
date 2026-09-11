<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * E.3 orden_compra_lineas — `cantidad_recibida` permite recepciones parciales
 * sin necesitar una tabla de recepciones aparte. Cada recepcion impacta como un
 * movimiento_stock de tipo `compra` y suma solo al fisico.
 *
 * La recepcion es tambien el disparador de la preventa: registrada la entrada,
 * el sistema recorre las ventas en `pendiente_stock` de ese producto ordenadas
 * por fecha y pasa a `en_preparacion` las que ya tengan cubiertas sus lineas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orden_compra_lineas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_compra_id')->constrained('ordenes_compra')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->unsignedInteger('cantidad_pedida');
            $table->unsignedInteger('cantidad_recibida')->default(0);
            $table->decimal('costo_unitario', 12, 2);

            // Un producto una sola vez por orden: hace confiable la busqueda de
            // "orden abierta para este producto" del job de reposicion.
            $table->unique(['orden_compra_id', 'producto_id']);
            $table->index('producto_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orden_compra_lineas');
    }
};
