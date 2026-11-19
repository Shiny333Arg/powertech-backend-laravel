<?php

use App\Models\AlicuotaIva;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\CondicionIva;
use App\Models\Producto;
use App\Models\Promocion;
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
