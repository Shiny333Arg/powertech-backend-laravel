<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A.6 clientes — entidad, no texto libre dentro de la venta.
 * ARCA exige tipo y numero de documento y condicion frente al IVA para
 * determinar si corresponde factura A o B: sin esto no se puede facturar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            // Nullable a proposito: el cliente de mostrador existe sin cuenta.
            // Si mas adelante se registra, se vincula en vez de duplicarlo.
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->restrictOnDelete();

            $table->string('razon_social', 150);
            $table->foreignId('tipo_documento_id')->constrained('tipos_documento')->restrictOnDelete();
            $table->string('nro_doc', 20);
            $table->foreignId('condicion_iva_id')->constrained('condiciones_iva')->restrictOnDelete();
            $table->string('telefono', 30)->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Evita el mismo cliente cargado dos veces.
            $table->unique(['tipo_documento_id', 'nro_doc']);
            $table->index('razon_social');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
