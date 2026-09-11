<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * B.3 alicuotas_iva — PowerTech es responsable inscripto: el IVA va
 * discriminado y la alicuota se informa con el codigo que espera el WS.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alicuotas_iva', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('codigo_afip')->unique();
            $table->decimal('porcentaje', 4, 2);
            $table->string('nombre', 30);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alicuotas_iva');
    }
};
