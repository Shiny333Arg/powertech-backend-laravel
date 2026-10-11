<?php

namespace Database\Seeders\Maestros;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Tipos de documento del padron de ARCA/AFIP.
 * Los codigos (80 CUIT, 86 CUIL, 96 DNI, 99 sin identificar) los fija
 * el organismo: no se inventan ni se renumeran.
 */
class TipoDocumentoSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('tipos_documento')->upsert([
            ['id' => 1, 'codigo_afip' => 80, 'nombre' => 'CUIT', 'created_at' => '2026-09-22 03:05:45', 'updated_at' => '2026-09-22 03:05:45'],
            ['id' => 2, 'codigo_afip' => 86, 'nombre' => 'CUIL', 'created_at' => '2026-09-22 03:05:45', 'updated_at' => '2026-09-22 03:05:45'],
            ['id' => 3, 'codigo_afip' => 96, 'nombre' => 'DNI', 'created_at' => '2026-09-22 03:05:45', 'updated_at' => '2026-09-22 03:05:45'],
            ['id' => 4, 'codigo_afip' => 99, 'nombre' => 'Consumidor final / Sin identificar', 'created_at' => '2026-09-22 03:05:45', 'updated_at' => '2026-09-22 03:05:45'],
        ], ['id']);
    }
}
