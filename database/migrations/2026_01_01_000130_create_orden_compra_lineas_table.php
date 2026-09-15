<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orden_compra_lineas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_compra_id')
                ->constrained('ordenes_compra')
                ->cascadeOnDelete();
            $table->foreignId('producto_id')
                ->constrained('productos')
                ->restrictOnDelete();
            $table->integer('cantidad_pedida');
            $table->integer('cantidad_recibida')->default(0);
            $table->decimal('costo_unitario', 12, 2);
            $table->timestamps();

            // busqueda de ordenes abiertas por producto (antes de crear una automatica)
            $table->index('producto_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orden_compra_lineas');
    }
};
