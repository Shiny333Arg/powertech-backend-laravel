<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\UpsertPorClaveNatural;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Usuario administrador inicial. Clave natural: usuario.
 *
 * Se inserta SOLO si falta y no se reescribe nunca: una segunda corrida del
 * seeder restauraria la contrasena provisoria de una cuenta ya en uso.
 *
 * La contrasena sale de POWERTECH_ADMIN_PASSWORD; si esa variable no esta,
 * se genera una al azar y se imprime una unica vez por consola.
 *
 * Nota: este usuario NO tiene ficha en 'empleados' (legajo, CUIL, documento).
 * Esos datos los carga un administrativo antes del go-live.
 */
class UsuarioAdministradorSeeder extends Seeder
{
    use UpsertPorClaveNatural;

    public function run(): void
    {
        $usuario = env('POWERTECH_ADMIN_USUARIO', 'admin');

        $rolId = DB::table('roles')->where('nombre', 'administrador')->value('id');

        if (! $rolId) {
            throw new RuntimeException('UsuarioAdministradorSeeder: corre despues de RolSeeder.');
        }

        if (DB::table('users')->where('usuario', $usuario)->exists()) {
            $this->command?->line("  users: '{$usuario}' ya existe, no se toca.");

            return;
        }

        $password = env('POWERTECH_ADMIN_PASSWORD') ?: Str::random(16);

        DB::table('users')->insert([
            'usuario'                => $usuario,
            'rol_id'                 => $rolId,
            'email'                  => env('POWERTECH_ADMIN_EMAIL'),
            'password'               => Hash::make($password),
            'nombres'                => 'Administrador',
            'apellido'               => 'PowerTech',
            'proveedor_auth'         => 'local',
            'forzar_cambio_password' => true,
            'activo'                 => true,
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);

        if (! env('POWERTECH_ADMIN_PASSWORD')) {
            $this->command?->warn("  Usuario administrador creado: {$usuario}");
            $this->command?->warn("  Contrasena provisoria: {$password}");
            $this->command?->warn('  Se muestra una sola vez. El primer ingreso obliga a cambiarla.');
        }
    }
}
