<?php

namespace App\Support;

use App\Models\OrdenCompra;

/**
 * Simple, madre o hija: la forma de un pedido a proveedor, en una palabra.
 *
 * <b>Que resuelve.</b> OrdenCompra ya sabe reconocerse —esMadre() y esHija()—,
 * pero la API tiene que publicar la forma como un dato, y la traduccion de esos
 * dos booleanos a un nombre la necesitan los DOS resources de compras: el del
 * listado y el del detalle. Escrita dos veces, el dia que aparezca una cuarta
 * forma uno de los dos la va a seguir llamando «simple».
 *
 * Son los tres casos del docblock de OrdenCompra, con los mismos nombres. No se
 * agrega un cuarto: una orden simple y una hija de un solo proveedor se
 * comportan igual salvo en que la segunda sabe de donde viene, y eso ya lo dice
 * el campo 'padre' del detalle.
 *
 * Es una clase de apoyo y no un metodo del modelo a proposito: no agrega
 * comportamiento al dominio —el modelo ya decide todo lo que importa con
 * esMadre()—, solo le pone nombre para serializarlo. El mismo criterio que
 * App\Support\DomicilioFiscal.
 */
final class FormaDeOrden
{
    public const SIMPLE = 'simple';

    public const MADRE = 'madre';

    public const HIJA = 'hija';

    public static function de(OrdenCompra $orden): string
    {
        return match (true) {
            // El orden importa: una madre nunca tiene padre, pero preguntar
            // primero por la hija dejaria sin nombrar el caso de una madre
            // emitida desde otra, si alguna vez existiera.
            $orden->esMadre() => self::MADRE,
            $orden->esHija()  => self::HIJA,
            default           => self::SIMPLE,
        };
    }
}
