<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\UpsertsMasterData;
use Illuminate\Database\Seeder;

/**
 * Puntos de venta habilitados ante el organismo.
 *
 * El correlativo fiscal pertenece al par (tipo de comprobante, punto de venta),
 * así que el número declarado acá tiene que coincidir exactamente con el que
 * PowerTech tiene dado de alta en ARCA. Un punto de venta inventado hace que el
 * WSFEv1 rechace todos los comprobantes.
 *
 * El valor se toma de config/arca.php (o del .env) para que producción y los
 * entornos de prueba no compartan la misma numeración.
 */
class PuntoVentaSeeder extends Seeder
{
    use UpsertsMasterData;

    public function run(): void
    {
        $numero = (int) env('ARCA_PUNTO_VENTA', 1);
        $tipoEmision = env('ARCA_TIPO_EMISION', 'wsfe'); // wsfe | manual

        $this->upsertPorClave(
            'puntos_venta',
            ['numero' => $numero],
            [
                'nombre' => 'Casa central',
                'tipo_emision' => $tipoEmision,
                'activo' => true,
            ]
        );

        $this->command?->info("puntos_venta: punto {$numero} ({$tipoEmision}) reconciliado.");
        $this->command?->warn('Verificá que el punto de venta esté habilitado en ARCA antes del go-live.');
    }
}
