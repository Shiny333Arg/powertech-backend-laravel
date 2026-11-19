<?php

namespace App\Http\Controllers\Api\V1\Gestion\Acciones;

use App\Http\Controllers\Controller;
use App\Http\Resources\Gestion\ProveedorResource;
use App\Models\Proveedor;
use Illuminate\Http\Request;

/**
 * PATCH /api/v1/gestion/proveedores/{proveedor}/estado
 *
 * Baja logica y reactivacion. Las dos en un endpoint porque son la misma
 * decision —y el mismo permiso, proveedor.baja— mirada desde los dos lados:
 * el cuerpo trae el estado al que se quiere llegar.
 *
 *   { "activo": false }   sale de operacion, reversible, la fila sigue
 *   { "activo": true }    vuelve a estar en operacion
 *
 * <b>Controlador de accion unica, y no un metodo mas del CRUD.</b> Es la forma
 * del proyecto para las transiciones de estado: con un solo __invoke no hay
 * manera de que la operacion quede sin su linea de autorizacion, que es
 * exactamente el agujero que F1 cerro en el panel. El equivalente web son dos
 * rutas —/baja y /reactivar—, porque un formulario Blade no manda un booleano.
 *
 * <b>La baja NO se bloquea, igual que en el panel.</b> Un proveedor con
 * productos u ordenes abiertas se puede dar de baja: la baja es reversible y el
 * proveedor sigue nombrando las ordenes que ya emitio. Lo que si hace es
 * avisar, porque la consecuencia no es obvia —los productos que dependen de el
 * dejan de generar orden automatica y su faltante pasa a ser invisible—.
 *
 * <b>El aviso viaja como datos, no como la frase del panel.</b> El controlador
 * web arma un texto para el flash message; aca van los dos numeros en
 * 'advertencias' y la pantalla escribe su propio mensaje. Copiar las frases
 * seria duplicar en la API una decision de presentacion de Blade.
 */
class CambiarEstadoProveedorController extends Controller
{
    public function __invoke(Request $request, Proveedor $proveedor): ProveedorResource
    {
        $datos = $request->validate([
            'activo' => ['required', 'boolean'],
        ], [
            'activo.required' => 'Indicá si el proveedor queda en operación o no.',
        ]);

        // Se usan darDeBaja()/reactivar() del trait TieneBajaLogica y no un
        // update() suelto, para que la baja siga siendo una sola operacion en
        // todo el sistema.
        $datos['activo']
            ? $proveedor->reactivar()
            : $proveedor->darDeBaja();

        $recurso = new ProveedorResource(
            $proveedor->refresh()
                ->load('condicionIva:id,nombre')
                ->loadCount([
                    'productos',
                    'ordenesCompra as ordenes_abiertas_count' => fn ($query) => $query->abiertas(),
                ])
        );

        // Solo al salir de operacion: al reactivar no hay consecuencia que
        // avisar, y una clave vacia invitaria a la pantalla a mostrar un cartel
        // sin contenido.
        return $datos['activo']
            ? $recurso
            : $recurso->additional(['advertencias' => $this->consecuenciasDeLaBaja($proveedor)]);
    }

    /**
     * Lo que queda sin atender cuando el proveedor sale de operacion.
     *
     * 'productos_en_reposicion_automatica' es el numero que importa: esos
     * productos dejan de generar orden sola y hay que reasignarles proveedor.
     * Las ordenes abiertas siguen su curso —se pueden recibir igual—, pero la
     * pantalla las nombra para que nadie las de por canceladas.
     *
     * @return array<string, int>
     */
    private function consecuenciasDeLaBaja(Proveedor $proveedor): array
    {
        return [
            'productos_en_reposicion_automatica' => $proveedor->productos()
                ->activos()
                ->where('reposicion_automatica', true)
                ->count(),

            'ordenes_abiertas' => (int) $proveedor->ordenes_abiertas_count,
        ];
    }
}
