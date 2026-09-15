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
            $table->foreignId('rol_id')
                ->constrained('roles')
                ->restrictOnDelete();

            // Dos vias de acceso: el empleado entra con usuario, el cliente con email o Google.
            $table->string('usuario', 30)->nullable()->unique();
            $table->string('email', 150)->nullable()->unique();
            $table->string('password', 255)->nullable(); // null con proveedor_auth = google

            $table->string('nombres', 80);
            $table->string('apellido', 80);

            $table->enum('proveedor_auth', ['local', 'google'])->default('local');
            $table->string('google_id', 50)->nullable()->unique();

            $table->boolean('forzar_cambio_password')->default(false);
            $table->boolean('activo')->default(true);

            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('ultimo_acceso_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
