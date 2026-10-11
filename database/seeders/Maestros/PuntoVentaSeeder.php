<?php

namespace Database\Seeders\Maestros;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Puntos de venta habilitados ante ARCA/AFIP.
 */
class PuntoVentaSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('puntos_venta')->upsert([
            ['id' => 1, 'numero' => 1, 'nombre' => 'Casa central', 'tipo_emision' => 'wsfe', 'activo' => 1, 'created_at' => '2026-09-22 03:05:45', 'updated_at' => '2026-09-22 03:05:45'],
        ], ['id']);
    }
}
