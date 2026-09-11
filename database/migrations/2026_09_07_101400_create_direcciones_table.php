<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A.7 direcciones — polimorfica: empleados | clientes | proveedores.
 * Repetir nueve columnas en tres tablas obliga a modificar las tres ante
 * cualquier cambio, y no soporta al cliente con varias direcciones de envio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('direcciones', function (Blueprint $table) {
            $table->id();
            $table->morphs('direccionable'); // direccionable_type + direccionable_id + indice
            $table->enum('tipo', ['fiscal', 'envio', 'particular']);
            $table->string('calle', 120);
            $table->string('numero', 10)->nullable();
            $table->string('piso_depto', 20)->nullable();
            $table->string('codigo_postal', 10);
            $table->string('localidad', 80);
            $table->string('provincia', 60);
            $table->boolean('es_predeterminada')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('direcciones');
    }
};
