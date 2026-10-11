<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promociones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->string('descripcion', 255)->nullable();
            $table->enum('tipo', ['porcentaje', 'monto_fijo', 'precio_especial']);
            // Segun el tipo: % de descuento, importe a descontar o precio final
            $table->decimal('valor', 12, 2);
            $table->string('banner', 255)->nullable();
            $table->enum('canal', ['ambos', 'mostrador', 'online'])->default('ambos');
            // A mayor prioridad, se evalua primero
            $table->integer('prioridad')->default(0);
            $table->boolean('acumulable')->default(false);
            $table->dateTime('vigencia_desde');
            $table->dateTime('vigencia_hasta');
            $table->boolean('activo')->default(true);
            $table->foreignId('creado_por')->constrained('users');
            $table->timestamps();

            $table->index(['activo', 'vigencia_desde', 'vigencia_hasta']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promociones');
    }
};
