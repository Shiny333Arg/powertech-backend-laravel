<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Driver de autorizacion
    |--------------------------------------------------------------------------
    |
    | 'simulado' : asigna un CAE ficticio sin salir a la red. Permite operar el
    |              circuito completo de mostrador antes de tener el certificado
    |              y el punto de venta habilitados. NUNCA en produccion.
    | 'wsfe'     : web service de facturacion electronica del organismo.
    |              Todavia no implementado (ver AutorizadorWsfe en el README).
    |
    */
    'driver' => env('FACTURACION_DRIVER', 'simulado'),

    /*
    | Punto de venta que usa la caja del local cuando no se indica otro.
    | Es el `numero` de la tabla puntos_venta, no el id.
    */
    'punto_venta' => (int) env('FACTURACION_PUNTO_VENTA', 1),

    /*
    |--------------------------------------------------------------------------
    | Codigos del organismo
    |--------------------------------------------------------------------------
    |
    | Viven en config y no hardcodeados en el servicio porque son normativa:
    | si el organismo cambia uno, se cambia aca y no se toca codigo.
    |
    */
    'codigos' => [
        'comprobante' => [
            'factura_a'        => 1,
            'nota_debito_a'    => 2,
            'nota_credito_a'   => 3,
            'factura_b'        => 6,
            'nota_debito_b'    => 7,
            'nota_credito_b'   => 8,
        ],
        'documento' => [
            'cuit'              => 80,
            'cuil'              => 86,
            'dni'               => 96,
            'consumidor_final'  => 99,
        ],
        'condicion_iva' => [
            'responsable_inscripto' => 1,
            'exento'                => 4,
            'consumidor_final'      => 5,
            'monotributo'           => 6,
        ],
    ],

];
