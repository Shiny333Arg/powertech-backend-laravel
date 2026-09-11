<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * C.1 categorias — planas por requerimiento: sin parent_id.
 * `activo` permite ocultar una categoria sin borrarla ni romper sus productos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->string('slug', 120)->unique();   // URLs de la tienda
            $table->integer('orden')->default(0);    // vitrina de la app
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};
