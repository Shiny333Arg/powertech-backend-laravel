<?php

namespace Database\Seeders\Maestros;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Alicuotas de IVA con su codigo de ARCA/AFIP.
 */
class AlicuotaIvaSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('alicuotas_iva')->upsert([
            ['id' => 1, 'codigo_afip' => 3, 'porcentaje' => '0.00', 'nombre' => '0%', 'created_at' => '2026-09-22 03:05:45', 'updated_at' => '2026-09-22 03:05:45'],
            ['id' => 2, 'codigo_afip' => 9, 'porcentaje' => '2.50', 'nombre' => '2,5%', 'created_at' => '2026-09-22 03:05:45', 'updated_at' => '2026-09-22 03:05:45'],
            ['id' => 3, 'codigo_afip' => 8, 'porcentaje' => '5.00', 'nombre' => '5%', 'created_at' => '2026-09-22 03:05:45', 'updated_at' => '2026-09-22 03:05:45'],
            ['id' => 4, 'codigo_afip' => 4, 'porcentaje' => '10.50', 'nombre' => '10,5%', 'created_at' => '2026-09-22 03:05:45', 'updated_at' => '2026-09-22 03:05:45'],
            ['id' => 5, 'codigo_afip' => 5, 'porcentaje' => '21.00', 'nombre' => '21%', 'created_at' => '2026-09-22 03:05:45', 'updated_at' => '2026-09-22 03:05:45'],
            ['id' => 6, 'codigo_afip' => 6, 'porcentaje' => '27.00', 'nombre' => '27%', 'created_at' => '2026-09-22 03:05:45', 'updated_at' => '2026-09-22 03:05:45'],
        ], ['id']);
    }
}
