<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alicuotas_iva', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('codigo_afip')->unique();
            $table->decimal('porcentaje', 4, 2);
            $table->string('nombre', 30);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alicuotas_iva');
    }
};
