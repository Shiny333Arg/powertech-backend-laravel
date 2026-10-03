<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * El permiso de recepcion de mercaderia.
 *
 * Es propio y no una reutilizacion de 'compra.aprobar' porque son dos tareas
 * distintas con dos responsables distintos: aprobar es autorizar un gasto y lo
 * hace un administrativo; recibir es constatar que la mercaderia llego y lo
 * hace quien esta en el deposito. En una empresa chica va a ser la misma
 * persona, pero el dia que no lo sea, separarlos no requiere una migracion con
 * datos ya cargados.
 *
 * Se asigna a administrador y administrativo, que son los dos roles de ambito
 * 'gestion' que hoy operan el circuito de compras.
 */
return new class extends Migration
{
    private const PERMISO = [
        'clave'       => 'compra.recibir',
        'modulo'      => 'compras',
        'descripcion' => 'Registrar la recepción de mercadería de un pedido y cerrarlo con faltante',
    ];

    /** @var list<string> */
    private const ROLES = ['administrador', 'administrativo'];

    public function up(): void
    {
        // updateOrInsert y no insert: la migracion tiene que poder correr sobre
        // una base que ya tenga el permiso cargado a mano sin reventar contra
        // el indice unico de 'clave'.
        DB::table('permisos')->updateOrInsert(
            ['clave' => self::PERMISO['clave']],
            [
                'modulo'      => self::PERMISO['modulo'],
                'descripcion' => self::PERMISO['descripcion'],
            ],
        );

        $permisoId = DB::table('permisos')
            ->where('clave', self::PERMISO['clave'])
            ->value('id');

        $roles = DB::table('roles')
            ->whereIn('nombre', self::ROLES)
            ->pluck('id');

        foreach ($roles as $rolId) {
            DB::table('rol_permiso')->updateOrInsert([
                'rol_id'     => $rolId,
                'permiso_id' => $permisoId,
            ]);
        }
    }

    public function down(): void
    {
        $permisoId = DB::table('permisos')
            ->where('clave', self::PERMISO['clave'])
            ->value('id');

        if ($permisoId === null) {
            return;
        }

        DB::table('rol_permiso')->where('permiso_id', $permisoId)->delete();
        DB::table('permisos')->where('id', $permisoId)->delete();
    }
};
