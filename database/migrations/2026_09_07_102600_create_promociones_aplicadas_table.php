<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * F.3 promociones_aplicadas — deja registrado POR QUE una linea salio mas barata.
 * Sirve para reconstruir el precio ante un reclamo y para medir el rendimiento
 * real de cada promocion. Sin esta tabla el descuento es un numero anonimo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promociones_aplicadas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->restrictOnDelete();
            $table->foreignId('venta_linea_id')->constrained('venta_lineas')->restrictOnDelete();
            $table->foreignId('promocion_id')->constrained('promociones')->restrictOnDelete();
            $table->decimal('importe_descuento', 12, 2);
            $table->timestamp('created_at')->useCurrent();

            // Rendimiento por promocion.
            $table->index(['promocion_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promociones_aplicadas');
    }
};
