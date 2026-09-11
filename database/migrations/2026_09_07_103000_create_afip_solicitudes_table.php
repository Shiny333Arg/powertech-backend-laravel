<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * I.3 afip_solicitudes — los WS del organismo fallan seguido: caidas, timeouts,
 * token vencido, errores de validacion. Sin log de request y response no hay
 * forma de saber si un comprobante quedo autorizado del otro lado cuando se
 * corto la conexion, ni de reintentar sin duplicar, ni de responder ante una
 * inspeccion. Una fila por INTENTO, no un campo JSON en `comprobantes`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('afip_solicitudes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comprobante_id')->constrained('comprobantes')->restrictOnDelete();
            $table->string('operacion', 40);          // FECAESolicitar, FECompUltimoAutorizado, ...
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->char('resultado', 1)->nullable(); // A / R
            $table->string('codigo_error', 20)->nullable();
            $table->string('mensaje_error', 255)->nullable();
            $table->unsignedInteger('intento')->default(1);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['comprobante_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('afip_solicitudes');
    }
};
