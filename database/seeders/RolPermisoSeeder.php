<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Matriz de asignación rol → permisos.
 *
 * La matriz declarada acá es la autoridad para los cinco roles de sistema: cada
 * corrida agrega lo que falta y quita lo que sobra, de modo que el estado de la
 * base converge a lo que está versionado en el repositorio. Los roles creados
 * después desde la aplicación no se tocan.
 *
 * El rol administrador se resuelve por comprensión: recibe todo el catálogo, así
 * que un permiso nuevo queda asignado sin editar esta clase.
 */
class RolPermisoSeeder extends Seeder
{
    /**
     * @var array<string, list<string>>
     */
    public const MATRIZ = [
        RolSeeder::ADMINISTRATIVO => [
            // Empleados y sus cuentas
            'usuario.ver', 'usuario.crear', 'usuario.editar', 'usuario.baja',
            'usuario.forzar_cambio_password',
            'empleado.ver', 'empleado.crear', 'empleado.editar', 'empleado.baja',
            'rol.ver',

            // Clientes
            'cliente.ver', 'cliente.crear', 'cliente.editar', 'cliente.baja',

            // Catálogo
            'producto.ver', 'producto.crear', 'producto.editar', 'producto.baja', 'producto.publicar',
            'categoria.ver', 'categoria.crear', 'categoria.editar', 'categoria.baja', 'categoria.eliminar',

            // Stock
            'stock.ver', 'stock.movimiento_ver', 'stock.ajustar', 'stock.carga_inicial',

            // Proveedores y compras
            'proveedor.ver', 'proveedor.crear', 'proveedor.editar', 'proveedor.baja',
            'compra.ver', 'compra.crear', 'compra.editar', 'compra.aprobar',
            'compra.enviar', 'compra.recibir', 'compra.cancelar',

            // Promociones: única fuente de descuento del sistema
            'promocion.ver', 'promocion.crear', 'promocion.editar', 'promocion.baja',

            // Ventas: consulta y excepciones
            'venta.ver', 'venta.cancelar', 'venta.devolver',

            // Facturación
            'comprobante.ver', 'comprobante.nota_credito', 'comprobante.reintentar',
            'afip.solicitud_ver',

            // Logística
            'venta.preparar', 'venta.entregar',
            'envio.ver', 'envio.despachar', 'envio.actualizar',

            'dashboard.administrativo',
        ],

        RolSeeder::VENDEDOR => [
            'producto.ver', 'categoria.ver',
            'stock.ver',                       // ve el disponible y la advertencia de preventa
            'cliente.ver', 'cliente.crear', 'cliente.editar',
            'presupuesto.crear', 'presupuesto.validar',
            'venta.crear', 'venta.ver_propias',
            'promocion.ver',
            'dashboard.vendedor',
        ],

        RolSeeder::CAJERO => [
            'venta.ver',                       // necesita encontrar la venta a cobrar
            'cliente.ver',
            'pago.registrar', 'pago.ver_propios',
            'caja.arqueo',
            'comprobante.emitir', 'comprobante.ver',
            'dashboard.cajero',
        ],

        RolSeeder::CLIENTE => [
            'tienda.catalogo_ver',
            'tienda.carrito_gestionar',
            'tienda.comprar',
            'tienda.pedido_ver',
            'tienda.perfil_editar',
        ],
    ];

    public function run(): void
    {
        $rolesPorNombre = DB::table('roles')->pluck('id', 'nombre');
        $permisosPorClave = DB::table('permisos')->pluck('id', 'clave');

        $matriz = self::MATRIZ;
        $matriz[RolSeeder::ADMINISTRADOR] = PermisoSeeder::todasLasClaves();

        foreach ($matriz as $nombreRol => $claves) {
            $rolId = $rolesPorNombre[$nombreRol] ?? null;

            if ($rolId === null) {
                throw new \RuntimeException("Falta el rol '{$nombreRol}'. Corré RolSeeder primero.");
            }

            $faltantes = array_diff($claves, $permisosPorClave->keys()->all());
            if ($faltantes !== []) {
                throw new \RuntimeException(
                    "Permisos inexistentes asignados a '{$nombreRol}': ".implode(', ', $faltantes)
                );
            }

            $deseados = array_values(array_unique(
                array_map(fn (string $clave) => (int) $permisosPorClave[$clave], $claves)
            ));

            $actuales = DB::table('rol_permiso')
                ->where('rol_id', $rolId)
                ->pluck('permiso_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $agregar = array_diff($deseados, $actuales);
            $quitar = array_diff($actuales, $deseados);

            if ($agregar !== []) {
                DB::table('rol_permiso')->insert(array_map(
                    fn (int $permisoId) => ['rol_id' => $rolId, 'permiso_id' => $permisoId],
                    array_values($agregar)
                ));
            }

            if ($quitar !== []) {
                DB::table('rol_permiso')
                    ->where('rol_id', $rolId)
                    ->whereIn('permiso_id', array_values($quitar))
                    ->delete();
            }

            $this->command?->info(sprintf(
                'rol_permiso: %-15s %d permisos (+%d / -%d).',
                $nombreRol, count($deseados), count($agregar), count($quitar)
            ));
        }
    }
}
