<?php

namespace Database\Seeders\Replica;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Clientes. El id 1 no existe en el dump (se borro).
 */
class ClienteSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('clientes')->upsert([
            ['id' => 2, 'user_id' => 9, 'razon_social' => 'Fontanet Patricio Rogelio', 'tipo_documento_id' => 3, 'nro_doc' => '14514514', 'condicion_iva_id' => 3, 'telefono' => '2974121415', 'activo' => 1, 'created_at' => '2026-09-23 06:19:47', 'updated_at' => '2026-09-23 06:19:47'],
            ['id' => 3, 'user_id' => 16, 'razon_social' => 'Simpson Homero', 'tipo_documento_id' => 3, 'nro_doc' => '12312332', 'condicion_iva_id' => 3, 'telefono' => null, 'activo' => 1, 'created_at' => '2026-09-25 20:42:03', 'updated_at' => '2026-09-25 20:42:03'],
            ['id' => 4, 'user_id' => 17, 'razon_social' => 'Simpson Bart', 'tipo_documento_id' => 3, 'nro_doc' => '12345676', 'condicion_iva_id' => 3, 'telefono' => null, 'activo' => 1, 'created_at' => '2026-09-25 20:46:30', 'updated_at' => '2026-09-25 20:46:30'],
        ], ['id']);

        // El dump tenia AUTO_INCREMENT=6 (clientes perdio ids por borrados).
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE clientes AUTO_INCREMENT = 6');
        }
    }
}
