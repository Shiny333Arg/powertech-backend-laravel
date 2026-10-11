<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_comprobante', function (Blueprint $table) {
            $table->id();
            // 1 FA, 2 NDA, 3 NCA, 6 FB, 7 NDB, 8 NCB
            $table->smallInteger('codigo_afip')->unique();
            $table->string('nombre', 60);
            $table->enum('letra', ['A', 'B']);
            // +1 suma al haber del cliente, -1 lo resta (notas de credito)
            $table->tinyInteger('signo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_comprobante');
    }
};
