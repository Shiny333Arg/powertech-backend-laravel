<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Los permisos del modulo de compras, y su asignacion a los roles.
 *
 * Los permisos son claves nombradas, no banderas CRUD: lo que no se asigna
 * explicitamente no se puede hacer. Por eso cada accion del circuito tiene la
 * suya, incluidas 'aprobar' y 'enviar', que no son ediciones.
 *
 * <b>compra.aprobar_propias merece una explicacion.</b> OrdenCompraPolicy
 * impide que quien creo una orden la apruebe: es la unica separacion de
 * funciones del circuito, y sin ella la aprobacion no significa nada. Ese
 * permiso es la excepcion, y solo la tiene el administrador, porque en una
 * empresa chica suele ser la misma persona y trabarlo lo dejaria sin poder
 * operar. El administrativo NO lo tiene: aprueba las automaticas —que no
 * las creo nadie— y las que cargo otro.
 */
return new class extends Migration
{
    /** @var array<string, string> clave => descripcion */
    private const PERMISOS = [
        'compra.ver'              => 'Ver los pedidos a proveedores',
        'compra.crear'            => 'Cargar un pedido a proveedor a mano',
        'compra.editar'           => 'Editar el detalle de un pedido en borrador',
        'compra.aprobar'          => 'Aprobar un pedido para que pueda enviarse',
        'compra.aprobar_propias'  => 'Aprobar pedidos cargados por uno mismo',
        'compra.enviar'           => 'Enviarle el remito del pedido al proveedor',
        'compra.cancelar'         => 'Cancelar un pedido',
    ];

    /** @var array<string, list<string>> rol => claves */
    private const ASIGNACION = [
        'administrador' => [
            'compra.ver', 'compra.crear', 'compra.editar',
            'compra.aprobar', 'compra.aprobar_propias', 'compra.enviar', 'compra.cancelar',
        ],
        'administrativo' => [
            'compra.ver', 'compra.crear', 'compra.editar',
            'compra.aprobar', 'compra.enviar', 'compra.cancelar',
        ],
    ];

    public function up(): void
    {
        foreach (self::PERMISOS as $clave => $descripcion) {
            // updateOrInsert y no insert: la migracion tiene que poder correr
            // sobre una base donde el seeder de permisos ya paso.
            DB::table('permisos')->updateOrInsert(
                ['clave' => $clave],
                ['modulo' => 'compra', 'descripcion' => $descripcion],
            );
        }

        $ids = DB::table('permisos')
            ->whereIn('clave', array_keys(self::PERMISOS))
            ->pluck('id', 'clave');

        foreach (self::ASIGNACION as $rol => $claves) {
            $rolId = DB::table('roles')->where('nombre', $rol)->value('id');

            if ($rolId === null) {
                // El rol no existe todavia: la asignacion la hara el seeder de
                // roles. No es motivo para frenar el deploy.
                continue;
            }

            foreach ($claves as $clave) {
                DB::table('rol_permiso')->updateOrInsert([
                    'rol_id'     => $rolId,
                    'permiso_id' => $ids[$clave],
                ]);
            }
        }
    }

    /**
     * rol_permiso es CASCADE sobre permisos, asi que borrar el permiso se lleva
     * sus asignaciones. Es de las pocas tablas del modelo que puede desaparecer
     * fisicamente: es configuracion, no historia.
     */
    public function down(): void
    {
        DB::table('permisos')->whereIn('clave', array_keys(self::PERMISOS))->delete();
    }
};
