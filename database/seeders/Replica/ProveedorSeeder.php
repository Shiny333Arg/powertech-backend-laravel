<?php

namespace Database\Seeders\Replica;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Proveedores. Las direcciones fiscales van en DireccionSeeder.
 */
class ProveedorSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('proveedores')->upsert([
            ['id' => 1, 'razon_social' => 'Nvidia', 'cuit' => '27100000011', 'condicion_iva_id' => 3, 'email' => 'nvidia@mail.com', 'telefono' => '2971245781', 'contacto' => 'Jensen Huang', 'canal_pedido' => 'email', 'portal_url' => null, 'plazo_entrega_dias' => 7, 'activo' => 1, 'created_at' => '2026-09-23 08:49:20', 'updated_at' => '2026-09-23 08:49:20'],
            ['id' => 2, 'razon_social' => 'AMD', 'cuit' => '27100000038', 'condicion_iva_id' => 3, 'email' => 'amd@mail.com', 'telefono' => '2978595959', 'contacto' => 'Lisa Su', 'canal_pedido' => 'email', 'portal_url' => null, 'plazo_entrega_dias' => 7, 'activo' => 1, 'created_at' => '2026-09-23 08:56:11', 'updated_at' => '2026-09-23 08:56:11'],
            ['id' => 3, 'razon_social' => 'Logitech', 'cuit' => '27100000046', 'condicion_iva_id' => 3, 'email' => 'logitech@mail.com', 'telefono' => '2975474747', 'contacto' => 'Carlitos', 'canal_pedido' => 'email', 'portal_url' => null, 'plazo_entrega_dias' => 3, 'activo' => 1, 'created_at' => '2026-09-23 09:00:23', 'updated_at' => '2026-09-23 19:20:03'],
            ['id' => 4, 'razon_social' => 'Corsair', 'cuit' => '24100000047', 'condicion_iva_id' => 1, 'email' => 'corsair@mail.com', 'telefono' => '2961456456', 'contacto' => 'Corsair Dueño', 'canal_pedido' => 'email', 'portal_url' => null, 'plazo_entrega_dias' => 5, 'activo' => 1, 'created_at' => '2026-09-24 08:37:32', 'updated_at' => '2026-09-24 08:37:32'],
            ['id' => 5, 'razon_social' => 'MSI', 'cuit' => '24100000012', 'condicion_iva_id' => 2, 'email' => 'msi@mail.com', 'telefono' => '2971459489', 'contacto' => 'MSI dueño', 'canal_pedido' => 'email', 'portal_url' => null, 'plazo_entrega_dias' => 7, 'activo' => 1, 'created_at' => '2026-09-25 06:21:34', 'updated_at' => '2026-09-25 06:21:34'],
            ['id' => 6, 'razon_social' => 'Asus', 'cuit' => '24100000020', 'condicion_iva_id' => 1, 'email' => 'asus@mail.com', 'telefono' => '2971256254', 'contacto' => 'Asus Tado', 'canal_pedido' => 'email', 'portal_url' => null, 'plazo_entrega_dias' => 6, 'activo' => 1, 'created_at' => '2026-09-25 06:22:45', 'updated_at' => '2026-09-25 06:22:45'],
            ['id' => 7, 'razon_social' => 'Genius', 'cuit' => '23100000059', 'condicion_iva_id' => 4, 'email' => 'genius@mail.com', 'telefono' => '2973126456', 'contacto' => 'Genio Genio', 'canal_pedido' => 'manual', 'portal_url' => null, 'plazo_entrega_dias' => 6, 'activo' => 1, 'created_at' => '2026-09-25 06:25:11', 'updated_at' => '2026-09-25 06:25:11'],
            ['id' => 8, 'razon_social' => 'Samsung', 'cuit' => '23100000040', 'condicion_iva_id' => 3, 'email' => 'samsung@mail.com', 'telefono' => '5979125125', 'contacto' => 'chang chung', 'canal_pedido' => 'portal_externo', 'portal_url' => 'https://www.samsung.com/', 'plazo_entrega_dias' => 7, 'activo' => 1, 'created_at' => '2026-09-25 06:26:19', 'updated_at' => '2026-09-25 06:26:19'],
            ['id' => 9, 'razon_social' => 'HyperX', 'cuit' => '23100000032', 'condicion_iva_id' => 3, 'email' => 'hyperx@mail.com', 'telefono' => '1452632633', 'contacto' => 'hyper dueño', 'canal_pedido' => 'email', 'portal_url' => null, 'plazo_entrega_dias' => 6, 'activo' => 1, 'created_at' => '2026-09-25 06:28:40', 'updated_at' => '2026-09-25 06:28:40'],
            ['id' => 10, 'razon_social' => 'Lenovo', 'cuit' => '23100000024', 'condicion_iva_id' => 2, 'email' => 'lenovo@mail.com', 'telefono' => null, 'contacto' => 'Lenovin', 'canal_pedido' => 'portal_externo', 'portal_url' => 'https://www.lenovo.com/', 'plazo_entrega_dias' => 6, 'activo' => 1, 'created_at' => '2026-09-25 06:29:35', 'updated_at' => '2026-09-25 06:29:35'],
            ['id' => 11, 'razon_social' => 'HP', 'cuit' => '23100000016', 'condicion_iva_id' => 2, 'email' => 'hp@mail.com', 'telefono' => null, 'contacto' => 'el hp', 'canal_pedido' => 'email', 'portal_url' => null, 'plazo_entrega_dias' => null, 'activo' => 1, 'created_at' => '2026-09-25 06:30:14', 'updated_at' => '2026-09-25 06:30:14'],
        ], ['id']);
    }
}
