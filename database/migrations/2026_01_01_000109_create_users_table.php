<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rol_id')->constrained('roles');
            $table->string('email', 150)->unique();
            // Nullable: los usuarios creados por Google no tienen password local
            $table->string('password')->nullable();
            $table->string('nombres', 80);
            $table->string('apellido', 80);
            $table->enum('proveedor_auth', ['local', 'google'])->default('local');
            $table->string('google_id', 50)->nullable()->unique();
            $table->boolean('activo')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('ultimo_acceso_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
