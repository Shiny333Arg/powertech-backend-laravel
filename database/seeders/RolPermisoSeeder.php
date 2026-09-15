<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Matriz rol-permiso. Se resuelve por clave natural (roles.nombre y
 * permisos.clave) y se inserta con insertOrIgnore: agrega lo que falta y
 * no borra lo que un administrador haya asignado a mano despues.
 *
 * ADVERTENCIA: por eso mismo, quitar una clave de esta matriz NO revoca el
 * permiso en una base ya sembrada. Hay que revocarlo desde la pantalla de
 * roles, o cambiar insertOrIgnore por un sync() si se prefiere que el seeder
 * sea la unica fuente de verdad.
 *
 * Lo que NO esta en los documentos aparece marcado abajo.
 */
class RolPermisoSeeder extends Seeder
{
    /**
     * El administrador recibe todos los permisos de ambito gestion. Se excluye
     * el modulo 'tienda' porque son acciones del comprador, no de la gestion.
     */
    private const MODULOS_EXCLUIDOS_ADMINISTRADOR = ['tienda'];

    /**
     * @return array<string, list<string>>  nombre de rol => claves de permiso
     */
    public static function matriz(): array
    {
        return [
            // Requerimientos 3.2
            'administrativo' => [
                'producto.ver', 'producto.crear', 'producto.editar', 'producto.baja', 'producto.publicar',
                'categoria.ver', 'categoria.crear', 'categoria.editar', 'categoria.baja', 'categoria.eliminar',
                'proveedor.ver', 'proveedor.crear', 'proveedor.editar', 'proveedor.baja',
                'promocion.ver', 'promocion.crear', 'promocion.editar', 'promocion.baja',
                'empleado.ver', 'empleado.crear', 'empleado.editar', 'empleado.baja', 'empleado.forzar_password',
                'cliente.ver', 'cliente.crear', 'cliente.editar', 'cliente.baja',
                'compra.ver', 'compra.crear', 'compra.aprobar', 'compra.enviar', 'compra.recibir', 'compra.cancelar',
                'stock.ver', 'stock.ajustar',
                'venta.ver',
                'comprobante.ver',
                'dashboard.administrativo',
            ],

            // Requerimientos 3.3, mas el circuito de venta completo (R 4).
            'vendedor' => [
                'producto.ver',          // ve el stock disponible al cargar la linea (R 5.1)
                'categoria.ver',
                'promocion.ver',
                'cliente.ver',           // NUEVO: no enunciado, pero R 4.1 admite cliente registrado
                'cliente.crear',         // NUEVO: alguien tiene que registrar al cliente de mostrador

                // Modulo 'ventas' completo: el circuito de la venta es del vendedor,
                // de punta a punta, incluida la entrega de la mercaderia.
                'venta.ver',
                'venta.ver_propias',
                'venta.crear',
                'presupuesto.validar',
                'venta.cancelar',
                'venta.preparar',
                'venta.entregar',
                'venta.devolver',

                'dashboard.vendedor',
            ],

            // Requerimientos 3.4
            'cajero' => [
                'venta.ver',             // NUEVO: necesita encontrar la venta a cobrar
                'cobro.registrar',
                'cobro.ver_propios',
                'comprobante.ver',       // NUEVO: la factura se emite al acreditarse el pago
                'dashboard.cajero',
            ],

            // Requerimientos 3.5
            'cliente' => [
                'tienda.catalogo.ver',
                'tienda.carrito',
                'tienda.comprar',
                'tienda.envio.ver',
                'tienda.perfil.editar',
            ],
        ];
    }

    public function run(): void
    {
        $roles = DB::table('roles')->pluck('id', 'nombre');
        $permisos = DB::table('permisos')->pluck('id', 'clave');

        if ($roles->isEmpty() || $permisos->isEmpty()) {
            throw new RuntimeException('RolPermisoSeeder: corre despues de RolSeeder y PermisoSeeder.');
        }

        $matriz = static::matriz();

        // El administrador: todo lo que no sea del ambito tienda (R 3.1).
        $matriz['administrador'] = DB::table('permisos')
            ->whereNotIn('modulo', self::MODULOS_EXCLUIDOS_ADMINISTRADOR)
            ->pluck('clave')
            ->all();

        $filas = [];

        foreach ($matriz as $nombreRol => $claves) {
            if (! isset($roles[$nombreRol])) {
                throw new RuntimeException("RolPermisoSeeder: no existe el rol '{$nombreRol}'.");
            }

            foreach ($claves as $clave) {
                if (! isset($permisos[$clave])) {
                    throw new RuntimeException("RolPermisoSeeder: no existe el permiso '{$clave}'.");
                }

                $filas[] = [
                    'rol_id'     => $roles[$nombreRol],
                    'permiso_id' => $permisos[$clave],
                ];
            }
        }

        // PK compuesta: insertOrIgnore descarta lo ya asignado sin fallar.
        DB::table('rol_permiso')->insertOrIgnore($filas);

        $this->avisarPermisosSinRol($permisos);
    }

    /**
     * Lista los permisos que quedaron solo en manos del administrador porque
     * los requerimientos no dicen que rol los ejerce. Es informativo.
     */
    private function avisarPermisosSinRol($permisos): void
    {
        $asignados = collect(static::matriz())->flatten()->unique();
        $huerfanos = $permisos->keys()
            ->reject(fn ($clave) => $asignados->contains($clave))
            ->reject(fn ($clave) => str_starts_with($clave, 'tienda.'))
            ->values();

        if ($huerfanos->isEmpty()) {
            return;
        }

        $this->command?->warn('  Permisos asignados solo al administrador (sin rol definido en los requerimientos):');

        foreach ($huerfanos as $clave) {
            $this->command?->line("    - {$clave}");
        }
    }
}
