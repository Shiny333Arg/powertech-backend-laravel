<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * E.1 proveedores — sin ellos no hay a quien emitir la orden de compra del
 * restock automatico. No son usuarios: no tienen login.
 * `canal_pedido` desacopla el proceso interno del medio de entrega.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('razon_social', 150);
            $table->string('cuit', 15)->unique();
            $table->foreignId('condicion_iva_id')->constrained('condiciones_iva')->restrictOnDelete();
            $table->string('email', 150)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('contacto', 100)->nullable();
            $table->enum('canal_pedido', ['email', 'portal_externo', 'manual'])->default('email');
            $table->string('portal_url', 255)->nullable();
            $table->unsignedInteger('plazo_entrega_dias')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};
