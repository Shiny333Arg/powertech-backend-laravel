<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Se lanza cuando la aplicacion intenta un borrado que el modelo de datos
 * prohibe. El motor ya rechaza el borrado fisico por las FK RESTRICT, pero
 * el error que devuelve es una violacion de integridad ilegible en pantalla:
 * esta excepcion lo convierte en un mensaje del dominio.
 */
class BorradoNoPermitidoException extends RuntimeException
{
    public static function para(string $entidad, string $motivo): self
    {
        return new self("No se puede eliminar {$entidad}: {$motivo}");
    }
}
