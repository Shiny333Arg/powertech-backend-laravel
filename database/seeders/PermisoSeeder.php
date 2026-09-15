<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\UpsertPorClaveNatural;
use Illuminate\Database\Seeder;

/**
 * Catalogo de permisos. Clave natural: clave.
 *
 * Los permisos son claves nombradas, no banderas CRUD por modulo: lo que no
 * se asigna explicitamente no se puede hacer (Modelo de datos A.3).
 *
 * ORIGEN DE CADA CLAVE
 *   [R 3.x]  derivada de una capacidad enunciada en Requerimientos, seccion 3.
 *   [R 4.x]  derivada del circuito de venta, seccion 4.
 *   [DER]    derivada del modelo de datos (acciones que el esquema contempla).
 *   [NUEVO]  no enunciada en los documentos; agregada para cubrir una accion
 *            que el sistema tiene que poder ejecutar. Revisar.
 */
class PermisoSeeder extends Seeder
{
    use UpsertPorClaveNatural;

    /**
     * @return array<string, array<string, string>>  modulo => [clave => descripcion]
     */
    public static function catalogo(): array
    {
        return [
            'catalogo' => [
                'producto.ver'          => 'Ver el catalogo de productos y su stock disponible.',      // R 3.2 / R 3.3
                'producto.crear'        => 'Dar de alta productos.',                                   // R 3.2
                'producto.editar'       => 'Modificar datos de un producto.',                          // R 3.2
                'producto.baja'         => 'Dar de baja o reactivar un producto (activo).',            // R 3.2 / R 9
                'producto.publicar'     => 'Publicar o despublicar un producto en la tienda.',         // R 9
                'categoria.ver'         => 'Ver las categorias.',                                       // R 3.2
                'categoria.crear'       => 'Crear categorias.',                                         // R 3.2
                'categoria.editar'      => 'Modificar categorias.',                                     // R 3.2
                'categoria.baja'        => 'Dar de baja o reactivar una categoria.',                    // R 3.2 / R 9
                'categoria.eliminar'    => 'Eliminar fisicamente una categoria sin productos.',         // R 9
            ],

            'proveedores' => [
                'proveedor.ver'         => 'Ver proveedores.',                                          // R 3.2
                'proveedor.crear'       => 'Dar de alta proveedores.',                                  // R 3.2
                'proveedor.editar'      => 'Modificar proveedores.',                                    // R 3.2
                'proveedor.baja'        => 'Dar de baja o reactivar un proveedor.',                     // R 3.2 / R 9
            ],

            'promociones' => [
                'promocion.ver'         => 'Ver las promociones vigentes.',                             // R 3.2 / R 3.3
                'promocion.crear'       => 'Crear promociones, con su banner.',                         // R 3.2
                'promocion.editar'      => 'Modificar promociones.',                                    // R 3.2
                'promocion.baja'        => 'Dar de baja o reactivar una promocion.',                    // R 3.2 / R 9
            ],

            'empleados' => [
                'empleado.ver'               => 'Ver empleados y sus datos personales y fiscales.',     // R 3.2
                'empleado.crear'             => 'Dar de alta empleados.',                               // R 3.2
                'empleado.editar'            => 'Modificar datos de un empleado.',                      // R 3.2
                'empleado.baja'              => 'Dar de baja o reactivar un empleado.',                 // R 3.2 / R 9
                'empleado.forzar_password'   => 'Forzar el cambio de contrasena en el proximo ingreso.', // R 2.1
            ],

            'clientes' => [
                'cliente.ver'           => 'Ver la ficha de clientes.',                                 // NUEVO (implicito en R 4.1)
                'cliente.crear'         => 'Registrar un cliente de mostrador.',                        // NUEVO (implicito en R 4.1)
                'cliente.editar'        => 'Modificar datos de un cliente.',                            // NUEVO
                'cliente.baja'          => 'Dar de baja o reactivar un cliente.',                       // R 9
            ],

            'compras' => [
                'compra.ver'            => 'Ver ordenes de compra.',                                    // R 3.2
                'compra.crear'          => 'Crear una orden de compra manual.',                         // R 6
                'compra.aprobar'        => 'Aprobar una orden de compra.',                              // R 3.2 / R 6
                'compra.enviar'         => 'Marcar una orden aprobada como enviada al proveedor.',      // DER (estado enviada)
                'compra.recibir'        => 'Registrar la recepcion, total o parcial, de una orden.',    // R 6
                'compra.cancelar'       => 'Cancelar una orden de compra.',                             // DER (estado cancelada)
            ],

            'stock' => [
                'stock.ver'             => 'Ver stock fisico y comprometido, y el historico de movimientos.', // R 5.1
                'stock.ajustar'         => 'Registrar ajustes de inventario, siempre con motivo.',      // R 3.2
            ],

            'ventas' => [
                'venta.ver'             => 'Ver todas las ventas y presupuestos.',                      // NUEVO
                'venta.ver_propias'     => 'Ver el historial propio de ventas.',                        // R 3.3
                'venta.crear'           => 'Generar presupuestos y ventas de mostrador.',               // R 3.3
                'presupuesto.validar'   => 'Validar un presupuesto para habilitar su cobro.',           // R 3.3 / R 4.1
                'venta.cancelar'        => 'Cancelar una venta antes de la entrega.',                   // R 4.3 -> vendedor
                'venta.preparar'        => 'Preparar una venta pagada para despacho o retiro.',         // R 4.2 -> vendedor
                'venta.entregar'        => 'Registrar la salida de la mercaderia: despacho o retiro.',  // R 4.1 -> vendedor
                'venta.devolver'        => 'Registrar una devolucion, total o parcial.',                // R 4.3 -> vendedor
            ],

            'cobros' => [
                'cobro.registrar'       => 'Cobrar una venta confirmada.',                              // R 3.4
                'cobro.ver'             => 'Ver todos los cobros.',                                     // NUEVO
                'cobro.ver_propios'     => 'Ver el historial propio de cobros.',                        // R 3.4
            ],

            'facturacion' => [
                'comprobante.ver'       => 'Ver comprobantes emitidos y su estado ante ARCA.',          // R 7
                'comprobante.reintentar'=> 'Reintentar la autorizacion de un comprobante rechazado.',   // DER (afip_solicitudes)
                'comprobante.nota_credito' => 'Emitir una nota de credito asociada.',                   // R 7 (sin rol asignado)
            ],

            'dashboards' => [
                'dashboard.administrativo' => 'Dashboard de gestion: mas vendidos, ventas del dia y del mes, ordenes pendientes, stock bajo punto de reposicion y ventas pendientes de stock.', // R 3.2
                'dashboard.vendedor'       => 'Dashboard de promociones activas e historial propio de ventas.', // R 3.3
                'dashboard.cajero'         => 'Dashboard de historial propio de cobros.',               // R 3.4
            ],

            'tienda' => [
                'tienda.catalogo.ver'   => 'Navegar el catalogo publicado y ver disponibilidad.',       // R 3.5
                'tienda.carrito'        => 'Administrar el carrito de compras.',                        // R 3.5
                'tienda.comprar'        => 'Confirmar la compra y pagar por Mercado Pago.',             // R 3.5
                'tienda.envio.ver'      => 'Ver el tracking de los envios propios.',                    // R 3.5
                'tienda.perfil.editar'  => 'Editar los datos personales propios.',                      // R 3.5
            ],

            'sistema' => [
                'rol.gestionar'         => 'Administrar roles y su matriz de permisos.',                // DER (roles, rol_permiso)
                'punto_venta.gestionar' => 'Administrar los puntos de venta habilitados.',              // DER (puntos_venta)
            ],
        ];
    }

    public function run(): void
    {
        foreach (static::catalogo() as $modulo => $permisos) {
            foreach ($permisos as $clave => $descripcion) {
                $this->upsert('permisos', ['clave' => $clave], [
                    'modulo'      => $modulo,
                    'descripcion' => $descripcion,
                ]);
            }
        }
    }
}
