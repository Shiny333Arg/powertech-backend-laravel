<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;

/**
 * Edicion del perfil del cliente: sus datos personales y los fiscales.
 *
 * <b>Existe porque la operacion toca dos tablas y tiene una consecuencia que
 * no se ve en los datos.</b> `users` y `clientes` se escriben juntas o no se
 * escribe ninguna, y cambiar el correo tiene un efecto aparte: la cuenta
 * queda sin verificar y hay que mandar el enlace de nuevo. Eso vivia inline
 * en ProfileController (Blade) y lo necesita igual Api\V1\PerfilController.
 * Copiado en los dos, el dia que se agregue un campo o cambie la regla del
 * correo, la web y la app harian cosas distintas con el mismo formulario.
 *
 * <b>No valida nada.</b> Lo que entra ya paso por ProfileUpdateRequest —o por
 * PerfilMovilRequest, que hereda de el— y llega como las dos listas de
 * columnas que arma ReglasDatosCliente. Este servicio escribe; decidir si los
 * datos son validos es del FormRequest, que es el unico lugar donde estan las
 * reglas.
 */
class PerfilService
{
    /**
     * Guarda los datos del perfil y devuelve si cambio el correo.
     *
     * <b>El correo nuevo arranca sin verificar.</b> Se limpia
     * `email_verified_at` y se manda el enlace: con una direccion sin
     * confirmar el cliente puede navegar y ver su historial, pero no armar
     * pedidos ni pagar. Marcarla verificada sola dejaria entrar a comprar con
     * un correo que nadie demostro tener.
     *
     * El envio del aviso va FUERA de la transaccion, a proposito: si el
     * servidor de correo falla, los datos ya guardados no se pierden por eso.
     * Al reves —adentro— un fallo del correo haria rollback de un cambio de
     * perfil perfectamente valido.
     *
     * @param  array<string, mixed>  $datosUsuario  columnas de `users`, de datosDelUsuario()
     * @param  array<string, mixed>  $datosCliente  columnas de `clientes`, de datosDelCliente()
     * @return bool  true si el correo cambio y hay una verificacion pendiente
     */
    public function actualizar(
        User $user,
        Cliente $cliente,
        array $datosUsuario,
        array $datosCliente,
    ): bool {
        $cambioElCorreo = DB::transaction(function () use ($user, $cliente, $datosUsuario, $datosCliente) {
            $user->fill($datosUsuario);

            // isDirty() se consulta DESPUES del fill y ANTES del save: es la
            // unica ventana en la que el modelo sabe que el valor cambio.
            $cambioElCorreo = $user->isDirty('email');

            if ($cambioElCorreo) {
                $user->email_verified_at = null;
            }

            $user->save();

            $cliente->update($datosCliente);

            return $cambioElCorreo;
        });

        if ($cambioElCorreo) {
            $user->sendEmailVerificationNotification();
        }

        return $cambioElCorreo;
    }

    /**
     * El documento del cliente no se puede cambiar por autogestion.
     *
     * Queda fijo una vez que tiene compras: identifica a quien se le facturo.
     * Los comprobantes ya emitidos no cambiarian —copian los datos al
     * emitirse— pero el historial pasaria a mostrar compras de un documento
     * que ya no es el suyo. Ese cambio lo hace un empleado.
     *
     * <b>La validacion hace la misma pregunta por su cuenta.</b>
     * ProfileUpdateRequest::documentoBloqueado() repite este `exists()`, y se
     * dejo asi a proposito: ese FormRequest es validacion pura, sin
     * dependencias de servicios, y es el que la API hereda sin modificar.
     * Meterle una resolucion del contenedor para ahorrar una linea no paga.
     * Lo que si importa: si esta regla cambia —por ejemplo, que el documento
     * se bloquee recien con una compra facturada— hay que cambiarla en los
     * dos lugares.
     *
     * Este metodo es el que usan los dos formularios, el web y el de la app,
     * para deshabilitar los campos.
     */
    public function documentoBloqueado(Cliente $cliente): bool
    {
        return Venta::where('cliente_id', $cliente->id)->exists();
    }
}
