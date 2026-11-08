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
