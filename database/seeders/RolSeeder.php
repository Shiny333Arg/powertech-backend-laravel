<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\UpsertsMasterData;
use Illuminate\Database\Seeder;

/**
 * Los cinco roles fijos del sistema (Requerimientos 2.2).
 *
 * `ambito` decide a qué aplicación entra el usuario al autenticarse:
 * gestión para el personal, tienda para el cliente.
 *
 * `es_sistema = true` en los cinco: son la base del modelo de permisos y
 * la pantalla de roles no debe permitir borrarlos.
 */
class RolSeeder extends Seeder
{
    use UpsertsMasterData;

    public const ADMINISTRADOR = 'administrador';
    public const ADMINISTRATIVO = 'administrativo';
    public const CAJERO = 'cajero';
    public const VENDEDOR = 'vendedor';
    public const CLIENTE = 'cliente';

    public function run(): void
    {
        $roles = [
            [
                'nombre' => self::ADMINISTRADOR,
                'descripcion' => 'Acceso completo al sistema, sin restricciones.',
                'ambito' => 'gestion',
            ],
            [
                'nombre' => self::ADMINISTRATIVO,
                'descripcion' => 'Catálogo, stock, proveedores, compras, promociones y empleados.',
                'ambito' => 'gestion',
            ],
            [
                'nombre' => self::VENDEDOR,
                'descripcion' => 'Presupuestos y ventas de mostrador. Valida presupuestos para su cobro.',
                'ambito' => 'gestion',
            ],
            [
                'nombre' => self::CAJERO,
                'descripcion' => 'Cobro de ventas confirmadas y arqueo de caja.',
                'ambito' => 'gestion',
            ],
            [
                'nombre' => self::CLIENTE,
                'descripcion' => 'Compra en la tienda online: carrito, pago y seguimiento del envío.',
                'ambito' => 'tienda',
            ],
        ];

        foreach ($roles as $rol) {
            $this->upsertPorClave(
                'roles',
                ['nombre' => $rol['nombre']],
                [
                    'descripcion' => $rol['descripcion'],
                    'ambito' => $rol['ambito'],
                    'es_sistema' => true,
                ]
            );
        }

        $this->command?->info('Roles: '.count($roles).' roles de sistema reconciliados.');
    }
}
