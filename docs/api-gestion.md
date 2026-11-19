# API de gestión — `/api/v1/gestion`

Ámbito administrativo de la API REST de PowerTech. Hermano de la API de tienda
(`/api/v1/...`, documentada por `routes/api.php`): mismos servicios, mismas
reglas de negocio y la misma forma de respuesta; lo que cambia es quién entra y
qué ve.

Estado: **Entrega A — categorías**, **Entrega B — productos** y **Entrega C.2 — stock**.

---

## 1. Autenticación

Bearer de Sanctum, el mismo mecanismo y los mismos endpoints de sesión que usa
Flutter (requisito 6 de la consigna):

| Operación | Endpoint |
|---|---|
| Iniciar sesión | `POST /api/v1/auth/login` con `{ email, password, ambito: "gestion" }` (el `ambito` es opcional: sin él se deriva del rol) |
| Usuario y permisos | `GET /api/v1/auth/yo` → trae `rol` y, con token de gestión, `permisos` |
| Cerrar sesión | `POST /api/v1/auth/logout` |

El token de gestión lleva la ability `gestion` y vive **8 horas**
(`config/gestion.php`). En el panel se mantiene **en memoria**
(`resources/js/gestion/api.js`), nunca en `localStorage` ni en una cookie propia.

```
Authorization: Bearer <token>
Accept: application/json
```

## 2. Capas de control

Toda ruta de `/api/v1/gestion/*` pasa por cinco capas, cada una respondiendo una
pregunta distinta:

| Capa | Pregunta | Falla con |
|---|---|---|
| `auth:sanctum` | ¿El token existe, no venció y no fue revocado? | **401** |
| `api.activo` | ¿La cuenta sigue activa? | **401** |
| `api.ambito:gestion` | ¿Esta cuenta pertenece al panel? | **403** `AMBITO_INCORRECTO` |
| `abilities:gestion` | ¿El token se emitió *para* el panel? | **403** `ABILITY_INSUFICIENTE` |
| `can:<policy>` | ¿Puede hacer **esta** operación sobre **este** recurso? | **403** |

Las dos del medio no son redundantes: `api.ambito` mira quién es el usuario
**ahora** (un rol que cambió después de emitir el token) y `abilities` mira
**para qué** se emitió (un token de la app de la tienda).

Los permisos se consultan a la base en cada request, así que **quitarle un
permiso a un rol se refleja en la llamada siguiente aunque el token siga vivo**.
Los permisos que `/auth/yo` devuelve al cliente son ayuda de interfaz para
dibujar menús y botones: la autorización se vuelve a comprobar en el servidor.

## 3. Forma de las respuestas

| Caso | Forma | Código |
|---|---|---|
| Recurso | `{ "data": {...} }` | 200 / 201 |
| Colección | `{ "data": [...], "links": {...}, "meta": {...}, "filtros": {...} }` | 200 |
| Acción sin contenido | sin cuerpo | 204 |
| Validación | `{ "message": ..., "errors": { "campo": [...] } }` | 422 |
| Sin token, vencido o revocado | `{ "message": ... }` | 401 |
| Ámbito, ability o permiso | `{ "message": ..., "codigo"?: ... }` | 403 |
| Conflicto con el estado actual | `{ "message": ... }` | 409 |

Límite de peticiones: `throttle:60,1` (60 por minuto).

---

## 4. Categorías

Recurso: `id`, `nombre`, `slug`, `orden`, `activo`, `productos_count`.

`productos_count` viaja en el listado y en el detalle; es el dato con el que la
pantalla sabe si el botón de eliminar va a terminar en 409.

| Método | Ruta | Permiso |
|---|---|---|
| `GET` | `/api/v1/gestion/categorias` | `categoria.ver` |
| `POST` | `/api/v1/gestion/categorias` | `categoria.crear` |
| `GET` | `/api/v1/gestion/categorias/{id}` | `categoria.ver` |
| `PUT` | `/api/v1/gestion/categorias/{id}` | `categoria.editar` |
| `PATCH` | `/api/v1/gestion/categorias/{id}/estado` | `categoria.baja` |
| `DELETE` | `/api/v1/gestion/categorias/{id}` | `categoria.eliminar` |

Por rol: **administrador** y **administrativo** tienen los cinco permisos;
**vendedor** y **cajero** solo `categoria.ver`.

### GET `/api/v1/gestion/categorias`

Query string: `nombre` (coincidencia parcial), `estado` (`todas` por defecto,
`activas`, `inactivas`), `por_pagina` (1 a 100, 15 por defecto).

Muestra activas e inactivas por defecto, igual que el listado del panel: es la
pantalla desde la que se reactiva una categoría dada de baja.

```json
{
  "data": [
    { "id": 1, "nombre": "Monitores", "slug": "monitores", "orden": 1, "activo": true, "productos_count": 12 }
  ],
  "links": { "first": "...", "last": "...", "prev": null, "next": null },
  "meta":  { "current_page": 1, "per_page": 15, "total": 1 },
  "filtros": { "nombre": null, "estado": "todas" }
}
```

### POST `/api/v1/gestion/categorias` → 201

```json
{ "nombre": "Placas de video", "slug": "placas-de-video", "orden": 3 }
```

`nombre` es obligatorio y único. `slug` es opcional: si no viene, se deriva del
nombre. `orden` es opcional (0 por defecto). `activo` es opcional y una
categoría nueva nace en operación.

### PUT `/api/v1/gestion/categorias/{id}` → 200

Reemplazo completo, con el mismo cuerpo del alta. **El estado no se toca desde
acá** aunque venga `activo` en el cuerpo: tiene su propio endpoint y su propio
permiso, y mezclarlos dejaría que alguien con `categoria.editar` diera de baja.

### PATCH `/api/v1/gestion/categorias/{id}/estado` → 200

```json
{ "activo": false }
```

Baja lógica y reactivación en un solo endpoint: son la misma decisión y el mismo
permiso. La fila sigue existiendo y los productos que la nombran la siguen
mostrando; lo que deja de hacer es ofrecerse al cargar un producto nuevo.

### DELETE `/api/v1/gestion/categorias/{id}` → 204

Borrado físico. La categoría es la única entidad del catálogo que lo admite, y
solo si ningún producto la referencia. Si hay productos, responde **409**:

```json
{ "message": "«Monitores» tiene productos asociados, así que no se puede eliminar. Dala de baja para sacarla de operación sin perder el historial." }
```

La regla la hace cumplir la clave foránea `productos.categoria_id` con
`ON DELETE RESTRICT`, no una validación en PHP: no se antepone un `count()`
porque reimplementar una comprobación que el motor ya hace abre una ventana
contra un alta concurrente. Como los productos nunca se eliminan, el `RESTRICT`
también ve a los dados de baja: una categoría que alguna vez tuvo un producto ya
no se puede eliminar.

---

## 5. Productos

Recurso: `id`, `codigo`, `nombre`, `marca`, `descripcion`, `imagen_url`, `precio`, `costo`,
`stock_fisico`, `stock_comprometido`, `stock_disponible`, `en_preventa`,
`punto_reposicion`, `cantidad_reposicion`, `reposicion_automatica`, `peso_gramos`,
`activo`, `publicado_app`, `destacado`, y los objetos `categoria`, `proveedor` y
`alicuota_iva`.

`costo`, `stock_fisico`, `stock_comprometido` y `proveedor` **no existen en el recurso de
la tienda**: son datos de la empresa. Por eso son dos resources distintos y no uno que
decida por request.

| Método | Ruta | Permiso |
|---|---|---|
| `GET` | `/api/v1/gestion/productos` | `producto.ver` |
| `POST` | `/api/v1/gestion/productos` | `producto.crear` |
| `GET` | `/api/v1/gestion/productos/{id}` | `producto.ver` |
| `PUT` | `/api/v1/gestion/productos/{id}` | `producto.editar` |
| `PATCH` | `/api/v1/gestion/productos/{id}/estado` | `producto.baja` |
| `PATCH` | `/api/v1/gestion/productos/{id}/publicacion` | `producto.publicar` |

Por rol: **administrador** y **administrativo** tienen los cinco permisos; **vendedor** y
**cajero** solo `producto.ver`.

**No hay `DELETE`, y la ausencia es la regla.** Un producto está referenciado por líneas de
venta, de comprobante, de orden de compra y por los movimientos de stock: eliminarlo
dejaría esa historia sin sujeto. La única baja es `PATCH .../estado` con `activo: false`.

### GET `/api/v1/gestion/productos`

Query string: `q` (busca en código, nombre y marca), `categoria_id`, `estado`
(`todos` por defecto, `activos`, `inactivos`), `stock` (`todos`, `reponer`, `preventa`),
`por_pagina` (1 a 100, 15 por defecto). Son los mismos filtros del listado del panel.

`stock=reponer` trae los que están en el punto de reposición o por debajo —la comparación
es `<=`, el umbral es un piso, no un valor al que llegar y quedarse—; `stock=preventa`,
los que tienen disponible negativo.

### POST `/api/v1/gestion/productos` → 201

`application/json`, o **`multipart/form-data` si trae imagen**.

Obligatorios: `codigo` (único), `nombre`, `categoria_id`, `alicuota_iva_id`, `precio`,
`punto_reposicion`, `cantidad_reposicion`. Opcionales: `marca`, `descripcion`, `costo`,
`proveedor_id`, `peso_gramos`, `reposicion_automatica`, `publicado_app`, `imagen`,
`stock_inicial`.

Tres reglas que conviene tener presentes, todas heredadas del formulario web:

- **`stock_inicial` no es la columna de stock**: es la cantidad de un movimiento de tipo
  `inicial`, que se registra en la misma transacción que el alta. Fuera del alta el campo
  está prohibido (422): el saldo se corrige con un ajuste de inventario, nunca editando el
  producto.
- **`activo` está prohibido** (422). El producto nace activo y el estado se cambia por su
  endpoint, que tiene otro permiso.
- **Con `reposicion_automatica: true`** hacen falta `proveedor_id` y
  `cantidad_reposicion >= 1`, o la respuesta es 422. Sin proveedor no hay a quién emitirle
  la orden, y en cero cada venta generaría un pedido nuevo.

`destacado` viaja de solo lectura: lo escribe un proceso por volumen de venta y no se edita
a mano.

### PUT `/api/v1/gestion/productos/{id}` → 200

Reemplazo completo, mismo cuerpo del alta. No toca las columnas de stock, ni el estado, ni
la publicación.

**Para mandar imagen hay que usar `POST` con `_method=PUT`**: PHP no parsea el cuerpo
multipart de un `PUT`. Es la misma convención que usa el formulario Blade con
`@method('PUT')`, así que no hay una ruta aparte. La imagen anterior se borra recién
después de guardar la nueva.

La imagen se valida como `jpg`, `jpeg`, `png` o `webp`, hasta 2 MB, y se guarda en
`storage/app/public/productos` (requiere `php artisan storage:link`).

### PATCH `/api/v1/gestion/productos/{id}/estado` → 200

```json
{ "activo": false }
```

Baja lógica y reactivación. **No toca `publicado_app`**: la vitrina filtra por activo *y*
publicado, así que un producto dado de baja ya no se ve, y conservar la decisión de
publicación evita tener que volver a tomarla si se reactiva.

### PATCH `/api/v1/gestion/productos/{id}/publicacion` → 200

```json
{ "publicado_app": true }
```

Publicar o retirar de la tienda. Es una pregunta distinta de la baja y tiene permiso propio:
un producto activo que se vende en mostrador y no se publica —reservado, de exhibición, a
pedido— es un caso normal.

**Un producto dado de baja no se puede publicar**: responde 422 con
«Un producto dado de baja no se puede publicar. Reactivalo primero.». Despublicar, en
cambio, siempre está permitido: sacar algo de la vitrina no puede quedar bloqueado.

---

## 6. Stock

Tres endpoints: dos de consulta y uno de escritura. **El ajuste es la única operación de toda
la API que modifica inventario.**

| Método | Ruta | Permiso |
|---|---|---|
| `GET` | `/api/v1/gestion/stock/movimientos` | `stock.ver` |
| `GET` | `/api/v1/gestion/productos/{id}/stock` | `stock.ver` |
| `POST` | `/api/v1/gestion/productos/{id}/stock/ajustes` | `stock.ajustar` |

Por rol: **administrador** y **administrativo** tienen los dos permisos; el **vendedor** tiene
`stock.ver` y no `stock.ajustar` —mira el kardex y no puede tocar un saldo—; el **cajero** no
tiene ninguno de los dos.

Los dos endpoints que cuelgan del producto se autorizan con `ProductoPolicy::verStock()` y
`::ajustarStock()`, que devuelven exactamente esos permisos: el criterio es el mismo que aplica
el panel.

### Renglón del kardex

```json
{
  "id": 42,
  "tipo": "ajuste",
  "tipo_etiqueta": "Ajuste de inventario",
  "cantidad_fisico": -2,
  "cantidad_comprometido": 0,
  "stock_fisico_resultante": 8,
  "stock_comprometido_resultante": 0,
  "motivo": "Rotura en depósito",
  "origen": null,
  "producto": { "id": 1, "codigo": "MON-1", "nombre": "Monitor 24" },
  "usuario": { "id": 3, "nombre": "Ana Pérez" },
  "registrado_el": "2026-11-19T18:40:11-03:00"
}
```

Se lee como un extracto bancario: cada fila trae el delta y el **saldo resultante**, que es lo
que permite reconstruir la historia sin volver a sumar. Viajan los dos saldos —físico y
comprometido— porque una reserva por venta no cambia la mercadería que hay en el depósito pero
sí lo disponible.

`origen` es `null` en el ajuste manual, que es el único movimiento sin documento que lo
explique; ahí la explicación es el `motivo`. En los demás apunta a la venta, la orden de compra
o el comprobante que lo originó. `producto` viaja solo en el kardex global, y `usuario` es
`null` en los movimientos que dispara el sistema.

Los tipos son `inicial`, `compra`, `reserva`, `entrega`, `liberacion`, `devolucion` y `ajuste`.

### GET `/api/v1/gestion/stock/movimientos`

El histórico de todos los productos, del más reciente al más viejo. Query string:
`producto_id`, `tipo`, `desde`, `hasta` (inclusive, por fecha) y `por_pagina` (1 a 100, 20 por
defecto).

A diferencia del panel —donde el tipo sale de un `<select>`— acá `tipo` se valida contra el
enum: un filtro mal escrito responde **422** en vez de una lista vacía que parezca «no hay
movimientos». Un rango invertido también es 422.

### GET `/api/v1/gestion/productos/{id}/stock`

La situación del producto y su histórico en una sola respuesta:

```json
{
  "data": [ ...renglones del kardex de ese producto... ],
  "producto": { ...el mismo recurso de producto del catálogo... },
  "links": {...}, "meta": {...},
  "filtros": { "tipo": null, "desde": null, "hasta": null }
}
```

`producto` trae los tres saldos, `en_preventa`, el punto de reposición y la cantidad a
reponer: es lo que se mira al decidir un ajuste.

**`producto_id` no se acepta acá**: el filtro lo impone la ruta. Mandarlo por query string no
hace nada, para que no se puedan pedir los movimientos de un producto ajeno desde la URL de
este.

### POST `/api/v1/gestion/productos/{id}/stock/ajustes` → 201

```json
{ "cantidad": -2, "motivo": "Rotura en depósito" }
```

`cantidad` va **con signo** —un ajuste resta tanto como suma— y no puede ser cero. `motivo` es
obligatorio.

**El producto sale de la ruta.** Si el cuerpo trae `producto_id`, se pisa: no hay forma de
ajustar el stock de un producto distinto del que dice la URL.

La respuesta trae el movimiento registrado en `data`, la situación actualizada en `producto`, y
`ventas_liberadas`: un ajuste positivo puede destrabar ventas que estaban esperando mercadería,
y ese número es el que el panel muestra como «N venta(s) pendientes de stock pasaron a
preparación».

Un ajuste negativo que dejaría el stock físico bajo cero responde **409**: una faltante mayor
al saldo indica que el error está en un movimiento anterior, no en el ajuste. Es 409 y no 422
porque el cuerpo está bien formado; lo que no da es el estado del sistema.

Se puede ajustar un producto dado de baja: sigue teniendo existencias que pueden tener que
corregirse.

### Lo que no se expone

- **La carga inicial del conteo** queda solo en el panel: es una operación de puesta en marcha
  que se hace una vez.
- **No hay `POST /stock/*` suelto.** Toda escritura de inventario cuelga de un producto, y pasa
  por `StockService`. Los movimientos de venta, entrega, reserva y recepción los generan sus
  propias operaciones, no un endpoint de stock.

---

## 7. Pendiente

- Las rutas web de `/gestion/category/*`, `/gestion/item/*` y `/gestion/stock/*` siguen en pie
  y operando por Blade. Se retirarán cuando las pantallas consuman estos endpoints.
- **Entrega D**: proveedores. **Entrega E**: compras (listado, detalle, borrador, y las
  transiciones con la recepción de mercadería).
- Ventas, clientes, promociones, empleados y comprobantes siguen sin endpoint.
