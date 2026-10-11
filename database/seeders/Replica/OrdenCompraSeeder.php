<?php

namespace Database\Seeders\Replica;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Ordenes de compra a proveedores con su detalle.
 */
class OrdenCompraSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('ordenes_compra')->upsert([
            ['id' => 1, 'numero' => 'OC-0000001', 'proveedor_id' => 2, 'estado' => 'recibida', 'origen' => 'automatica', 'total_estimado' => '250000.00', 'usuario_creo_id' => null, 'usuario_aprobo_id' => 2, 'fecha_aprobacion' => '2026-09-30 23:33:08', 'fecha_envio' => '2026-09-30 23:34:30', 'fecha_entrega_estimada' => '2026-10-07', 'observaciones' => 'Generada automáticamente por reposición de stock.', 'created_at' => '2026-10-01 02:14:50', 'updated_at' => '2026-10-03 05:37:48'],
            ['id' => 2, 'numero' => 'OC-0000002', 'proveedor_id' => 3, 'estado' => 'recibida_parcial', 'origen' => 'automatica', 'total_estimado' => '156.00', 'usuario_creo_id' => null, 'usuario_aprobo_id' => 2, 'fecha_aprobacion' => '2026-09-30 23:35:13', 'fecha_envio' => '2026-09-30 23:35:15', 'fecha_entrega_estimada' => '2026-10-03', 'observaciones' => 'Generada automáticamente por reposición de stock.', 'created_at' => '2026-10-01 02:14:50', 'updated_at' => '2026-10-03 04:53:58'],
            ['id' => 3, 'numero' => 'OC-0000003', 'proveedor_id' => 11, 'estado' => 'cancelada', 'origen' => 'automatica', 'total_estimado' => '20000.00', 'usuario_creo_id' => null, 'usuario_aprobo_id' => null, 'fecha_aprobacion' => null, 'fecha_envio' => null, 'fecha_entrega_estimada' => null, 'observaciones' => "Generada automáticamente por reposición de stock.\n[30/09/2026 23:36] Cancelada por : ya no la vamos a vender", 'created_at' => '2026-10-01 02:14:50', 'updated_at' => '2026-10-01 02:36:08'],
            ['id' => 4, 'numero' => 'OC-0000004', 'proveedor_id' => 11, 'estado' => 'recibida', 'origen' => 'automatica', 'total_estimado' => '20000.00', 'usuario_creo_id' => null, 'usuario_aprobo_id' => 2, 'fecha_aprobacion' => '2026-10-02 01:34:47', 'fecha_envio' => '2026-10-02 01:34:50', 'fecha_entrega_estimada' => null, 'observaciones' => 'Generada automáticamente por reposición de stock.', 'created_at' => '2026-10-01 02:41:08', 'updated_at' => '2026-10-03 04:36:12'],
            ['id' => 5, 'numero' => 'OC-0000005', 'proveedor_id' => 4, 'estado' => 'cancelada', 'origen' => 'automatica', 'total_estimado' => '360000.00', 'usuario_creo_id' => null, 'usuario_aprobo_id' => 2, 'fecha_aprobacion' => '2026-10-01 01:21:57', 'fecha_envio' => '2026-10-01 01:21:59', 'fecha_entrega_estimada' => '2026-10-05', 'observaciones' => "Generada automáticamente por reposición de stock.\n[01/10/2026 01:27] Cancelada por : el proveedor quebró", 'created_at' => '2026-10-01 02:45:58', 'updated_at' => '2026-10-01 04:27:12'],
            ['id' => 6, 'numero' => 'OC-0000006', 'proveedor_id' => 4, 'estado' => 'cancelada', 'origen' => 'automatica', 'total_estimado' => '420000.00', 'usuario_creo_id' => null, 'usuario_aprobo_id' => 2, 'fecha_aprobacion' => '2026-10-01 03:20:30', 'fecha_envio' => '2026-10-01 03:20:34', 'fecha_entrega_estimada' => '2026-10-06', 'observaciones' => "Generada automáticamente por reposición de stock.\n[01/10/2026 03:20] Cancelada por : missclick", 'created_at' => '2026-10-01 04:56:19', 'updated_at' => '2026-10-01 06:20:50'],
            ['id' => 7, 'numero' => 'OC-0000007', 'proveedor_id' => 4, 'estado' => 'cancelada', 'origen' => 'automatica', 'total_estimado' => '420000.00', 'usuario_creo_id' => null, 'usuario_aprobo_id' => 2, 'fecha_aprobacion' => '2026-10-01 03:21:05', 'fecha_envio' => '2026-10-01 03:21:09', 'fecha_entrega_estimada' => '2026-10-06', 'observaciones' => "Generada automáticamente por reposición de stock.\n[01/10/2026 03:21] Cancelada por : otro missclick", 'created_at' => '2026-10-01 06:20:51', 'updated_at' => '2026-10-01 06:21:49'],
            ['id' => 8, 'numero' => 'OC-0000008', 'proveedor_id' => 4, 'estado' => 'recibida', 'origen' => 'automatica', 'total_estimado' => '480000.00', 'usuario_creo_id' => null, 'usuario_aprobo_id' => 2, 'fecha_aprobacion' => '2026-10-03 01:37:11', 'fecha_envio' => '2026-10-03 01:37:16', 'fecha_entrega_estimada' => '2026-10-06', 'observaciones' => "Generada automáticamente por reposición de stock.\n[03/10/2026 01:38] Cerrado con faltante por : missclick. Quedó sin recibir: RM850eCorsair x6.", 'created_at' => '2026-10-01 06:21:51', 'updated_at' => '2026-10-03 04:38:28'],
            ['id' => 9, 'numero' => 'OC-0000009', 'proveedor_id' => 4, 'estado' => 'recibida', 'origen' => 'automatica', 'total_estimado' => '180000.00', 'usuario_creo_id' => null, 'usuario_aprobo_id' => 2, 'fecha_aprobacion' => '2026-10-03 01:42:26', 'fecha_envio' => '2026-10-03 01:42:30', 'fecha_entrega_estimada' => '2026-10-08', 'observaciones' => 'Generada automáticamente por reposición de stock.', 'created_at' => '2026-10-03 04:41:39', 'updated_at' => '2026-10-03 04:42:41'],
            ['id' => 10, 'numero' => 'OC-0000010', 'proveedor_id' => 4, 'estado' => 'cancelada', 'origen' => 'manual', 'total_estimado' => '3000000.00', 'usuario_creo_id' => 2, 'usuario_aprobo_id' => null, 'fecha_aprobacion' => null, 'fecha_envio' => null, 'fecha_entrega_estimada' => '2026-10-13', 'observaciones' => '[08/10/2026 01:24] Cancelada por : asdas', 'created_at' => '2026-10-08 04:21:06', 'updated_at' => '2026-10-08 04:24:34'],
            ['id' => 11, 'numero' => 'OC-0000011', 'proveedor_id' => 4, 'estado' => 'borrador', 'origen' => 'manual', 'total_estimado' => '1000001.00', 'usuario_creo_id' => 2, 'usuario_aprobo_id' => null, 'fecha_aprobacion' => null, 'fecha_envio' => null, 'fecha_entrega_estimada' => '2026-10-13', 'observaciones' => null, 'created_at' => '2026-10-08 04:24:12', 'updated_at' => '2026-10-08 04:24:12'],
        ], ['id']);

        DB::table('orden_compra_lineas')->upsert([
            ['id' => 1, 'orden_compra_id' => 1, 'producto_id' => 3, 'cantidad_pedida' => 5, 'cantidad_recibida' => 5, 'costo_unitario' => '50000.00', 'created_at' => '2026-10-01 02:14:50', 'updated_at' => '2026-10-03 05:37:48'],
            ['id' => 2, 'orden_compra_id' => 2, 'producto_id' => 6, 'cantidad_pedida' => 13, 'cantidad_recibida' => 10, 'costo_unitario' => '12.00', 'created_at' => '2026-10-01 02:14:50', 'updated_at' => '2026-10-03 04:53:58'],
            ['id' => 3, 'orden_compra_id' => 3, 'producto_id' => 11, 'cantidad_pedida' => 2, 'cantidad_recibida' => 0, 'costo_unitario' => '10000.00', 'created_at' => '2026-10-01 02:14:50', 'updated_at' => '2026-10-01 02:14:50'],
            ['id' => 4, 'orden_compra_id' => 4, 'producto_id' => 11, 'cantidad_pedida' => 2, 'cantidad_recibida' => 2, 'costo_unitario' => '10000.00', 'created_at' => '2026-10-01 02:41:08', 'updated_at' => '2026-10-03 04:36:11'],
            ['id' => 5, 'orden_compra_id' => 5, 'producto_id' => 8, 'cantidad_pedida' => 12, 'cantidad_recibida' => 0, 'costo_unitario' => '30000.00', 'created_at' => '2026-10-01 02:45:58', 'updated_at' => '2026-10-01 04:17:38'],
            ['id' => 6, 'orden_compra_id' => 6, 'producto_id' => 8, 'cantidad_pedida' => 14, 'cantidad_recibida' => 0, 'costo_unitario' => '30000.00', 'created_at' => '2026-10-01 04:56:19', 'updated_at' => '2026-10-01 06:20:15'],
            ['id' => 7, 'orden_compra_id' => 7, 'producto_id' => 8, 'cantidad_pedida' => 14, 'cantidad_recibida' => 0, 'costo_unitario' => '30000.00', 'created_at' => '2026-10-01 06:20:51', 'updated_at' => '2026-10-01 06:20:51'],
            ['id' => 8, 'orden_compra_id' => 8, 'producto_id' => 8, 'cantidad_pedida' => 16, 'cantidad_recibida' => 10, 'costo_unitario' => '30000.00', 'created_at' => '2026-10-01 06:21:51', 'updated_at' => '2026-10-03 04:37:29'],
            ['id' => 9, 'orden_compra_id' => 9, 'producto_id' => 8, 'cantidad_pedida' => 6, 'cantidad_recibida' => 6, 'costo_unitario' => '30000.00', 'created_at' => '2026-10-03 04:41:39', 'updated_at' => '2026-10-03 04:42:41'],
            ['id' => 10, 'orden_compra_id' => 10, 'producto_id' => 10, 'cantidad_pedida' => 3, 'cantidad_recibida' => 0, 'costo_unitario' => '1000000.00', 'created_at' => '2026-10-08 04:21:06', 'updated_at' => '2026-10-08 04:21:06'],
            ['id' => 11, 'orden_compra_id' => 11, 'producto_id' => 10, 'cantidad_pedida' => 1, 'cantidad_recibida' => 0, 'costo_unitario' => '1000001.00', 'created_at' => '2026-10-08 04:24:12', 'updated_at' => '2026-10-08 04:24:12'],
        ], ['id']);
    }
}
