<?php

namespace App\Support;

use App\Enums\EstadoVenta;

/**
 * El estado de un pedido en el lenguaje del cliente.
 *
 * <b>No es la etiqueta del enum y es a proposito.</b> EstadoVenta::etiqueta()
 * habla el vocabulario del circuito —"confirmada", "pendiente de stock"— que
 * es lo que el mostrador necesita leer. El cliente necesita saber que le pasa
 * a SU pedido: una venta "confirmada" para el es "pendiente de pago". Son dos
 * vocabularios distintos sobre el mismo dato, y por eso esta traduccion vive
 * afuera del enum, que lo comparten las dos aplicaciones.
 *
 * <b>Por que no quedo solo en el partial de Blade.</b> Nacio en
 * resources/views/compras/partials/estado.blade.php y la app necesita
 * exactamente el mismo texto: si el chip de la web dice "Listo para retirar"
 * y el de la app dice "Lista para retirar", son dos productos distintos
 * mirando la misma fila. Con el mapeo aca, el partial y el JSON no pueden
 * desalinearse.
 *
 * <b>`tono()` y `colorBootstrap()` responden a dos consumidores.</b> La web
 * necesita el nombre de la clase de Bootstrap; la app no sabe nada de
 * Bootstrap y necesita un token semantico que resuelva contra su propia
 * paleta. El tono es la decision —que gravedad tiene este estado— y el color
 * es como la escribe la web.
 */
final class EstadoCompraCliente
{
    /**
     * Texto del chip, tal como lo lee el cliente.
     *
     * Los estados que no se renombran caen en etiqueta(): ahi el vocabulario
     * del circuito ya coincide con el del cliente ("Despachada", "Cancelada").
     */
    public static function etiqueta(EstadoVenta $estado): string
    {
        return match ($estado) {
            EstadoVenta::Confirmada     => 'Pendiente de pago',
            EstadoVenta::Pagada         => 'Pago acreditado',
            EstadoVenta::PendienteStock => 'Esperando reposición',
            EstadoVenta::EnPreparacion  => 'En preparación',
            EstadoVenta::ListaRetiro    => 'Listo para retirar',
            EstadoVenta::Entregada      => 'Entregado',
            default                     => $estado->etiqueta(),
        };
    }

    /**
     * Gravedad del estado, para que la interfaz lo distinga de un vistazo.
     *
     * Cinco tonos y no uno por estado: lo que el cliente tiene que percibir
     * es si el pedido avanza (info), si llego (exito), si espera algo de el
     * o de nosotros (atencion), si se cayo (error) o si ya no esta en curso
     * (neutro).
     *
     * @return 'exito'|'info'|'atencion'|'error'|'neutro'
     */
    public static function tono(EstadoVenta $estado): string
    {
        return match ($estado) {
            EstadoVenta::ListaRetiro,
            EstadoVenta::Entregada => 'exito',

            EstadoVenta::Pagada,
            EstadoVenta::EnPreparacion,
            EstadoVenta::Despachada => 'info',

            EstadoVenta::Confirmada,
            EstadoVenta::PendienteStock => 'atencion',

            EstadoVenta::Cancelada => 'error',

            // Presupuesto y vencida entran por aca y no se muestran nunca en
            // esta seccion: no son compras. Ver CompraDelCliente.
            default => 'neutro',
        };
    }

    /**
     * El tono escrito como clase de Bootstrap, para las vistas Blade.
     *
     * Devuelve exactamente los mismos colores que tenia el partial antes de
     * centralizar el mapeo. Si se cambia uno, cambia en la web y no en la app,
     * que es lo correcto: la app elige los suyos a partir del tono.
     */
    public static function colorBootstrap(EstadoVenta $estado): string
    {
        return match (self::tono($estado)) {
            'exito'    => 'success',
            'info'     => 'info',
            'atencion' => 'warning',
            'error'    => 'danger',
            default    => 'secondary',
        };
    }

    /**
     * El estado listo para serializar: el valor para comparar y el texto y el
     * tono para mostrar.
     *
     * Viaja el `valor` crudo ademas del texto para que la app pueda decidir
     * por dato y no comparando cadenas traducidas.
     *
     * @return array{valor:string, etiqueta:string, tono:string}
     */
    public static function paraJson(EstadoVenta $estado): array
    {
        return [
            'valor'    => $estado->value,
            'etiqueta' => self::etiqueta($estado),
            'tono'     => self::tono($estado),
        ];
    }
}
