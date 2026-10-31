<?php

namespace App\Enums;

use Illuminate\Database\Eloquent\Builder;

/**
 * Criterios de orden de la vista de resultados.
 *
 * El orden vive en un enum y no en un match suelto del controlador porque el
 * valor llega por querystring: cualquier cadena que no sea uno de estos casos
 * tiene que caer en Relevancia y no romper la consulta. tryFrom() lo resuelve
 * en un solo lugar.
 *
 * Todos los criterios desempatan por id. Sin desempate, dos productos con el
 * mismo precio pueden intercambiarse entre la pagina 1 y la 2 y aparecer
 * duplicados o desaparecer al paginar.
 */
enum OrdenCatalogo: string
{
    case Relevancia = 'relevancia';
    case PrecioAsc  = 'precio-asc';
    case PrecioDesc = 'precio-desc';
    case NombreAsc  = 'nombre-asc';
    case NombreDesc = 'nombre-desc';
    case Recientes  = 'recientes';

    public static function desde(?string $valor): self
    {
        return self::tryFrom((string) $valor) ?? self::Relevancia;
    }

    public function etiqueta(): string
    {
        return match ($this) {
            self::Relevancia => 'Relevancia',
            self::PrecioAsc  => 'Menor precio',
            self::PrecioDesc => 'Mayor precio',
            self::NombreAsc  => 'Nombre: A-Z',
            self::NombreDesc => 'Nombre: Z-A',
            self::Recientes  => 'Más recientes',
        };
    }

    /** @return array<string, string> value => etiqueta, para el select */
    public static function opciones(): array
    {
        $opciones = [];

        foreach (self::cases() as $caso) {
            $opciones[$caso->value] = $caso->etiqueta();
        }

        return $opciones;
    }

    /**
     * Aplica el orden a la consulta.
     *
     * @param  bool  $conRelevancia  true si la consulta trae la columna
     *                               calculada 'relevancia' (solo existe cuando
     *                               hubo termino de busqueda).
     */
    public function aplicar(Builder $query, bool $conRelevancia): Builder
    {
        match ($this) {
            self::Relevancia => $conRelevancia
                // Con termino: el puntaje de coincidencia manda.
                ? $query->orderByDesc('relevancia')->orderBy('productos.nombre')
                // Sin termino (categoria, marca o promocion sola) no hay nada
                // que puntuar: los destacados primero y despues lo mas nuevo.
                : $query->orderByDesc('productos.destacado')->orderByDesc('productos.created_at'),

            self::PrecioAsc  => $query->orderBy('productos.precio'),
            self::PrecioDesc => $query->orderByDesc('productos.precio'),
            self::NombreAsc  => $query->orderBy('productos.nombre'),
            self::NombreDesc => $query->orderByDesc('productos.nombre'),
            self::Recientes  => $query->orderByDesc('productos.created_at'),
        };

        return $query->orderByDesc('productos.id');
    }
}
