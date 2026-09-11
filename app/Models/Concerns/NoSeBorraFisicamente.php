<?php

namespace App\Models\Concerns;

use App\Exceptions\BorradoNoPermitidoException;

/**
 * Para modelos que usan SoftDeletes y NUNCA pueden borrarse fisicamente:
 * `User` y `Producto`.
 *
 * Las FK RESTRICT ya rechazan el DELETE en el motor, pero solo si existe una
 * fila hija que lo impida: un producto recien creado y nunca vendido no tiene
 * ninguna, y un `forceDelete()` lo borraria de verdad. Esta guarda cierra ese
 * hueco y, de paso, hace que el intento falle con un mensaje entendible en
 * lugar de con una violacion de integridad.
 *
 * Se engancha al evento `deleting`, que se dispara tanto en la baja logica
 * como en el borrado fisico, y se discrimina con isForceDeleting(). Es
 * equivalente a escuchar `forceDeleting` pero no depende de la version.
 */
trait NoSeBorraFisicamente
{
    public static function bootNoSeBorraFisicamente(): void
    {
        static::deleting(function ($model) {
            if ($model->isForceDeleting()) {
                throw BorradoNoPermitidoException::para(
                    class_basename($model) . " #{$model->getKey()}",
                    'solo admite baja logica. Es historia referenciada por '
                    . 'documentos fiscales y movimientos de stock.'
                );
            }
        });
    }
}
