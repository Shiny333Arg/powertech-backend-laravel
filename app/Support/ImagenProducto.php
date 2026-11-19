<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * La foto del producto: donde se guarda y como se reemplaza.
 *
 * <b>Existe porque ahora hay dos clientes que suben la misma imagen</b> —el
 * formulario Blade y POST/PUT /api/v1/gestion/productos—, y la carpeta de
 * destino tiene que ser una sola. Con una copia de estas tres lineas en cada
 * controlador, el dia que alguien cambie el directorio en uno, las fotos
 * quedan repartidas en dos lugares y las viejas dejan de encontrarse.
 *
 * No hace nada mas que eso: no valida —de eso se ocupa ProductoRequest, con
 * 'image', 'mimes' y 'max:2048'— ni decide cuando reemplazar.
 *
 * Requiere `php artisan storage:link`. En la columna queda la ruta relativa,
 * y UrlPublica::deStorage() la convierte en URL al serializar.
 */
final class ImagenProducto
{
    /** Carpeta del disco publico donde vive la foto del producto. */
    private const DIRECTORIO = 'productos';

    private const DISCO = 'public';

    /** Guarda el archivo subido y devuelve la ruta relativa para la columna. */
    public static function guardar(UploadedFile $archivo): string
    {
        return $archivo->store(self::DIRECTORIO, self::DISCO);
    }

    /**
     * Borra una imagen anterior, si existe.
     *
     * Tolera null y rutas que ya no estan: se la llama despues de reemplazar,
     * y que el archivo viejo falte no es un error que deba cortar la edicion.
     */
    public static function borrar(?string $ruta): void
    {
        if ($ruta && Storage::disk(self::DISCO)->exists($ruta)) {
            Storage::disk(self::DISCO)->delete($ruta);
        }
    }
}
