<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Usuario administrador inicial. Clave natural: email.
 *
 * Se inserta SOLO si falta y no se reescribe nunca: una segunda corrida del
 * seeder pisaria la contraseña de una cuenta que ya esta en uso.
 *
 * El correo sale de SEED_ADMIN_EMAIL (obligatorio: es la credencial). La
 * contraseña sale de SEED_ADMIN_PASSWORD; si no esta, se genera una al azar y
 * se imprime una unica vez por consola. En cualquier caso, el administrador
 * deberia cambiarla desde "Mi cuenta" en su primer ingreso.
 *
 * Nace verificado: el correo lo eligio quien instala el sistema.
 *
 * Nota: este usuario NO tiene ficha en 'empleados' (legajo, CUIL, documento).
 * Esos datos los carga un administrativo antes del go-live.
 */
class UsuarioAdministradorSeeder extends Seeder
{
    public function run(): void
    {
        $email = Str::lower(trim((string) env('SEED_ADMIN_EMAIL')));

        if ($email === '') {
            throw new RuntimeException('UsuarioAdministradorSeeder: falta SEED_ADMIN_EMAIL en el .env.');
        }

        $rolId = DB::table('roles')->where('nombre', 'administrador')->value('id');

        if (! $rolId) {
            throw new RuntimeException('UsuarioAdministradorSeeder: corre despues de RolSeeder.');
        }

        if (DB::table('users')->where('email', $email)->exists()) {
            $this->command?->line("  users: '{$email}' ya existe, no se toca.");

            return;
        }

        $password = env('SEED_ADMIN_PASSWORD') ?: Str::random(16);

        DB::table('users')->insert([
            'rol_id'            => $rolId,
            'email'             => $email,
            'password'          => Hash::make($password),
            'nombres'           => 'Administrador',
            'apellido'          => 'PowerTech',
            'proveedor_auth'    => 'local',
            'activo'            => true,
            'email_verified_at' => now(),
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        if (! env('SEED_ADMIN_PASSWORD')) {
            $this->command?->warn("  Usuario administrador creado: {$email}");
            $this->command?->warn("  Contraseña: {$password}");
            $this->command?->warn('  Se muestra una sola vez. Cambiala desde "Mi cuenta" al ingresar.');
        }
    }
}
