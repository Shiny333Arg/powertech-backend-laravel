<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A.5 empleados — datos personales y fiscales. No son de autenticacion y no
 * todos los users los tienen: un cliente no tiene legajo ni CUIL. Separarlos
 * evita una tabla con la mitad de las columnas en NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empleados', function (Blueprint $table) {
            $table->id();
            // 1:1 con users. Sin el user, la ficha no tiene sentido.
            $table->foreignId('user_id')->unique()->constrained('users')->restrictOnDelete();

            $table->string('legajo', 20)->unique();
            $table->foreignId('tipo_documento_id')->constrained('tipos_documento')->restrictOnDelete();
            $table->string('nro_doc', 20);
            $table->string('cuil', 13)->unique();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('telefono', 30)->nullable();
            $table->date('fecha_ingreso');
            // Conserva el legajo de quien ya no trabaja sin perder sus ventas.
            $table->date('fecha_baja')->nullable();
            $table->timestamps();

            $table->unique(['tipo_documento_id', 'nro_doc']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};
