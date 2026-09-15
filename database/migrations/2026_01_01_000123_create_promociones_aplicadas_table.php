<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promociones_aplicadas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')
                ->constrained('ventas')
                ->restrictOnDelete();
            $table->foreignId('venta_linea_id')
                ->constrained('venta_lineas')
                ->restrictOnDelete();
            $table->foreignId('promocion_id')
                ->constrained('promociones')
                ->restrictOnDelete();
            $table->decimal('importe_descuento', 12, 2);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promociones_aplicadas');
    }
};
