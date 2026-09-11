<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * F.2 promocion_productos — N:M. Sin la pivot habria que crear una promocion
 * por producto. Si a futuro hacen falta promociones por categoria, esta tabla
 * se vuelve polimorfica (objetivo_type / objetivo_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promocion_productos', function (Blueprint $table) {
            $table->foreignId('promocion_id')->constrained('promociones')->restrictOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->primary(['promocion_id', 'producto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promocion_productos');
    }
};
