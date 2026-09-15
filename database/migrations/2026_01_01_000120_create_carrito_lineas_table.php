<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carrito_lineas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrito_id')
                ->constrained('carritos')
                ->cascadeOnDelete(); // el carrito si puede desaparecer fisicamente
            $table->foreignId('producto_id')
                ->constrained('productos')
                ->restrictOnDelete();
            $table->integer('cantidad');
            $table->timestamps();

            // agregar dos veces el mismo producto suma cantidad, no crea otra fila
            $table->unique(['carrito_id', 'producto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carrito_lineas');
    }
};
