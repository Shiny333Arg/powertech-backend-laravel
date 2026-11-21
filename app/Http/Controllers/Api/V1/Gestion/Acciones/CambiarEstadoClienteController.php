<?php

namespace App\Http\Controllers\Api\V1\Gestion\Acciones;

use App\Actions\RevocarTodosLosTokens;
use App\Http\Controllers\Controller;
use App\Http\Resources\Gestion\ClienteResource;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * PATCH /api/v1/gestion/clientes/{cliente}/estado
 *
 * Baja lógica y reactivación. Las dos en un endpoint porque son la misma
 * decisión —y el mismo permiso, cliente.baja— mirada desde los dos lados: el
 * cuerpo trae el estado al que se quiere llegar.
 *
 *   { "activo": false }   el cliente queda inhabilitado
 *   { "activo": true }    vuelve a estar en operación
 *
 * <b>Controlador de acción única, y no un método más del CRUD.</b> Es la forma
 * del proyecto para las transiciones de estado: con un solo __invoke no hay
 * manera de que la operación quede sin su línea de autorización. El equivalente
 * web eran dos rutas —/baja y /reactivar—, porque un formulario Blade no manda
 * un booleano y un cliente JSON sí.
 *
 * ──────────────────────────────────────────────────────────────────────────
 * TOCA DOS COLUMNAS, Y ESO ES EL ARREGLO
 * ──────────────────────────────────────────────────────────────────────────
 *
 * `clientes.activo` lo saca de la operación de mostrador y `users.activo` le
 * corta el acceso al sistema. <b>Son dos columnas, pero una sola decisión.</b>
 *
 * Hasta esta corrección la baja escribía sólo `clientes.activo`, igual que el
 * controlador web del que salió, y el resultado era que un cliente dado de baja
 * seguía pudiendo ingresar —por la web, por Flutter y por el ingreso del
 * panel— y hasta comprar online: ninguno de los cinco puntos de acceso lee
 * `clientes.activo`, que en todo el sistema tiene exactamente dos lectores y
 * los dos son de mostrador (BusquedaController::clientes y VentaRequest). La
 * credencial vive en `users.activo` y nadie la estaba tocando.
 *
 * <b>Es el mismo criterio que EmpleadoController::darDeBaja()</b>, que toca
 * `users.activo` y `empleados.fecha_baja` juntos y lo dice en su comentario:
 * clientes era el caso desalineado, no el precedente nuevo. Y es el mismo
 * trait, TieneBajaLogica, en los dos modelos: la baja sigue siendo una sola
 * operación en todo el sistema.
 *
 * <b>En una transacción</b> porque medio aplicada es peor que no aplicada: un
 * cliente fuera del mostrador que todavía entra a la tienda es el bug que esto
 * corrige, y uno sin acceso que el mostrador sigue ofreciendo manda a un
 * empleado a cargarle una venta a alguien que no puede operar.
 *
 * <b>Lo que NO cambia:</b> la validación no se duplicó en ninguna parte. Los
 * cinco puntos de acceso —el login web, el login de la API que usan Flutter y
 * el panel, la invitación, la recuperación de contraseña y los middleware
 * VerificarCuentaActiva / VerificarCuentaActivaApi— siguen leyendo
 * `users.activo` como siempre. Lo único que se arregló es que ahora esa columna
 * se escribe.
 *
 * <b>El cliente de mostrador sin cuenta sigue funcionando:</b> `user_id` es
 * nullable y el `?->` lo cubre. No tiene credencial que cortar, así que su baja
 * es sólo la de `clientes.activo`, como antes.
 *
 * <b>Y no afecta a los demás roles.</b> Este endpoint sólo opera sobre
 * clientes: el empleado conserva su propio circuito en EmpleadoController, con
 * su permiso y su regla del último administrador.
 *
 * ──────────────────────────────────────────────────────────────────────────
 *
 * <b>La baja no se bloquea.</b> Un cliente con compras se da de baja igual: es
 * reversible y sus ventas anteriores lo siguen nombrando. A diferencia del
 * proveedor, acá no hay consecuencia oculta que avisar —ningún circuito
 * automático depende de un cliente—, así que no hay 'advertencias'.
 *
 * <b>Este es el único permiso de clientes que no tienen los cuatro roles.</b>
 * Según la matriz de PermisoSeeder, el vendedor y el cajero tienen
 * 'cliente.ver', 'cliente.crear' y 'cliente.editar', y NO 'cliente.baja': el
 * mostrador carga y corrige clientes, pero inhabilitar a uno es del
 * administrador y del administrativo. Por eso en este módulo los botones por
 * permiso no son decorativos.
 */
class CambiarEstadoClienteController extends Controller
{
    public function __construct(private readonly RevocarTodosLosTokens $revocarTodos) {}

    public function __invoke(Request $request, Cliente $cliente): ClienteResource
    {
        $datos = $request->validate([
            'activo' => ['required', 'boolean'],
        ], [
            'activo.required' => 'Indicá si el cliente queda en operación o no.',
        ]);

        $activo = (bool) $datos['activo'];

        DB::transaction(function () use ($cliente, $activo) {
            $usuario = $cliente->user;

            // Se usan darDeBaja()/reactivar() del trait TieneBajaLogica —que
            // usan los dos modelos— y no un update() suelto.
            if ($activo) {
                $cliente->reactivar();
                $usuario?->reactivar();

                return;
            }

            $cliente->darDeBaja();

            if ($usuario === null) {
                return; // cliente de mostrador: no hay credencial que cortar
            }

            $usuario->darDeBaja();

            // Dentro de la transacción: si la baja se revierte, los tokens no
            // tienen que haber desaparecido. El corte del acceso no depende de
            // esto —lo garantiza el middleware 'api.activo' leyendo
            // users.activo—, pero borrarlos lo hace inmediato en lugar de
            // dejar credenciales que serán rechazadas recién al usarse.
            ($this->revocarTodos)($usuario);
        });

        return new ClienteResource(
            $cliente->refresh()->load([
                'tipoDocumento',
                'condicionIva',
                // La ficha repinta su encabezado y su panel de cuenta con esta
                // respuesta, así que viajan igual que en el detalle. Ver el
                // comentario de ClienteController::recargar() sobre el alias.
                'user' => fn ($query) => $query
                    ->select(['id', 'email', 'nombres', 'apellido', 'activo', 'email_verified_at'])
                    ->selectRaw('password IS NULL AS invitacion_pendiente'),
            ])
        );
    }
}
