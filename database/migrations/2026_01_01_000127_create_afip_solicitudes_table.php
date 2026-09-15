<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('afip_solicitudes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comprobante_id')
                ->constrained('comprobantes')
                ->restrictOnDelete();
            $table->string('operacion', 40);
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->char('resultado', 1)->nullable();
            $table->string('codigo_error', 20)->nullable();
            $table->string('mensaje_error', 255)->nullable();
            $table->integer('intento')->default(1);
            // append-only: una fila por intento, sin updated_at y sin borrado
            $table->timestamp('created_at')->useCurrent();

            $table->index(['comprobante_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('afip_solicitudes');
    }
};
