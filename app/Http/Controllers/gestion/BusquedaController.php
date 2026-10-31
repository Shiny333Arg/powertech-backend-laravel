<?php

namespace App\Http\Controllers\gestion;

use App\Models\Cliente;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Autocompletado de las pantallas de gestion. Devuelve JSON, no vistas.
 *
 * Lo consumen la carga de una venta, la seleccion de productos alcanzados por
 * una promocion y el armado de un pedido a proveedores. Por eso el permiso que
 * se exige es de lectura del catalogo y no 'venta.crear': buscar un producto no
 * es vender, y gatearlo con el permiso del primer consumidor dejaba afuera al
 * administrativo, que no vende pero si arma promociones y pedidos.
 *
 * El precio y el costo que viajan son informativos: el servidor los vuelve a
 * tomar del catalogo al guardar. Un precio que llega del formulario es un
 * precio que el operador puede editar.
 *
 * Se incluye stock_disponible porque el vendedor tiene que ver, al cargar la
 * linea, si la venta es preventa. No se filtra por disponibilidad: vender sin
 * stock esta admitido y es un aviso, no un bloqueo; una promocion sobre un
 * producto agotado es un caso legitimo —se promociona lo que esta por
 * reponerse— y un pedido a proveedor se hace justamente por lo que falta.
 *
 * El proveedor viaja con su estado: el formulario de compras necesita mostrar a
 * quien se le va a pedir cada renglon, y marcar los que no se pueden pedir
 * —producto sin proveedor, o con uno dado de baja— antes de que el usuario
 * mande el formulario y se lo rechace la validacion.
 *
 * Los errores salen como JSON y no con abort(): el cliente de esta ruta es un
 * fetch, y una pagina de error HTML del lado del navegador es indistinguible
 * de una respuesta vacia.
 */
class BusquedaController extends Controller
{
    public function productos(Request $request): JsonResponse
    {
        if (! $request->user()?->tienePermiso('producto.ver')) {
            return response()->json([
                'message' => 'El usuario no tiene el permiso producto.ver.',
            ], 403);
        }

        $texto = trim((string) $request->query('q'));

        if (mb_strlen($texto) < 2) {
            return response()->json([]);
        }

        $productos = Producto::query()
            ->where('activo', true)
            ->where(fn ($q) => $q
                ->where('nombre', 'like', "%{$texto}%")
                ->orWhere('codigo', 'like', "%{$texto}%")
                ->orWhere('marca', 'like', "%{$texto}%"))
            // La categoria la usa la tabla de productos alcanzados de
            // promociones; el proveedor, el formulario de compras. La venta
            // ignora las dos: un campo de mas en el JSON no le molesta.
            ->with(['categoria:id,nombre', 'proveedor:id,razon_social,activo'])
            ->orderBy('nombre')
            ->limit(20)
            ->get(['id', 'codigo', 'nombre', 'marca', 'precio', 'costo', 'stock_disponible', 'punto_reposicion', 'categoria_id', 'proveedor_id']);

        return response()->json(
            $productos->map(fn (Producto $p) => [
                'id'         => $p->id,
                'codigo'     => $p->codigo,
                'nombre'     => $p->nombre,
                'marca'      => $p->marca,
                'categoria'  => $p->categoria?->nombre,
                'precio'     => (float) $p->precio,
                'costo'      => $p->costo === null ? null : (float) $p->costo,
                'disponible' => (int) $p->stock_disponible,
                'preventa'   => (int) $p->stock_disponible <= 0,
                'reposicion' => (int) $p->punto_reposicion,
                'proveedor'  => $p->proveedor === null ? null : [
                    'id'           => $p->proveedor->id,
                    'razon_social' => $p->proveedor->razon_social,
                    'activo'       => (bool) $p->proveedor->activo,
                ],
            ])->values()
        );
    }

    public function clientes(Request $request): JsonResponse
    {
        if (! $request->user()?->tienePermiso('cliente.ver')) {
            return response()->json([
                'message' => 'El usuario no tiene el permiso cliente.ver.',
            ], 403);
        }

        $texto = trim((string) $request->query('q'));

        if (mb_strlen($texto) < 2) {
            return response()->json([]);
        }

        $clientes = Cliente::query()
            ->where('activo', true)
            ->where(fn ($q) => $q
                ->where('razon_social', 'like', "%{$texto}%")
                ->orWhere('nro_doc', 'like', "%{$texto}%"))
            ->with('condicionIva:id,nombre')
            ->orderBy('razon_social')
            ->limit(20)
            ->get(['id', 'razon_social', 'nro_doc', 'condicion_iva_id']);

        return response()->json(
            $clientes->map(fn (Cliente $c) => [
                'id'           => $c->id,
                'razon_social' => $c->razon_social,
                'nro_doc'      => $c->nro_doc,
                'condicion'    => $c->condicionIva?->nombre ?? '—',
            ])->values()
        );
    }
}
