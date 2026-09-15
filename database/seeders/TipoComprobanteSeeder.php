<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\UpsertPorClaveNatural;
use Illuminate\Database\Seeder;

/**
 * Tipos de comprobante. Clave natural: codigo_afip.
 *
 * PowerTech es responsable inscripto: factura A y B con sus notas.
 * signo (+1 / -1) permite sumar saldos sin condicionales.
 * VERIFICAR contra la tabla vigente del organismo antes del go-live.
 */
class TipoComprobanteSeeder extends Seeder
{
    use UpsertPorClaveNatural;

    public function run(): void
    {
        $tipos = [
            ['codigo_afip' => 1, 'nombre' => 'Factura A',          'letra' => 'A', 'signo' => 1],
            ['codigo_afip' => 2, 'nombre' => 'Nota de Debito A',   'letra' => 'A', 'signo' => 1],
            ['codigo_afip' => 3, 'nombre' => 'Nota de Credito A',  'letra' => 'A', 'signo' => -1],
            ['codigo_afip' => 6, 'nombre' => 'Factura B',          'letra' => 'B', 'signo' => 1],
            ['codigo_afip' => 7, 'nombre' => 'Nota de Debito B',   'letra' => 'B', 'signo' => 1],
            ['codigo_afip' => 8, 'nombre' => 'Nota de Credito B',  'letra' => 'B', 'signo' => -1],
        ];

        foreach ($tipos as $tipo) {
            $this->upsert('tipos_comprobante', ['codigo_afip' => $tipo['codigo_afip']], [
                'nombre' => $tipo['nombre'],
                'letra'  => $tipo['letra'],
                'signo'  => $tipo['signo'],
            ]);
        }
    }
}
