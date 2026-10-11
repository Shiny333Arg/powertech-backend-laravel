<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('condiciones_iva', function (Blueprint $table) {
            $table->id();
            // 1 RI, 4 Exento, 5 Consumidor Final, 6 Monotributo
            $table->smallInteger('codigo_afip')->unique();
            $table->string('nombre', 60);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('condiciones_iva');
    }
};
