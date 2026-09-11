<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * G.2 carrito_lineas — el indice unico evita la linea duplicada del mismo
 * producto: agregar dos veces suma cantidad en lugar de crear otra fila.
 *
 * NO guarda precio: el carrito debe mostrar siempre el precio vigente,
 * incluidas las promociones activas. El precio se congela al convertirse en venta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carrito_lineas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrito_id')->constrained('carritos')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->unsignedInteger('cantidad');

            $table->unique(['carrito_id', 'producto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carrito_lineas');
    }
};
