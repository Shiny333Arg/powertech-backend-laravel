<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegistroClienteRequest;
use App\Models\CondicionIva;
use App\Models\TipoDocumento;
use Illuminate\Http\JsonResponse;

/**
 * Catalogos fiscales que el formulario de registro de la app necesita para
 * armar sus dos listas desplegables.
 *
 * <b>Por que existe.</b> El registro exige tipo_documento_id y
 * condicion_iva_id, que son claves primarias de esta base. En la web el
 * <select> se arma en el servidor; la app no tiene de donde sacarlas. La
 * alternativa era escribir los id en el codigo Flutter, que se rompe en
 * cuanto el seeder corre distinto o se cambia de base.
 *
 * <b>Solo lectura y publico.</b> No hay nada reservado aca: son los codigos
 * de ARCA, los mismos que estan impresos en cualquier factura. Pedir token
 * para leerlos obligaria a iniciar sesion antes de poder registrarse, que es
 * un circulo.
 *
 * <b>No duplica reglas.</b> El filtro de tipos de documento sale de la misma
 * constante que usa la validacion: si manana el cliente pudiera
 * identificarse con un tercer tipo, se cambia en ReglasDatosCliente y este
 * endpoint acompaña solo. La constante se lee a traves de
 * RegistroClienteRequest y no del trait porque PHP no permite acceder a la
 * constante de un trait por el nombre del trait.
 *
 * <b>codigo_afip viaja a proposito.</b> Le permite al formulario adaptar el
 * teclado y el largo del campo de documento —once digitos para CUIT, siete u
 * ocho para DNI— sin que la app tenga esos numeros escritos adentro. La
 * validacion definitiva sigue siendo la de ReglasDatosCliente: lo que hace
 * la app es adelantar el mensaje, no decidir.
 */
class DatosFiscalesController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([

            'tipos_documento' => TipoDocumento::query()
                ->whereIn('codigo_afip', RegistroClienteRequest::TIPOS_DOCUMENTO_CLIENTE)
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'codigo_afip']),

            'condiciones_iva' => CondicionIva::query()
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'codigo_afip']),
        ]);
    }
}
