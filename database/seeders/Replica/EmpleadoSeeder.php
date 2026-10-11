<?php

namespace Database\Seeders\Replica;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Legajos de empleados, uno por usuario de gestion.
 */
class EmpleadoSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('empleados')->upsert([
            ['id' => 1, 'user_id' => 2, 'legajo' => 'EMP001', 'tipo_documento_id' => 3, 'nro_doc' => '12345678', 'cuil' => '20329851657', 'fecha_nacimiento' => '1950-01-23', 'telefono' => '2974121212', 'fecha_ingreso' => '2026-09-22', 'fecha_baja' => null, 'created_at' => '2026-09-23 01:03:17', 'updated_at' => '2026-09-23 01:03:17'],
            ['id' => 2, 'user_id' => 3, 'legajo' => 'EMP002', 'tipo_documento_id' => 3, 'nro_doc' => '12345677', 'cuil' => '27280335148', 'fecha_nacimiento' => '1949-01-17', 'telefono' => '2974123456', 'fecha_ingreso' => '2026-09-22', 'fecha_baja' => null, 'created_at' => '2026-09-23 03:14:18', 'updated_at' => '2026-09-23 03:14:18'],
            ['id' => 3, 'user_id' => 4, 'legajo' => 'EMP003', 'tipo_documento_id' => 3, 'nro_doc' => '12345676', 'cuil' => '20100000017', 'fecha_nacimiento' => '1961-09-15', 'telefono' => '2974124578', 'fecha_ingreso' => '2026-09-22', 'fecha_baja' => null, 'created_at' => '2026-09-23 03:27:42', 'updated_at' => '2026-09-25 03:10:12'],
            ['id' => 4, 'user_id' => 6, 'legajo' => 'EMP004', 'tipo_documento_id' => 3, 'nro_doc' => '12354687', 'cuil' => '20100000025', 'fecha_nacimiento' => '1961-11-20', 'telefono' => '2971447758', 'fecha_ingreso' => '2026-09-22', 'fecha_baja' => null, 'created_at' => '2026-09-23 04:44:54', 'updated_at' => '2026-09-23 04:44:54'],
            ['id' => 5, 'user_id' => 7, 'legajo' => 'EMP005', 'tipo_documento_id' => 3, 'nro_doc' => '32653222', 'cuil' => '20100000033', 'fecha_nacimiento' => '1959-08-06', 'telefono' => '2974717171', 'fecha_ingreso' => '2026-09-22', 'fecha_baja' => null, 'created_at' => '2026-09-23 05:08:04', 'updated_at' => '2026-09-23 05:08:04'],
            ['id' => 6, 'user_id' => 8, 'legajo' => 'EMP006', 'tipo_documento_id' => 3, 'nro_doc' => '12451425', 'cuil' => '20100000041', 'fecha_nacimiento' => '1987-06-24', 'telefono' => '2977989898', 'fecha_ingreso' => '2026-09-22', 'fecha_baja' => null, 'created_at' => '2026-09-23 05:53:03', 'updated_at' => '2026-09-23 19:20:44'],
            ['id' => 7, 'user_id' => 10, 'legajo' => 'EMP007', 'tipo_documento_id' => 3, 'nro_doc' => '36256256', 'cuil' => '27100000054', 'fecha_nacimiento' => '1981-09-23', 'telefono' => null, 'fecha_ingreso' => '2026-09-23', 'fecha_baja' => null, 'created_at' => '2026-09-23 10:01:51', 'updated_at' => '2026-09-23 10:01:51'],
            ['id' => 8, 'user_id' => 11, 'legajo' => 'EMP008', 'tipo_documento_id' => 3, 'nro_doc' => '85456456', 'cuil' => '27100000062', 'fecha_nacimiento' => '1951-10-23', 'telefono' => null, 'fecha_ingreso' => '2026-09-23', 'fecha_baja' => null, 'created_at' => '2026-09-23 10:05:00', 'updated_at' => '2026-09-23 10:05:00'],
            ['id' => 9, 'user_id' => 12, 'legajo' => 'EMP009', 'tipo_documento_id' => 3, 'nro_doc' => '36236236', 'cuil' => '23100000016', 'fecha_nacimiento' => '1961-08-22', 'telefono' => null, 'fecha_ingreso' => '2026-09-23', 'fecha_baja' => null, 'created_at' => '2026-09-23 10:07:02', 'updated_at' => '2026-09-23 10:07:02'],
            ['id' => 10, 'user_id' => 13, 'legajo' => 'EMP010', 'tipo_documento_id' => 3, 'nro_doc' => '99478459', 'cuil' => '23100000024', 'fecha_nacimiento' => '1959-08-11', 'telefono' => null, 'fecha_ingreso' => '2026-09-23', 'fecha_baja' => null, 'created_at' => '2026-09-23 10:09:15', 'updated_at' => '2026-09-23 10:09:15'],
            ['id' => 11, 'user_id' => 14, 'legajo' => 'EMP011', 'tipo_documento_id' => 3, 'nro_doc' => '66256237', 'cuil' => '23100000032', 'fecha_nacimiento' => '1962-06-25', 'telefono' => null, 'fecha_ingreso' => '2026-09-23', 'fecha_baja' => null, 'created_at' => '2026-09-23 10:10:52', 'updated_at' => '2026-09-23 10:10:52'],
            ['id' => 12, 'user_id' => 15, 'legajo' => 'EMP012', 'tipo_documento_id' => 3, 'nro_doc' => '77485485', 'cuil' => '24100000055', 'fecha_nacimiento' => '1953-05-17', 'telefono' => null, 'fecha_ingreso' => '2026-09-24', 'fecha_baja' => null, 'created_at' => '2026-09-24 08:32:30', 'updated_at' => '2026-09-24 08:32:30'],
        ], ['id']);
    }
}
