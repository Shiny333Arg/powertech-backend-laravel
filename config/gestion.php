<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Panel de gestion (empleados)
    |--------------------------------------------------------------------------
    |
    | El equivalente de config/movil.php para el otro ambito. Existe por el
    | mismo motivo que aquel: la vigencia y la ability del token no son
    | configuracion de Sanctum sino decisiones del producto, y meter un
    | parametro de gestion dentro de un archivo cuyo encabezado dice
    | "Aplicacion movil (Flutter) - tienda" seria mentir sobre su alcance.
    |
    */

    /*
    | Vigencia ABSOLUTA del token de gestion, en horas.
    |
    | Son horas y no dias porque el panel se usa en el mostrador: una jornada
    | laboral, no un telefono que queda logueado un mes. Ocho horas cubren el
    | turno completo y obligan a reingresar al dia siguiente.
    |
    | <b>Se aplica POR TOKEN</b> —createToken(..., now()->addHours(...))— y
    | NUNCA por config('sanctum.expiration'). Ese valor es global y, segun el
    | comentario del propio config de Sanctum, "will override any values set
    | in the token's expires_at attribute": ponerle un numero anularia de un
    | golpe los 30 dias de Flutter y estas 8 horas. Ver config/sanctum.php.
    |
    | Es independiente de session.lifetime (120 minutos de INACTIVIDAD). Son
    | dos relojes distintos: la sesion muere por no usarse, el token por
    | antiguedad. Que exista la sesion no implica que el token siga vivo, y de
    | eso se ocupa VerificarTokenDeSesionGestion en cada request.
    */
    'token_horas' => (int) env('GESTION_TOKEN_HORAS', 8),

    /*
    | Ability del token de gestion.
    |
    | Deja cerrado por defecto cualquier endpoint de tienda: el middleware
    | 'abilities:tienda' de routes/api.php rechaza este token sin necesidad de
    | enumerar que no puede hacer. El espejo de config('movil.ability').
    */
    'ability' => 'gestion',

];
