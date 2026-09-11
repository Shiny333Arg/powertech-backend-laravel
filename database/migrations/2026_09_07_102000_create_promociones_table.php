<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * F.1 promociones — unica fuente de descuento del sistema: por requerimiento
 * se elimino el descuento manual sobre las ventas.
 *
 * `prioridad` y `acumulable` definen que pasa cuando dos promociones alcanzan
 * el mismo producto. Sin ellos el resultado depende del orden en que el motor
 * devuelva las filas: impredecible y no reproducible ante un reclamo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promociones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->string('descripcion', 255)->nullable();
            $table->enum('tipo', ['porcentaje', 'monto_fijo', 'precio_especial']);
            $table->decimal('valor', 12, 2);
            // El banner vive aca: un banner sin promocion no tiene razon de existir.
            $table->string('banner', 255)->nullable();
            $table->enum('canal', ['ambos', 'mostrador', 'online'])->default('ambos');
            $table->integer('prioridad')->default(0);
            $table->boolean('acumulable')->default(false);
            $table->dateTime('vigencia_desde');
            $table->dateTime('vigencia_hasta');
            $table->boolean('activo')->default(true);
            // Deja responsabilidad sobre los descuentos, que son dinero.
            $table->foreignId('creado_por')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Dashboard de promociones vigentes (vendedor y tienda).
            $table->index(['activo', 'vigencia_desde', 'vigencia_hasta']);
            $table->index('canal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promociones');
    }
};
