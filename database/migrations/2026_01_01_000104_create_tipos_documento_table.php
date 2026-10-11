<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_documento', function (Blueprint $table) {
            $table->id();
            // Codigo del padron ARCA/AFIP: 80 CUIT, 86 CUIL, 96 DNI, 99 sin identificar
            $table->smallInteger('codigo_afip')->unique();
            $table->string('nombre', 60);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_documento');
    }
};
