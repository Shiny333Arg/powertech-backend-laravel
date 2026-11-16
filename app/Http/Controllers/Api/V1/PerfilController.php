<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PasswordMovilRequest;
use App\Http\Requests\Api\PerfilMovilRequest;
use App\Http\Resources\PerfilResource;
use App\Models\User;
use App\Services\PerfilService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Perfil del cliente para la app: sus datos personales, los fiscales y su
 * contraseña.
 *
 * Es la pantalla `cliente/perfil/edit.blade.php`, que en la web son dos
 * formularios independientes —datos y contraseña— y aca son dos endpoints,
 * por lo mismo: son dos operaciones distintas, con validaciones distintas, y
 * una puede fallar sin arrastrar a la otra.
 *
 * <b>Siempre es el perfil de quien trae el token.</b> Ninguna de las tres
 * rutas acepta un id: ni en la URL ni en el cuerpo. `$request->user()` es la
 * unica fuente, asi que no hay nada que manipular para editar la cuenta de
 * otro. Los FormRequest tampoco leen `user_id` ni `cliente_id`:
 * `datosDelUsuario()` y `datosDelCliente()` devuelven listas blancas de
 * columnas, asi que mandar esas claves de mas no hace nada.
 *
 * <b>Sin 'api.verificado', igual que la web.</b> routes/web.php deja el
 * bloque de perfil bajo 'auth' + 'ambito:tienda' y exige 'verified' recien en
 * el carrito y el checkout. Tiene que ser asi: si editar el perfil pidiera el
 * correo confirmado, un cliente que se equivoco al escribirlo al registrarse
 * no podria corregirlo nunca. Es la puerta de salida de ese callejon.
 *
 * <b>Lo que escribe no esta aca.</b> La transaccion sobre `users` y
 * `clientes`, y la baja de la verificacion al cambiar el correo, viven en
 * PerfilService, que es el mismo que usa el controlador web.
 */
class PerfilController extends Controller
{
    public function __construct(
        private readonly PerfilService $perfil,
    ) {
    }

    /**
     * GET /api/v1/perfil
     *
     * Los datos actuales mas `documento_bloqueado`, que es lo que este
     * endpoint agrega sobre `/auth/yo` y la razon de que exista: el
     * formulario necesita saber si deshabilitar los campos del documento, y
     * eso es una consulta a `ventas` que no corresponde hacer en cada
     * arranque de la app.
     *
     * <b>Un usuario sin ficha en `clientes` recibe 200, no un error.</b>
     * `usuario.cliente` viaja en null y la app muestra el aviso de "acercate
     * al local" en lugar del formulario, que es lo mismo que ya hace la
     * pantalla de Mi cuenta. La web responde 404 porque no tiene como mostrar
     * media pantalla; la app si.
     */
    public function show(Request $request): PerfilResource
    {
        $user = $this->conDatosFiscales($request->user());

        return new PerfilResource([
            'usuario' => $user,
            'documento_bloqueado' => $user->cliente !== null
                && $this->perfil->documentoBloqueado($user->cliente),
        ]);
    }

    /**
     * PATCH /api/v1/perfil
     *
     * Devuelve el MISMO cuerpo que el GET, mas `message` y
     * `email_reverificacion_pendiente`. La app se queda con el estado nuevo
     * completo sin una llamada de seguimiento, y eso importa en un caso: si
     * cambio el correo, `email_verificado` vuelve a false y el carrito y el
     * checkout quedan bloqueados hasta que confirme. Devolver solo "ok"
     * dejaria a la app creyendo que puede comprar.
     */
    public function update(PerfilMovilRequest $request): PerfilResource
    {
        $user    = $request->user();
        $cliente = $request->cliente();

        $cambioElCorreo = $this->perfil->actualizar(
            $user,
            $cliente,
            $request->datosDelUsuario(),
            $request->datosDelCliente(),
        );

        // load() y no loadMissing(): si cambio el tipo de documento o la
        // condicion de IVA, las relaciones que ya estaban cargadas quedaron
        // viejas y hay que volver a leerlas.
        $user->load(['cliente.tipoDocumento', 'cliente.condicionIva']);

        $recurso = new PerfilResource([
            'usuario' => $user,
            'documento_bloqueado' => $this->perfil->documentoBloqueado($cliente),
        ]);

        return $recurso->additional([
            'message' => $cambioElCorreo
                ? 'Guardamos tus datos. Como cambiaste el correo, te mandamos un enlace a '
                    .$user->email.' para confirmarlo.'
                : 'Listo, guardamos tus datos.',

            // La app lo usa para decidir si mostrar el aviso de verificacion
            // sin tener que comparar el correo anterior con el nuevo.
            'email_reverificacion_pendiente' => $cambioElCorreo,
        ]);
    }

    /**
     * PUT /api/v1/perfil/password
     *
     * <b>No devuelve el usuario ni un token.</b> Cambiar la contraseña no
     * cambia ningun dato del perfil, y los tokens vigentes siguen valiendo:
     * es lo mismo que hace la web, que tampoco cierra las otras sesiones. Si
     * alguna vez se decide revocarlas, hay que decidirlo para los dos
     * canales, no solo para la app.
     *
     * El cast 'hashed' del modelo se encarga del hash.
     */
    public function password(PasswordMovilRequest $request): JsonResponse
    {
        $request->user()->update([
            'password' => $request->nuevaPassword(),
        ]);

        return response()->json([
            'message' => 'Listo, cambiamos tu contraseña.',
        ]);
    }

    /**
     * Las dos relaciones que UsuarioResource necesita para armar el bloque
     * fiscal. Mismo loadMissing que hace AuthController en `/auth/yo`.
     */
    private function conDatosFiscales(User $user): User
    {
        return $user->loadMissing([
            'cliente.tipoDocumento',
            'cliente.condicionIva',
        ]);
    }
}
