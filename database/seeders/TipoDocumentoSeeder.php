<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\UpsertPorClaveNatural;
use Illuminate\Database\Seeder;

/**
 * Tipos de documento de ARCA/AFIP. Clave natural: codigo_afip.
 *
 * VERIFICAR contra la tabla vigente del organismo antes del go-live.
 */
class TipoDocumentoSeeder extends Seeder
{
    use UpsertPorClaveNatural;

    public function run(): void
    {
        $tipos = [
            80 => 'CUIT',
            86 => 'CUIL',
            96 => 'DNI',
            99 => 'Consumidor final / Sin identificar',
        ];

        foreach ($tipos as $codigo => $nombre) {
            $this->upsert('tipos_documento', ['codigo_afip' => $codigo], ['nombre' => $nombre]);
        }
    }
}
