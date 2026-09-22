<?php

namespace App\Enums;

use App\Enums\Concerns\ConEtiqueta;

/** Decide a que aplicacion entra el usuario al autenticarse. */
enum AmbitoRol: string
{
    use ConEtiqueta;

    case Gestion = 'gestion';
    case Tienda  = 'tienda';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Gestion => 'Gestión',
            self::Tienda  => 'Tienda',
        };
    }

    /**
     * Nombre de la ruta a la que va el usuario despues de ingresar. Es el unico
     * lugar donde se decide: lo usan el login, la invitacion, la verificacion
     * de correo y el middleware 'guest'.
     */
    public function rutaInicio(): string
    {
        return match ($this) {
            self::Gestion => 'gestion.home',
            self::Tienda  => 'cliente.home',
        };
    }

    /** A que ambito pertenece una URL (se usa para no mandar a nadie a una pantalla ajena). */
    public static function deUrl(string $url): self
    {
        $path = '/'.ltrim((string) parse_url($url, PHP_URL_PATH), '/');

        return $path === '/gestion' || str_starts_with($path, '/gestion/')
            ? self::Gestion
            : self::Tienda;
    }
}
