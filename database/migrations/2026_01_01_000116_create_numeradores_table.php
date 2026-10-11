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
            // 'venta', 'orden_compra', ...
            $table->string('serie', 30)->unique();
            $table->string('prefijo', 10)->nullable();
            // Se incrementa con lockForUpdate() dentro de la transaccion
            $table->unsignedInteger('ultimo_numero')->default(0);
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('numeradores');
    }
};
