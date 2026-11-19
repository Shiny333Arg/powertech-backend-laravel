<?php

namespace App\Exceptions;

use Illuminate\Database\QueryException;
use RuntimeException;

/**
 * Se lanza cuando se intenta un DELETE fisico sobre una entidad que, por
 * politica del modelo (L.1), solo admite baja logica.
 *
 * Existe para que un delete() distraido, un comando de consola o un tinker
 * fallen con un mensaje del dominio, y no con una violacion de integridad
 * o, peor, con una fila borrada que todavia no tenia historia.
 *
 * En la API sale como <b>409</b>: lo mapea el render de bootstrap/app.php, asi
 * que un controlador que la lanza no tiene que armar la respuesta.
 */
class EntidadNoEliminableException extends RuntimeException
{
    /**
     * Codigos del motor para "hay filas hijas que referencian esta".
     *
     * 1451 es el de MySQL/MariaDB al borrar el padre con ON DELETE RESTRICT;
     * 1217 es el equivalente cuando la FK se evalua al cierre. SQLite —el
     * motor de las pruebas— no usa estos numeros: ahi el rechazo llega con
     * codigo 19 y el texto 'FOREIGN KEY constraint failed', y por eso se mira
     * tambien el mensaje.
     */
    private const ERRORES_CLAVE_FORANEA = [1451, 1217];

    /**
     * ¿Este error del motor es un rechazo de clave foranea?
     *
     * Vive aca y no en un controlador porque la hacen la misma pregunta el
     * panel Blade —que traduce el rechazo a un mensaje en pantalla— y la API
     * —que lo convierte en esta excepcion y responde 409—. Dos copias de la
     * lista de codigos son dos lugares donde puede desalinearse.
     */
    public static function esRechazoDeClaveForanea(QueryException $e): bool
    {
        if (in_array((int) ($e->errorInfo[1] ?? 0), self::ERRORES_CLAVE_FORANEA, true)) {
            return true;
        }

        return str_contains($e->getMessage(), 'FOREIGN KEY constraint failed');
    }
}
