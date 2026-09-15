<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_compra', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 20)->unique(); // serie 'orden_compra' de numeradores
            $table->foreignId('proveedor_id')
                ->constrained('proveedores')
                ->restrictOnDelete();
            $table->enum('estado', [
                'borrador',
                'aprobada',
                'enviada',
                'recibida_parcial',
                'recibida',
                'cancelada',
            ])->default('borrador');
            $table->enum('origen', ['manual', 'automatica'])->default('manual');
            $table->decimal('total_estimado', 12, 2)->nullable();
            $table->foreignId('usuario_creo_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('usuario_aprobo_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
            $table->dateTime('fecha_aprobacion')->nullable();
            $table->dateTime('fecha_envio')->nullable();
            $table->date('fecha_entrega_estimada')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            // ordenes abiertas por proveedor / dashboard de pendientes de aprobacion
            $table->index(['estado', 'proveedor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_compra');
    }
};
