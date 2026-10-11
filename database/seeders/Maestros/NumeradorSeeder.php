<?php

namespace Database\Seeders\Maestros;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Series de numeracion, en cero.
 *
 * insertOrIgnore: si la serie ya existe no se pisa el contador.
 * Replica\NumeradorSeeder los deja en el valor actual del dump.
 */
class NumeradorSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('numeradores')->insertOrIgnore([
            ['id' => 1, 'serie' => 'venta', 'prefijo' => 'V', 'ultimo_numero' => 0, 'updated_at' => null],
            ['id' => 2, 'serie' => 'orden_compra', 'prefijo' => 'OC', 'ultimo_numero' => 0, 'updated_at' => null],
        ]);
    }
}
