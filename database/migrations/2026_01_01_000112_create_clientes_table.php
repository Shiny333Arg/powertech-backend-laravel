<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            // nullable: el cliente de mostrador existe sin tener cuenta.
            $table->foreignId('user_id')
                ->nullable()
                ->unique()
                ->constrained('users')
                ->restrictOnDelete();
            $table->string('razon_social', 150);
            $table->foreignId('tipo_documento_id')
                ->constrained('tipos_documento')
                ->restrictOnDelete();
            $table->string('nro_doc', 20);
            $table->foreignId('condicion_iva_id')
                ->constrained('condiciones_iva')
                ->restrictOnDelete();
            $table->string('telefono', 30)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['tipo_documento_id', 'nro_doc']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
