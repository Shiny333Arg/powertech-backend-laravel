<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\UpsertsMasterData;
use Illuminate\Database\Seeder;

/**
 * Contadores de la numeración interna (sección K.1 del modelo de datos).
 *
 * Dos series: la venta y la orden de compra. Nada que ver con la numeración
 * fiscal, que la autoriza ARCA y vive en `comprobantes`.
 *
 * Este seeder usa inserción sin reconciliación, a diferencia de los catálogos.
 * La razón es la que importa de toda la clase: `ultimo_numero` es estado vivo del
 * negocio. Un upsert que reescriba el valor declarado acá dejaría el contador
 * atrás del último documento emitido, y la venta siguiente chocaría contra el
 * índice único de `ventas.numero` — o peor, reutilizaría un número ya entregado a
 * un cliente. Corrido dos veces, este seeder no toca las filas existentes.
 *
 * Si la empresa quiere continuar una numeración anterior, ese ajuste se hace una
 * sola vez antes del go-live, con la base todavía sin ventas.
 */
class NumeradorSeeder extends Seeder
{
    use UpsertsMasterData;

    public function run(): void
    {
        $series = [
            [
                'serie' => 'venta',
                'prefijo' => 'V',
                'ultimo_numero' => (int) env('SEED_NUMERADOR_VENTA', 0),
            ],
            [
                'serie' => 'orden_compra',
                'prefijo' => 'OC',
                'ultimo_numero' => (int) env('SEED_NUMERADOR_ORDEN_COMPRA', 0),
            ],
        ];

        foreach ($series as $serie) {
            $creado = $this->insertarSiFalta(
                'numeradores',
                ['serie' => $serie['serie']],
                [
                    'prefijo' => $serie['prefijo'],
                    'ultimo_numero' => $serie['ultimo_numero'],
                ]
            );

            $this->command?->info(sprintf(
                'numeradores: serie %-13s %s',
                $serie['serie'],
                $creado
                    ? "inicializada en {$serie['ultimo_numero']}."
                    : 'ya existe, no se toca el contador.'
            ));
        }
    }
}
