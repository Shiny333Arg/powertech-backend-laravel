<?php

namespace App\Http\Controllers\gestion;

use App\Models\Cliente;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Autocompletado de la pantalla de venta. Devuelve JSON, no vistas.
 *
 * El precio que viaja es informativo: el servidor lo vuelve a tomar del
 * catalogo al guardar. Un precio que llega del formulario es un precio que el
 * operador puede editar.
 *
 * Se incluye stock_disponible porque el vendedor tiene que ver, al cargar la
 * linea, si la venta es preventa. No se filtra por disponibilidad: vender sin
 * stock esta admitido y es un aviso, no un bloqueo.
 *
 * Los errores salen como JSON y no con abort(): el cliente de esta ruta es un
 * fetch, y una pagina de error HTML del lado del navegador es indistinguible
 * de una respuesta vacia.
 */
class BusquedaController extends Controller
{
    public function productos(Request $request): JsonResponse
    {
        if (! $request->user()?->tienePermiso('venta.crear')) {
            return response()->json([
                'message' => 'El usuario no tiene el permiso venta.crear.',
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
            ->orderBy('nombre')
            ->limit(20)
            ->get(['id', 'codigo', 'nombre', 'marca', 'precio', 'stock_disponible']);

        return response()->json(
            $productos->map(fn (Producto $p) => [
                'id'         => $p->id,
                'codigo'     => $p->codigo,
                'nombre'     => $p->nombre,
                'marca'      => $p->marca,
                'precio'     => (float) $p->precio,
                'disponible' => (int) $p->stock_disponible,
                'preventa'   => (int) $p->stock_disponible <= 0,
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