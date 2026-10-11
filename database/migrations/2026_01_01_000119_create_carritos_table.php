<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carritos', function (Blueprint $table) {
            $table->id();
            // Nullable para permitir carrito de invitado identificado por token
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('session_token', 64)->nullable()->index();
            $table->enum('estado', ['activo', 'en_checkout', 'convertido', 'abandonado'])
                ->default('activo');
            $table->timestamps();

            $table->index(['user_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carritos');
    }
};
