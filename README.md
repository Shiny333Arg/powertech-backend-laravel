# PowerTech — Backend (Laravel)

Sistema de gestión comercial y tienda online para una tienda informática. Cubre el circuito completo del negocio: catálogo, inventario, ventas de mostrador y online, cobros, facturación, entregas, compras a proveedores y reposición automática de stock.

Este repositorio contiene **el backend y el panel de gestión web**. La aplicación móvil de clientes vive en el repositorio complementario [`powertech-frontend-flutter`](https://github.com/Shiny333Arg/powertech-frontend-flutter) y consume la API REST que se documenta acá.

> **Estado del proyecto:** en desarrollo. La facturación electrónica funciona hoy contra un autorizador **simulado** (`FACTURACION_DRIVER=simulado`); la integración real con los web services de ARCA/AFIP está pendiente.

---

## Contexto académico

| | |
|---|---|
| **Asignatura** | Laboratorio de Desarrollo de Aplicaciones |
| **Carrera** | Ingeniería en Sistemas |
| **Institución** | Universidad Nacional de la Patagonia Austral (UNPA) — Unidad Académica Caleta Olivia |

---

## Tabla de contenidos

- [Descripción y alcance](#descripción-y-alcance)
- [Funcionalidades principales](#funcionalidades-principales)
- [Reglas de negocio que conviene conocer](#reglas-de-negocio-que-conviene-conocer)
- [Tecnologías utilizadas](#tecnologías-utilizadas)
- [Arquitectura general](#arquitectura-general)
- [Requisitos previos](#requisitos-previos)
- [Instalación desde cero](#instalación-desde-cero)
- [Configuración del entorno](#configuración-del-entorno)
- [Importación del respaldo SQL](#importación-del-respaldo-sql)
- [Puesta en marcha diaria](#puesta-en-marcha-diaria)
- [Mercado Pago y webhooks en desarrollo local](#mercado-pago-y-webhooks-en-desarrollo-local)
- [Acceso al sistema](#acceso-al-sistema)
- [Solución de problemas frecuentes](#solución-de-problemas-frecuentes)

---

## Descripción y alcance

PowerTech es **un solo negocio con dos ámbitos de aplicación** que comparten catálogo, stock y numeración:

| Ámbito | Ruta base | Quién entra |
|---|---|---|
| **Tienda** | `/` | Clientes |
| **Gestión** | `/gestion` | Empleados |

El ámbito no se elige: lo determina el rol del usuario al autenticarse (enum `AmbitoRol`), y los middlewares `ambito:gestion` / `ambito:tienda` impiden que alguien abra una pantalla que no le corresponde. En la API el equivalente son `api.ambito:*` y las *abilities* de Sanctum.

### Roles

Cinco roles fijos, **uno solo por usuario** (no se acumulan). Están cargados en la tabla `roles` del respaldo SQL:

| Rol | Ámbito | Alcance |
|---|---|---|
| **Administrador** | Gestión | Acceso completo, sin restricciones |
| **Administrativo** | Gestión | Datos maestros, promociones, empleados, compras y ajustes de inventario |
| **Vendedor** | Gestión | Presupuestos y ventas de mostrador; validación de presupuestos |
| **Cajero** | Gestión | Cobro de ventas confirmadas |
| **Cliente** | Tienda | Compra en la tienda online |

Los permisos son granulares (tablas `permisos` y `rol_permiso`) y se consultan con `$user->tienePermiso('clave')`: lo que no se asigna explícitamente, no se puede hacer. Los proveedores **no** son usuarios y no tienen acceso al sistema.

### Panel de gestión web vs. aplicación móvil

| | Panel de gestión (este repo) | Aplicación móvil (repo Flutter) |
|---|---|---|
| Para quién | Empleados | Clientes |
| Tecnología | Blade + Bootstrap 5, servido por Laravel | Flutter (Android) |
| Acceso | `http://localhost:8000/gestion` | APK instalada en emulador o teléfono |
| Alcance | Toda la gestión interna del negocio | Solo la experiencia de compra |
| Autenticación | Sesión web + token Sanctum con ability `gestion` (8 h) | Token Sanctum con ability `tienda` (30 días) |

La aplicación móvil **no** tiene pantallas de gestión: es exclusivamente el canal de venta al cliente.

---

## Funcionalidades principales

**Gestión interna (panel web)**

- **Catálogo** — categorías, productos con foto, marca como atributo, un proveedor por producto, punto y cantidad de reposición, publicación en la tienda.
- **Inventario** — tres valores de stock por producto, kardex completo de movimientos, ajustes con motivo obligatorio y carga inicial por conteo físico.
- **Ventas** — presupuestos, ventas de mostrador y ventas online como una única entidad diferenciada por el atributo `canal`, con numeración interna correlativa compartida. Transiciones de validación y cancelación.
- **Cobros** — efectivo, tarjeta y transferencia en mostrador; Mercado Pago Checkout Pro en la tienda.
- **Devoluciones** — nota de crédito con reingreso del stock físico.
- **Comprobantes** — facturas A/B, notas de crédito y débito, IVA discriminado y registro de cada solicitud al organismo en `afip_solicitudes` para poder reintentar sin duplicar.
- **Entregas** — despacho a domicilio o retiro en el local, con seguimiento para el cliente.
- **Compras** — órdenes de compra a proveedores con aprobación obligatoria, envío del remito por correo y recepciones parciales.
- **Reposición automática** — generación de órdenes en borrador cuando el stock disponible cruza el punto de reposición.
- **Promociones** — los únicos descuentos del sistema; no existe el descuento manual.
- **Clientes, empleados y proveedores** — alta, edición, baja lógica y reactivación. Los empleados se dan de alta por invitación por correo.
- **Dashboard** — un solo tablero que arma sus bloques según los permisos de quien entra.

**Tienda (web y API para la app móvil)**

Vitrina, catálogo con filtros, detalle de producto, carrito, checkout con datos fiscales y de entrega, pago con Mercado Pago, historial de compras con seguimiento, perfil, registro con verificación de correo y recuperación de contraseña.

---

## Reglas de negocio que conviene conocer

Cuatro reglas explican por qué el sistema está hecho como está.

**1. El stock son tres números, no uno.** El **físico** son las unidades que están en el depósito y nunca es negativo. El **comprometido** son las unidades vendidas o reservadas que todavía no salieron, y tampoco es negativo. El **disponible** es `físico − comprometido`, es lo único que ve el vendedor y el cliente, y **sí puede ser negativo**: un disponible negativo no es un error, es una **preventa**, y mide cuántas unidades se le deben a clientes y todavía no se le compraron al proveedor. El stock físico baja cuando la mercadería sale del depósito, no cuando se cobra. Ninguna pantalla escribe el stock directamente: todo cambio deja un movimiento con tipo, cantidad, responsable y documento de origen.

**2. Nada con historia se elimina.** Usuarios, productos y categorías tienen baja lógica (columna `activo`; no se usa `SoftDeletes`). Las ventas no se eliminan: se **cancelan** o se **devuelven** con nota de crédito. Órdenes de compra, pagos, comprobantes y movimientos de stock no se modifican ni se eliminan; un error se corrige con un movimiento inverso o una nota de crédito. Por eso **no hay rutas `DELETE`** para esas entidades.

**3. Venta y comprobante no son la misma cosa.** Un presupuesto es una venta sin comprobante; una venta devuelta tiene dos. La numeración interna es correlativa, compartida por ambos canales y no cambia nunca; la numeración fiscal es independiente, por punto de venta y tipo de comprobante. Las facturas se emiten al acreditarse el pago, nunca antes.

**4. El comprobante es inmutable una vez autorizado.** Toda corrección se resuelve emitiendo una nota de crédito asociada.

---

## Tecnologías utilizadas

Versiones tomadas de `composer.json`, `composer.lock`, `package.json` y del encabezado del respaldo SQL.

| Capa | Tecnología | Versión |
|---|---|---|
| Lenguaje | PHP | `^8.2` |
| Framework | Laravel (estructura moderna: `bootstrap/app.php`, sin `Kernel.php`) | 12.67.0 |
| Autenticación por token | Laravel Sanctum | 4.3.3 |
| Scaffolding de autenticación | Laravel Breeze (stack Blade) | 2.4.2 |
| Consola interactiva | Laravel Tinker | 2.11.1 |
| Base de datos | MariaDB (origen del respaldo), InnoDB, `utf8mb4` / `utf8mb4_unicode_ci` | 10.4.32 |
| ORM | Eloquent | — |
| Vistas | Blade + Bootstrap + Bootstrap Icons | 5.3.8 / 1.13.1 |
| Interactividad liviana | Alpine.js | `^3.4.2` |
| Build de assets | Vite + `laravel-vite-plugin` | `^7.0.7` / `^2.0.0` |
| CSS utilitario | Tailwind CSS (+ `@tailwindcss/forms`) | `^3.1.0` |
| Orquestador de procesos | concurrently | `^9.0.1` |
| Colas y caché | Driver `database` | — |
| Pasarela de pago | Mercado Pago Checkout Pro | — |
| Pruebas | Pest / PHPUnit | 3.8.7 / 11.5.56 |
| Formateo | Laravel Pint | 1.30.4 |

Notas sobre dependencias:

- **`mercadopago/dx-php ^3.16` está declarado en `composer.json`, pero la integración no usa el SDK.** `App\Services\Pagos\PasarelaMercadoPago` habla HTTP directo contra `https://api.mercadopago.com` usando el cliente `Http` de Laravel, por una decisión explicada en el propio archivo.
- El servidor de correo de desarrollo (**Mailpit**) y el túnel (**ngrok**) son herramientas externas: no forman parte de las dependencias del proyecto y se instalan por separado.

---

## Arquitectura general

```
Panel de gestión (Blade)  ─┐
                           ├─→  Laravel 12  ─→  MariaDB
App Flutter (tienda)  ─────┘      │
                                  └─→  Mercado Pago (HTTP) / SMTP
```

**Capas.** Los controladores no contienen lógica de negocio: validan con un *Form Request*, autorizan con una *Policy* y delegan en un *Service*. Los services son los dueños de las transacciones y los únicos que escriben stock, numeración y comprobantes. Los eventos se emiten fuera de la transacción.

**Dos superficies HTTP.**

| Superficie | Archivo de rutas | Prefijo | Autenticación |
|---|---|---|---|
| Tienda web + panel de gestión | `routes/web.php` | `/` y `/gestion` | Sesión (guard `web`) |
| Autenticación web | `routes/auth.php` | `/` | Breeze: login, registro, invitación, verificación, recupero |
| API de tienda | `routes/api.php` | `/api/v1` | Bearer Sanctum, ability `tienda` |
| API de gestión | `routes/api_gestion.php` | `/api/v1/gestion` | Bearer Sanctum, ability `gestion` |

La **API de tienda** expone catálogo, vitrina, categorías, marcas, promociones, carrito, checkout, compras, perfil, datos fiscales, autenticación y recupero de contraseña. Es la que consume la aplicación Flutter.

La **API de gestión** expone categorías, productos, stock (movimientos y ajustes), proveedores y órdenes de compra con sus transiciones (aprobación, envío, cancelación, cierre y recepciones). El panel web la consume desde `resources/js/gestion/api.js`, manteniendo el token **en memoria** (nunca en `localStorage`). Está documentada en [`docs/api-gestion.md`](docs/api-gestion.md), que detalla las capas de control, la forma de las respuestas y los endpoints de categorías, productos y stock.

**Sanctum.** `config/sanctum.php` deja `'guard' => []` a propósito, de modo que `auth:sanctum` sea *Bearer-only*, y `'expiration' => null`, porque la vigencia se fija **por token**: 30 días para la tienda (`config/movil.php`) y 8 horas para gestión (`config/gestion.php`).

**Procesos de fondo.** Tres disparadores terminan en la evaluación de reposición: el listener sobre cada movimiento de stock, el listener de cancelación de una orden y el barrido diario `compras:revisar-reposicion` de las 07:00 definido en `routes/console.php`. Los listeners del restock corren en la cola **`reposicion`**; las notificaciones por correo (invitaciones, aviso de pedido listo) corren en la cola `default`.

En un servidor, todo esto requiere una sola entrada de cron:

```cron
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

---

## Requisitos previos

- **PHP 8.2 o superior** con las extensiones que exigen las dependencias instaladas: `ctype`, `dom`, `fileinfo`, `filter`, `hash`, `iconv`, `json`, `libxml`, `mbstring`, `openssl`, `pcre`, `session` y `tokenizer`, más **`pdo_mysql`** por la conexión a la base.
- **Composer 2.x**
- **Node.js** y **npm**. El repositorio no fija una versión mínima; Vite 7 requiere una versión moderna de Node, así que conviene verificar con `node -v` y actualizar si el build falla.
- **MariaDB o MySQL.** El respaldo se generó en MariaDB 10.4.32; no se probó la importación en otras versiones.
- **Mailpit** — servidor SMTP de prueba, para ver los correos que emite el sistema en desarrollo.
- **ngrok** (o otro túnel HTTP) — imprescindible para probar Mercado Pago. Ver [Mercado Pago y webhooks](#mercado-pago-y-webhooks-en-desarrollo-local).
- **Git**

> La extensión `gd` puede ser necesaria para el procesamiento de imágenes de productos y `soap` para la integración real con ARCA. Ninguna dependencia del proyecto las declara y, con el driver de facturación `simulado`, no se usan: habilitarlas es opcional.

---

## Instalación desde cero

Todos los comandos se ejecutan **desde la raíz del repositorio** salvo que se indique otra cosa.

### 1. Clonar el repositorio

```bash
git clone https://github.com/Shiny333Arg/powertech-backend-laravel.git
cd powertech-backend-laravel
git checkout etapa-03
```

### 2. Instalar dependencias

```bash
composer install
npm install
```

### 3. Crear el archivo de entorno y la clave de la aplicación

```bash
cp .env.example .env
php artisan key:generate
```

En Windows (PowerShell), si `cp` no está disponible:

```powershell
Copy-Item .env.example .env
```

### 4. Configurar el entorno

Editar `.env` antes de seguir. Ver [Configuración del entorno](#configuración-del-entorno).

### 5. Crear la base de datos e importar el respaldo

Ver [Importación del respaldo SQL](#importación-del-respaldo-sql). **La instalación usa el respaldo incluido en el repositorio**, no migraciones.

### 6. Enlazar el almacenamiento público

Las fotos de productos y los banners de promociones se sirven desde `storage/app/public`:

```bash
php artisan storage:link
```

### 7. Compilar los assets

```bash
npm run build
```

En desarrollo no hace falta: `composer run dev` levanta Vite en modo recarga en caliente.

### 8. Limpiar la configuración en caché

```bash
php artisan config:clear
```

A partir de acá el sistema queda instalado. Para levantarlo, ver [Puesta en marcha diaria](#puesta-en-marcha-diaria).

---

## Configuración del entorno

### Variables que hay que completar

`.env.example` deja estos valores vacíos o con marcadores de posición:

```dotenv
APP_KEY=                      # la genera php artisan key:generate

DB_USERNAME=                  # usuario de MariaDB/MySQL
DB_PASSWORD=                  # su contraseña

APP_URL=https://tu-tunel.ngrok-free.app   # reemplazar por la URL real del túnel

MP_ACCESS_TOKEN=TEST-xxxxxxxx # credencial de prueba de Mercado Pago
MP_PUBLIC_KEY=TEST-xxxxxxxx   # credencial de prueba de Mercado Pago
```

Las credenciales de Mercado Pago se obtienen en *mercadopago.com.ar/developers → Tus integraciones → Credenciales*. Las de prueba empiezan con `TEST-`. **El access token es un secreto de servidor: no se versiona ni se expone en el frontend.**

`DB_DATABASE` ya viene con el valor correcto (`powertech_laravel`), que es el nombre que espera el respaldo.

### Variables opcionales

| Variable | Valor por defecto | Cuándo se necesita |
|---|---|---|
| `MP_WEBHOOK_SECRET` | vacío | Para verificar la firma del webhook. **Vacío, la verificación se saltea y queda un aviso en el log**: alcanza para la primera prueba local, nunca para producción |
| `MP_AUTO_RETURN` | `true` | Poner en `false` si Mercado Pago rechaza las `back_urls` del túnel |
| `MP_USAR_SANDBOX_INIT_POINT` | `false` | Poner en `true` si el redirect al checkout cae en un error de Mercado Pago |
| `MP_TIMEOUT` | `15` | Segundos de espera de las llamadas a la API de Mercado Pago |
| `MP_CUOTAS_MAXIMAS` | `12` | Máximo de cuotas ofrecidas |
| `MP_MINUTOS_VIGENCIA` | `30` | Minutos que vive el link de pago |
| `MP_EXCLUIR_TIPOS` | `ticket,atm` | Medios de pago excluidos (los diferidos están excluidos por diseño) |
| `GESTION_TOKEN_HORAS` | `8` | Vigencia absoluta del token del panel |
| `MOVIL_TOKEN_DIAS` | `30` | Vigencia del token de la app móvil |
| `FACTURACION_DRIVER` | `simulado` | `simulado` asigna un CAE ficticio sin salir a la red. El driver real (`wsfe`) todavía no está implementado |

### Valores que no conviene cambiar

```dotenv
QUEUE_CONNECTION=database     # la cola 'reposicion' del circuito de compras vive acá
CACHE_STORE=database          # el driver 'file' no soporta Cache::lock(), que serializa
                              # la evaluación de reposición por producto y proveedor
MAIL_PORT=2526                # el puerto SMTP con el que se levanta Mailpit (ver más abajo)
```

### Correo

`.env.example` queda apuntado a Mailpit en `127.0.0.1:2526`. El remitente por defecto es `no-reply@powertech.com.ar`.

> **Observación.** `.env.example` define `MAIL_COMPRAS_ADDRESS`, que `App\Mail\RemitoDePedido` intenta leer como `config('mail.compras.address')` para usarla como `replyTo` del remito al proveedor. **Esa clave no existe hoy en `config/mail.php`**, por lo que la variable no tiene efecto y el remito se envía sin `replyTo`. No impide el envío. Requiere revisión del equipo.

---

## Importación del respaldo SQL

La instalación **no** usa `php artisan migrate --seed`: parte del respaldo incluido en el repositorio, que ya trae la estructura y los datos necesarios para operar.

### Respaldo a utilizar

**`database/powertech_laravel-11-16.sql`**

| Dato | Valor |
|---|---|
| Fecha del volcado | 17-11-2026 01:30 (generado con phpMyAdmin 5.2.1) |
| Servidor de origen | MariaDB 10.4.32 |
| Base que espera | `powertech_laravel` |
| Tablas | 39, todas InnoDB con `utf8mb4` / `utf8mb4_unicode_ci` |
| Contenido | Estructura y datos: catálogo, clientes, empleados, ventas, comprobantes, movimientos de stock, órdenes de compra, pagos, promociones, numeradores, roles, permisos y las tablas de referencia fiscales |

En `database/` hay respaldos anteriores (`powertech_laravel-10_05.sql` y siguientes). **Son históricos: no usarlos.** El respaldo vigente es el del 11-16.

Los otros dos archivos `.sql` del repositorio **no son respaldos y no hay que ejecutarlos** durante la instalación:

- `database/permisos-unificacion.sql` — parche incremental de permisos, ya aplicado en el respaldo del 11-16.
- `sql/verificacion_invariantes.sql` — script de prueba de los invariantes de stock. Crea y descarta su propia base `powertech_test`.

### Procedimiento

El respaldo **no** contiene `CREATE DATABASE` ni `USE`, así que la base hay que crearla y seleccionarla.

**1. Crear la base de datos**

```bash
mysql -u root -p -e "CREATE DATABASE powertech_laravel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

**2. Importar el respaldo** (desde la raíz del repositorio)

```bash
mysql -u root -p powertech_laravel < database/powertech_laravel-11-16.sql
```

En PowerShell, el operador `<` no redirige entrada; usar:

```powershell
cmd /c "mysql -u root -p powertech_laravel < database/powertech_laravel-11-16.sql"
```

También puede importarse desde phpMyAdmin: crear la base `powertech_laravel` con el juego de caracteres `utf8mb4_unicode_ci` y usar *Importar* sobre el archivo.

**3. Después de importar**

```bash
php artisan storage:link
php artisan config:clear
```

**No hace falta ejecutar `php artisan migrate`.** Las 39 migraciones de `database/migrations/` figuran todas registradas en la tabla `migrations` del respaldo, por lo que no quedan migraciones pendientes. Tampoco hay que correr seeders: los datos de referencia ya vienen cargados.

> **Las imágenes no están versionadas.** `storage/app/public/` está vacío en el repositorio, así que los productos del respaldo apuntan a archivos que no existen y sus fotos se verán rotas. No afecta el funcionamiento del sistema; las imágenes se vuelven a cargar desde el panel.

---

## Puesta en marcha diaria

Rutina de desarrollo en **Windows**, con cuatro procesos que quedan corriendo, cada uno en su propia terminal, más la aplicación Flutter.

### 1. Mailpit — servidor de correo de prueba

Desde la carpeta donde está el ejecutable de Mailpit:

```powershell
.\mailpit.exe --smtp=0.0.0.0:2526 --listen=0.0.0.0:8025
```

Captura todos los correos que emite el sistema (invitaciones de empleado y cliente, verificación de cuenta, recupero de contraseña, remito al proveedor y aviso de pedido listo para retirar) en lugar de enviarlos de verdad. La bandeja se abre en **http://localhost:8025**. El puerto SMTP `2526` es el que espera `MAIL_PORT` en el `.env`.

**Mantener activo** mientras se prueben acciones que mandan correo. No depende de ningún otro proceso.

### 2. Servidor Laravel

Desde la raíz del repositorio:

```bash
composer run dev
```

Levanta **tres procesos a la vez** con `concurrently`, identificados por color en la consola:

| Proceso | Comando | Qué hace |
|---|---|---|
| `server` | `php artisan serve` | Servidor HTTP de desarrollo en `http://localhost:8000` |
| `queue` | `php artisan queue:listen --tries=1 --timeout=0` | Procesa trabajos en cola recargando el código en cada trabajo |
| `vite` | `npm run dev` | Compila y recarga assets en caliente |

**Mantener activo** siempre. Es el proceso base: sin él no hay sistema.

### 3. Túnel de ngrok

```bash
ngrok http 8000
```

Expone el servidor local en una URL pública `https://...ngrok-free.app`. **Es imprescindible para probar Mercado Pago**, que rechaza URL locales y necesita alcanzar el webhook desde internet. Ver [Mercado Pago y webhooks](#mercado-pago-y-webhooks-en-desarrollo-local).

**Mantener activo** mientras se pruebe el pago online. Depende de que el servidor Laravel esté arriba (apunta al puerto 8000). Cada vez que se reinicia, la URL pública cambia.

### 4. Trabajador de colas

Desde la raíz del repositorio:

```bash
php artisan queue:work
```

Toma los trabajos encolados y los ejecuta. Es lo que permite que **salgan los correos a los clientes sobre el estado de sus pedidos**: las notificaciones del sistema se despachan en cola, no durante el request.

**Mantener activo** mientras se prueben acciones que generan correos o trabajos de fondo. Depende de la base de datos (`QUEUE_CONNECTION=database`).

> **Después de tocar código hay que reiniciar este proceso:** `queue:work` conserva las clases en memoria y seguiría ejecutando la versión anterior. (El proceso `queue` de `composer run dev` usa `queue:listen`, que recarga el código solo.)

> **Observación sobre las colas.** Los listeners del circuito de reposición automática (`EvaluarReposicion`, `ReponerPorCancelacion`, `ReponerPorCierre`) declaran la cola `reposicion`, y un trabajador sin la opción `--queue` atiende únicamente la cola `default`. En un servidor esto lo cubre la tarea programada de `routes/console.php`, que cada cinco minutos drena `reposicion` y `default`; en desarrollo, con el planificador detenido, las órdenes de reposición automática pueden quedar encoladas hasta el barrido diario de las 07:00. Esto no afecta el envío de correos. Queda como observación pendiente de resolución por el equipo.

### 5. Aplicación Flutter

Desde la raíz del repositorio del frontend:

```bash
flutter run --dart-define=API_URL_BASE=http://10.0.2.2:8000
```

Detalles de este comando y de la configuración por dispositivo, en el [README del frontend](https://github.com/Shiny333Arg/powertech-frontend-flutter).

### Orden recomendado

Mailpit → `composer run dev` → ngrok → `php artisan queue:work` → Flutter.

Cuando ngrok entrega una URL nueva, actualizar `APP_URL` en el `.env` y ejecutar `php artisan config:clear` antes de probar el pago.

---

## Mercado Pago y webhooks en desarrollo local

### Por qué hace falta un túnel

Mercado Pago rechaza las `back_urls` y la `notification_url` que apunten a `localhost`, a `127.0.0.1` o a un host sin DNS público. Para probar el pago en desarrollo hay que exponer el entorno local con un túnel (ngrok, expose, Cloudflare Tunnel).

### La trampa importante: hay que navegar por la URL del túnel

Durante un request HTTP, el helper `route()` de Laravel arma las URL con **el host del request entrante**, no con `APP_URL`. `APP_URL` es solo el valor de reserva para cuando no hay request (consola, colas, correos).

**Consecuencia:** si se navega la tienda en `http://localhost:8000`, las `back_urls` salen apuntando a `localhost` por más que `APP_URL` apunte al túnel, y Mercado Pago rechaza la preferencia. Hay que **entrar a la tienda por la dirección de ngrok** y hacer el checkout desde ahí.

`PasarelaMercadoPago` detecta este caso antes de llamar a la API y devuelve un mensaje explicando qué corregir, porque el error original de Mercado Pago es engañoso (ver [Solución de problemas](#solución-de-problemas-frecuentes)).

> Verificar esto desde `php artisan tinker` no sirve: ahí no hay request, `route()` cae en `APP_URL` y muestra la URL correcta mientras el navegador sigue mandando la equivocada.

### El webhook

| | |
|---|---|
| Ruta | `POST /checkout/webhook` |
| `notification_url` enviada a Mercado Pago | se arma con `route('cliente.checkout.webhook')` |
| CSRF | exceptuada en `bootstrap/app.php` |
| Firma | HMAC-SHA256 en la cabecera `x-signature`, verificada con `MP_WEBHOOK_SECRET` |

El webhook es **el camino autoritativo del cobro**: la venta se crea cuando llega la notificación de pago acreditado, aunque el cliente cierre el navegador y no vuelva al sitio. Por eso la app móvil no necesita un *deep link*: consulta `GET /api/v1/checkout/estado`, que lee lo que dejó el webhook.

Si `MP_WEBHOOK_SECRET` está vacío, la verificación de firma **se saltea** y queda un aviso en el log. Es aceptable para una primera prueba local; en producción, sin firma, cualquiera que conozca la URL podría avisar que un pago se acreditó.

### Qué actualizar cuando cambia la URL del túnel

Cada reinicio de ngrok entrega un subdominio nuevo. Hay que actualizar:

1. **`APP_URL`** en el `.env`, y después `php artisan config:clear`.
2. **La URL del webhook** configurada en el panel de Mercado Pago (*Tus integraciones → Webhooks*), apuntando a `https://<subdominio>.ngrok-free.app/checkout/webhook`.
3. **El `--dart-define=API_URL_BASE`** de la aplicación Flutter, si se la prueba a través del túnel.
4. La dirección por la que se navega la tienda, según la trampa descripta arriba.

---

## Acceso al sistema

| Destino | URL local |
|---|---|
| Tienda | `http://localhost:8000` |
| Panel de gestión | `http://localhost:8000/gestion` |
| Bandeja de correo (Mailpit) | `http://localhost:8025` |
| Estado de la aplicación | `http://localhost:8000/up` |
| API de tienda | `http://localhost:8000/api/v1` |
| API de gestión | `http://localhost:8000/api/v1/gestion` |

Para probar el pago con Mercado Pago, entrar a la tienda por la URL de ngrok en lugar de `localhost`.

**Usuarios.** El respaldo SQL incluye las cuentas de usuario con las que opera el sistema, con sus roles ya asignados. **Las contraseñas no están documentadas en el repositorio** —se almacenan como hash— por lo que las credenciales de acceso debe proporcionarlas el equipo del proyecto. Este README no incluye credenciales por seguridad.

---

## Solución de problemas frecuentes

| Síntoma | Causa y solución |
|---|---|
| `auto_return invalid. back_url.success must be defined`, aunque la URL se esté mandando | Mercado Pago no puede alcanzar la `back_url`. Se está navegando por `localhost`: entrar por la URL de ngrok. Si el dominio del túnel sigue sin ser aceptado, poner `MP_AUTO_RETURN=false` |
| `Mercado Pago no puede alcanzar «...»: necesita una URL pública` | Es la validación previa del propio proyecto avisando lo mismo que arriba, antes de llamar a la API |
| `La URL «...» tiene un espacio` | Espacio sobrante al final de `APP_URL` en el `.env`. Corregirlo y ejecutar `php artisan config:clear` |
| `Falta MP_ACCESS_TOKEN: la tienda no puede cobrar` | Falta completar la credencial en el `.env`, o quedó el marcador `TEST-xxxxxxxx`. Después de cambiarla, `php artisan config:clear` |
| Aviso en el log: `Webhook de Mercado Pago sin verificar: falta MP_WEBHOOK_SECRET` | Esperado si no se configuró el secreto. Completar `MP_WEBHOOK_SECRET` para habilitar la verificación de firma |
| El pago se acredita pero no aparece el pedido | El webhook no llegó. Verificar que ngrok esté activo, que la URL del panel de Mercado Pago sea la del túnel actual y revisar `storage/logs/laravel.log` |
| Al redirigir al checkout aparece una pantalla de error de Mercado Pago | Probar con `MP_USAR_SANDBOX_INIT_POINT=true` |
| No llega ningún correo | Mailpit no está levantado, o `MAIL_PORT` no coincide con el `--smtp` del comando. Revisar la bandeja en `http://localhost:8025` |
| El correo aparece en Mailpit recién mucho después | El trabajo quedó en cola. Verificar que el trabajador de colas esté corriendo |
| Las órdenes de reposición automática no aparecen | Ver la observación sobre colas en [Puesta en marcha diaria](#puesta-en-marcha-diaria) |
| Un cambio de código no se refleja en los trabajos en cola | Reiniciar `php artisan queue:work` |
| Un cambio en el frontend no se ve | Ejecutar `npm run build`, o verificar que el proceso `vite` de `composer run dev` esté activo |
| `Vite manifest not found` | No se compilaron los assets: `npm run build`, o levantar `composer run dev` |
| Las fotos de los productos no se ven | Falta `php artisan storage:link`, o las imágenes no están en `storage/app/public` (no se versionan, ver [Importación del respaldo SQL](#importación-del-respaldo-sql)) |
| La aplicación Flutter no conecta al backend | Ver la guía de diagnóstico del [README del frontend](https://github.com/Shiny333Arg/powertech-frontend-flutter) |
| Un cambio en el `.env` no surte efecto | `php artisan config:clear` |

### Observación específica de un entorno de desarrollo

Se reportó que, con el antivirus activo, **la comunicación con Mercado Pago no funciona correctamente**. No pudo determinarse la causa: el repositorio no contiene ninguna configuración relacionada con antivirus, certificados ni verificación TLS, y la única opción ajustable del cliente HTTP es el tiempo de espera (`MP_TIMEOUT`, 15 segundos por defecto).

**Queda pendiente de verificación.** No se recomienda desactivar el antivirus ni se considera un requisito de instalación. Para diagnosticarlo, conviene reproducir el fallo y revisar el error exacto registrado en `storage/logs/laravel.log`.

---

## Documentación de referencia

| Documento | Contenido |
|---|---|
| [`docs/api-gestion.md`](docs/api-gestion.md) | API de gestión: autenticación, capas de control, forma de las respuestas y endpoints |
| `Requerimientos_PowerTech.md` | Relevamiento: alcance, roles, circuito de venta, modelo de stock y reglas de persistencia |
| `Modelo_Datos_PowerTech_final.md` | Diccionario de tablas con la justificación de cada decisión |
| `der_powertech_final.txt` | Diagrama entidad-relación |
| [Repositorio del frontend](https://github.com/Shiny333Arg/powertech-frontend-flutter) | Aplicación móvil de clientes |

> Los documentos de relevamiento y modelo de datos se mantienen fuera de este repositorio, en la documentación del proyecto.
