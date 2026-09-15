<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Se lanza cuando se intenta un DELETE fisico sobre una entidad que, por
 * politica del modelo (L.1), solo admite baja logica.
 *
 * Existe para que un delete() distraido, un comando de consola o un tinker
 * fallen con un mensaje del dominio, y no con una violacion de integridad
 * o, peor, con una fila borrada que todavia no tenia historia.
 */
class EntidadNoEliminableException extends RuntimeException
{
}
