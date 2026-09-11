<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * B.4 tipos_comprobante — factura A y B, notas de credito y debito.
 * `signo` (+1 / -1) permite sumar saldos sin condicionales dispersos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_comprobante', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('codigo_afip')->unique();
            $table->string('nombre', 60);
            $table->enum('letra', ['A', 'B']);
            $table->tinyInteger('signo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_comprobante');
    }
};
