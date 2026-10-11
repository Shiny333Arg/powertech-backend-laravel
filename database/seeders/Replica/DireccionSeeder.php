<?php

namespace Database\Seeders\Replica;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Direcciones polimorficas de empleados y proveedores.
 *
 * direccionable_type guarda el FQCN como en el dump. Si despues
 * defines un morphMap ('empleado', 'proveedor'), hay que migrar
 * estos valores.
 */
class DireccionSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('direcciones')->upsert([
            ['id' => 1, 'direccionable_type' => 'App\\Models\\Empleado', 'direccionable_id' => 1, 'tipo' => 'particular', 'calle' => 'Calle Falsa', 'numero' => '123', 'piso_depto' => null, 'codigo_postal' => '9011', 'localidad' => 'Caleta Olivia', 'provincia' => 'Santa Cruz', 'es_predeterminada' => 1, 'created_at' => '2026-09-23 01:03:17', 'updated_at' => '2026-09-23 01:03:17'],
            ['id' => 2, 'direccionable_type' => 'App\\Models\\Empleado', 'direccionable_id' => 2, 'tipo' => 'particular', 'calle' => 'Calle Falsa', 'numero' => '123', 'piso_depto' => null, 'codigo_postal' => '9011', 'localidad' => 'Caleta Olivia', 'provincia' => 'Santa Cruz', 'es_predeterminada' => 1, 'created_at' => '2026-09-23 03:14:18', 'updated_at' => '2026-09-23 03:14:18'],
            ['id' => 3, 'direccionable_type' => 'App\\Models\\Empleado', 'direccionable_id' => 3, 'tipo' => 'particular', 'calle' => 'Calle Falsa', 'numero' => '123', 'piso_depto' => null, 'codigo_postal' => '9011', 'localidad' => 'Caleta Olivia', 'provincia' => 'Santa Cruz', 'es_predeterminada' => 1, 'created_at' => '2026-09-23 03:27:42', 'updated_at' => '2026-09-23 03:27:42'],
            ['id' => 4, 'direccionable_type' => 'App\\Models\\Empleado', 'direccionable_id' => 4, 'tipo' => 'particular', 'calle' => 'Calle Falsa', 'numero' => '123', 'piso_depto' => null, 'codigo_postal' => '9011', 'localidad' => 'Caleta Olivia', 'provincia' => 'Santa Cruz', 'es_predeterminada' => 1, 'created_at' => '2026-09-23 04:44:54', 'updated_at' => '2026-09-23 04:44:54'],
            ['id' => 5, 'direccionable_type' => 'App\\Models\\Empleado', 'direccionable_id' => 5, 'tipo' => 'particular', 'calle' => 'Calle Falsa', 'numero' => '123', 'piso_depto' => null, 'codigo_postal' => '9011', 'localidad' => 'Caleta Olivia', 'provincia' => 'Santa Cruz', 'es_predeterminada' => 1, 'created_at' => '2026-09-23 05:08:04', 'updated_at' => '2026-09-23 05:08:04'],
            ['id' => 6, 'direccionable_type' => 'App\\Models\\Empleado', 'direccionable_id' => 6, 'tipo' => 'particular', 'calle' => 'Calle Falsa', 'numero' => '123', 'piso_depto' => null, 'codigo_postal' => '9011', 'localidad' => 'Caleta Olivia', 'provincia' => 'Santa Cruz', 'es_predeterminada' => 1, 'created_at' => '2026-09-23 05:53:03', 'updated_at' => '2026-09-23 05:53:03'],
            ['id' => 7, 'direccionable_type' => 'App\\Models\\Proveedor', 'direccionable_id' => 1, 'tipo' => 'fiscal', 'calle' => 'Calle Falsa', 'numero' => '123', 'piso_depto' => null, 'codigo_postal' => '9011', 'localidad' => 'Caleta Olivia', 'provincia' => 'Santa Cruz', 'es_predeterminada' => 1, 'created_at' => '2026-09-23 08:49:20', 'updated_at' => '2026-09-23 08:49:20'],
            ['id' => 8, 'direccionable_type' => 'App\\Models\\Proveedor', 'direccionable_id' => 2, 'tipo' => 'fiscal', 'calle' => 'Calle Falsa', 'numero' => '123', 'piso_depto' => null, 'codigo_postal' => '9011', 'localidad' => 'Caleta Olivia', 'provincia' => 'Santa Cruz', 'es_predeterminada' => 1, 'created_at' => '2026-09-23 08:56:11', 'updated_at' => '2026-09-23 08:56:11'],
            ['id' => 9, 'direccionable_type' => 'App\\Models\\Proveedor', 'direccionable_id' => 3, 'tipo' => 'fiscal', 'calle' => 'Calle Falsa', 'numero' => '123', 'piso_depto' => null, 'codigo_postal' => '9011', 'localidad' => 'Caleta Olivia', 'provincia' => 'Santa Cruz', 'es_predeterminada' => 1, 'created_at' => '2026-09-23 09:00:23', 'updated_at' => '2026-09-23 09:00:23'],
        ], ['id']);
    }
}
