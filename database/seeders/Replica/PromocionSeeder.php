<?php

namespace Database\Seeders\Replica;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Promociones y los productos que alcanza cada una.
 *
 * Las vigencias son las del dump: varias ya vencieron. Si queres
 * que el entorno levante con promos vivas, corre PromocionSeeder
 * con fechas relativas a now().
 */
class PromocionSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('promociones')->upsert([
            ['id' => 1, 'nombre' => 'Procesadores AMD a mitad de precio', 'descripcion' => 'Todos los procesadores AMD a mitad de precio', 'tipo' => 'porcentaje', 'valor' => '15.00', 'banner' => 'promociones/Ewp08Y59S1LZrCSWooZW06FFpBWfIjGR3o0UlKdq.jpg', 'canal' => 'ambos', 'prioridad' => 0, 'acumulable' => 0, 'vigencia_desde' => '2026-09-23 00:00:00', 'vigencia_hasta' => '2026-10-23 23:59:00', 'activo' => 1, 'creado_por' => 2, 'created_at' => '2026-09-23 09:05:01', 'updated_at' => '2026-09-23 19:18:08'],
            ['id' => 2, 'nombre' => 'Descuentos Septiembre 2026', 'descripcion' => 'placas de video', 'tipo' => 'porcentaje', 'valor' => '10.00', 'banner' => 'promociones/ELRlvm7tAaqMCju7moFnvWDdQvqFLTBXGOUPpycG.webp', 'canal' => 'ambos', 'prioridad' => 0, 'acumulable' => 0, 'vigencia_desde' => '2026-09-23 00:00:00', 'vigencia_hasta' => '2026-10-23 23:59:00', 'activo' => 1, 'creado_por' => 2, 'created_at' => '2026-09-23 09:10:12', 'updated_at' => '2026-09-23 09:10:12'],
            ['id' => 3, 'nombre' => 'Descuentos otoño 2026', 'descripcion' => 'sadasds', 'tipo' => 'monto_fijo', 'valor' => '15000.00', 'banner' => null, 'canal' => 'ambos', 'prioridad' => 0, 'acumulable' => 1, 'vigencia_desde' => '2026-09-24 00:00:00', 'vigencia_hasta' => '2026-10-24 23:59:00', 'activo' => 1, 'creado_por' => 2, 'created_at' => '2026-09-24 08:28:02', 'updated_at' => '2026-09-24 08:28:02'],
            ['id' => 4, 'nombre' => 'log 2026', 'descripcion' => null, 'tipo' => 'porcentaje', 'valor' => '15.00', 'banner' => 'promociones/li5R9qisBXTxjlpzKGIemFFcbdTati2dTcnyukVI.jpg', 'canal' => 'ambos', 'prioridad' => 0, 'acumulable' => 0, 'vigencia_desde' => '2026-10-03 00:00:00', 'vigencia_hasta' => '2026-11-03 23:59:00', 'activo' => 1, 'creado_por' => 2, 'created_at' => '2026-10-03 09:07:47', 'updated_at' => '2026-10-03 09:07:47'],
        ], ['id']);

        DB::table('promocion_productos')->insertOrIgnore([
            ['promocion_id' => 1, 'producto_id' => 1],
            ['promocion_id' => 2, 'producto_id' => 2],
            ['promocion_id' => 3, 'producto_id' => 4],
            ['promocion_id' => 4, 'producto_id' => 4],
            ['promocion_id' => 4, 'producto_id' => 6],
            ['promocion_id' => 4, 'producto_id' => 9],
        ]);
    }
}
