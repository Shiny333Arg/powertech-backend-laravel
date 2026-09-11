<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * E.2 ordenes_compra — el restock automatico se materializa en un documento,
 * no en una notificacion suelta. El job detecta stock_disponible < punto_reposicion,
 * arma la orden en `borrador` y el administrativo la aprueba.
 *
 * Antes de crear una automatica, el job verifica que el producto no tenga ya una
 * orden ABIERTA (borrador, aprobada, enviada, recibida_parcial). Sin esa
 * verificacion cada corrida generaria un pedido nuevo por el mismo faltante.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_compra', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 20)->unique();
            $table->foreignId('proveedor_id')->constrained('proveedores')->restrictOnDelete();

            $table->enum('estado', [
                'borrador', 'aprobada', 'enviada',
                'recibida_parcial', 'recibida', 'cancelada',
            ])->default('borrador');

            // Distingue automaticas de manuales: unica forma de medir si los
            // umbrales estan bien calibrados.
            $table->enum('origen', ['manual', 'automatica'])->default('manual');

            $table->decimal('total_estimado', 12, 2)->nullable();
            $table->foreignId('usuario_creo_id')->nullable()->constrained('users')->restrictOnDelete();
            // Requerimiento explicito: queda registro de quien aprobo.
            $table->foreignId('usuario_aprobo_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->dateTime('fecha_aprobacion')->nullable();
            $table->dateTime('fecha_envio')->nullable();
            $table->date('fecha_entrega_estimada')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            // Dashboard del administrativo: ordenes pendientes de aprobacion.
            $table->index(['estado', 'created_at']);
            $table->index(['proveedor_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_compra');
    }
};
