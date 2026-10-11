<?php

namespace Database\Seeders\Replica;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Contadores en el valor actual, para que la proxima venta u orden
 * de compra siga la numeracion sin pisar nada de lo sembrado.
 */
class NumeradorSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('numeradores')->upsert([
            ['id' => 1, 'serie' => 'venta', 'prefijo' => 'V', 'ultimo_numero' => 19, 'updated_at' => '2026-10-07 03:18:11'],
            ['id' => 2, 'serie' => 'orden_compra', 'prefijo' => 'OC', 'ultimo_numero' => 11, 'updated_at' => '2026-10-08 04:24:12'],
        ], ['id']);
    }
}
