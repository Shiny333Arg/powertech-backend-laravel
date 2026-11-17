<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Rebote del checkout movil: la pagina a la que Mercado Pago devuelve al
 * cliente que pago desde la app.
 *
 * <b>Por que no sirve `cliente.checkout.retorno`.</b> Esa ruta vive detras de
 * `auth` y `verified`. El cliente que paga desde la app lo hace en el
 * NAVEGADOR DEL SISTEMA, que no tiene la sesion de Laravel —la app se
 * autentica con un token Bearer que el navegador no conoce— asi que al
 * volver caeria en la pantalla de login. Esta ruta es publica por eso.
 *
 * <b>Y no hace absolutamente nada con la compra.</b> No consulta el pago, no
 * crea la venta y no toca el carrito. Es una pagina de cortesia: le dice al
 * cliente que ya puede volver a la app. Quien decide que paso es el webhook,
 * que llega desde los servidores de Mercado Pago y es el camino autoritativo;
 * y cuando la app vuelve al frente consulta `GET /api/v1/checkout/estado`,
 * que lee lo que el webhook haya dejado.
 *
 * <b>Nada de la query string se toma como verdad.</b> Esa URL es visible y
 * editable: un ?status=approved escrito a mano no puede hacer nada acá
 * porque acá no se decide nada.
 *
 * <b>El deep link es opcional y hoy no esta activo.</b> Si la app registra el
 * esquema `powertech://` en Android e iOS, esta pagina puede rebotar sola y
 * el cliente vuelve sin tocar nada. Eso exige editar AndroidManifest.xml e
 * Info.plist, que son archivos nativos; mientras no esten configurados, el
 * boton «Volver a PowerTech» de la vista intenta el esquema y, si no hay nada
 * registrado, el cliente vuelve a la app con el gesto de siempre. El circuito
 * funciona igual en los dos casos, porque la confirmacion no depende de este
 * rebote.
 */
class RetornoMovilController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('checkout.retorno-movil', [
            // Solo para el texto de la pagina. No decide nada: el estado real
            // lo da la API cuando la app vuelve al frente.
            'estadoSugerido' => $this->estadoSugerido($request),

            // El esquema propio de la app, para el boton de volver.
            'esquema' => config('movil.esquema'),
        ]);
    }

    /**
     * Lo que la query string insinua, nada mas que para elegir el texto.
     *
     * Se usa para no decirle «ya podes volver» a alguien que cancelo, pero
     * no se usa para confirmar ni para descartar un pago.
     */
    private function estadoSugerido(Request $request): string
    {
        $estado = (string) ($request->query('status')
            ?? $request->query('collection_status')
            ?? '');

        return match ($estado) {
            'approved' => 'aprobado',
            'pending', 'in_process' => 'pendiente',
            'rejected', 'failure' => 'rechazado',
            'null', '' => 'desconocido',
            default => 'desconocido',
        };
    }
}
