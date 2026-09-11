<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A.2 roles — cinco roles fijos (administrador, administrativo, cajero, vendedor, cliente).
 * Tabla y no ENUM: agregar o renombrar un rol no exige ALTER TABLE sobre users.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50)->unique();
            $table->string('descripcion', 255)->nullable();
            // Decide a que aplicacion entra el usuario al autenticarse.
            $table->enum('ambito', ['gestion', 'tienda']);
            // Protege los cinco roles base del borrado accidental.
            $table->boolean('es_sistema')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
