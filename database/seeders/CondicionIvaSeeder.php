<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\UpsertPorClaveNatural;
use Illuminate\Database\Seeder;

/**
 * Condiciones frente al IVA del receptor. Clave natural: codigo_afip.
 *
 * Determina la letra del comprobante: inscripto recibe A, el resto B.
 * VERIFICAR contra la tabla vigente del organismo antes del go-live.
 */
class CondicionIvaSeeder extends Seeder
{
    use UpsertPorClaveNatural;

    public function run(): void
    {
        $condiciones = [
            1 => 'IVA Responsable Inscripto',
            4 => 'IVA Sujeto Exento',
            5 => 'Consumidor Final',
            6 => 'Responsable Monotributo',
        ];

        foreach ($condiciones as $codigo => $nombre) {
            $this->upsert('condiciones_iva', ['codigo_afip' => $codigo], ['nombre' => $nombre]);
        }
    }
}
