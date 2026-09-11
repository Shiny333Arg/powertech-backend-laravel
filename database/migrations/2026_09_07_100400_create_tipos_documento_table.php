<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * B.1 tipos_documento — CUIT, CUIL, DNI, consumidor final.
 * El web service de ARCA recibe codigos numericos, no etiquetas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_documento', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('codigo_afip')->unique();
            $table->string('nombre', 60);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_documento');
    }
};
