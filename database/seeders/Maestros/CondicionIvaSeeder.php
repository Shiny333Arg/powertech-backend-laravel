<?php

namespace Database\Seeders\Maestros;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Condiciones frente al IVA habilitadas en el sistema.
 */
class CondicionIvaSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('condiciones_iva')->upsert([
            ['id' => 1, 'codigo_afip' => 1, 'nombre' => 'IVA Responsable Inscripto', 'created_at' => '2026-09-22 03:05:45', 'updated_at' => '2026-09-22 03:05:45'],
            ['id' => 2, 'codigo_afip' => 4, 'nombre' => 'IVA Sujeto Exento', 'created_at' => '2026-09-22 03:05:45', 'updated_at' => '2026-09-22 03:05:45'],
            ['id' => 3, 'codigo_afip' => 5, 'nombre' => 'Consumidor Final', 'created_at' => '2026-09-22 03:05:45', 'updated_at' => '2026-09-22 03:05:45'],
            ['id' => 4, 'codigo_afip' => 6, 'nombre' => 'Responsable Monotributo', 'created_at' => '2026-09-22 03:05:45', 'updated_at' => '2026-09-22 03:05:45'],
        ], ['id']);
    }
}
