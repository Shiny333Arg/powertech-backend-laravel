<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\UpsertsMasterData;
use Illuminate\Database\Seeder;

/**
 * Catálogo de permisos.
 *
 * Son claves nombradas por acción, no banderas CRUD por módulo (ver A.3 del modelo
 * de datos): lo que no está declarado acá no existe, y lo que no se asigna
 * explícitamente a un rol no se puede hacer.
 *
 * `modulo` es solo el agrupador de la pantalla de asignación.
 */
class PermisoSeeder extends Seeder
{
    use UpsertsMasterData;

    /**
     * modulo => [ clave => descripcion ]
     *
     * @var array<string, array<string, string>>
     */
    public const CATALOGO = [
        'usuarios' => [
            'usuario.ver' => 'Consultar cuentas de usuario',
            'usuario.crear' => 'Crear cuentas de usuario',
            'usuario.editar' => 'Editar cuentas de usuario',
            'usuario.baja' => 'Dar de baja lógica una cuenta de usuario',
            'usuario.forzar_cambio_password' => 'Forzar el cambio de contraseña en el próximo ingreso',
        ],

        'roles' => [
            'rol.ver' => 'Consultar roles',
            'rol.crear' => 'Crear roles',
            'rol.editar' => 'Editar roles no marcados como de sistema',
            'rol.eliminar' => 'Eliminar roles no marcados como de sistema',
            'permiso.asignar' => 'Asignar permisos a un rol',
        ],

        'empleados' => [
            'empleado.ver' => 'Consultar la ficha de empleados',
            'empleado.crear' => 'Dar de alta un empleado con sus datos personales y fiscales',
            'empleado.editar' => 'Editar los datos de un empleado',
            'empleado.baja' => 'Registrar la fecha de baja de un empleado',
        ],

        'clientes' => [
            'cliente.ver' => 'Consultar clientes',
            'cliente.crear' => 'Dar de alta un cliente',
            'cliente.editar' => 'Editar los datos fiscales y de contacto de un cliente',
            'cliente.baja' => 'Dar de baja lógica un cliente',
        ],

        'catalogo' => [
            'producto.ver' => 'Consultar productos',
            'producto.crear' => 'Dar de alta productos',
            'producto.editar' => 'Editar productos, precio y punto de reposición',
            'producto.baja' => 'Dar de baja lógica un producto',
            'producto.publicar' => 'Publicar o despublicar un producto en la tienda',
            'categoria.ver' => 'Consultar categorías',
            'categoria.crear' => 'Crear categorías',
            'categoria.editar' => 'Editar categorías',
            'categoria.baja' => 'Dar de baja lógica una categoría',
            'categoria.eliminar' => 'Eliminar físicamente una categoría sin productos asociados',
        ],

        'stock' => [
            'stock.ver' => 'Ver los tres valores de stock de un producto',
            'stock.movimiento_ver' => 'Consultar el histórico de movimientos de stock',
            'stock.ajustar' => 'Registrar ajustes de inventario, siempre con motivo',
            'stock.carga_inicial' => 'Cargar el conteo físico inicial previo al go-live',
        ],

        'proveedores' => [
            'proveedor.ver' => 'Consultar proveedores',
            'proveedor.crear' => 'Dar de alta proveedores',
            'proveedor.editar' => 'Editar proveedores',
            'proveedor.baja' => 'Dar de baja lógica un proveedor',
        ],

        'compras' => [
            'compra.ver' => 'Consultar órdenes de compra',
            'compra.crear' => 'Crear órdenes de compra manuales',
            'compra.editar' => 'Editar una orden de compra en borrador',
            'compra.aprobar' => 'Aprobar una orden de compra',
            'compra.enviar' => 'Marcar como enviada una orden aprobada',
            'compra.recibir' => 'Registrar la recepción, total o parcial, de una orden',
            'compra.cancelar' => 'Cancelar una orden de compra',
        ],

        'promociones' => [
            'promocion.ver' => 'Consultar promociones vigentes',
            'promocion.crear' => 'Crear promociones y cargar su banner',
            'promocion.editar' => 'Editar promociones',
            'promocion.baja' => 'Dar de baja lógica una promoción',
        ],

        'ventas' => [
            'venta.ver' => 'Consultar todas las ventas',
            'venta.ver_propias' => 'Consultar únicamente las ventas propias',
            'presupuesto.crear' => 'Generar presupuestos',
            'presupuesto.validar' => 'Validar un presupuesto para habilitar su cobro',
            'venta.crear' => 'Registrar una venta de mostrador',
            'venta.cancelar' => 'Cancelar una venta antes de la entrega',
            'venta.devolver' => 'Registrar una devolución total o parcial',
        ],

        'caja' => [
            'pago.ver' => 'Consultar todos los cobros',
            'pago.ver_propios' => 'Consultar únicamente los cobros propios',
            'pago.registrar' => 'Registrar el cobro de una venta confirmada',
            'pago.devolver' => 'Registrar la devolución de un pago',
            'caja.arqueo' => 'Realizar el arqueo de caja',
        ],

        'facturacion' => [
            'comprobante.ver' => 'Consultar comprobantes emitidos',
            'comprobante.emitir' => 'Emitir la factura al acreditarse el pago',
            'comprobante.nota_credito' => 'Emitir notas de crédito',
            'comprobante.reintentar' => 'Reintentar la autorización de un comprobante rechazado',
            'afip.solicitud_ver' => 'Consultar el log de solicitudes a ARCA',
        ],

        'logistica' => [
            'venta.preparar' => 'Pasar una venta pagada a preparación',
            'venta.entregar' => 'Registrar la entrega de la mercadería',
            'envio.ver' => 'Consultar envíos',
            'envio.despachar' => 'Despachar un envío',
            'envio.actualizar' => 'Actualizar el estado y el tracking de un envío',
        ],

        'tienda' => [
            'tienda.catalogo_ver' => 'Ver el catálogo publicado y las promociones vigentes',
            'tienda.carrito_gestionar' => 'Administrar el propio carrito de compras',
            'tienda.comprar' => 'Confirmar la compra y pagar en línea',
            'tienda.pedido_ver' => 'Ver los pedidos propios y el tracking del envío',
            'tienda.perfil_editar' => 'Editar los datos y direcciones propias',
        ],

        'dashboard' => [
            'dashboard.administrativo' => 'Dashboard de gestión: reposición, aprobaciones y pendientes de stock',
            'dashboard.vendedor' => 'Dashboard del vendedor: promociones activas e historial propio',
            'dashboard.cajero' => 'Dashboard del cajero: historial de cobros propios',
        ],
    ];

    public function run(): void
    {
        $total = 0;

        foreach (self::CATALOGO as $modulo => $permisos) {
            foreach ($permisos as $clave => $descripcion) {
                $this->upsertPorClave(
                    'permisos',
                    ['clave' => $clave],
                    ['modulo' => $modulo, 'descripcion' => $descripcion]
                );
                $total++;
            }
        }

        $this->command?->info("Permisos: {$total} claves reconciliadas.");
    }

    /** Todas las claves declaradas, en un solo array plano. */
    public static function todasLasClaves(): array
    {
        return array_merge(...array_map('array_keys', array_values(self::CATALOGO)));
    }
}
