<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A.3 permisos — claves nombradas (venta.anular, compra.aprobar, stock.ajustar),
 * no banderas CRUD. Lo que no se asigna explicitamente, no se puede hacer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permisos', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 60)->unique();
            $table->string('modulo', 40)->index();
            $table->string('descripcion', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permisos');
    }
};
