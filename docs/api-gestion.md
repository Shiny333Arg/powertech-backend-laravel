# API de gestión — `/api/v1/gestion`

Ámbito administrativo de la API REST de PowerTech. Hermano de la API de tienda
(`/api/v1/...`, documentada por `routes/api.php`): mismos servicios, mismas
reglas de negocio y la misma forma de respuesta; lo que cambia es quién entra y
qué ve.

Estado: **Entrega A — categorías**. Productos va en la Entrega B.

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

## 5. Pendiente

- **Entrega B**: productos (alta, edición, estado y publicación en la tienda).
- Las rutas web de `/gestion/category/*` siguen en pie y operando por Blade. Se
  retirarán cuando las pantallas consuman estos endpoints.
