<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\UpsertsMasterData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Usuario administrador inicial: la única cuenta que crea el repositorio.
 *
 * Es la puerta de entrada al sistema recién instalado. Desde ella el
 * administrativo carga categorías, proveedores, productos y el resto de los
 * empleados, que son datos de negocio y no van en un seeder.
 *
 * Igual que `numeradores`, se inserta pero no se reconcilia: si la cuenta ya
 * existe, la corrida siguiente no la toca. Reescribir la fila devolvería la
 * contraseña provisoria y volvería a levantar `forzar_cambio_password` sobre una
 * cuenta que ya fue configurada — un seeder corrido por costumbre en producción
 * terminaría restaurando una credencial conocida.
 *
 * La contraseña sale del entorno. El default existe solo para levantar el
 * proyecto en local y no debe usarse en producción: `forzar_cambio_password`
 * obliga al cambio en el primer ingreso de todos modos.
 */
class UsuarioAdministradorSeeder extends Seeder
{
    use UpsertsMasterData;

    public function run(): void
    {
        $usuario = env('SEED_ADMIN_USUARIO', 'admin');
        $email = env('SEED_ADMIN_EMAIL', 'admin@powertech.local');
        $password = env('SEED_ADMIN_PASSWORD', 'Cambiar.Esta.Clave');

        $rolId = $this->idPorClave('roles', ['nombre' => RolSeeder::ADMINISTRADOR]);

        $creado = $this->insertarSiFalta(
            'users',
            ['usuario' => $usuario],
            [
                'rol_id' => $rolId,
                'email' => $email,
                'password' => Hash::make($password),
                'nombres' => 'Administrador',
                'apellido' => 'PowerTech',
                'proveedor_auth' => 'local',
                'google_id' => null,
                'forzar_cambio_password' => true,
                'activo' => true,
                'email_verified_at' => null,
                'ultimo_acceso_at' => null,
            ]
        );

        if (! $creado) {
            $this->command?->info("users: el usuario '{$usuario}' ya existe, no se modifica.");

            return;
        }

        $this->command?->info("users: administrador inicial '{$usuario}' creado.");
        $this->command?->warn('Debe cambiar la contraseña en el primer ingreso (forzar_cambio_password = true).');

        if (! app()->environment('production')) {
            $this->command?->line("  usuario: {$usuario}");
            $this->command?->line("  clave:   {$password}");
        }
    }
}
