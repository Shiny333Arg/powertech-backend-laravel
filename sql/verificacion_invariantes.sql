-- ============================================================
-- PowerTech — verificacion de los invariantes de stock
-- Ejecutar contra la base recien migrada (o contra una copia).
-- Reproduce el DDL que genera la migracion de `productos` y
-- prueba las cuatro reglas que el motor debe hacer cumplir.
-- ============================================================

SET SESSION sql_mode = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

DROP DATABASE IF EXISTS powertech_test;
CREATE DATABASE powertech_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE powertech_test;

-- DDL equivalente al que emite la migracion de productos (parte de stock)
CREATE TABLE productos (
  id                  bigint unsigned NOT NULL AUTO_INCREMENT,
  codigo              varchar(30) NOT NULL,
  nombre              varchar(150) NOT NULL,
  precio              decimal(12,2) NOT NULL,
  stock_fisico        int unsigned NOT NULL DEFAULT 0,
  stock_comprometido  int unsigned NOT NULL DEFAULT 0,
  stock_disponible    int AS (CAST(stock_fisico AS SIGNED) - CAST(stock_comprometido AS SIGNED)) STORED,
  punto_reposicion    int NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY productos_codigo_unique (codigo),
  KEY productos_stock_disponible_index (stock_disponible)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO productos (codigo, nombre, precio, stock_fisico, stock_comprometido, punto_reposicion)
VALUES ('SSD480', 'SSD Kingston 480GB', 45000.00, 10, 0, 5);

-- ------------------------------------------------------------
-- 1. Venta confirmada de 4 u.: compromete, no toca el fisico
-- ------------------------------------------------------------
UPDATE productos SET stock_comprometido = stock_comprometido + 4 WHERE codigo = 'SSD480';
SELECT '1. venta confirmada (4u)' AS caso, stock_fisico, stock_comprometido, stock_disponible
FROM productos WHERE codigo = 'SSD480';   -- esperado: 10 / 4 / 6

-- ------------------------------------------------------------
-- 2. PREVENTA: comprometer 20 u. mas sin stock. Debe funcionar
--    y dejar el disponible NEGATIVO. Es el caso que rompe si
--    falta el CAST en la columna generada.
-- ------------------------------------------------------------
UPDATE productos SET stock_comprometido = stock_comprometido + 20 WHERE codigo = 'SSD480';
SELECT '2. preventa (+20u)' AS caso, stock_fisico, stock_comprometido, stock_disponible
FROM productos WHERE codigo = 'SSD480';   -- esperado: 10 / 24 / -14

-- ------------------------------------------------------------
-- 3. Entrega guardada: intentar entregar 30 u. teniendo 10.
--    El WHERE debe devolver 0 filas afectadas.
-- ------------------------------------------------------------
UPDATE productos
   SET stock_fisico = stock_fisico - 30, stock_comprometido = stock_comprometido - 30
 WHERE codigo = 'SSD480' AND stock_fisico >= 30 AND stock_comprometido >= 30;
SELECT '3. entrega sin stock' AS caso, ROW_COUNT() AS filas_afectadas;  -- esperado: 0

-- ------------------------------------------------------------
-- 4. Entrega valida de 10 u.
-- ------------------------------------------------------------
UPDATE productos
   SET stock_fisico = stock_fisico - 10, stock_comprometido = stock_comprometido - 10
 WHERE codigo = 'SSD480' AND stock_fisico >= 10 AND stock_comprometido >= 10;
SELECT '4. entrega valida (10u)' AS caso, stock_fisico, stock_comprometido, stock_disponible
FROM productos WHERE codigo = 'SSD480';   -- esperado: 0 / 14 / -14

-- ------------------------------------------------------------
-- 5. La columna generada es de solo lectura
-- ------------------------------------------------------------
-- UPDATE productos SET stock_disponible = 999 WHERE codigo='SSD480';  -- ERROR 1906
