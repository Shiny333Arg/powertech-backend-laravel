<?php

namespace Database\Seeders\Maestros;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Roles de sistema. es_sistema=1 impide borrarlos desde la UI.
 */
class RolSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('roles')->upsert([
            ['id' => 1, 'nombre' => 'administrador', 'descripcion' => 'Acceso completo al sistema, sin restricciones.', 'ambito' => 'gestion', 'es_sistema' => 1, 'created_at' => '2026-09-22 03:05:45', 'updated_at' => '2026-09-22 03:05:45'],
            ['id' => 2, 'nombre' => 'administrativo', 'descripcion' => 'Datos maestros, promociones, empleados, compras y ajustes de inventario.', 'ambito' => 'gestion', 'es_sistema' => 1, 'created_at' => '2026-09-22 03:05:45', 'updated_at' => '2026-09-22 03:05:45'],
            ['id' => 3, 'nombre' => 'vendedor', 'descripcion' => 'Presupuestos y ventas de mostrador. Valida presupuestos.', 'ambito' => 'gestion', 'es_sistema' => 1, 'created_at' => '2026-09-22 03:05:45', 'updated_at' => '2026-09-22 03:05:45'],
            ['id' => 4, 'nombre' => 'cajero', 'descripcion' => 'Cobro de ventas confirmadas.', 'ambito' => 'gestion', 'es_sistema' => 1, 'created_at' => '2026-09-22 03:05:45', 'updated_at' => '2026-09-22 03:05:45'],
            ['id' => 5, 'nombre' => 'cliente', 'descripcion' => 'Compra en la tienda online.', 'ambito' => 'tienda', 'es_sistema' => 1, 'created_at' => '2026-09-22 03:05:45', 'updated_at' => '2026-09-22 03:05:45'],
        ], ['id']);
    }
}
