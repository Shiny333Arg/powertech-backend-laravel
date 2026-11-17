<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Quien esta mirando el catalogo, y si tiene derecho a ver precios.
 *
 * <b>El catalogo es publico y la autenticacion es OPCIONAL.</b> Esa
 * combinacion no se resuelve con el middleware 'auth:sanctum': ese exige
 * token y devuelve 401 cuando falta o cuando es invalido, que convertiria una
 * consulta publica en un error. Las rutas del catalogo no llevan middleware de
 * autenticacion y el usuario se resuelve aca.
 *
 * <b>Un token vencido, revocado o inventado no rompe nada.</b> El guard de
 * Sanctum devuelve null en los tres casos en lugar de lanzar, asi que el
 * visitante queda tratado como invitado y la consulta responde 200 sin
 * precios. Es el comportamiento buscado: alguien que abre la app despues de
 * 30 dias tiene que poder seguir navegando la vitrina mientras la app
 * descubre que le toca volver a ingresar.
 *
 * <b>Se usa el guard 'sanctum' explicitamente y no $request->user().</b> Aquel
 * resuelve con el guard por defecto ('web', de sesion) y haria que el mismo
 * endpoint se comportara distinto segun si la peticion trae cookie de sesion
 * o no. Con el guard nombrado, el unico criterio es el token.
 *
 * <b>Ver precios exige las cuatro condiciones.</b> No alcanza con tener un
 * token valido: la cuenta tiene que estar activa, tiene que ser del ambito
 * tienda —un empleado no mira la vitrina de clientes con su token— y el token
 * tiene que llevar la ability de la app. Un token de gestion, si alguna vez
 * existe, no abre el precio de la tienda por el solo hecho de ser valido.
 *
 * El resultado se memoriza en el request porque una sola respuesta puede
 * instanciar 24 resources y los cuatro chequeos no tienen por que repetirse.
 */
final class VisitanteTienda
{
    private const CLAVE = 'powertech.visitante_tienda';

    private function __construct(
        private readonly ?User $usuario,
    ) {
    }

    public static function de(Request $request): self
    {
        $cacheado = $request->attributes->get(self::CLAVE);

        if ($cacheado instanceof self) {
            return $cacheado;
        }

        /** @var User|null $usuario */
        $usuario = auth('sanctum')->user();

        $visitante = new self($usuario);

        $request->attributes->set(self::CLAVE, $visitante);

        return $visitante;
    }

    public function usuario(): ?User
    {
        return $this->usuario;
    }

    public function estaAutenticado(): bool
    {
        return $this->usuario !== null;
    }

    /**
     * <b>Este metodo es el unico lugar donde se decide si se muestran
     * precios.</b> Todo lo que exponga un importe —el precio de lista, el
     * final, el descuento, el valor de una promocion— tiene que preguntarle a
     * el. Si aparece un endpoint nuevo con importes, la pregunta va aca y no
     * se reimplementa el criterio al lado.
     */
    public function puedeVerPrecios(): bool
    {
        $usuario = $this->usuario;

        if ($usuario === null) {
            return false;
        }

        return $usuario->activo
            && $usuario->esCliente()
            && $usuario->tokenCan((string) config('movil.ability'));
    }
}