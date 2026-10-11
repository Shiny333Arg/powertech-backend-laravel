# PowerTech

Sistema de gestión comercial y tienda online para una empresa de venta de tecnología (responsable inscripto en Argentina). Reemplaza al sistema anterior —PHP a medida + Angular + MariaDB— por una aplicación Laravel única que cubre el circuito completo: catálogo, stock, ventas de mostrador y online, cobros, facturación electrónica, entregas y reposición automática de inventario.

> **Estado del proyecto:** en desarrollo. El circuito de venta, stock, compras y reposición automática están implementados. La facturación electrónica corre hoy contra un autorizador simulado (`AutorizadorSimulado`); la integración real con los web services de ARCA/AFIP está pendiente. Ver [Estado y pendientes](#estado-y-pendientes).

---

## Tabla de contenidos

- [Descripción del sistema](#descripción-del-sistema)
- [Conceptos de negocio que hay que entender antes de tocar el código](#conceptos-de-negocio-que-hay-que-entender-antes-de-tocar-el-código)
- [Stack tecnológico](#stack-tecnológico)
- [Requisitos previos](#requisitos-previos)
- [Puesta en marcha](#puesta-en-marcha)
- [Variables de entorno críticas](#variables-de-entorno-críticas)
- [Carga inicial de datos](#carga-inicial-de-datos)
- [Procesos de fondo](#procesos-de-fondo)
- [Estructura del proyecto](#estructura-del-proyecto)
- [Mapa de rutas](#mapa-de-rutas)
- [Convenciones del código](#convenciones-del-código)
- [Estado y pendientes](#estado-y-pendientes)

---

## Descripción del sistema

PowerTech es **un solo negocio con dos ámbitos de aplicación** que comparten catálogo, stock y numeración:

| Ámbito | Ruta base | Quién entra |
|---|---|---|
| **Tienda** | `/` | Clientes (registrados o anónimos) |
| **Gestión** | `/gestion` | Empleados |

El ámbito no se elige: lo determina el rol del usuario al autenticarse (`AmbitoRol`), y el middleware `ambito:gestion` / `ambito:tienda` impide que alguien abra una pantalla que no le corresponde.

### Roles

Cinco roles fijos, **uno solo por empleado** (no se acumulan):

| Rol | Ámbito | Alcance |
|---|---|---|
| **Administrador** | Gestión | Acceso completo, sin restricciones |
| **Administrativo** | Gestión | Productos, categorías, proveedores, promociones, empleados, aprobación de órdenes de compra, ajustes de inventario, dashboard gerencial |
| **Vendedor** | Gestión | Presupuestos y ventas de mostrador, validación de presupuestos, historial propio |
| **Cajero** | Gestión | Cobros de ventas confirmadas, historial propio |
| **Cliente** | Tienda | Carrito, compra online, pago con Mercado Pago, tracking de envíos, perfil |

Los permisos son granulares (`tabla permisos` + `rol_permiso`) y se consultan con `$user->tienePermiso('clave')`. **Lo que no se asigna explícitamente, no se puede hacer.** Los proveedores *no* son usuarios: no tienen login.

### Módulos

- **Catálogo** — categorías planas, productos con una foto, marca como atributo, un proveedor por producto, punto y cantidad de reposición.
- **Inventario** — tres valores de stock por producto, kardex completo de movimientos, ajustes con motivo obligatorio, carga inicial por conteo físico.
- **Ventas** — presupuestos, ventas de mostrador y ventas online como una única entidad diferenciada por el atributo `canal`. Numeración interna correlativa y compartida entre ambos canales.
- **Cobros** — efectivo, tarjeta y transferencia en mostrador; Mercado Pago Checkout Pro en la tienda.
- **Facturación electrónica** — factura A/B, notas de crédito y débito, IVA discriminado, log de cada solicitud a ARCA para poder reintentar sin duplicar.
- **Entregas** — despacho a domicilio o retiro en local, con tracking para el cliente.
- **Compras** — órdenes de compra a proveedores con aprobación obligatoria, envío del remito por correo y recepciones parciales.
- **Reposición automática** — generación de órdenes en borrador cuando el stock disponible cruza el punto de reposición.
- **Promociones** — los únicos descuentos del sistema; no existe el descuento manual.

---

## Conceptos de negocio que hay que entender antes de tocar el código

Estas cuatro reglas explican por qué el código está como está. Ignorarlas lleva a "arreglar" cosas que no están rotas.

### 1. El stock son tres números, no uno

| Valor | Qué es | Signo |
|---|---|---|
| **Físico** | Unidades que realmente están en el depósito | Nunca negativo |
| **Comprometido** | Unidades vendidas o reservadas que todavía no salieron | Nunca negativo |
| **Disponible** | `físico − comprometido`. Lo único que ve el vendedor y el cliente | **Puede ser negativo** |

Un disponible negativo no es un bug: es una **preventa**, y mide exactamente cuántas unidades se le deben a clientes y todavía no se le compraron al proveedor.

**El stock físico baja cuando la mercadería sale del depósito, no cuando se cobra.** Cobrar una preventa no mueve nada: la venta queda pagada, facturada y comprometida, esperando la reposición.

| Evento | Físico | Comprometido | Disponible |
|---|---|---|---|
| Carga inicial / recepción de compra | +q | — | +q |
| Presupuesto emitido | — | — | — |
| Venta confirmada | — | +q | −q |
| Pago acreditado / factura emitida | — | — | — |
| **Entrega de la mercadería** | −q | −q | — |
| Venta cancelada o presupuesto vencido | — | −q | +q |
| Devolución con nota de crédito | +q | — | +q |
| Ajuste de inventario | ±q | — | ±q |

Ninguna pantalla escribe `productos.stock_fisico` directamente: **todo cambio deja un movimiento de stock** con tipo, cantidad, responsable y documento de origen.

### 2. Nada con historia se elimina

- **Usuarios y productos:** solo baja lógica (`activo = false`). Borrarlos rompería la trazabilidad de ventas, cobros y comprobantes ya emitidos.
- **Categorías:** baja lógica, o borrado físico únicamente si ningún producto las referencia. La FK con `ON DELETE RESTRICT` ya hace cumplir la regla en el motor; la aplicación solo traduce el rechazo a un mensaje entendible.
- **Ventas:** no se eliminan ni se dan de baja. La que no prospera se **cancela**; la facturada que se revierte se **devuelve** con nota de crédito. Su fin de vida es siempre un estado, nunca una ausencia.
- **Órdenes de compra, pagos, comprobantes y movimientos de stock:** no se modifican ni se eliminan. Un error se corrige con un movimiento inverso o una nota de crédito.

Por eso **no hay rutas DELETE** para ventas, compras, pagos, comprobantes ni movimientos. No es un olvido.

> **Baja lógica = desactivación.** Es un solo mecanismo, no dos. No se usa `deleted_at` ni el trait `SoftDeletes` en ninguna parte.

### 3. Venta y comprobante no son la misma cosa

Un presupuesto es una venta sin comprobante. Una venta devuelta tiene dos (factura + nota de crédito). La **numeración interna** de la venta se asigna al crear el documento, es correlativa, compartida por ambos canales y no cambia nunca; la **numeración fiscal** es independiente, por punto de venta y tipo de comprobante, y la autoriza ARCA.

Las facturas se emiten **al acreditarse el pago**, nunca antes. Una venta facturada y no entregada es el caso normal de la preventa.

### 4. El comprobante es inmutable una vez que tiene CAE

Toda corrección se resuelve emitiendo una nota de crédito asociada. Cada intento contra ARCA queda registrado en `afip_solicitudes` con su request y su response.

---

## Stack tecnológico

| Capa | Tecnología |
|---|---|
| Framework | Laravel (estructura moderna: `bootstrap/app.php`, sin `Kernel.php`) |
| Lenguaje | PHP 8.2+ |
| Base de datos | MariaDB / MySQL |
| ORM | Eloquent |
| Autenticación | Laravel Breeze (stack Blade) + login con Google |
| Vistas | Blade + Bootstrap 5 |
| Build de assets | Vite |
| Colas y caché | Driver `database` (requerido, ver abajo) |
| Pasarela de pago | Mercado Pago Checkout Pro |
| Facturación | Web services ARCA/AFIP (hoy, autorizador simulado) |

---

## Requisitos previos

- **PHP 8.2 o superior**, con las extensiones `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `json`, `mbstring`, `openssl`, `pcre`, `pdo`, `pdo_mysql`, `tokenizer`, `xml`, `gd` (fotos de producto) y `soap` (web services de ARCA).
- **Composer 2.x**
- **Node.js 18+** y npm
- **MariaDB 10.6+** o **MySQL 8.0+**
- Un servidor SMTP accesible (invitaciones de empleado, verificación de correo, recupero de contraseña y envío de remitos a proveedores).

---

## Puesta en marcha

### 1. Clonar e instalar dependencias

```bash
git clone <url-del-repositorio> powertech
cd powertech

composer install
npm install
```

### 2. Configurar el entorno

```bash
cp .env.example .env
php artisan key:generate
```

Editar `.env` con los datos de la base y del correo. Ver [Variables de entorno críticas](#variables-de-entorno-críticas) — hay tres que **no son opcionales** y rompen el circuito de reposición si se dejan en su valor por defecto.

### 3. Crear la base de datos

El esquema se crea íntegramente con migraciones, sobre una **base nueva y vacía**. No se importa el esquema ni los datos del sistema anterior.

```sql
CREATE DATABASE powertech CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4. Migrar y cargar los datos de configuración

```bash
php artisan migrate --seed
```

Esto crea las 31 tablas del modelo y carga, vía seeders, los datos sin los cuales el sistema no arranca: roles, permisos, asignación rol–permiso, tipos de documento, condiciones frente al IVA, alícuotas de IVA, tipos de comprobante y puntos de venta.

### 5. Enlazar el almacenamiento público

Las fotos de productos y los banners de promociones viven en `storage/app/public`:

```bash
php artisan storage:link
```

### 6. Compilar los assets

```bash
npm run dev      # desarrollo, con recarga en caliente
npm run build    # producción
```

### 7. Levantar la aplicación

En desarrollo hacen falta **tres procesos simultáneos**:

```bash
# Terminal 1 — servidor web
php artisan serve

# Terminal 2 — assets
npm run dev

# Terminal 3 — worker de colas (la cola 'reposicion' hay que nombrarla)
php artisan queue:work --queue=reposicion,default
```

La aplicación queda en `http://localhost:8000`. El endpoint de salud es `/up`.

> **Sin el worker corriendo, la reposición automática no genera órdenes de compra** hasta el barrido diario de las 7:00.

### 8. Crear el primer administrador

Los seeders cargan roles y permisos, pero no usuarios. Para el primer acceso, desde `php artisan tinker`:

```php
\App\Models\User::create([
    'rol_id'            => \App\Models\Rol::where('clave', 'administrador')->value('id'),
    'email'             => 'admin@powertech.local',
    'password'          => 'una-clave-provisoria',
    'nombres'           => 'Nombre',
    'apellido'          => 'Apellido',
    'activo'            => true,
    'email_verified_at' => now(),
]);
```

> Verificar el nombre exacto de la columna de clave del rol contra `RolSeeder` antes de ejecutarlo. A partir de ahí, el resto de los empleados se dan de alta desde `/gestion/empleado`, que les envía una invitación por correo para que elijan su propia contraseña.

---

## Variables de entorno críticas

Más allá de lo habitual (`APP_*`, `DB_*`, `MAIL_*`), estas tres decisiones de infraestructura **no son negociables**:

```dotenv
# El driver 'file' NO soporta Cache::lock() y rompe la reposición automática,
# que serializa la evaluación por producto y por proveedor.
CACHE_STORE=database

# La cola 'reposicion' es propia del circuito de compras.
QUEUE_CONNECTION=database

SESSION_DRIVER=database
```

Además:

```dotenv
# Casilla de compras: va como replyTo en el remito que recibe el proveedor.
# Requiere una clave 'compras' (address, name) en config/mail.php.
MAIL_COMPRAS_ADDRESS=compras@powertech.com.ar
MAIL_COMPRAS_NAME="PowerTech — Compras"

# Login de clientes con cuenta de Google
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"

# Mercado Pago Checkout Pro
MERCADOPAGO_ACCESS_TOKEN=
MERCADOPAGO_PUBLIC_KEY=
MERCADOPAGO_WEBHOOK_SECRET=

# ARCA / AFIP — facturación electrónica
AFIP_CUIT=
AFIP_ENTORNO=homologacion       # homologacion | produccion
AFIP_CERT_PATH=storage/app/afip/cert.crt
AFIP_KEY_PATH=storage/app/afip/private.key
AFIP_PUNTO_VENTA=1
```

> Los certificados de ARCA y los tokens de Mercado Pago **nunca se versionan**. `storage/app/afip/` debe estar en `.gitignore`.

---

## Carga inicial de datos

El sistema entra en producción recién cuando el conteo físico está cargado y conciliado. El orden es:

| Paso | Origen | Contenido |
|---|---|---|
| 1 | Seeders (versionados) | Roles, permisos, rol–permiso, tipos de documento, condiciones de IVA, alícuotas, tipos de comprobante, puntos de venta |
| 2 | Administrativo | Categorías, proveedores, productos (precio, costo, alícuota, punto y cantidad de reposición, foto), empleados con sus datos personales y fiscales |
| 3 | Conteo físico | Stock inicial de cada producto, cargado como movimiento de tipo `inicial` desde `/gestion/stock/carga-inicial`. El comprometido arranca en cero |

A partir del go-live, el stock físico de la base debe coincidir con el depósito, y toda diferencia posterior se explica con un movimiento.

---

## Procesos de fondo

### Worker de colas

```bash
php artisan queue:work --queue=reposicion,default
```

> **Después de tocar código hay que reiniciar el worker.** `queue:work` conserva las clases en memoria y seguirá ejecutando la versión vieja.

### Tareas programadas

Definidas en `routes/console.php`. En producción, un único cron:

```cron
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

| Comando | Horario | Qué hace |
|---|---|---|
| `compras:revisar-reposicion` | 07:00 diario | Barrido de respaldo del restock: detecta cambios de umbral, productos cargados ya en falta y eventos perdidos por un worker caído |

### Cómo funciona la reposición automática

Tres disparadores, ninguno redundante, los tres terminando en `ReposicionService::evaluarProducto()` (que es idempotente):

| Disparador | Cuándo | Qué ve que los otros no |
|---|---|---|
| `MovimientoStockRegistrado` → `EvaluarReposicion` | Cada movimiento de stock | El faltante en el momento mismo de la venta |
| `OrdenCompraCancelada` → `ReponerPorCancelacion` | Al cancelar un pedido | Cancelar no mueve stock, así que el reactivo es ciego a esto |
| `compras:revisar-reposicion` | Diario, 07:00 | Cambios de umbral y eventos perdidos |

Reglas: se evalúa si el producto está activo, tiene `reposicion_automatica`, tiene proveedor y `stock_disponible <= punto_reposicion`. La cantidad pedida es `(punto_reposicion − stock_disponible) + cantidad_reposicion`, de modo que un disponible negativo por preventa arrastra al pedido lo que se le debe a los clientes. Si ya hay un borrador abierto del mismo proveedor, se amplía (solo sube, nunca baja); si la orden ya fue aprobada o enviada, no se toca, y la pantalla avisa que lo pendiente dejó de alcanzar.

Los enganches se registran **explícitamente** en `AppServiceProvider`, no por descubrimiento automático, para que quien lea ese archivo vea que mover stock puede terminar generando una orden de compra.

> **Trampa conocida:** si se cancela una orden porque el proveedor discontinuó el producto, el sistema lo vuelve a pedir en el acto. Hay que apagarle `reposicion_automatica` al producto **antes** de cancelar. El modal de cancelación lo advierte.

---

## Estructura del proyecto

```
app/
├── Console/Commands/      RevisarReposicion (barrido diario de restock)
├── Enums/                 AmbitoRol, EstadoOrdenCompra, TipoMovimientoStock,
│                          OrigenOrdenCompra, ProveedorAuth…
├── Events/                MovimientoStockRegistrado, OrdenCompraCancelada
├── Http/
│   ├── Controllers/
│   │   ├── Auth/          Breeze + invitación de empleados + Google
│   │   ├── gestion/       Todo el backoffice
│   │   └── …              HomeController, HelpController, ProfileController
│   ├── Middleware/        VerificarAmbito, VerificarCuentaActiva
│   └── Requests/          Validación por entidad
├── Listeners/             EvaluarReposicion, ReponerPorCancelacion
├── Mail/                  RemitoDePedido
├── Models/
│   └── Concerns/          TieneBajaLogica, ProtegeContraBorrado
├── Policies/              Una por entidad; definen qué puede cada rol
├── Providers/             AppServiceProvider (eventos, gates, bindings)
└── Services/
    ├── Facturacion/       AutorizadorComprobante (contrato) y su implementación
    ├── StockService       Único punto de escritura del stock
    ├── VentaService       Circuito de venta
    ├── OrdenCompraService Circuito de compras
    └── ReposicionService  Lógica del restock
resources/views/
├── layouts/               auth, cliente, gestion
├── gestion/               Una carpeta por módulo
├── emails/                remito-pedido
└── home/, help/           Tienda pública
routes/
├── web.php                Tienda + gestión
├── auth.php               Breeze: login, registro, invitación, verificación
└── console.php            Tareas programadas
database/
├── migrations/            El esquema completo, desde cero
└── seeders/               RolSeeder, PermisoSeeder, RolPermisoSeeder,
                           PermisosMostradorSeeder, tablas de referencia AFIP
```

### Capas

Los controladores **no contienen lógica de negocio**: validan con un *Form Request*, autorizan con una *Policy* y delegan en un *Service*. Los services son los dueños de las transacciones y los únicos que escriben stock, numeración y comprobantes. Los eventos se emiten **fuera de la transacción**, para que un listener nunca vea un estado que todavía puede revertirse.

---

## Mapa de rutas

Todo el backoffice cuelga de `/gestion` con `['auth', 'ambito:gestion']`.

| Módulo | Prefijo | Acciones propias del dominio |
|---|---|---|
| Dashboard | `/gestion` | Un solo tablero: arma sus bloques según los permisos de quien entra |
| Categorías | `/gestion/category` | CRUD + baja / reactivar (+ `DELETE` solo sin productos) |
| Productos | `/gestion/item` | CRUD + baja / reactivar + publicar / despublicar + movimientos |
| Stock | `/gestion/stock` | Kardex, ajustes con motivo, carga inicial |
| Ventas | `/gestion/sale` | Alta, edición solo de presupuestos, `validar`, `cancelar`. **Sin DELETE** |
| Devoluciones | `/gestion/sale/{venta}/return` | Nota de crédito + reingreso de stock físico |
| Cobros | `/gestion/payment` | Listado, pendientes y alta. Un pago no se edita ni se borra |
| Entregas | `/gestion/delivery` | `lista-retiro`, `despachar`, `entregar`. Único lugar donde baja el físico |
| Comprobantes | `/gestion/invoice` | Lectura y `reintentar`. Sin alta manual ni edición |
| Órdenes de compra | `/gestion/purchase-order` | `aprobar`, `enviar`, `cancelar`, `recibir`, `cerrar`. **Sin DELETE** |
| Clientes | `/gestion/customer` | CRUD + baja / reactivar |
| Empleados | `/gestion/empleado` | CRUD + baja / reactivar + reenviar invitación |
| Proveedores | `/gestion/proveedor` | CRUD + baja / reactivar |
| Promociones | `/gestion/promotion` | CRUD + baja / reactivar |
| Direcciones | `/gestion/{tipo}/{id}/address` | Tabla polimórfica para clientes, proveedores y empleados |
| Búsqueda | `/gestion/search` | Autocompletado JSON de la pantalla de venta |

### Criterio de verbos

| Verbo | Significado | Ejemplos |
|---|---|---|
| `PUT` | Modifica el recurso | Editar un presupuesto, el detalle de un borrador de compra |
| `PATCH` | Cambia un estado del recurso | `baja`, `reactivar`, `publicar` |
| `POST` | Dispara una acción del circuito | `validar`, `cobrar`, `entregar`, `aprobar`, `enviar`, `cancelar` |
| `DELETE` | Solo donde la entidad no es un documento | Direcciones, categorías sin productos |

Las transiciones del circuito de venta son **acciones**, no ediciones: generan movimientos de stock y comprobantes, no actualizan una fila.

### Direcciones: el dueño sale de la URL

`{tipo}` es un discriminador restringido a `clientes|proveedores|empleados`, y nunca llega del cuerpo del formulario: si llegara, cualquiera podría colgarle una dirección fiscal a otro. Los envíos **copian** el domicilio en lugar de apuntar a él, para que editar un perfil no cambie el destino de un despacho ya salido.

---

## Convenciones del código

| Criterio | Decisión |
|---|---|
| Nomenclatura en BD | `snake_case`, tablas en plural (Laravel infiere modelo, tabla y FK) |
| Clave primaria | `id` BIGINT UNSIGNED |
| Timestamps | `created_at` y `updated_at` en todas las tablas |
| Dinero | `DECIMAL(12,2)`. Nunca `FLOAT` |
| Porcentajes | `DECIMAL(4,2)` |
| Stock | Físico y comprometido `INT UNSIGNED`; disponible `INT` con signo, columna generada |
| Baja lógica | Columna `activo` BOOLEAN. **Nunca `SoftDeletes`** |
| Integridad | `ON DELETE` explícito en toda FK: `RESTRICT` por defecto, `CASCADE` solo donde el padre puede desaparecer físicamente. Sin `SET NULL` |
| Numeración | Correlativos de la tabla `numeradores`, tomados con bloqueo de fila dentro de la transacción del documento. Nunca `MAX(numero) + 1` |
| Idioma | Dominio y vistas en español; API de Laravel en inglés |

---

## Estado y pendientes

### Implementado

- Autenticación unificada con ámbitos, invitación de empleados y login con Google.
- Catálogo, inventario con kardex, ajustes y carga inicial.
- Circuito de venta completo: presupuesto → validación → cobro → facturación → entrega.
- Devoluciones con nota de crédito.
- Órdenes de compra con aprobación, envío por correo y recepciones parciales.
- Reposición automática con sus tres disparadores.

### Pendiente

- **Integración real con ARCA/AFIP.** Hoy `AutorizadorComprobante` está bindeado a `AutorizadorSimulado` en `AppServiceProvider`. El contrato ya está definido: reemplazar la implementación no debería tocar nada más.
- **Checkout de la tienda** con Mercado Pago Checkout Pro. Cuando exista, sus rutas llevan además el middleware `verified`: no se compra con un correo sin confirmar.
- **Priorización por antigüedad al recibir mercadería.** Al entrar stock hay que recorrer las ventas en `pendiente_stock` de ese producto en orden de antigüedad y pasar a `en_preparacion` las que ya tengan cubiertas todas sus líneas. Sin ese orden explícito, dos preventas compiten por las unidades recién llegadas según lo que devuelva el motor, y quien pagó primero puede quedar esperando otro ciclo.

### Deuda técnica conocida

| Dónde | Qué |
|---|---|
| `routes/web.php` | `items.ajustarStock` e `items.movimientos` se superponen con `stock.ajustar` y `stock.show`. Hay que quedarse con un solo camino, o el ajuste termina existiendo en dos pantallas con dos validaciones distintas |
| `ProveedorController::update()` | Redirige a `gestion.proveedors.index` (falta la **e**). Tira 404 al guardar |
| `VentaService` | Llama a `$this->stock->comprometer(...)` con cuatro argumentos y el método recibe tres. PHP ignora el extra, así que el `usuario_id` sale de `auth()->id()`; en un job en cola no hay sesión y el movimiento queda sin firmar |
| Claves de sesión | `ProveedorController` y `OrdenCompraController` usan `->with('exito', …)`, pero el bloque de avisos lee `session('success')`. Unificar según lo que lea `gestion/partials/alertas.blade.php` |
| Vistas | `gestion/home/index.blade.php` y `gestion/dashboard.blade.php` coexisten; hay que revisar cuál está muerta |
| Documentación | `Modelo_Datos_PowerTech_final.md` y `der_powertech_final.txt` dicen que el umbral de reposición se compara con `<`. El código usa `<=`, que es lo correcto: "quedan 5 y el punto de reposición es 5" ya es momento de pedir. Hay que corregir los documentos |

---

## Documentación de referencia

| Documento | Contenido |
|---|---|
| `Requerimientos_PowerTech.md` | Relevamiento cerrado: alcance, roles, circuito de venta, modelo de stock, reglas de persistencia |
| `Modelo_Datos_PowerTech_final.md` | Diccionario de las 31 tablas con la justificación de cada decisión |
| `der_powertech_final.puml` | Diagrama entidad-relación |
| `claude/restock-automatico.md` | Decisiones y trampas del circuito de reposición |
