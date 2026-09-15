<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\UpsertPorClaveNatural;
use Illuminate\Database\Seeder;

/**
 * Las dos series internas. Clave natural: serie.
 *
 * Se insertan SOLO si faltan y no se reescriben nunca: pisar ultimo_numero
 * romperia la serie correlativa. Si la empresa quiere continuar una
 * numeracion anterior, ultimo_numero es el unico valor a ajustar a mano
 * antes del go-live.
 */
class NumeradorSeeder extends Seeder
{
    use UpsertPorClaveNatural;

    public function run(): void
    {
        $series = [
            ['serie' => 'venta',         'prefijo' => 'V'],
            ['serie' => 'orden_compra',  'prefijo' => 'OC'],
        ];

        foreach ($series as $serie) {
            $inserto = $this->insertarSiFalta('numeradores', ['serie' => $serie['serie']], [
                'prefijo'       => $serie['prefijo'],
                'ultimo_numero' => 0,
                'updated_at'    => now(),
            ]);

            if (! $inserto) {
                $this->command?->line("  numeradores: la serie '{$serie['serie']}' ya existe, no se toca.");
            }
        }
    }
}
