<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * G.1 carritos — el carrito NO es una venta: no consume numeracion, no
 * descuenta stock, no genera comprobante y se abandona la mayoria de las veces.
 *
 * EL CARRITO NO COMPROMETE STOCK. Se admite preventa, asi que reservar no
 * protege de nada; y reservar obligaria a un job de expiracion que devuelva
 * unidades de carritos abandonados, generando comprometido fantasma.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carritos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete();
            // Habilita el carrito anonimo antes de que el cliente se registre.
            $table->string('session_token', 64)->nullable()->index();
            $table->enum('estado', ['activo', 'en_checkout', 'convertido', 'abandonado'])
                  ->default('activo');
            $table->timestamps();

            $table->index(['user_id', 'estado']);
            // Reporte de carritos abandonados.
            $table->index(['estado', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carritos');
    }
};
