<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('razon_social', 150);
            $table->string('cuit', 15)->unique();
            $table->foreignId('condicion_iva_id')->constrained('condiciones_iva');
            $table->string('email', 150)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('contacto', 100)->nullable();
            // Como se le hace llegar la orden de compra
            $table->enum('canal_pedido', ['email', 'portal_externo', 'manual'])->default('email');
            $table->string('portal_url', 255)->nullable();
            // Usado para estimar la fecha de entrega de las OC
            $table->integer('plazo_entrega_dias')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};
