<?php

namespace Database\Seeders;

use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Database\Seeder;

/**
 * Permisos del circuito de mostrador y su asignacion por rol.
 *
 * Es <b>idempotente y aditivo</b>: usa updateOrCreate y
 * syncWithoutDetaching, asi que se puede correr sobre una base que ya tiene
 * permisos cargados sin pisar nada. Solo agrega las claves de este modulo.
 *
 * Los permisos son claves nombradas, no banderas CRUD por modulo: con flags
 * CRUD, toda accion que no sea alta, baja, modificacion o consulta cae en
 * alguna de ellas, y un vendedor termina pudiendo anular una venta cobrada.
 * Aca, lo que no se asigna explicitamente no se puede hacer.
 */
class PermisosMostradorSeeder extends Seeder
{
    /** @var array<string, array{modulo:string, descripcion:string}> */
    private const PERMISOS = [
        'venta.ver'              => ['modulo' => 'ventas', 'descripcion' => 'Ver ventas y presupuestos'],
        'venta.ver_todas'        => ['modulo' => 'ventas', 'descripcion' => 'Ver las ventas de todos los vendedores'],
        'venta.crear'            => ['modulo' => 'ventas', 'descripcion' => 'Generar presupuestos y ventas'],
        'venta.editar'           => ['modulo' => 'ventas', 'descripcion' => 'Editar el detalle de un presupuesto'],
        'venta.validar'          => ['modulo' => 'ventas', 'descripcion' => 'Validar un presupuesto y habilitar su cobro'],
        'venta.anular'           => ['modulo' => 'ventas', 'descripcion' => 'Cancelar una venta'],
        'venta.cobrar'           => ['modulo' => 'ventas', 'descripcion' => 'Registrar el cobro de una venta'],
        'venta.entregar'         => ['modulo' => 'ventas', 'descripcion' => 'Preparar, despachar y entregar mercadería'],
        'venta.devolver'         => ['modulo' => 'ventas', 'descripcion' => 'Registrar devoluciones con nota de crédito'],

        'cliente.ver'            => ['modulo' => 'clientes', 'descripcion' => 'Ver clientes'],
        'cliente.crear'          => ['modulo' => 'clientes', 'descripcion' => 'Dar de alta clientes'],
        'cliente.editar'         => ['modulo' => 'clientes', 'descripcion' => 'Editar clientes y sus direcciones'],
        'cliente.baja'           => ['modulo' => 'clientes', 'descripcion' => 'Dar de baja y reactivar clientes'],

        'stock.ver'              => ['modulo' => 'stock', 'descripcion' => 'Consultar el histórico de movimientos'],
        'stock.ajustar'          => ['modulo' => 'stock', 'descripcion' => 'Registrar ajustes de inventario y carga inicial'],

        'comprobante.ver'        => ['modulo' => 'facturacion', 'descripcion' => 'Ver comprobantes emitidos'],
        'comprobante.reintentar' => ['modulo' => 'facturacion', 'descripcion' => 'Reintentar la autorización ante ARCA'],
    ];

    /** @var array<string, list<string>> rol => claves. El administrador recibe todo. */
    private const MATRIZ = [
        'administrativo' => [
            'venta.ver', 'venta.ver_todas', 'venta.anular', 'venta.entregar', 'venta.devolver',
            'cliente.ver', 'cliente.crear', 'cliente.editar', 'cliente.baja',
            'stock.ver', 'stock.ajustar',
            'comprobante.ver', 'comprobante.reintentar',
        ],
        'vendedor' => [
            'venta.ver', 'venta.crear', 'venta.editar', 'venta.validar',
            'cliente.ver', 'cliente.crear', 'cliente.editar',
            'stock.ver',
        ],
        'cajero' => [
            'venta.ver', 'venta.ver_todas', 'venta.cobrar', 'venta.entregar',
            'cliente.ver',
            'comprobante.ver',
        ],
        // El cliente no tiene ninguno de estos: son todos del ámbito gestión.
        'cliente' => [],
    ];

    public function run(): void
    {
        $permisos = collect(self::PERMISOS)->mapWithKeys(fn (array $datos, string $clave) => [
            $clave => Permiso::updateOrCreate(['clave' => $clave], $datos),
        ]);

        // El administrador tiene acceso completo, sin restricciones.
        $this->asignar('administrador', $permisos->keys()->all(), $permisos);

        foreach (self::MATRIZ as $rol => $claves) {
            $this->asignar($rol, $claves, $permisos);
        }
    }

    /**
     * @param  list<string>  $claves
     * @param  \Illuminate\Support\Collection<string, Permiso>  $permisos
     */
    private function asignar(string $nombreRol, array $claves, $permisos): void
    {
        $rol = Rol::where('nombre', $nombreRol)->first();

        if (! $rol) {
            $this->command?->warn("Rol «{$nombreRol}» inexistente: se omite la asignación.");

            return;
        }

        $ids = $permisos->only($claves)->map->id->values()->all();

        // syncWithoutDetaching: no saca lo que otro seeder ya asignó.
        $rol->permisos()->syncWithoutDetaching($ids);
    }
}
