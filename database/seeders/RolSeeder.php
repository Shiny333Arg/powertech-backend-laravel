<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\UpsertPorClaveNatural;
use Illuminate\Database\Seeder;

/**
 * Los cinco roles fijos (Requerimientos 2.2).
 * Clave natural: nombre.
 */
class RolSeeder extends Seeder
{
    use UpsertPorClaveNatural;

    public function run(): void
    {
        $roles = [
            ['nombre' => 'administrador',  'ambito' => 'gestion', 'descripcion' => 'Acceso completo al sistema, sin restricciones.'],
            ['nombre' => 'administrativo', 'ambito' => 'gestion', 'descripcion' => 'Datos maestros, promociones, empleados, compras y ajustes de inventario.'],
            ['nombre' => 'vendedor',       'ambito' => 'gestion', 'descripcion' => 'Presupuestos y ventas de mostrador. Valida presupuestos.'],
            ['nombre' => 'cajero',         'ambito' => 'gestion', 'descripcion' => 'Cobro de ventas confirmadas.'],
            ['nombre' => 'cliente',        'ambito' => 'tienda',  'descripcion' => 'Compra en la tienda online.'],
        ];

        foreach ($roles as $rol) {
            $this->upsert('roles', ['nombre' => $rol['nombre']], [
                'descripcion' => $rol['descripcion'],
                'ambito'      => $rol['ambito'],
                'es_sistema'  => true,
            ]);
        }
    }
}
