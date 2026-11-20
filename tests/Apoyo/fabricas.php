<?php

use App\Models\AlicuotaIva;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\CondicionIva;
use App\Models\OrdenCompra;
use App\Models\Producto;
use App\Models\Promocion;
use App\Models\Proveedor;
use App\Models\TipoDocumento;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

/*
|--------------------------------------------------------------------------
| Fabricas de prueba de la tienda
|--------------------------------------------------------------------------
|
| <b>Las entidades se arman a mano y no con factories.</b> El proyecto solo
| tiene UserFactory, y de los modelos del catalogo ninguno usa el trait
| HasFactory: fabricarlos exigiria tocar Producto, Categoria y Promocion, que
| son codigo de produccion y estan fuera del alcance de estas etapas.
|
| Viven en un archivo propio —que carga Pest.php— y no dentro de un test,
| porque los usan CarritoTest y CheckoutTest. Dos copias de la misma fabrica
| divergen, y cuando divergen el sintoma es un test que pasa contra datos que
| ya no se parecen a los del otro.
|
| Los maestros NO se siembran aca: eso lo hace el beforeEach de cada archivo,
| que es donde se ve.
|
*/


/** Cliente de tienda, activo y con el correo confirmado. */
function clienteDeTienda(array $atributos = []): User
{
    $user = User::factory()->create($atributos);

    Cliente::create([
        'user_id' => $user->id,
        'razon_social' => 'Cliente de prueba',
        'tipo_documento_id' => TipoDocumento::query()->value('id'),
        'nro_doc' => (string) fake()->unique()->numberBetween(10_000_000, 99_999_999),
        'condicion_iva_id' => CondicionIva::query()->value('id'),
        'activo' => true,
    ]);

    return $user->refresh();
}

/** Autentica con la ability 'tienda', que es la que emite la app. */
function comoCliente(User $user): User
{
    Sanctum::actingAs($user, [config('movil.ability')]);

    return $user;
}

/*
|--------------------------------------------------------------------------
| Tokens REALES
|--------------------------------------------------------------------------
|
| <b>Sanctum::actingAs() no sirve para probar revocacion, y conviene saber por
| que antes de usarlo.</b> Instala un mock de Mockery con
| shouldIgnoreMissing(false), asi que $token->delete() devuelve false en
| silencio, sin excepcion y sin tocar la base. Ademas no persiste ninguna fila
| en personal_access_tokens. Una asercion del tipo "el token quedo revocado"
| contra ese mock pasa SIEMPRE y no prueba nada.
|
| Todo lo que afirme sobre revocacion, vigencia o persistencia tiene que emitir
| con createToken() y afirmar contra la base con assertDatabaseMissing /
| assertDatabaseHas. Eso es lo que hacen estos helpers.
|
*/

/** Token de gestion con la vigencia real de config('gestion.token_horas'). */
function tokenDeGestion(User $user, ?DateTimeInterface $vence = null): Laravel\Sanctum\NewAccessToken
{
    return $user->createToken(
        'Panel de prueba',
        [config('gestion.ability')],
        $vence ?? now()->addHours((int) config('gestion.token_horas')),
    );
}

/** Token de tienda con la vigencia real de config('movil.token_dias'). */
function tokenDeTienda(User $user, ?DateTimeInterface $vence = null): Laravel\Sanctum\NewAccessToken
{
    return $user->createToken(
        'App de prueba',
        [config('movil.ability')],
        $vence ?? now()->addDays((int) config('movil.token_dias')),
    );
}

/**
 * Las claves que el intercambio deja en la sesion, para poder probar el
 * middleware de coherencia sin pasar por el circuito completo.
 *
 * @return array<string, mixed>
 */
function sesionDeGestion(Laravel\Sanctum\NewAccessToken $token): array
{
    return [
        App\Support\SesionGestion::TOKEN     => $token->plainTextToken,
        App\Support\SesionGestion::TOKEN_ID  => $token->accessToken->getKey(),
        App\Support\SesionGestion::EXPIRA_EL => $token->accessToken->expires_at?->toIso8601String(),
    ];
}

/**
 * Autentica a un empleado en el panel: sesion web MAS el token que la origino.
 *
 * <b>Desde F2, actingAs() por si solo no alcanza para entrar a /gestion/*.</b>
 * El middleware 'gestion.token' exige que la sesion referencie un token de
 * gestion vigente, porque la sesion Blade y el token son dos credenciales con
 * relojes distintos y una sesion viva con un token muerto serviria pantallas
 * cuyas llamadas a la API fallarian todas con 401.
 *
 * Reemplaza a $this->actingAs($empleado) en las pruebas del panel y devuelve el
 * TestCase, asi que se puede seguir encadenando.
 */
function comoEmpleado(User $empleado): Tests\TestCase
{
    return test()
        ->actingAs($empleado)
        ->withSession(sesionDeGestion(tokenDeGestion($empleado)));
}

function categoriaDePrueba(): Categoria
{
    return Categoria::firstOrCreate(
        ['slug' => 'monitor'],
        ['nombre' => 'Monitores', 'orden' => 1, 'activo' => true],
    );
}

/**
 * Producto publicado y con stock, salvo que se indique otra cosa.
 *
 * `stock_disponible` es una columna GENERADA: no se puede escribir. Se setea
 * `stock_fisico` y el motor calcula el disponible.
 */
function productoDePrueba(array $atributos = []): Producto
{
    return Producto::create(array_merge([
        'categoria_id' => categoriaDePrueba()->id,
        'alicuota_iva_id' => AlicuotaIva::query()->value('id'),
        'codigo' => 'P-'.fake()->unique()->numberBetween(1000, 9999),
        'nombre' => 'Monitor de prueba',
        'marca' => 'Genérica',
        'precio' => 100000.00,
        'stock_fisico' => 10,
        'publicado_app' => true,
        'activo' => true,
    ], $atributos));
}

/**
 * Proveedor activo, por correo y con CUIT valido.
 *
 * <b>Vivia dentro de AutorizacionEstadoTest.</b> Se movio aca en la entrega D,
 * cuando la paso a necesitar tambien la prueba de la API de proveedores: Pest
 * carga todos los archivos en el mismo proceso, asi que declararla dos veces no
 * compila, y una segunda copia con otro nombre es de las que despues divergen.
 * Es la regla que explica la cabecera de este archivo.
 *
 * El CUIT se arma con el digito verificador correcto porque ProveedorRequest lo
 * valida con App\Rules\Cuit: un numero al azar de 11 digitos entra igual por
 * Proveedor::create() —el modelo no valida— pero despues no se puede editar por
 * la API sin corregirlo.
 */
function proveedorDePrueba(array $atributos = []): Proveedor
{
    return Proveedor::create(array_merge([
        'razon_social'     => 'Proveedor de prueba',
        'cuit'             => cuitValido(),
        'condicion_iva_id' => CondicionIva::query()->value('id'),
        'canal_pedido'     => 'email',
        'activo'           => true,
    ], $atributos));
}

/**
 * Un CUIT distinto en cada llamada, con digito verificador correcto.
 *
 * Los diez primeros digitos salen de un contador, asi que no hay colisiones
 * contra el indice unico de la tabla, y el ultimo se calcula con el mismo
 * modulo 11 que verifica App\Rules\Cuit.
 */
function cuitValido(): string
{
    static $siguiente = 0;

    $base = '30'.str_pad((string) (71000000 + $siguiente++), 8, '0', STR_PAD_LEFT);

    $pesos = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
    $suma  = 0;

    foreach ($pesos as $posicion => $peso) {
        $suma += ((int) $base[$posicion]) * $peso;
    }

    $resto = $suma % 11;

    $verificador = match ($resto) {
        0       => 0,
        1       => 9,
        default => 11 - $resto,
    };

    return $base.$verificador;
}

/**
 * Orden de compra cruda, en el estado que se le pida.
 *
 * <b>Vivia dentro de ProveedorTest como ordenAbiertaDe().</b> Se movio aca en
 * la entrega E.1, cuando la paso a necesitar tambien la prueba de la API de
 * compras: Pest carga todos los archivos en el mismo proceso, asi que
 * declararla dos veces no compila. Es la regla que explica la cabecera de este
 * archivo.
 *
 * <b>Escribe la orden directo y no pasa por OrdenCompraService.</b> Es a
 * proposito: las pruebas necesitan pedidos ya aprobados, enviados o repartidos,
 * y llegar a esos estados por el servicio obligaria a aprobar y enviar de
 * verdad —con su correo al proveedor— para armar el escenario de un test que no
 * es sobre eso. El alta por el servicio se prueba contra el endpoint, que es
 * donde importa.
 *
 * Para armar una MADRE hay que pasar proveedor_id en null explicito: sin la
 * clave, la fabrica le crea un proveedor, porque lo normal es un pedido con
 * destinatario.
 *
 *   ordenDeCompraDePrueba()                           simple, borrador
 *   ordenDeCompraDePrueba(['proveedor_id' => null])    madre
 *   ordenDeCompraDePrueba(['estado' => 'enviada'])     simple, enviada
 */
function ordenDeCompraDePrueba(array $atributos = []): OrdenCompra
{
    static $siguiente = 0;

    if (! array_key_exists('proveedor_id', $atributos)) {
        $atributos['proveedor_id'] = proveedorDePrueba()->id;
    }

    // La serie arranca en 9.000.000 y no en 1 para no chocar con el indice
    // unico de `numero` cuando el mismo test crea ordenes por el endpoint: esas
    // las numera el Numerador real, que empieza en OC-0000001. El contador es
    // estatico y no OrdenCompra::count() porque una madre y sus hijas comparten
    // la cuenta y el +1 se repetiria.
    return OrdenCompra::create(array_merge([
        'numero'         => 'OC-'.str_pad((string) (9_000_000 + ++$siguiente), 7, '0', STR_PAD_LEFT),
        'estado'         => 'borrador',
        'origen'         => 'manual',
        'total_estimado' => 0,
    ], $atributos));
}

/*
|--------------------------------------------------------------------------
| Compras a proveedores
|--------------------------------------------------------------------------
|
| <b>Vivian dentro de OrdenCompraTest.</b> Se movieron aca en E.2.a, cuando las
| paso a necesitar tambien la prueba de las transiciones: Pest carga todos los
| archivos en el mismo proceso, asi que declararlas dos veces no compila, y una
| segunda copia con otro nombre es de las que despues divergen. Es la misma
| regla que explica la cabecera de este archivo y el comentario de
| proveedorDePrueba().
|
*/

/**
 * Rol con los permisos de compras, menos 'compra.aprobar_propias'.
 *
 * Segun la matriz real de rol_permiso, vendedor y cajero no tienen ninguno de
 * los ocho permisos de compras. El administrativo tiene siete: todos menos
 * 'compra.aprobar_propias', que es del administrador. Esa diferencia es la que
 * hace cumplir la separacion de funciones: quien carga un pedido por la API no
 * lo puede aprobar el mismo.
 */
function administrativoDeCompras(): User
{
    return User::factory()->empleado('administrativo')->create();
}

/**
 * Producto pedible: con proveedor y con costo.
 *
 * Las dos cosas hacen falta. Sin proveedor, OrdenCompraRequest rechaza el
 * renglon —no hay a quien pedirselo— y sin costo el total estimado del pedido
 * siempre da cero, que es justo lo que varias pruebas verifican.
 */
function productoPedible(?Proveedor $proveedor = null, array $cambios = []): Producto
{
    $proveedor ??= proveedorDePrueba();

    return productoDePrueba(array_merge([
        'proveedor_id' => $proveedor->id,
        'costo'        => 1500.00,
    ], $cambios));
}

/**
 * Renglon del cuerpo del alta, con la forma que espera OrdenCompraRequest.
 *
 * @return array<string, mixed>
 */
function renglonDePedido(Producto $producto, int $cantidad = 2): array
{
    return [
        'producto_id'     => $producto->id,
        'cantidad_pedida' => $cantidad,
    ];
}

/** Le agrega un renglon a una orden cruda, sin pasar por el servicio. */
function lineaCrudaEn(OrdenCompra $orden, Producto $producto, int $cantidad = 2, int $recibida = 0): void
{
    $orden->lineas()->create([
        'producto_id'       => $producto->id,
        'proveedor_id'      => $producto->proveedor_id,
        'cantidad_pedida'   => $cantidad,
        'cantidad_recibida' => $recibida,
        'costo_unitario'    => $producto->costo ?? 0,
    ]);

    $orden->recalcularTotalEstimado();
}

/**
 * Proveedor al que SE LE PUEDE mandar el remito.
 *
 * proveedorDePrueba() deja el canal en 'email' pero no carga casilla, y
 * OrdenCompraService::exigirCanalEmail() exige las dos cosas: el pedido se
 * aprueba igual, pero no se puede enviar. Las pruebas del envio necesitan un
 * proveedor completo, y las del envio fallido necesitan justamente el otro.
 */
function proveedorConCasilla(array $atributos = []): Proveedor
{
    return proveedorDePrueba(array_merge([
        'email' => 'pedidos'.fake()->unique()->numberBetween(1000, 9999).'@proveedor.test',
    ], $atributos));
}

/**
 * Pedido de UN proveedor, con un renglon cargado y en el estado que se pida.
 *
 * Se arma a mano —sin pasar por OrdenCompraService— a proposito: las pruebas de
 * las transiciones necesitan partir de un pedido ya 'aprobada' o 'enviada', y
 * llegar ahi por el circuito completo las haria depender de las operaciones que
 * estan probando.
 */
function pedidoDeUnProveedor(
    string $estado = 'borrador',
    ?Proveedor $proveedor = null,
    array $atributos = [],
    int $cantidad = 2,
    int $recibida = 0,
): OrdenCompra {
    $proveedor ??= proveedorConCasilla();

    $orden = ordenDeCompraDePrueba(array_merge([
        'proveedor_id' => $proveedor->id,
        'estado'       => $estado,
    ], $atributos));

    lineaCrudaEn($orden, productoPedible($proveedor), $cantidad, $recibida);

    return $orden->fresh('lineas');
}

/**
 * Pedido mezclado en borrador: un renglon por proveedor, sin destinatario
 * propio. Es la madre que al aprobarse se reparte en una orden por proveedor.
 */
function pedidoMezcladoEnBorrador(int $proveedores = 2, array $atributos = []): OrdenCompra
{
    $orden = ordenDeCompraDePrueba(array_merge([
        'proveedor_id' => null,
        'estado'       => 'borrador',
    ], $atributos));

    for ($i = 0; $i < $proveedores; $i++) {
        lineaCrudaEn($orden, productoPedible(proveedorConCasilla()));
    }

    return $orden->fresh('lineas');
}

/** Promocion vigente del canal online aplicada a un producto. */
function promocionSobre(Producto $producto, array $atributos = []): Promocion
{
    $promocion = Promocion::create(array_merge([
        'nombre' => 'Promo de prueba',
        'tipo' => 'porcentaje',
        'valor' => 10,
        'canal' => 'online',
        'prioridad' => 10,
        'acumulable' => false,
        'vigencia_desde' => now()->subDay(),
        'vigencia_hasta' => now()->addDay(),
        'activo' => true,
        'creado_por' => User::factory()->empleado('administrativo')->create()->id,
    ], $atributos));

    $promocion->productos()->attach($producto->id);

    return $promocion;
}
