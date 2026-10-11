<?php

namespace Database\Seeders\Replica;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Cobros registrados. Todos de mostrador: los campos mp_* quedan
 * en null porque no hubo pagos por Mercado Pago.
 */
class PagoSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('pagos')->upsert([
            ['id' => 3, 'venta_id' => 5, 'metodo' => 'efectivo', 'monto' => '30000.00', 'comision' => null, 'neto_acreditado' => null, 'cuotas' => null, 'estado' => 'acreditado', 'mp_preference_id' => null, 'mp_payment_id' => null, 'mp_merchant_order_id' => null, 'mp_status' => null, 'mp_status_detail' => null, 'mp_payment_type' => null, 'usuario_id' => 3, 'fecha' => '2026-09-26 23:59:04', 'created_at' => '2026-09-27 02:59:04', 'updated_at' => '2026-09-27 02:59:04'],
            ['id' => 4, 'venta_id' => 4, 'metodo' => 'efectivo', 'monto' => '375000.00', 'comision' => null, 'neto_acreditado' => null, 'cuotas' => null, 'estado' => 'acreditado', 'mp_preference_id' => null, 'mp_payment_id' => null, 'mp_merchant_order_id' => null, 'mp_status' => null, 'mp_status_detail' => null, 'mp_payment_type' => null, 'usuario_id' => 3, 'fecha' => '2026-09-27 00:00:25', 'created_at' => '2026-09-27 03:00:25', 'updated_at' => '2026-09-27 03:00:25'],
            ['id' => 9, 'venta_id' => 7, 'metodo' => 'efectivo', 'monto' => '45000.15', 'comision' => null, 'neto_acreditado' => null, 'cuotas' => null, 'estado' => 'acreditado', 'mp_preference_id' => null, 'mp_payment_id' => null, 'mp_merchant_order_id' => null, 'mp_status' => null, 'mp_status_detail' => null, 'mp_payment_type' => null, 'usuario_id' => 3, 'fecha' => '2026-09-27 01:38:11', 'created_at' => '2026-09-27 04:38:11', 'updated_at' => '2026-09-27 04:38:11'],
            ['id' => 12, 'venta_id' => 8, 'metodo' => 'efectivo', 'monto' => '1402151.00', 'comision' => null, 'neto_acreditado' => null, 'cuotas' => null, 'estado' => 'acreditado', 'mp_preference_id' => null, 'mp_payment_id' => null, 'mp_merchant_order_id' => null, 'mp_status' => null, 'mp_status_detail' => null, 'mp_payment_type' => null, 'usuario_id' => 3, 'fecha' => '2026-09-27 01:40:17', 'created_at' => '2026-09-27 04:40:17', 'updated_at' => '2026-09-27 04:40:17'],
            ['id' => 17, 'venta_id' => 14, 'metodo' => 'efectivo', 'monto' => '50000.00', 'comision' => null, 'neto_acreditado' => null, 'cuotas' => null, 'estado' => 'acreditado', 'mp_preference_id' => null, 'mp_payment_id' => null, 'mp_merchant_order_id' => null, 'mp_status' => null, 'mp_status_detail' => null, 'mp_payment_type' => null, 'usuario_id' => 3, 'fecha' => '2026-10-01 01:34:53', 'created_at' => '2026-10-01 04:34:53', 'updated_at' => '2026-10-01 04:34:53'],
            ['id' => 19, 'venta_id' => 15, 'metodo' => 'efectivo', 'monto' => '15000.00', 'comision' => null, 'neto_acreditado' => null, 'cuotas' => null, 'estado' => 'acreditado', 'mp_preference_id' => null, 'mp_payment_id' => null, 'mp_merchant_order_id' => null, 'mp_status' => null, 'mp_status_detail' => null, 'mp_payment_type' => null, 'usuario_id' => 3, 'fecha' => '2026-10-05 06:14:29', 'created_at' => '2026-10-05 09:14:29', 'updated_at' => '2026-10-05 09:14:29'],
            ['id' => 20, 'venta_id' => 18, 'metodo' => 'efectivo', 'monto' => '100000.00', 'comision' => null, 'neto_acreditado' => null, 'cuotas' => null, 'estado' => 'acreditado', 'mp_preference_id' => null, 'mp_payment_id' => null, 'mp_merchant_order_id' => null, 'mp_status' => null, 'mp_status_detail' => null, 'mp_payment_type' => null, 'usuario_id' => 3, 'fecha' => '2026-10-07 00:19:31', 'created_at' => '2026-10-07 03:19:31', 'updated_at' => '2026-10-07 03:19:31'],
            ['id' => 22, 'venta_id' => 9, 'metodo' => 'efectivo', 'monto' => '100000.00', 'comision' => null, 'neto_acreditado' => null, 'cuotas' => null, 'estado' => 'acreditado', 'mp_preference_id' => null, 'mp_payment_id' => null, 'mp_merchant_order_id' => null, 'mp_status' => null, 'mp_status_detail' => null, 'mp_payment_type' => null, 'usuario_id' => 3, 'fecha' => '2026-10-07 23:11:02', 'created_at' => '2026-10-08 02:11:02', 'updated_at' => '2026-10-08 02:11:02'],
        ], ['id']);
    }
}
