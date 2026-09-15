<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('direcciones', function (Blueprint $table) {
            $table->id();
            // Polimorfica: empleados | clientes | proveedores. Sin FK, por definicion.
            $table->string('direccionable_type', 100);
            $table->unsignedBigInteger('direccionable_id');
            $table->enum('tipo', ['fiscal', 'envio', 'particular']);
            $table->string('calle', 120);
            $table->string('numero', 10)->nullable();
            $table->string('piso_depto', 20)->nullable();
            $table->string('codigo_postal', 10);
            $table->string('localidad', 80);
            $table->string('provincia', 60);
            $table->boolean('es_predeterminada')->default(false);
            $table->timestamps();

            $table->index(['direccionable_type', 'direccionable_id'], 'direcciones_direccionable_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('direcciones');
    }
};
