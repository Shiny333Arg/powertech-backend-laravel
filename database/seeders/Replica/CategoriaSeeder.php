<?php

namespace Database\Seeders\Replica;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Categorias del catalogo.
 */
class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('categorias')->upsert([
            ['id' => 1, 'nombre' => 'Almacenamiento', 'slug' => 'almacenamiento', 'orden' => 0, 'activo' => 1, 'created_at' => '2026-09-23 08:33:17', 'updated_at' => '2026-09-23 19:16:42'],
            ['id' => 2, 'nombre' => 'Procesador', 'slug' => 'procesador', 'orden' => 0, 'activo' => 1, 'created_at' => '2026-09-23 08:33:22', 'updated_at' => '2026-09-23 08:33:22'],
            ['id' => 3, 'nombre' => 'Placa de video', 'slug' => 'placa-de-video', 'orden' => 0, 'activo' => 1, 'created_at' => '2026-09-23 08:33:26', 'updated_at' => '2026-09-23 08:33:26'],
            ['id' => 4, 'nombre' => 'RAM', 'slug' => 'ram', 'orden' => 0, 'activo' => 1, 'created_at' => '2026-09-23 08:33:33', 'updated_at' => '2026-09-23 08:33:33'],
            ['id' => 5, 'nombre' => 'Fuente de alimentación', 'slug' => 'fuente-de-alimentacion', 'orden' => 0, 'activo' => 1, 'created_at' => '2026-09-23 08:33:40', 'updated_at' => '2026-09-23 08:33:40'],
            ['id' => 6, 'nombre' => 'Gabinete', 'slug' => 'gabinete', 'orden' => 0, 'activo' => 1, 'created_at' => '2026-09-23 08:33:43', 'updated_at' => '2026-09-23 09:13:45'],
            ['id' => 7, 'nombre' => 'Mouse', 'slug' => 'mouse', 'orden' => 0, 'activo' => 1, 'created_at' => '2026-09-23 09:44:10', 'updated_at' => '2026-09-23 09:44:10'],
            ['id' => 8, 'nombre' => 'Teclado', 'slug' => 'teclado', 'orden' => 0, 'activo' => 1, 'created_at' => '2026-09-23 09:44:14', 'updated_at' => '2026-09-23 09:44:14'],
            ['id' => 9, 'nombre' => 'Mousepad', 'slug' => 'mousepad', 'orden' => 0, 'activo' => 1, 'created_at' => '2026-09-23 09:44:19', 'updated_at' => '2026-09-23 09:44:19'],
            ['id' => 10, 'nombre' => 'Refrigeración', 'slug' => 'refrigeracion', 'orden' => 0, 'activo' => 1, 'created_at' => '2026-09-23 09:44:27', 'updated_at' => '2026-09-24 08:26:50'],
            ['id' => 11, 'nombre' => 'Cable', 'slug' => 'cable', 'orden' => 0, 'activo' => 1, 'created_at' => '2026-09-23 09:44:37', 'updated_at' => '2026-09-23 09:44:37'],
            ['id' => 12, 'nombre' => 'Parlante', 'slug' => 'parlante', 'orden' => 0, 'activo' => 1, 'created_at' => '2026-09-24 11:46:57', 'updated_at' => '2026-09-24 11:46:57'],
            ['id' => 13, 'nombre' => 'Monitor', 'slug' => 'monitor', 'orden' => 0, 'activo' => 1, 'created_at' => '2026-09-25 06:14:59', 'updated_at' => '2026-09-25 06:14:59'],
            ['id' => 14, 'nombre' => 'PC Pre-armada', 'slug' => 'pc-pre-armada', 'orden' => 0, 'activo' => 1, 'created_at' => '2026-09-25 06:16:29', 'updated_at' => '2026-09-25 06:16:29'],
            ['id' => 15, 'nombre' => 'Notebook', 'slug' => 'notebook', 'orden' => 0, 'activo' => 1, 'created_at' => '2026-09-25 06:31:43', 'updated_at' => '2026-09-25 06:31:43'],
        ], ['id']);
    }
}
