<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * B.5 puntos_venta — la numeracion fiscal es por punto de venta y tipo de
 * comprobante. La unicidad correlativa se hace cumplir en `comprobantes`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('puntos_venta', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('numero')->unique();
            $table->string('nombre', 60);
            $table->enum('tipo_emision', ['wsfe', 'manual'])->default('wsfe');
            $table->boolean('activo')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('puntos_venta');
    }
};
