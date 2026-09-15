<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Solo datos maestros. Sin productos, clientes, ventas, pagos, stock ni
 * ordenes de compra: eso lo carga un administrativo antes del go-live.
 *
 * Todos los seeders son idempotentes: se pueden correr sobre una base
 * ya sembrada sin duplicar ni pisar nada.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolSeeder::class,
            PermisoSeeder::class,
            RolPermisoSeeder::class,

            TipoDocumentoSeeder::class,
            CondicionIvaSeeder::class,
            AlicuotaIvaSeeder::class,
            TipoComprobanteSeeder::class,
            PuntoVentaSeeder::class,

            NumeradorSeeder::class,
            UsuarioAdministradorSeeder::class,
        ]);
    }
}
