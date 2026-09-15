<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('numeradores', function (Blueprint $table) {
            $table->id();
            $table->string('serie', 30)->unique(); // 'venta' | 'orden_compra'
            $table->string('prefijo', 10)->nullable();
            $table->unsignedInteger('ultimo_numero')->default(0);
            // Sin created_at: la fila la crea el seeder y solo se actualiza.
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('numeradores');
    }
};
