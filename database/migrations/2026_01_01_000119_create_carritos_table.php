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
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
            $table->string('session_token', 64)->nullable(); // carrito anonimo
            $table->enum('estado', ['activo', 'en_checkout', 'convertido', 'abandonado'])
                ->default('activo');
            $table->timestamps();

            $table->index('session_token');
            $table->index(['user_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carritos');
    }
};
