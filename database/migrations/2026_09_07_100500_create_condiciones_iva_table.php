<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * B.2 condiciones_iva — determina la letra del comprobante:
 * responsable inscripto recibe factura A, el resto B.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('condiciones_iva', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('codigo_afip')->unique();
            $table->string('nombre', 60);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('condiciones_iva');
    }
};
