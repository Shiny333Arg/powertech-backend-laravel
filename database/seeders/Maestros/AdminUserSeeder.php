<?php

namespace Database\Seeders\Maestros;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Administrador inicial para el arranque limpio (produccion).
 *
 * Solo corre cuando NO se siembra la replica: en modo replica el admin ya
 * viene con los demas usuarios, con su password actual.
 *
 * Credenciales por .env:
 *
 *     ADMIN_EMAIL=admin@powertech.com.ar
 *     ADMIN_PASSWORD=...
 *
 * Si no se define ADMIN_PASSWORD se usa 'password', que sirve para levantar
 * el entorno y hay que cambiar antes de exponer la app.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@powertech.com.ar');

        DB::table('users')->updateOrInsert(
            ['email' => $email],
            [
                'rol_id' => 1,                   // administrador
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
                'nombres' => 'Administrador',
                'apellido' => 'PowerTech',
                'proveedor_auth' => 'local',
                'activo' => 1,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
