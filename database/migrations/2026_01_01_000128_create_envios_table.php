<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('envios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')
                ->unique()
                ->constrained('ventas')
                ->restrictOnDelete();

            // La direccion se COPIA, no se referencia: un envio despachado no
            // cambia de destino porque el cliente edito su perfil.
            $table->string('calle', 120);
            $table->string('numero', 10)->nullable();
            $table->string('piso_depto', 20)->nullable();
            $table->string('codigo_postal', 10);
            $table->string('localidad', 80);
            $table->string('provincia', 60);

            $table->decimal('costo', 12, 2)->nullable();
            $table->string('proveedor_envio', 60)->nullable();
            $table->string('envio_externo_id', 60)->nullable();
            $table->string('tracking_url', 255)->nullable();
            $table->string('estado', 40);
            $table->dateTime('fecha_despacho')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('envios');
    }
};
