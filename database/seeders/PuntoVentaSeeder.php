<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\UpsertPorClaveNatural;
use Illuminate\Database\Seeder;

/**
 * Puntos de venta habilitados ante el organismo. Clave natural: numero.
 *
 * ATENCION: el numero real lo asigna ARCA al habilitar el punto de venta.
 * El 1 de abajo es un placeholder y debe reemplazarse por el habilitado
 * antes del go-live, o la numeracion fiscal no va a coincidir.
 */
class PuntoVentaSeeder extends Seeder
{
    use UpsertPorClaveNatural;

    public function run(): void
    {
        $numero = (int) env('POWERTECH_PUNTO_VENTA', 1);

        $this->upsert('puntos_venta', ['numero' => $numero], [
            'nombre'       => 'Casa central',
            'tipo_emision' => 'wsfe',
            'activo'       => true,
        ]);
    }
}
