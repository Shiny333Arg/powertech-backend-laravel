<?php

namespace App\Support;

/**
 * Construye las URL publicas de los archivos subidos a storage.
 *
 * <b>Por que no se usa asset().</b> Durante un request HTTP, asset() y route()
 * arman la URL con el host del REQUEST ENTRANTE, no con APP_URL. En la web eso
 * es lo correcto —el navegador ya esta en ese host— pero en la API es un
 * problema: el emulador de Android le pega al backend por la IP de la LAN
 * (192.168.x.x) o por 10.0.2.2, y las URL de imagen saldrian con esa IP
 * adentro. Un telefono real en otra red, o la misma app abierta mañana desde
 * otra conexion, recibe URL que no resuelven.
 *
 * Es el mismo problema que PasarelaMercadoPago::exigirUrlAlcanzable() ya
 * documenta para las back_urls, visto desde el otro lado.
 *
 * <b>De donde sale la base.</b> De la configuracion y no del request:
 * `app.asset_url` si esta definida (es la que Laravel reserva para servir
 * archivos desde un CDN o un dominio aparte) y `app.url` si no. Cambiar de
 * entorno es cambiar una variable del .env, no tocar codigo.
 *
 * En desarrollo con tunel: APP_URL tiene que apuntar al tunel, igual que ya
 * hace falta para Mercado Pago.
 */
final class UrlPublica
{
    /**
     * URL absoluta de un archivo guardado en el disco 'public'.
     *
     * Devuelve null cuando no hay archivo: la app pone su propio placeholder.
     * Es a proposito distinto de la web, que sirve images/Sin_Imagen.png desde
     * el servidor —un placeholder empaquetado en la app se ve al instante y
     * funciona sin conexion.
     */
    public static function deStorage(?string $ruta): ?string
    {
        if (blank($ruta)) {
            return null;
        }

        // Defensivo: si alguna vez se guarda una URL completa en la columna
        // (una imagen alojada afuera), se devuelve tal cual.
        if (str_starts_with($ruta, 'http://') || str_starts_with($ruta, 'https://')) {
            return $ruta;
        }

        return self::base().'/storage/'.ltrim($ruta, '/');
    }

    private static function base(): string
    {
        $base = config('app.asset_url') ?: config('app.url');

        return rtrim((string) $base, '/');
    }
}