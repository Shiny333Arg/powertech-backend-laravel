<?php

/*
|--------------------------------------------------------------------------
| Aplicacion movil (Flutter) - tienda
|--------------------------------------------------------------------------
|
| Parametros propios del canal movil. Nada de lo de aca lo lee una pantalla
| web: todo vive detras de routes/api.php.
|
*/

return [

    /*
    |----------------------------------------------------------------------
    | Vigencia del token de sesion
    |----------------------------------------------------------------------
    |
    | Dias que vive el token que se emite al iniciar sesion desde la app.
    |
    | <b>Se fija POR TOKEN, no con sanctum.expiration.</b> Esa opcion es
    | global y alcanzaria a cualquier otro token que la aplicacion emita en
    | el futuro —una integracion, un token de servicio—. Aca se decide
    | unicamente la vigencia de los tokens de la app.
    |
    | 30 dias es un compromiso explicito: no hay refresh token, asi que
    | vencer significa mandar al cliente de vuelta al login. Mas corto es
    | mas friccion sobre la conversion; mas largo deja un bearer vivo
    | demasiado tiempo en un telefono que se puede perder.
    |
    */

    'token_dias' => (int) env('MOVIL_TOKEN_DIAS', 30),

    /*
    |----------------------------------------------------------------------
    | Habilidad del token
    |----------------------------------------------------------------------
    |
    | Los tokens de la app nacen con esta unica ability en lugar de '*'. Si
    | manana se agregan endpoints de otro ambito, quedan cerrados por
    | defecto en vez de abiertos por omision: un token de la tienda nunca
    | tiene que servir para gestion.
    |
    | Para exigirla en una ruta: ->middleware('abilities:tienda').
    |
    */

    'ability' => 'tienda',

    /*
    |----------------------------------------------------------------------
    | Deep link
    |----------------------------------------------------------------------
    |
    | Esquema propio con el que la app recupera el control despues de pagar.
    | Lo va a usar la ruta publica de rebote del checkout. Declarado desde
    | ahora para que no quede escrito a mano en dos lugares.
    |
    */

    'esquema' => env('MOVIL_ESQUEMA', 'powertech'),

];