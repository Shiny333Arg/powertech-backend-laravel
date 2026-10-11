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
            // Nullable: un cliente de mostrador puede no tener cuenta en la app
            $table->foreignId('user_id')->nullable()->unique()->constrained('users');
            $table->string('razon_social', 150);
            $table->foreignId('tipo_documento_id')->constrained('tipos_documento');
            $table->string('nro_doc', 20);
            $table->foreignId('condicion_iva_id')->constrained('condiciones_iva');
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
