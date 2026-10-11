<?php

namespace Database\Seeders\Maestros;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Tipos de comprobante que emite el sistema (A y B).
 * signo: +1 factura y nota de debito, -1 nota de credito.
 */
class TipoComprobanteSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('tipos_comprobante')->upsert([
            ['id' => 1, 'codigo_afip' => 1, 'nombre' => 'Factura A', 'letra' => 'A', 'signo' => 1, 'created_at' => '2026-09-22 03:05:45', 'updated_at' => '2026-09-22 03:05:45'],
            ['id' => 2, 'codigo_afip' => 2, 'nombre' => 'Nota de Debito A', 'letra' => 'A', 'signo' => 1, 'created_at' => '2026-09-22 03:05:45', 'updated_at' => '2026-09-22 03:05:45'],
            ['id' => 3, 'codigo_afip' => 3, 'nombre' => 'Nota de Credito A', 'letra' => 'A', 'signo' => -1, 'created_at' => '2026-09-22 03:05:45', 'updated_at' => '2026-09-22 03:05:45'],
            ['id' => 4, 'codigo_afip' => 6, 'nombre' => 'Factura B', 'letra' => 'B', 'signo' => 1, 'created_at' => '2026-09-22 03:05:45', 'updated_at' => '2026-09-22 03:05:45'],
            ['id' => 5, 'codigo_afip' => 7, 'nombre' => 'Nota de Debito B', 'letra' => 'B', 'signo' => 1, 'created_at' => '2026-09-22 03:05:45', 'updated_at' => '2026-09-22 03:05:45'],
            ['id' => 6, 'codigo_afip' => 8, 'nombre' => 'Nota de Credito B', 'letra' => 'B', 'signo' => -1, 'created_at' => '2026-09-22 03:05:45', 'updated_at' => '2026-09-22 03:05:45'],
        ], ['id']);
    }
}
