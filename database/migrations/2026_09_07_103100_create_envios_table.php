<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * J.1 envios — 1:1 opcional con ventas: modo_entrega = retiro significa que no
 * hay fila aca.
 *
 * DECISION CLAVE: la direccion se COPIA, no se referencia a `direcciones`.
 * Un envio ya despachado no puede cambiar de destino porque el cliente edito su
 * domicilio en el perfil. El documento logistico es tan inmutable como el fiscal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('envios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->unique()->constrained('ventas')->restrictOnDelete();

            $table->string('calle', 120);
            $table->string('numero', 10)->nullable();
            $table->string('piso_depto', 20)->nullable();
            $table->string('codigo_postal', 10);
            $table->string('localidad', 80);
            $table->string('provincia', 60);

            $table->decimal('costo', 12, 2)->default(0);
            $table->string('proveedor_envio', 60)->nullable();
            $table->string('envio_externo_id', 60)->nullable();
            $table->string('tracking_url', 255)->nullable();
            $table->string('estado', 40)->default('pendiente');
            $table->dateTime('fecha_despacho')->nullable();
            $table->timestamps();

            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('envios');
    }
};
