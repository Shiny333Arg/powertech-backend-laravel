<?php

namespace App\Support;

use Illuminate\Contracts\Session\Session;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Las claves con las que la sesion web del panel guarda el token de gestion,
 * en un solo lugar.
 *
 * <b>Por que la sesion guarda el token.</b> El panel son 82 vistas Blade con
 * navegacion tradicional: cada clic de menu es una recarga completa, y una
 * recarga vacia la memoria del navegador. El requisito 6 de la consigna pide
 * que el token viva "en memoria" y admite, como alcance minimo, volver a pedir
 * el ingreso al recargar; eso ultimo seria inusable aca. La salida es que lo
 * que sobreviva a la recarga sea la sesion del SERVIDOR, no el navegador: cada
 * pagina vuelve a inyectar el token en una variable de modulo. En el navegador
 * sigue estando solo en memoria, que es lo que el requisito exige.
 *
 * <b>Donde NO vive el token:</b> localStorage, sessionStorage, ninguna cookie
 * propia, ningun campo oculto, ningun atributo data- del DOM, ninguna URL. La
 * unica cookie que lleva el navegador es la de sesion de Laravel, que es
 * HttpOnly y no contiene el token sino el identificador de la sesion que lo
 * guarda del lado del servidor.
 *
 * Como la sesion ahora contiene un secreto, conviene SESSION_ENCRYPT=true.
 * Activarlo deja ilegibles los payloads existentes y desloguea una vez a todo
 * el mundo —Laravel captura el fallo de descifrado y arranca una sesion
 * vacia—, asi que va en una ventana de bajo uso.
 */
final class SesionGestion
{
    /** El token en claro. Lo lee el layout para inyectarlo en memoria JS. */
    public const TOKEN = 'gestion.token';

    /** La clave primaria en personal_access_tokens. Es con esto que se verifica. */
    public const TOKEN_ID = 'gestion.token_id';

    /** Corte barato antes de ir a la base. No reemplaza la verificacion. */
    public const EXPIRA_EL = 'gestion.token_expira_el';

    /** @return list<string> */
    public static function claves(): array
    {
        return [self::TOKEN, self::TOKEN_ID, self::EXPIRA_EL];
    }

    public static function guardar(Session $session, string $enClaro, PersonalAccessToken $token): void
    {
        $session->put(self::TOKEN, $enClaro);
        $session->put(self::TOKEN_ID, $token->getKey());
        $session->put(self::EXPIRA_EL, $token->expires_at?->toIso8601String());
    }

    public static function olvidar(Session $session): void
    {
        $session->forget(self::claves());
    }

    public static function tokenId(Session $session): ?int
    {
        $id = $session->get(self::TOKEN_ID);

        return $id === null ? null : (int) $id;
    }

    public static function token(Session $session): ?string
    {
        $token = $session->get(self::TOKEN);

        return is_string($token) && $token !== '' ? $token : null;
    }
}
