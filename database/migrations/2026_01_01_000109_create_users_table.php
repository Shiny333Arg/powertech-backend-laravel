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

            // Empleados y clientes entran igual: correo + contraseña. El cliente
            // puede usar ademas su cuenta de Google.
            $table->string('email', 150)->unique();

            // null en dos casos: empleado con la invitacion todavia sin aceptar,
            // y cliente que solo entra con Google.
            $table->string('password', 255)->nullable();

            $table->string('nombres', 80);
            $table->string('apellido', 80);

            $table->enum('proveedor_auth', ['local', 'google'])->default('local');
            $table->string('google_id', 50)->nullable()->unique();

            $table->boolean('activo')->default(true);

            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('ultimo_acceso_at')->nullable();

            // "Recordarme" del login. Ademas, restablecer la contraseña lo
            // regenera, y eso invalida las sesiones recordadas en otros equipos.
            $table->rememberToken();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
