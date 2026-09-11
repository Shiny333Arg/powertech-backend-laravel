# Seeders de datos maestros — PowerTech

Cargan solo configuración. No hay datos de negocio: productos, clientes, ventas, pagos, stock y órdenes de compra los carga el administrativo antes del go-live (Requerimientos 11.3).

## Ejecución

```bash
php artisan db:seed
php artisan db:seed --class=CatalogosArcaSeeder   # un catálogo suelto
```

Todo corre dentro de una transacción y es idempotente: repetir la corrida no duplica filas.

## Qué hace cada clase

| Seeder | Tabla | Clave natural | Comportamiento al repetir |
|---|---|---|---|
| `RolSeeder` | `roles` | `nombre` | Reconcilia descripción y ámbito |
| `PermisoSeeder` | `permisos` | `clave` | Reconcilia módulo y descripción |
| `RolPermisoSeeder` | `rol_permiso` | `(rol_id, permiso_id)` | **Sincroniza**: agrega faltantes y quita sobrantes de los 5 roles de sistema |
| `CatalogosArcaSeeder` | `tipos_documento`, `condiciones_iva`, `alicuotas_iva`, `tipos_comprobante` | `codigo_afip` | Reconcilia nombres |
| `PuntoVentaSeeder` | `puntos_venta` | `numero` | Reconcilia nombre y tipo de emisión |
| `NumeradorSeeder` | `numeradores` | `serie` | **Solo inserta.** Nunca pisa `ultimo_numero` |
| `UsuarioAdministradorSeeder` | `users` | `usuario` | **Solo inserta.** Nunca restaura la contraseña |

Las dos últimas filas son la diferencia importante: `ultimo_numero` y la contraseña del administrador son estado vivo. Reescribirlos desde el repositorio rompería la serie correlativa o devolvería una credencial conocida.

`RolPermisoSeeder` sincroniza a propósito, para que la matriz versionada sea la autoridad. Solo alcanza a los cinco roles declarados; un rol creado desde la aplicación no se toca.

## Variables de entorno

```dotenv
ARCA_PUNTO_VENTA=1
ARCA_TIPO_EMISION=wsfe

SEED_ADMIN_USUARIO=admin
SEED_ADMIN_EMAIL=admin@powertech.com.ar
SEED_ADMIN_PASSWORD=            # obligatoria en producción

SEED_NUMERADOR_VENTA=0          # ajustar solo si se continúa una serie anterior
SEED_NUMERADOR_ORDEN_COMPRA=0
```

## Antes del go-live

1. Verificar los códigos de ARCA contra la tabla de parámetros vigente del WSFEv1. Están hardcodeados con los valores habituales, pero el organismo los cambia sin aviso.
2. Confirmar que el punto de venta esté habilitado ante el organismo.
3. Definir si la empresa continúa una numeración interna anterior: es el único valor a ajustar en `numeradores`, y solo sirve con la base todavía sin ventas.
4. Cambiar la contraseña del administrador en el primer ingreso (el flag `forzar_cambio_password` ya lo obliga).
