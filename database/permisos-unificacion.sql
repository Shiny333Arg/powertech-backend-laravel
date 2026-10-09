-- ============================================================
-- PowerTech — Unificación del vocabulario de permisos
-- ============================================================
--
-- Se aplica sobre la base YA POBLADA (powertech_laravel). No reemplaza al
-- dump: es un parche incremental, para no perder ventas, comprobantes ni
-- movimientos de stock reimportando todo.
--
-- Por qué hace falta: PermisoSeeder y PermisosMostradorSeeder nombran las
-- mismas acciones con claves distintas y los dos son aditivos, así que la
-- tabla `permisos` terminó con la unión de ambos vocabularios. El código
-- (VentaPolicy, PagoPolicy, DashboardController) lee solo el de Mostrador;
-- las claves del otro quedaron en la base sin que nadie las consulte.
--
-- Eso importa ahora que vamos a poner autorización en las rutas: la futura
-- pantalla de roles listaría las dos familias, y revocar la clave muerta no
-- cambiaría nada. Un permiso que aparenta funcionar es peor que no tenerlo.
--
-- Ejecutar una sola vez, en phpMyAdmin o con:
--   mysql -u root powertech_laravel < permisos-unificacion.sql

START TRANSACTION;


-- ------------------------------------------------------------
-- 1. El vendedor pasa a poder anular ventas
-- ------------------------------------------------------------
--
-- Requerimiento 4.3: el circuito de la venta es del vendedor de punta a
-- punta. Ya tiene venta.entregar y venta.devolver; lo único que le falta es
-- venta.anular, porque la matriz de Mostrador se la asignó solo al
-- administrativo y al administrador.
--
-- Se resuelve por clave natural y no por id: los ids del dump son de esta
-- base y no tienen por qué coincidir en otra.

INSERT IGNORE INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id
FROM roles r
JOIN permisos p
WHERE r.nombre = 'vendedor'
  AND p.clave  = 'venta.anular';


-- ------------------------------------------------------------
-- 2. Baja de las claves que ningún código consulta
-- ------------------------------------------------------------
--
-- Cada una tiene un reemplazo vigente, salvo las de dashboards, que no se
-- reemplazaron por nada: DashboardController arma los bloques mirando
-- venta.ver_todas, venta.crear y venta.cobrar, no estas tres.
--
--   venta.ver_propias        -> el alcance lo resuelve venta.ver_todas
--   presupuesto.validar      -> venta.validar
--   venta.cancelar           -> venta.anular
--   venta.preparar           -> venta.entregar (quedaron fusionadas)
--   cobro.registrar          -> venta.cobrar
--   cobro.ver                -> venta.ver_todas
--   cobro.ver_propios        -> sin reemplazo: PagoPolicy::view ya compara
--                               pago.usuario_id contra el usuario
--   dashboard.*              -> sin reemplazo, nunca se consultaron
--
-- rol_permiso se limpia sola: su FK a permisos es ON DELETE CASCADE.
--
-- NO se borra comprobante.nota_credito. Tampoco la lee nadie hoy, pero el
-- circuito de devoluciones emite notas de crédito y es el permiso que le
-- corresponde; conviene conectarlo en DevolucionService antes que borrarlo.

DELETE FROM permisos
WHERE clave IN (
    'venta.ver_propias',
    'presupuesto.validar',
    'venta.cancelar',
    'venta.preparar',
    'cobro.registrar',
    'cobro.ver',
    'cobro.ver_propios',
    'dashboard.administrativo',
    'dashboard.vendedor',
    'dashboard.cajero'
);


-- ------------------------------------------------------------
-- 3. Un solo nombre de módulo para compras
-- ------------------------------------------------------------
--
-- compra.recibir quedó en el módulo 'compras' y las otras seis en 'compra'.
-- La pantalla de roles agrupa por este campo: sin esto, el mismo circuito
-- aparecería partido en dos secciones.

UPDATE permisos SET modulo = 'compras' WHERE modulo = 'compra';


COMMIT;


-- ------------------------------------------------------------
-- Verificación
-- ------------------------------------------------------------
--
-- Descomentar y correr después del COMMIT.
--
-- Esperado: 56 permisos (eran 66), y el vendedor con venta.anular entre los
-- suyos.
--
-- SELECT COUNT(*) AS permisos FROM permisos;
--
-- SELECT r.nombre AS rol, p.clave
-- FROM roles r
-- JOIN rol_permiso rp ON rp.rol_id = r.id
-- JOIN permisos p     ON p.id = rp.permiso_id
-- WHERE r.nombre = 'vendedor'
-- ORDER BY p.modulo, p.clave;
