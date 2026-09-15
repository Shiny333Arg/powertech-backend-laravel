<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\UpsertPorClaveNatural;
use Illuminate\Database\Seeder;

/**
 * Alicuotas de IVA. Clave natural: codigo_afip.
 *
 * El web service espera el codigo, no el porcentaje.
 * VERIFICAR contra la tabla vigente del organismo antes del go-live.
 */
class AlicuotaIvaSeeder extends Seeder
{
    use UpsertPorClaveNatural;

    public function run(): void
    {
        $alicuotas = [
            ['codigo_afip' => 3, 'porcentaje' => '0.00',  'nombre' => '0%'],
            ['codigo_afip' => 9, 'porcentaje' => '2.50',  'nombre' => '2,5%'],
            ['codigo_afip' => 8, 'porcentaje' => '5.00',  'nombre' => '5%'],
            ['codigo_afip' => 4, 'porcentaje' => '10.50', 'nombre' => '10,5%'],
            ['codigo_afip' => 5, 'porcentaje' => '21.00', 'nombre' => '21%'],
            ['codigo_afip' => 6, 'porcentaje' => '27.00', 'nombre' => '27%'],
        ];

        foreach ($alicuotas as $alicuota) {
            $this->upsert('alicuotas_iva', ['codigo_afip' => $alicuota['codigo_afip']], [
                'porcentaje' => $alicuota['porcentaje'],
                'nombre'     => $alicuota['nombre'],
            ]);
        }
    }
}
