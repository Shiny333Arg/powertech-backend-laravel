<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Datos maestros de PowerTech.
 *
 * Contiene únicamente configuración: roles, permisos, su matriz, los catálogos
 * fiscales de ARCA, el punto de venta, los contadores internos y el
 * administrador inicial. Ningún dato de negocio (productos, clientes, ventas,
 * stock, órdenes de compra): esa carga la hace el administrativo antes del
 * go-live, según la sección 11.3 de los requerimientos.
 *
 * Es idempotente: se puede correr las veces que haga falta.
 *
 *   php artisan db:seed
 *   php artisan db:seed --class=CatalogosArcaSeeder
 *
 * El orden importa: RolPermisoSeeder resuelve ids por clave natural y falla si
 * los roles o los permisos todavía no están, y UsuarioAdministradorSeeder
 * necesita el id del rol administrador.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Una sola transacción: si un catálogo falla a mitad de camino, la base
        // queda como estaba y no con media matriz de permisos aplicada.
        DB::transaction(function () {
            $this->call([
                RolSeeder::class,
                PermisoSeeder::class,
                RolPermisoSeeder::class,
                CatalogosArcaSeeder::class,
                PuntoVentaSeeder::class,
                NumeradorSeeder::class,
                UsuarioAdministradorSeeder::class,
            ]);
        });

        $this->command?->newLine();
        $this->command?->info('Datos maestros cargados. La base queda lista para la carga previa al go-live.');
    }
}
