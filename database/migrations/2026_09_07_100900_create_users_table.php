<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A.1 users — empleados y clientes inician sesion con la misma mecanica.
 * Unificar credenciales evita duplicar login, reset y bloqueo de cuenta.
 * Los datos propios de cada poblacion viven en `empleados` y `clientes`.
 *
 * IMPORTANTE: eliminar la migracion por defecto de Laravel
 * database/migrations/0001_01_01_000000_create_users_table.php
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rol_id')->constrained('roles')->restrictOnDelete();

            // Vias de acceso distintas: el empleado entra con usuario,
            // el cliente con correo o Google. Ambas nullable y unicas.
            $table->string('usuario', 30)->nullable()->unique();
            $table->string('email', 150)->nullable()->unique();

            // Nullable: con Google no hay contrasena local que guardar.
            $table->string('password', 255)->nullable();

            $table->string('nombres', 80);
            $table->string('apellido', 80);

            $table->enum('proveedor_auth', ['local', 'google'])->default('local');
            $table->string('google_id', 50)->nullable()->unique();

            // Circuito de olvido de clave del empleado: el administrativo
            // activa el flag y el sistema obliga al cambio en el proximo ingreso.
            // Es tambien el estado de alta de todos los empleados en el go-live.
            $table->boolean('forzar_cambio_password')->default(true);

            $table->boolean('activo')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('ultimo_acceso_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['rol_id', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
