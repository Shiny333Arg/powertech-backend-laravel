-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 01-11-2026 a las 02:12:25
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `powertech_laravel`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `afip_solicitudes`
--

CREATE TABLE `afip_solicitudes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `comprobante_id` bigint(20) UNSIGNED NOT NULL,
  `operacion` varchar(40) NOT NULL,
  `request_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`request_payload`)),
  `response_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`response_payload`)),
  `resultado` char(1) DEFAULT NULL,
  `codigo_error` varchar(20) DEFAULT NULL,
  `mensaje_error` varchar(255) DEFAULT NULL,
  `intento` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `afip_solicitudes`
--

INSERT INTO `afip_solicitudes` (`id`, `comprobante_id`, `operacion`, `request_payload`, `response_payload`, `resultado`, `codigo_error`, `mensaje_error`, `intento`, `created_at`) VALUES
(2, 2, 'FECAESolicitar (SIMULADO)', '{\"CbteTipo\":6,\"PtoVta\":1,\"CbteDesde\":1,\"CbteHasta\":1,\"DocTipo\":96,\"DocNro\":\"14514514\",\"CbteFch\":\"20260926\",\"ImpNeto\":24793.39,\"ImpIVA\":5206.61,\"ImpTotal\":30000}', '{\"Resultado\":\"A\",\"CAE\":\"95206081829857\",\"CAEFchVto\":\"20261006\",\"Observaciones\":\"Comprobante emitido por el autorizador simulado. Sin validez fiscal.\"}', 'A', NULL, NULL, 1, '2026-09-27 02:59:04'),
(3, 3, 'FECAESolicitar (SIMULADO)', '{\"CbteTipo\":6,\"PtoVta\":1,\"CbteDesde\":2,\"CbteHasta\":2,\"DocTipo\":96,\"DocNro\":\"14514514\",\"CbteFch\":\"20260927\",\"ImpNeto\":375000,\"ImpIVA\":0,\"ImpTotal\":375000}', '{\"Resultado\":\"A\",\"CAE\":\"12132322395628\",\"CAEFchVto\":\"20261007\",\"Observaciones\":\"Comprobante emitido por el autorizador simulado. Sin validez fiscal.\"}', 'A', NULL, NULL, 1, '2026-09-27 03:00:25'),
(6, 6, 'FECAESolicitar (SIMULADO)', '{\"CbteTipo\":6,\"PtoVta\":1,\"CbteDesde\":3,\"CbteHasta\":3,\"DocTipo\":96,\"DocNro\":\"14514514\",\"CbteFch\":\"20260927\",\"ImpNeto\":35433.19,\"ImpIVA\":9566.96,\"ImpTotal\":45000.15}', '{\"Resultado\":\"A\",\"CAE\":\"66007347466755\",\"CAEFchVto\":\"20261007\",\"Observaciones\":\"Comprobante emitido por el autorizador simulado. Sin validez fiscal.\"}', 'A', NULL, NULL, 1, '2026-09-27 04:38:11'),
(8, 8, 'FECAESolicitar (SIMULADO)', '{\"CbteTipo\":6,\"PtoVta\":1,\"CbteDesde\":4,\"CbteHasta\":4,\"DocTipo\":96,\"DocNro\":\"14514514\",\"CbteFch\":\"20260927\",\"ImpNeto\":1158802.48,\"ImpIVA\":243348.52,\"ImpTotal\":1402151}', '{\"Resultado\":\"A\",\"CAE\":\"79408384791666\",\"CAEFchVto\":\"20261007\",\"Observaciones\":\"Comprobante emitido por el autorizador simulado. Sin validez fiscal.\"}', 'A', NULL, NULL, 1, '2026-09-27 04:40:17'),
(11, 11, 'FECAESolicitar (SIMULADO)', '{\"CbteTipo\":6,\"PtoVta\":1,\"CbteDesde\":5,\"CbteHasta\":5,\"DocTipo\":96,\"DocNro\":\"14514514\",\"CbteFch\":\"20261001\",\"ImpNeto\":50000,\"ImpIVA\":0,\"ImpTotal\":50000}', '{\"Resultado\":\"A\",\"CAE\":\"97028902203233\",\"CAEFchVto\":\"20261011\",\"Observaciones\":\"Comprobante emitido por el autorizador simulado. Sin validez fiscal.\"}', 'A', NULL, NULL, 1, '2026-10-01 04:34:53'),
(13, 13, 'FECAESolicitar (SIMULADO)', '{\"CbteTipo\":6,\"PtoVta\":1,\"CbteDesde\":6,\"CbteHasta\":6,\"DocTipo\":96,\"DocNro\":\"14514514\",\"CbteFch\":\"20261005\",\"ImpNeto\":15000,\"ImpIVA\":0,\"ImpTotal\":15000}', '{\"Resultado\":\"A\",\"CAE\":\"56127616389477\",\"CAEFchVto\":\"20261015\",\"Observaciones\":\"Comprobante emitido por el autorizador simulado. Sin validez fiscal.\"}', 'A', NULL, NULL, 1, '2026-10-05 09:14:29'),
(14, 14, 'FECAESolicitar (SIMULADO)', '{\"CbteTipo\":6,\"PtoVta\":1,\"CbteDesde\":7,\"CbteHasta\":7,\"DocTipo\":96,\"DocNro\":\"14514514\",\"CbteFch\":\"20261007\",\"ImpNeto\":100000,\"ImpIVA\":0,\"ImpTotal\":100000}', '{\"Resultado\":\"A\",\"CAE\":\"50301864547749\",\"CAEFchVto\":\"20261017\",\"Observaciones\":\"Comprobante emitido por el autorizador simulado. Sin validez fiscal.\"}', 'A', NULL, NULL, 1, '2026-10-07 03:19:31'),
(15, 15, 'FECAESolicitar (SIMULADO)', '{\"CbteTipo\":8,\"PtoVta\":1,\"CbteDesde\":1,\"CbteHasta\":1,\"DocTipo\":96,\"DocNro\":\"14514514\",\"CbteFch\":\"20261007\",\"ImpNeto\":100000,\"ImpIVA\":0,\"ImpTotal\":100000}', '{\"Resultado\":\"A\",\"CAE\":\"38783921757477\",\"CAEFchVto\":\"20261017\",\"Observaciones\":\"Comprobante emitido por el autorizador simulado. Sin validez fiscal.\"}', 'A', NULL, NULL, 1, '2026-10-07 03:20:26'),
(17, 17, 'FECAESolicitar (SIMULADO)', '{\"CbteTipo\":6,\"PtoVta\":1,\"CbteDesde\":8,\"CbteHasta\":8,\"DocTipo\":96,\"DocNro\":\"14514514\",\"CbteFch\":\"20261007\",\"ImpNeto\":100000,\"ImpIVA\":0,\"ImpTotal\":100000}', '{\"Resultado\":\"A\",\"CAE\":\"61164754009699\",\"CAEFchVto\":\"20261017\",\"Observaciones\":\"Comprobante emitido por el autorizador simulado. Sin validez fiscal.\"}', 'A', NULL, NULL, 1, '2026-10-08 02:11:02'),
(18, 18, 'FECAESolicitar (SIMULADO)', '{\"CbteTipo\":6,\"PtoVta\":1,\"CbteDesde\":9,\"CbteHasta\":9,\"DocTipo\":96,\"DocNro\":\"14514514\",\"CbteFch\":\"20261009\",\"ImpNeto\":50000,\"ImpIVA\":0,\"ImpTotal\":50000}', '{\"Resultado\":\"A\",\"CAE\":\"10652916595972\",\"CAEFchVto\":\"20261019\",\"Observaciones\":\"Comprobante emitido por el autorizador simulado. Sin validez fiscal.\"}', 'A', NULL, NULL, 1, '2026-10-09 03:55:13'),
(19, 19, 'FECAESolicitar (SIMULADO)', '{\"CbteTipo\":6,\"PtoVta\":1,\"CbteDesde\":10,\"CbteHasta\":10,\"DocTipo\":96,\"DocNro\":\"14514514\",\"CbteFch\":\"20261009\",\"ImpNeto\":100000,\"ImpIVA\":0,\"ImpTotal\":100000}', '{\"Resultado\":\"A\",\"CAE\":\"51640345405104\",\"CAEFchVto\":\"20261019\",\"Observaciones\":\"Comprobante emitido por el autorizador simulado. Sin validez fiscal.\"}', 'A', NULL, NULL, 1, '2026-10-09 03:58:36'),
(20, 20, 'FECAESolicitar (SIMULADO)', '{\"CbteTipo\":8,\"PtoVta\":1,\"CbteDesde\":2,\"CbteHasta\":2,\"DocTipo\":96,\"DocNro\":\"14514514\",\"CbteFch\":\"20261009\",\"ImpNeto\":100000,\"ImpIVA\":0,\"ImpTotal\":100000}', '{\"Resultado\":\"A\",\"CAE\":\"44016391815820\",\"CAEFchVto\":\"20261019\",\"Observaciones\":\"Comprobante emitido por el autorizador simulado. Sin validez fiscal.\"}', 'A', NULL, NULL, 1, '2026-10-09 03:59:14'),
(22, 22, 'FECAESolicitar (SIMULADO)', '{\"CbteTipo\":6,\"PtoVta\":1,\"CbteDesde\":11,\"CbteHasta\":11,\"DocTipo\":96,\"DocNro\":\"14514514\",\"CbteFch\":\"20261009\",\"ImpNeto\":100000,\"ImpIVA\":0,\"ImpTotal\":100000}', '{\"Resultado\":\"A\",\"CAE\":\"22571413256626\",\"CAEFchVto\":\"20261019\",\"Observaciones\":\"Comprobante emitido por el autorizador simulado. Sin validez fiscal.\"}', 'A', NULL, NULL, 1, '2026-10-09 20:52:16'),
(23, 23, 'FECAESolicitar (SIMULADO)', '{\"CbteTipo\":6,\"PtoVta\":1,\"CbteDesde\":12,\"CbteHasta\":12,\"DocTipo\":96,\"DocNro\":\"14514514\",\"CbteFch\":\"20261009\",\"ImpNeto\":241732.76,\"ImpIVA\":38267.84,\"ImpTotal\":280000.6}', '{\"Resultado\":\"A\",\"CAE\":\"96766508865596\",\"CAEFchVto\":\"20261019\",\"Observaciones\":\"Comprobante emitido por el autorizador simulado. Sin validez fiscal.\"}', 'A', NULL, NULL, 1, '2026-10-09 23:11:07'),
(25, 25, 'FECAESolicitar (SIMULADO)', '{\"CbteTipo\":6,\"PtoVta\":1,\"CbteDesde\":13,\"CbteHasta\":13,\"DocTipo\":96,\"DocNro\":\"14514514\",\"CbteFch\":\"20261029\",\"ImpNeto\":37190.08,\"ImpIVA\":7809.92,\"ImpTotal\":45000}', '{\"Resultado\":\"A\",\"CAE\":\"17445458964792\",\"CAEFchVto\":\"20261108\",\"Observaciones\":\"Comprobante emitido por el autorizador simulado. Sin validez fiscal.\"}', 'A', NULL, NULL, 1, '2026-10-30 01:58:50'),
(26, 26, 'FECAESolicitar (SIMULADO)', '{\"CbteTipo\":6,\"PtoVta\":1,\"CbteDesde\":14,\"CbteHasta\":14,\"DocTipo\":96,\"DocNro\":\"14514514\",\"CbteFch\":\"20261029\",\"ImpNeto\":7438016.53,\"ImpIVA\":1561983.47,\"ImpTotal\":9000000}', '{\"Resultado\":\"A\",\"CAE\":\"24621521097866\",\"CAEFchVto\":\"20261108\",\"Observaciones\":\"Comprobante emitido por el autorizador simulado. Sin validez fiscal.\"}', 'A', NULL, NULL, 1, '2026-10-30 02:20:28'),
(27, 27, 'FECAESolicitar (SIMULADO)', '{\"CbteTipo\":6,\"PtoVta\":1,\"CbteDesde\":15,\"CbteHasta\":15,\"DocTipo\":96,\"DocNro\":\"14514514\",\"CbteFch\":\"20261031\",\"ImpNeto\":42500,\"ImpIVA\":0,\"ImpTotal\":42500}', '{\"Resultado\":\"A\",\"CAE\":\"42146493756710\",\"CAEFchVto\":\"20261110\",\"Observaciones\":\"Comprobante emitido por el autorizador simulado. Sin validez fiscal.\"}', 'A', NULL, NULL, 1, '2026-10-31 06:00:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alicuotas_iva`
--

CREATE TABLE `alicuotas_iva` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `codigo_afip` smallint(6) NOT NULL,
  `porcentaje` decimal(4,2) NOT NULL,
  `nombre` varchar(30) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `alicuotas_iva`
--

INSERT INTO `alicuotas_iva` (`id`, `codigo_afip`, `porcentaje`, `nombre`, `created_at`, `updated_at`) VALUES
(1, 3, 0.00, '0%', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(2, 9, 2.50, '2,5%', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(3, 8, 5.00, '5%', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(4, 4, 10.50, '10,5%', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(5, 5, 21.00, '21%', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(6, 6, 27.00, '27%', '2026-09-22 03:05:45', '2026-09-22 03:05:45');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('powertech-cache-5c785c036466adea360111aa28563bfd556b5fba', 'i:1;', 1792111928),
('powertech-cache-5c785c036466adea360111aa28563bfd556b5fba:timer', 'i:1792111928;', 1792111928),
('powertech-cache-9e6a55b6b4563e652a23be9d623ca5055c356940', 'i:1;', 1791509547),
('powertech-cache-9e6a55b6b4563e652a23be9d623ca5055c356940:timer', 'i:1791509547;', 1791509547),
('powertech-cache-b3f0c7f6bb763af1be91d9e74eabfeb199dc1f1f', 'i:1;', 1791575805),
('powertech-cache-b3f0c7f6bb763af1be91d9e74eabfeb199dc1f1f:timer', 'i:1791575805;', 1791575805);
INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('powertech-cache-roster:project:v3:0920972b879ba9910f5485b3085bc8ce', 'O:26:\"Laravel\\Roster\\ProjectScan\":8:{s:8:\"basePath\";s:126:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\\";s:3:\"php\";O:35:\"Laravel\\Roster\\Ecosystems\\Ecosystem\":2:{s:9:\"\0*\0byName\";a:133:{s:10:\"brick/math\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:10:\"brick/math\";s:10:\"\0*\0version\";s:6:\"0.14.8\";s:9:\"\0*\0source\";E:43:\"Laravel\\Roster\\Enums\\PackageSource:Composer\";s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:143:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\brick\\math\";}s:31:\"carbonphp/carbon-doctrine-types\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:31:\"carbonphp/carbon-doctrine-types\";s:10:\"\0*\0version\";s:5:\"3.2.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:164:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\carbonphp\\carbon-doctrine-types\";}s:23:\"dflydev/dot-access-data\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:23:\"dflydev/dot-access-data\";s:10:\"\0*\0version\";s:5:\"3.0.3\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:156:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\dflydev\\dot-access-data\";}s:18:\"doctrine/inflector\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:18:\"doctrine/inflector\";s:10:\"\0*\0version\";s:5:\"2.1.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:151:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\doctrine\\inflector\";}s:14:\"doctrine/lexer\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:14:\"doctrine/lexer\";s:10:\"\0*\0version\";s:5:\"3.0.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:147:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\doctrine\\lexer\";}s:29:\"dragonmantank/cron-expression\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:29:\"dragonmantank/cron-expression\";s:10:\"\0*\0version\";s:5:\"3.6.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:162:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\dragonmantank\\cron-expression\";}s:23:\"egulias/email-validator\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:23:\"egulias/email-validator\";s:10:\"\0*\0version\";s:5:\"4.0.4\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:156:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\egulias\\email-validator\";}s:18:\"fruitcake/php-cors\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:18:\"fruitcake/php-cors\";s:10:\"\0*\0version\";s:5:\"1.4.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:151:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\fruitcake\\php-cors\";}s:27:\"graham-campbell/result-type\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:27:\"graham-campbell/result-type\";s:10:\"\0*\0version\";s:5:\"1.2.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:160:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\graham-campbell\\result-type\";}s:17:\"guzzlehttp/guzzle\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:17:\"guzzlehttp/guzzle\";s:10:\"\0*\0version\";s:6:\"7.15.5\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:150:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\guzzlehttp\\guzzle\";}s:19:\"guzzlehttp/promises\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:19:\"guzzlehttp/promises\";s:10:\"\0*\0version\";s:5:\"2.5.3\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:152:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\guzzlehttp\\promises\";}s:15:\"guzzlehttp/psr7\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:15:\"guzzlehttp/psr7\";s:10:\"\0*\0version\";s:6:\"2.13.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:148:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\guzzlehttp\\psr7\";}s:23:\"guzzlehttp/uri-template\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:23:\"guzzlehttp/uri-template\";s:10:\"\0*\0version\";s:6:\"1.0.11\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:156:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\guzzlehttp\\uri-template\";}s:17:\"laravel/framework\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:17:\"laravel/framework\";s:10:\"\0*\0version\";s:7:\"12.67.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:5:\"^12.0\";s:7:\"\0*\0path\";s:150:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\laravel\\framework\";}s:15:\"laravel/prompts\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:15:\"laravel/prompts\";s:10:\"\0*\0version\";s:6:\"0.3.23\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:148:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\laravel\\prompts\";}s:15:\"laravel/sanctum\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:15:\"laravel/sanctum\";s:10:\"\0*\0version\";s:5:\"4.3.3\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:4:\"^4.3\";s:7:\"\0*\0path\";s:148:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\laravel\\sanctum\";}s:28:\"laravel/serializable-closure\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:28:\"laravel/serializable-closure\";s:10:\"\0*\0version\";s:6:\"2.0.15\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:161:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\laravel\\serializable-closure\";}s:14:\"laravel/tinker\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:14:\"laravel/tinker\";s:10:\"\0*\0version\";s:6:\"2.11.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:7:\"^2.10.1\";s:7:\"\0*\0path\";s:147:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\laravel\\tinker\";}s:17:\"league/commonmark\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:17:\"league/commonmark\";s:10:\"\0*\0version\";s:6:\"2.10.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:150:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\league\\commonmark\";}s:13:\"league/config\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:13:\"league/config\";s:10:\"\0*\0version\";s:5:\"1.2.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:146:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\league\\config\";}s:16:\"league/flysystem\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:16:\"league/flysystem\";s:10:\"\0*\0version\";s:6:\"3.35.3\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:149:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\league\\flysystem\";}s:22:\"league/flysystem-local\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:22:\"league/flysystem-local\";s:10:\"\0*\0version\";s:6:\"3.35.3\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:155:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\league\\flysystem-local\";}s:26:\"league/mime-type-detection\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:26:\"league/mime-type-detection\";s:10:\"\0*\0version\";s:6:\"1.17.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:159:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\league\\mime-type-detection\";}s:10:\"league/uri\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:10:\"league/uri\";s:10:\"\0*\0version\";s:5:\"7.8.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:143:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\league\\uri\";}s:21:\"league/uri-interfaces\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:21:\"league/uri-interfaces\";s:10:\"\0*\0version\";s:5:\"7.8.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:154:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\league\\uri-interfaces\";}s:18:\"mercadopago/dx-php\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:18:\"mercadopago/dx-php\";s:10:\"\0*\0version\";s:6:\"3.16.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:1:\"*\";s:7:\"\0*\0path\";s:151:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\mercadopago\\dx-php\";}s:15:\"monolog/monolog\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:15:\"monolog/monolog\";s:10:\"\0*\0version\";s:6:\"3.10.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:148:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\monolog\\monolog\";}s:13:\"nesbot/carbon\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:13:\"nesbot/carbon\";s:10:\"\0*\0version\";s:6:\"3.13.2\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:146:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\nesbot\\carbon\";}s:12:\"nette/schema\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:12:\"nette/schema\";s:10:\"\0*\0version\";s:5:\"1.3.6\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:145:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\nette\\schema\";}s:11:\"nette/utils\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:11:\"nette/utils\";s:10:\"\0*\0version\";s:5:\"4.1.5\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:144:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\nette\\utils\";}s:16:\"nikic/php-parser\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:16:\"nikic/php-parser\";s:10:\"\0*\0version\";s:5:\"5.8.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:149:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\nikic\\php-parser\";}s:19:\"nunomaduro/termwind\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:19:\"nunomaduro/termwind\";s:10:\"\0*\0version\";s:5:\"2.4.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:152:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\nunomaduro\\termwind\";}s:19:\"phpoption/phpoption\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:19:\"phpoption/phpoption\";s:10:\"\0*\0version\";s:6:\"1.10.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:152:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\phpoption\\phpoption\";}s:9:\"psr/clock\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:9:\"psr/clock\";s:10:\"\0*\0version\";s:5:\"1.0.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:142:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\psr\\clock\";}s:13:\"psr/container\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:13:\"psr/container\";s:10:\"\0*\0version\";s:5:\"2.0.2\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:146:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\psr\\container\";}s:20:\"psr/event-dispatcher\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:20:\"psr/event-dispatcher\";s:10:\"\0*\0version\";s:5:\"1.0.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:153:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\psr\\event-dispatcher\";}s:15:\"psr/http-client\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:15:\"psr/http-client\";s:10:\"\0*\0version\";s:5:\"1.0.3\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:148:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\psr\\http-client\";}s:16:\"psr/http-factory\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:16:\"psr/http-factory\";s:10:\"\0*\0version\";s:5:\"1.1.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:149:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\psr\\http-factory\";}s:16:\"psr/http-message\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:16:\"psr/http-message\";s:10:\"\0*\0version\";s:3:\"2.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:149:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\psr\\http-message\";}s:7:\"psr/log\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:7:\"psr/log\";s:10:\"\0*\0version\";s:5:\"3.0.2\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:140:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\psr\\log\";}s:16:\"psr/simple-cache\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:16:\"psr/simple-cache\";s:10:\"\0*\0version\";s:5:\"3.0.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:149:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\psr\\simple-cache\";}s:9:\"psy/psysh\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:9:\"psy/psysh\";s:10:\"\0*\0version\";s:7:\"0.12.24\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:142:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\psy\\psysh\";}s:23:\"ralouphie/getallheaders\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:23:\"ralouphie/getallheaders\";s:10:\"\0*\0version\";s:5:\"3.0.3\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:156:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\ralouphie\\getallheaders\";}s:17:\"ramsey/collection\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:17:\"ramsey/collection\";s:10:\"\0*\0version\";s:5:\"2.1.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:150:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\ramsey\\collection\";}s:11:\"ramsey/uuid\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:11:\"ramsey/uuid\";s:10:\"\0*\0version\";s:5:\"4.9.3\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:144:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\ramsey\\uuid\";}s:13:\"symfony/clock\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:13:\"symfony/clock\";s:10:\"\0*\0version\";s:5:\"7.4.8\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:146:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\symfony\\clock\";}s:15:\"symfony/console\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:15:\"symfony/console\";s:10:\"\0*\0version\";s:6:\"7.4.17\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:148:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\symfony\\console\";}s:20:\"symfony/css-selector\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:20:\"symfony/css-selector\";s:10:\"\0*\0version\";s:6:\"7.4.17\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:153:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\symfony\\css-selector\";}s:29:\"symfony/deprecation-contracts\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:29:\"symfony/deprecation-contracts\";s:10:\"\0*\0version\";s:5:\"3.7.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:162:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\symfony\\deprecation-contracts\";}s:21:\"symfony/error-handler\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:21:\"symfony/error-handler\";s:10:\"\0*\0version\";s:6:\"7.4.17\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:154:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\symfony\\error-handler\";}s:24:\"symfony/event-dispatcher\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:24:\"symfony/event-dispatcher\";s:10:\"\0*\0version\";s:6:\"7.4.17\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:157:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\symfony\\event-dispatcher\";}s:34:\"symfony/event-dispatcher-contracts\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:34:\"symfony/event-dispatcher-contracts\";s:10:\"\0*\0version\";s:5:\"3.7.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:167:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\symfony\\event-dispatcher-contracts\";}s:14:\"symfony/finder\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:14:\"symfony/finder\";s:10:\"\0*\0version\";s:6:\"7.4.17\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:147:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\symfony\\finder\";}s:23:\"symfony/http-foundation\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:23:\"symfony/http-foundation\";s:10:\"\0*\0version\";s:6:\"7.4.17\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:156:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\symfony\\http-foundation\";}s:19:\"symfony/http-kernel\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:19:\"symfony/http-kernel\";s:10:\"\0*\0version\";s:6:\"7.4.17\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:152:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\symfony\\http-kernel\";}s:14:\"symfony/mailer\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:14:\"symfony/mailer\";s:10:\"\0*\0version\";s:6:\"7.4.17\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:147:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\symfony\\mailer\";}s:12:\"symfony/mime\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:12:\"symfony/mime\";s:10:\"\0*\0version\";s:6:\"7.4.17\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:145:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\symfony\\mime\";}s:22:\"symfony/polyfill-ctype\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:22:\"symfony/polyfill-ctype\";s:10:\"\0*\0version\";s:6:\"1.37.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:155:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\symfony\\polyfill-ctype\";}s:30:\"symfony/polyfill-intl-grapheme\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:30:\"symfony/polyfill-intl-grapheme\";s:10:\"\0*\0version\";s:6:\"1.41.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:163:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\symfony\\polyfill-intl-grapheme\";}s:25:\"symfony/polyfill-intl-idn\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:25:\"symfony/polyfill-intl-idn\";s:10:\"\0*\0version\";s:6:\"1.42.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:158:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\symfony\\polyfill-intl-idn\";}s:32:\"symfony/polyfill-intl-normalizer\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:32:\"symfony/polyfill-intl-normalizer\";s:10:\"\0*\0version\";s:6:\"1.42.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:165:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\symfony\\polyfill-intl-normalizer\";}s:25:\"symfony/polyfill-mbstring\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:25:\"symfony/polyfill-mbstring\";s:10:\"\0*\0version\";s:6:\"1.38.2\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:158:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\symfony\\polyfill-mbstring\";}s:22:\"symfony/polyfill-php80\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:22:\"symfony/polyfill-php80\";s:10:\"\0*\0version\";s:6:\"1.37.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:155:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\symfony\\polyfill-php80\";}s:22:\"symfony/polyfill-php83\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:22:\"symfony/polyfill-php83\";s:10:\"\0*\0version\";s:6:\"1.41.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:155:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\symfony\\polyfill-php83\";}s:22:\"symfony/polyfill-php84\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:22:\"symfony/polyfill-php84\";s:10:\"\0*\0version\";s:6:\"1.38.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:155:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\symfony\\polyfill-php84\";}s:22:\"symfony/polyfill-php85\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:22:\"symfony/polyfill-php85\";s:10:\"\0*\0version\";s:6:\"1.41.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:155:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\symfony\\polyfill-php85\";}s:21:\"symfony/polyfill-uuid\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:21:\"symfony/polyfill-uuid\";s:10:\"\0*\0version\";s:6:\"1.37.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:154:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\symfony\\polyfill-uuid\";}s:15:\"symfony/process\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:15:\"symfony/process\";s:10:\"\0*\0version\";s:6:\"7.4.17\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:148:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\symfony\\process\";}s:15:\"symfony/routing\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:15:\"symfony/routing\";s:10:\"\0*\0version\";s:6:\"7.4.17\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:148:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\symfony\\routing\";}s:25:\"symfony/service-contracts\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:25:\"symfony/service-contracts\";s:10:\"\0*\0version\";s:5:\"3.7.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:158:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\symfony\\service-contracts\";}s:14:\"symfony/string\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:14:\"symfony/string\";s:10:\"\0*\0version\";s:6:\"7.4.15\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:147:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\symfony\\string\";}s:19:\"symfony/translation\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:19:\"symfony/translation\";s:10:\"\0*\0version\";s:6:\"7.4.17\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:152:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\symfony\\translation\";}s:29:\"symfony/translation-contracts\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:29:\"symfony/translation-contracts\";s:10:\"\0*\0version\";s:5:\"3.7.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:162:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\symfony\\translation-contracts\";}s:11:\"symfony/uid\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:11:\"symfony/uid\";s:10:\"\0*\0version\";s:6:\"7.4.17\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:144:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\symfony\\uid\";}s:18:\"symfony/var-dumper\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:18:\"symfony/var-dumper\";s:10:\"\0*\0version\";s:6:\"7.4.17\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:151:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\symfony\\var-dumper\";}s:33:\"tijsverkoyen/css-to-inline-styles\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:33:\"tijsverkoyen/css-to-inline-styles\";s:10:\"\0*\0version\";s:5:\"2.4.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:166:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\tijsverkoyen\\css-to-inline-styles\";}s:16:\"vlucas/phpdotenv\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:16:\"vlucas/phpdotenv\";s:10:\"\0*\0version\";s:5:\"5.7.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:149:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\vlucas\\phpdotenv\";}s:19:\"voku/portable-ascii\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:19:\"voku/portable-ascii\";s:10:\"\0*\0version\";s:5:\"2.1.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:152:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\voku\\portable-ascii\";}s:17:\"brianium/paratest\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:17:\"brianium/paratest\";s:10:\"\0*\0version\";s:5:\"7.8.5\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:150:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\brianium\\paratest\";}s:15:\"composer/semver\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:15:\"composer/semver\";s:10:\"\0*\0version\";s:5:\"3.4.4\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:148:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\composer\\semver\";}s:21:\"doctrine/deprecations\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:21:\"doctrine/deprecations\";s:10:\"\0*\0version\";s:5:\"1.1.6\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:154:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\doctrine\\deprecations\";}s:14:\"fakerphp/faker\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:14:\"fakerphp/faker\";s:10:\"\0*\0version\";s:6:\"1.24.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:5:\"^1.23\";s:7:\"\0*\0path\";s:147:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\fakerphp\\faker\";}s:22:\"fidry/cpu-core-counter\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:22:\"fidry/cpu-core-counter\";s:10:\"\0*\0version\";s:5:\"1.3.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:155:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\fidry\\cpu-core-counter\";}s:11:\"filp/whoops\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:11:\"filp/whoops\";s:10:\"\0*\0version\";s:6:\"2.18.4\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:144:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\filp\\whoops\";}s:21:\"hamcrest/hamcrest-php\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:21:\"hamcrest/hamcrest-php\";s:10:\"\0*\0version\";s:5:\"3.0.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:154:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\hamcrest\\hamcrest-php\";}s:30:\"jean85/pretty-package-versions\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:30:\"jean85/pretty-package-versions\";s:10:\"\0*\0version\";s:5:\"2.1.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:163:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\jean85\\pretty-package-versions\";}s:13:\"laravel/boost\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:13:\"laravel/boost\";s:10:\"\0*\0version\";s:5:\"2.5.5\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:4:\"^2.2\";s:7:\"\0*\0path\";s:146:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\laravel\\boost\";}s:14:\"laravel/breeze\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:14:\"laravel/breeze\";s:10:\"\0*\0version\";s:5:\"2.4.2\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:4:\"^2.4\";s:7:\"\0*\0path\";s:147:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\laravel\\breeze\";}s:11:\"laravel/mcp\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:11:\"laravel/mcp\";s:10:\"\0*\0version\";s:5:\"0.9.4\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:144:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\laravel\\mcp\";}s:12:\"laravel/pail\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:12:\"laravel/pail\";s:10:\"\0*\0version\";s:5:\"1.2.7\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:6:\"^1.2.2\";s:7:\"\0*\0path\";s:145:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\laravel\\pail\";}s:12:\"laravel/pint\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:12:\"laravel/pint\";s:10:\"\0*\0version\";s:6:\"1.30.4\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:5:\"^1.24\";s:7:\"\0*\0path\";s:145:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\laravel\\pint\";}s:14:\"laravel/roster\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:14:\"laravel/roster\";s:10:\"\0*\0version\";s:5:\"1.0.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:147:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\laravel\\roster\";}s:12:\"laravel/sail\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:12:\"laravel/sail\";s:10:\"\0*\0version\";s:6:\"1.67.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:5:\"^1.41\";s:7:\"\0*\0path\";s:145:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\laravel\\sail\";}s:15:\"mockery/mockery\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:15:\"mockery/mockery\";s:10:\"\0*\0version\";s:6:\"1.6.15\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:4:\"^1.6\";s:7:\"\0*\0path\";s:148:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\mockery\\mockery\";}s:17:\"myclabs/deep-copy\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:17:\"myclabs/deep-copy\";s:10:\"\0*\0version\";s:6:\"1.14.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:150:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\myclabs\\deep-copy\";}s:20:\"nunomaduro/collision\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:20:\"nunomaduro/collision\";s:10:\"\0*\0version\";s:5:\"8.9.5\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:4:\"^8.6\";s:7:\"\0*\0path\";s:153:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\nunomaduro\\collision\";}s:12:\"pestphp/pest\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:12:\"pestphp/pest\";s:10:\"\0*\0version\";s:5:\"3.8.7\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:4:\"^3.8\";s:7:\"\0*\0path\";s:145:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\pestphp\\pest\";}s:19:\"pestphp/pest-plugin\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:19:\"pestphp/pest-plugin\";s:10:\"\0*\0version\";s:5:\"3.0.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:152:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\pestphp\\pest-plugin\";}s:24:\"pestphp/pest-plugin-arch\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:24:\"pestphp/pest-plugin-arch\";s:10:\"\0*\0version\";s:5:\"3.1.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:157:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\pestphp\\pest-plugin-arch\";}s:27:\"pestphp/pest-plugin-laravel\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:27:\"pestphp/pest-plugin-laravel\";s:10:\"\0*\0version\";s:5:\"3.2.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:4:\"^3.2\";s:7:\"\0*\0path\";s:160:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\pestphp\\pest-plugin-laravel\";}s:26:\"pestphp/pest-plugin-mutate\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:26:\"pestphp/pest-plugin-mutate\";s:10:\"\0*\0version\";s:5:\"3.0.5\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:159:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\pestphp\\pest-plugin-mutate\";}s:16:\"phar-io/manifest\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:16:\"phar-io/manifest\";s:10:\"\0*\0version\";s:5:\"2.0.4\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:149:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\phar-io\\manifest\";}s:15:\"phar-io/version\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:15:\"phar-io/version\";s:10:\"\0*\0version\";s:5:\"3.2.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:148:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\phar-io\\version\";}s:31:\"phpdocumentor/reflection-common\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:31:\"phpdocumentor/reflection-common\";s:10:\"\0*\0version\";s:5:\"2.2.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:164:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\phpdocumentor\\reflection-common\";}s:33:\"phpdocumentor/reflection-docblock\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:33:\"phpdocumentor/reflection-docblock\";s:10:\"\0*\0version\";s:5:\"6.0.3\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:166:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\phpdocumentor\\reflection-docblock\";}s:27:\"phpdocumentor/type-resolver\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:27:\"phpdocumentor/type-resolver\";s:10:\"\0*\0version\";s:5:\"2.0.0\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:160:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\phpdocumentor\\type-resolver\";}s:21:\"phpstan/phpdoc-parser\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:21:\"phpstan/phpdoc-parser\";s:10:\"\0*\0version\";s:5:\"2.3.3\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:154:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\phpstan\\phpdoc-parser\";}s:25:\"phpunit/php-code-coverage\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:25:\"phpunit/php-code-coverage\";s:10:\"\0*\0version\";s:7:\"11.0.12\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:158:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\phpunit\\php-code-coverage\";}s:25:\"phpunit/php-file-iterator\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:25:\"phpunit/php-file-iterator\";s:10:\"\0*\0version\";s:5:\"5.1.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:158:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\phpunit\\php-file-iterator\";}s:19:\"phpunit/php-invoker\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:19:\"phpunit/php-invoker\";s:10:\"\0*\0version\";s:5:\"5.0.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:152:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\phpunit\\php-invoker\";}s:25:\"phpunit/php-text-template\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:25:\"phpunit/php-text-template\";s:10:\"\0*\0version\";s:5:\"4.0.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:158:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\phpunit\\php-text-template\";}s:17:\"phpunit/php-timer\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:17:\"phpunit/php-timer\";s:10:\"\0*\0version\";s:5:\"7.0.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:150:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\phpunit\\php-timer\";}s:15:\"phpunit/phpunit\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:15:\"phpunit/phpunit\";s:10:\"\0*\0version\";s:7:\"11.5.56\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:148:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\phpunit\\phpunit\";}s:20:\"sebastian/cli-parser\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:20:\"sebastian/cli-parser\";s:10:\"\0*\0version\";s:5:\"3.0.2\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:153:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\sebastian\\cli-parser\";}s:19:\"sebastian/code-unit\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:19:\"sebastian/code-unit\";s:10:\"\0*\0version\";s:5:\"3.0.3\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:152:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\sebastian\\code-unit\";}s:34:\"sebastian/code-unit-reverse-lookup\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:34:\"sebastian/code-unit-reverse-lookup\";s:10:\"\0*\0version\";s:5:\"4.0.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:167:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\sebastian\\code-unit-reverse-lookup\";}s:20:\"sebastian/comparator\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:20:\"sebastian/comparator\";s:10:\"\0*\0version\";s:5:\"6.3.3\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:153:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\sebastian\\comparator\";}s:20:\"sebastian/complexity\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:20:\"sebastian/complexity\";s:10:\"\0*\0version\";s:5:\"4.0.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:153:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\sebastian\\complexity\";}s:14:\"sebastian/diff\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:14:\"sebastian/diff\";s:10:\"\0*\0version\";s:5:\"6.0.2\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:147:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\sebastian\\diff\";}s:21:\"sebastian/environment\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:21:\"sebastian/environment\";s:10:\"\0*\0version\";s:5:\"7.2.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:154:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\sebastian\\environment\";}s:18:\"sebastian/exporter\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:18:\"sebastian/exporter\";s:10:\"\0*\0version\";s:5:\"6.3.2\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:151:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\sebastian\\exporter\";}s:22:\"sebastian/global-state\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:22:\"sebastian/global-state\";s:10:\"\0*\0version\";s:5:\"7.0.2\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:155:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\sebastian\\global-state\";}s:23:\"sebastian/lines-of-code\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:23:\"sebastian/lines-of-code\";s:10:\"\0*\0version\";s:5:\"3.0.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:156:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\sebastian\\lines-of-code\";}s:27:\"sebastian/object-enumerator\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:27:\"sebastian/object-enumerator\";s:10:\"\0*\0version\";s:5:\"6.0.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:160:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\sebastian\\object-enumerator\";}s:26:\"sebastian/object-reflector\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:26:\"sebastian/object-reflector\";s:10:\"\0*\0version\";s:5:\"4.0.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:159:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\sebastian\\object-reflector\";}s:27:\"sebastian/recursion-context\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:27:\"sebastian/recursion-context\";s:10:\"\0*\0version\";s:5:\"6.0.3\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:160:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\sebastian\\recursion-context\";}s:14:\"sebastian/type\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:14:\"sebastian/type\";s:10:\"\0*\0version\";s:5:\"5.1.3\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:147:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\sebastian\\type\";}s:17:\"sebastian/version\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:17:\"sebastian/version\";s:10:\"\0*\0version\";s:5:\"5.0.2\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:150:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\sebastian\\version\";}s:28:\"staabm/side-effects-detector\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:28:\"staabm/side-effects-detector\";s:10:\"\0*\0version\";s:5:\"1.0.5\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:161:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\staabm\\side-effects-detector\";}s:12:\"symfony/yaml\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:12:\"symfony/yaml\";s:10:\"\0*\0version\";s:6:\"7.4.17\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:145:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\symfony\\yaml\";}s:35:\"ta-tikoma/phpunit-architecture-test\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:35:\"ta-tikoma/phpunit-architecture-test\";s:10:\"\0*\0version\";s:5:\"0.8.7\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:168:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\ta-tikoma\\phpunit-architecture-test\";}s:17:\"theseer/tokenizer\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:17:\"theseer/tokenizer\";s:10:\"\0*\0version\";s:5:\"1.3.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:150:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\theseer\\tokenizer\";}s:16:\"webmozart/assert\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:16:\"webmozart/assert\";s:10:\"\0*\0version\";s:5:\"2.4.1\";s:9:\"\0*\0source\";r:8;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:149:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\vendor\\webmozart\\assert\";}}s:11:\"\0*\0packages\";O:32:\"Laravel\\Roster\\PackageCollection\":2:{s:8:\"\0*\0items\";a:133:{i:0;r:5;i:1;r:13;i:2;r:21;i:3;r:29;i:4;r:37;i:5;r:45;i:6;r:53;i:7;r:61;i:8;r:69;i:9;r:77;i:10;r:85;i:11;r:93;i:12;r:101;i:13;r:109;i:14;r:117;i:15;r:125;i:16;r:133;i:17;r:141;i:18;r:149;i:19;r:157;i:20;r:165;i:21;r:173;i:22;r:181;i:23;r:189;i:24;r:197;i:25;r:205;i:26;r:213;i:27;r:221;i:28;r:229;i:29;r:237;i:30;r:245;i:31;r:253;i:32;r:261;i:33;r:269;i:34;r:277;i:35;r:285;i:36;r:293;i:37;r:301;i:38;r:309;i:39;r:317;i:40;r:325;i:41;r:333;i:42;r:341;i:43;r:349;i:44;r:357;i:45;r:365;i:46;r:373;i:47;r:381;i:48;r:389;i:49;r:397;i:50;r:405;i:51;r:413;i:52;r:421;i:53;r:429;i:54;r:437;i:55;r:445;i:56;r:453;i:57;r:461;i:58;r:469;i:59;r:477;i:60;r:485;i:61;r:493;i:62;r:501;i:63;r:509;i:64;r:517;i:65;r:525;i:66;r:533;i:67;r:541;i:68;r:549;i:69;r:557;i:70;r:565;i:71;r:573;i:72;r:581;i:73;r:589;i:74;r:597;i:75;r:605;i:76;r:613;i:77;r:621;i:78;r:629;i:79;r:637;i:80;r:645;i:81;r:653;i:82;r:661;i:83;r:669;i:84;r:677;i:85;r:685;i:86;r:693;i:87;r:701;i:88;r:709;i:89;r:717;i:90;r:725;i:91;r:733;i:92;r:741;i:93;r:749;i:94;r:757;i:95;r:765;i:96;r:773;i:97;r:781;i:98;r:789;i:99;r:797;i:100;r:805;i:101;r:813;i:102;r:821;i:103;r:829;i:104;r:837;i:105;r:845;i:106;r:853;i:107;r:861;i:108;r:869;i:109;r:877;i:110;r:885;i:111;r:893;i:112;r:901;i:113;r:909;i:114;r:917;i:115;r:925;i:116;r:933;i:117;r:941;i:118;r:949;i:119;r:957;i:120;r:965;i:121;r:973;i:122;r:981;i:123;r:989;i:124;r:997;i:125;r:1005;i:126;r:1013;i:127;r:1021;i:128;r:1029;i:129;r:1037;i:130;r:1045;i:131;r:1053;i:132;r:1061;}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}}s:2:\"js\";O:37:\"Laravel\\Roster\\Ecosystems\\JsEcosystem\":3:{s:9:\"\0*\0byName\";a:192:{s:14:\"@popperjs/core\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:14:\"@popperjs/core\";s:10:\"\0*\0version\";s:6:\"2.11.8\";s:9:\"\0*\0source\";E:38:\"Laravel\\Roster\\Enums\\PackageSource:Npm\";s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:7:\"^2.11.8\";s:7:\"\0*\0path\";s:153:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@popperjs\\core\";}s:9:\"bootstrap\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:9:\"bootstrap\";s:10:\"\0*\0version\";s:5:\"5.3.8\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:6:\"^5.3.8\";s:7:\"\0*\0path\";s:148:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\bootstrap\";}s:15:\"bootstrap-icons\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:15:\"bootstrap-icons\";s:10:\"\0*\0version\";s:6:\"1.13.1\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:0;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:7:\"^1.13.1\";s:7:\"\0*\0path\";s:154:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\bootstrap-icons\";}s:16:\"@alloc/quick-lru\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:16:\"@alloc/quick-lru\";s:10:\"\0*\0version\";s:5:\"5.3.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:155:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@alloc\\quick-lru\";}s:18:\"@esbuild/aix-ppc64\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:18:\"@esbuild/aix-ppc64\";s:10:\"\0*\0version\";s:6:\"0.28.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:157:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@esbuild\\aix-ppc64\";}s:20:\"@esbuild/android-arm\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:20:\"@esbuild/android-arm\";s:10:\"\0*\0version\";s:6:\"0.28.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:159:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@esbuild\\android-arm\";}s:22:\"@esbuild/android-arm64\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:22:\"@esbuild/android-arm64\";s:10:\"\0*\0version\";s:6:\"0.28.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:161:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@esbuild\\android-arm64\";}s:20:\"@esbuild/android-x64\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:20:\"@esbuild/android-x64\";s:10:\"\0*\0version\";s:6:\"0.28.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:159:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@esbuild\\android-x64\";}s:21:\"@esbuild/darwin-arm64\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:21:\"@esbuild/darwin-arm64\";s:10:\"\0*\0version\";s:6:\"0.28.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:160:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@esbuild\\darwin-arm64\";}s:19:\"@esbuild/darwin-x64\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:19:\"@esbuild/darwin-x64\";s:10:\"\0*\0version\";s:6:\"0.28.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:158:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@esbuild\\darwin-x64\";}s:22:\"@esbuild/freebsd-arm64\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:22:\"@esbuild/freebsd-arm64\";s:10:\"\0*\0version\";s:6:\"0.28.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:161:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@esbuild\\freebsd-arm64\";}s:20:\"@esbuild/freebsd-x64\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:20:\"@esbuild/freebsd-x64\";s:10:\"\0*\0version\";s:6:\"0.28.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:159:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@esbuild\\freebsd-x64\";}s:18:\"@esbuild/linux-arm\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:18:\"@esbuild/linux-arm\";s:10:\"\0*\0version\";s:6:\"0.28.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:157:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@esbuild\\linux-arm\";}s:20:\"@esbuild/linux-arm64\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:20:\"@esbuild/linux-arm64\";s:10:\"\0*\0version\";s:6:\"0.28.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:159:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@esbuild\\linux-arm64\";}s:19:\"@esbuild/linux-ia32\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:19:\"@esbuild/linux-ia32\";s:10:\"\0*\0version\";s:6:\"0.28.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:158:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@esbuild\\linux-ia32\";}s:22:\"@esbuild/linux-loong64\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:22:\"@esbuild/linux-loong64\";s:10:\"\0*\0version\";s:6:\"0.28.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:161:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@esbuild\\linux-loong64\";}s:23:\"@esbuild/linux-mips64el\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:23:\"@esbuild/linux-mips64el\";s:10:\"\0*\0version\";s:6:\"0.28.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:162:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@esbuild\\linux-mips64el\";}s:20:\"@esbuild/linux-ppc64\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:20:\"@esbuild/linux-ppc64\";s:10:\"\0*\0version\";s:6:\"0.28.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:159:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@esbuild\\linux-ppc64\";}s:22:\"@esbuild/linux-riscv64\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:22:\"@esbuild/linux-riscv64\";s:10:\"\0*\0version\";s:6:\"0.28.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:161:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@esbuild\\linux-riscv64\";}s:20:\"@esbuild/linux-s390x\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:20:\"@esbuild/linux-s390x\";s:10:\"\0*\0version\";s:6:\"0.28.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:159:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@esbuild\\linux-s390x\";}s:18:\"@esbuild/linux-x64\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:18:\"@esbuild/linux-x64\";s:10:\"\0*\0version\";s:6:\"0.28.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:157:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@esbuild\\linux-x64\";}s:21:\"@esbuild/netbsd-arm64\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:21:\"@esbuild/netbsd-arm64\";s:10:\"\0*\0version\";s:6:\"0.28.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:160:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@esbuild\\netbsd-arm64\";}s:19:\"@esbuild/netbsd-x64\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:19:\"@esbuild/netbsd-x64\";s:10:\"\0*\0version\";s:6:\"0.28.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:158:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@esbuild\\netbsd-x64\";}s:22:\"@esbuild/openbsd-arm64\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:22:\"@esbuild/openbsd-arm64\";s:10:\"\0*\0version\";s:6:\"0.28.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:161:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@esbuild\\openbsd-arm64\";}s:20:\"@esbuild/openbsd-x64\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:20:\"@esbuild/openbsd-x64\";s:10:\"\0*\0version\";s:6:\"0.28.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:159:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@esbuild\\openbsd-x64\";}s:26:\"@esbuild/openharmony-arm64\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:26:\"@esbuild/openharmony-arm64\";s:10:\"\0*\0version\";s:6:\"0.28.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:165:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@esbuild\\openharmony-arm64\";}s:18:\"@esbuild/sunos-x64\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:18:\"@esbuild/sunos-x64\";s:10:\"\0*\0version\";s:6:\"0.28.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:157:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@esbuild\\sunos-x64\";}s:20:\"@esbuild/win32-arm64\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:20:\"@esbuild/win32-arm64\";s:10:\"\0*\0version\";s:6:\"0.28.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:159:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@esbuild\\win32-arm64\";}s:19:\"@esbuild/win32-ia32\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:19:\"@esbuild/win32-ia32\";s:10:\"\0*\0version\";s:6:\"0.28.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:158:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@esbuild\\win32-ia32\";}s:18:\"@esbuild/win32-x64\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:18:\"@esbuild/win32-x64\";s:10:\"\0*\0version\";s:6:\"0.28.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:157:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@esbuild\\win32-x64\";}s:23:\"@jridgewell/gen-mapping\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:23:\"@jridgewell/gen-mapping\";s:10:\"\0*\0version\";s:6:\"0.3.13\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:162:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@jridgewell\\gen-mapping\";}s:23:\"@jridgewell/resolve-uri\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:23:\"@jridgewell/resolve-uri\";s:10:\"\0*\0version\";s:5:\"3.1.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:162:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@jridgewell\\resolve-uri\";}s:27:\"@jridgewell/sourcemap-codec\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:27:\"@jridgewell/sourcemap-codec\";s:10:\"\0*\0version\";s:5:\"1.6.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:166:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@jridgewell\\sourcemap-codec\";}s:25:\"@jridgewell/trace-mapping\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:25:\"@jridgewell/trace-mapping\";s:10:\"\0*\0version\";s:6:\"0.3.31\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:164:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@jridgewell\\trace-mapping\";}s:27:\"@napi-rs/lzma-linux-x64-gnu\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:27:\"@napi-rs/lzma-linux-x64-gnu\";s:10:\"\0*\0version\";s:5:\"1.5.1\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:166:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@napi-rs\\lzma-linux-x64-gnu\";}s:19:\"@nodelib/fs.scandir\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:19:\"@nodelib/fs.scandir\";s:10:\"\0*\0version\";s:5:\"2.1.5\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:158:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@nodelib\\fs.scandir\";}s:16:\"@nodelib/fs.stat\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:16:\"@nodelib/fs.stat\";s:10:\"\0*\0version\";s:5:\"2.0.5\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:155:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@nodelib\\fs.stat\";}s:16:\"@nodelib/fs.walk\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:16:\"@nodelib/fs.walk\";s:10:\"\0*\0version\";s:5:\"1.2.8\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:155:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@nodelib\\fs.walk\";}s:31:\"@rollup/rollup-android-arm-eabi\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:31:\"@rollup/rollup-android-arm-eabi\";s:10:\"\0*\0version\";s:6:\"4.62.5\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:170:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@rollup\\rollup-android-arm-eabi\";}s:28:\"@rollup/rollup-android-arm64\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:28:\"@rollup/rollup-android-arm64\";s:10:\"\0*\0version\";s:6:\"4.62.5\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:167:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@rollup\\rollup-android-arm64\";}s:27:\"@rollup/rollup-darwin-arm64\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:27:\"@rollup/rollup-darwin-arm64\";s:10:\"\0*\0version\";s:6:\"4.62.5\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:166:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@rollup\\rollup-darwin-arm64\";}s:25:\"@rollup/rollup-darwin-x64\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:25:\"@rollup/rollup-darwin-x64\";s:10:\"\0*\0version\";s:6:\"4.62.5\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:164:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@rollup\\rollup-darwin-x64\";}s:28:\"@rollup/rollup-freebsd-arm64\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:28:\"@rollup/rollup-freebsd-arm64\";s:10:\"\0*\0version\";s:6:\"4.62.5\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:167:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@rollup\\rollup-freebsd-arm64\";}s:26:\"@rollup/rollup-freebsd-x64\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:26:\"@rollup/rollup-freebsd-x64\";s:10:\"\0*\0version\";s:6:\"4.62.5\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:165:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@rollup\\rollup-freebsd-x64\";}s:34:\"@rollup/rollup-linux-arm-gnueabihf\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:34:\"@rollup/rollup-linux-arm-gnueabihf\";s:10:\"\0*\0version\";s:6:\"4.62.5\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:173:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@rollup\\rollup-linux-arm-gnueabihf\";}s:35:\"@rollup/rollup-linux-arm-musleabihf\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:35:\"@rollup/rollup-linux-arm-musleabihf\";s:10:\"\0*\0version\";s:6:\"4.62.5\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:174:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@rollup\\rollup-linux-arm-musleabihf\";}s:30:\"@rollup/rollup-linux-arm64-gnu\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:30:\"@rollup/rollup-linux-arm64-gnu\";s:10:\"\0*\0version\";s:6:\"4.62.5\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:169:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@rollup\\rollup-linux-arm64-gnu\";}s:31:\"@rollup/rollup-linux-arm64-musl\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:31:\"@rollup/rollup-linux-arm64-musl\";s:10:\"\0*\0version\";s:6:\"4.62.5\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:170:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@rollup\\rollup-linux-arm64-musl\";}s:32:\"@rollup/rollup-linux-loong64-gnu\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:32:\"@rollup/rollup-linux-loong64-gnu\";s:10:\"\0*\0version\";s:6:\"4.62.5\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:171:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@rollup\\rollup-linux-loong64-gnu\";}s:33:\"@rollup/rollup-linux-loong64-musl\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:33:\"@rollup/rollup-linux-loong64-musl\";s:10:\"\0*\0version\";s:6:\"4.62.5\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:172:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@rollup\\rollup-linux-loong64-musl\";}s:30:\"@rollup/rollup-linux-ppc64-gnu\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:30:\"@rollup/rollup-linux-ppc64-gnu\";s:10:\"\0*\0version\";s:6:\"4.62.5\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:169:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@rollup\\rollup-linux-ppc64-gnu\";}s:31:\"@rollup/rollup-linux-ppc64-musl\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:31:\"@rollup/rollup-linux-ppc64-musl\";s:10:\"\0*\0version\";s:6:\"4.62.5\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:170:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@rollup\\rollup-linux-ppc64-musl\";}s:32:\"@rollup/rollup-linux-riscv64-gnu\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:32:\"@rollup/rollup-linux-riscv64-gnu\";s:10:\"\0*\0version\";s:6:\"4.62.5\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:171:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@rollup\\rollup-linux-riscv64-gnu\";}s:33:\"@rollup/rollup-linux-riscv64-musl\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:33:\"@rollup/rollup-linux-riscv64-musl\";s:10:\"\0*\0version\";s:6:\"4.62.5\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:172:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@rollup\\rollup-linux-riscv64-musl\";}s:30:\"@rollup/rollup-linux-s390x-gnu\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:30:\"@rollup/rollup-linux-s390x-gnu\";s:10:\"\0*\0version\";s:6:\"4.62.5\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:169:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@rollup\\rollup-linux-s390x-gnu\";}s:28:\"@rollup/rollup-linux-x64-gnu\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:28:\"@rollup/rollup-linux-x64-gnu\";s:10:\"\0*\0version\";s:6:\"4.62.5\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:167:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@rollup\\rollup-linux-x64-gnu\";}s:29:\"@rollup/rollup-linux-x64-musl\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:29:\"@rollup/rollup-linux-x64-musl\";s:10:\"\0*\0version\";s:6:\"4.62.5\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:168:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@rollup\\rollup-linux-x64-musl\";}s:26:\"@rollup/rollup-openbsd-x64\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:26:\"@rollup/rollup-openbsd-x64\";s:10:\"\0*\0version\";s:6:\"4.62.5\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:165:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@rollup\\rollup-openbsd-x64\";}s:32:\"@rollup/rollup-openharmony-arm64\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:32:\"@rollup/rollup-openharmony-arm64\";s:10:\"\0*\0version\";s:6:\"4.62.5\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:171:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@rollup\\rollup-openharmony-arm64\";}s:31:\"@rollup/rollup-win32-arm64-msvc\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:31:\"@rollup/rollup-win32-arm64-msvc\";s:10:\"\0*\0version\";s:6:\"4.62.5\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:170:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@rollup\\rollup-win32-arm64-msvc\";}s:30:\"@rollup/rollup-win32-ia32-msvc\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:30:\"@rollup/rollup-win32-ia32-msvc\";s:10:\"\0*\0version\";s:6:\"4.62.5\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:169:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@rollup\\rollup-win32-ia32-msvc\";}s:28:\"@rollup/rollup-win32-x64-gnu\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:28:\"@rollup/rollup-win32-x64-gnu\";s:10:\"\0*\0version\";s:6:\"4.62.5\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:167:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@rollup\\rollup-win32-x64-gnu\";}s:29:\"@rollup/rollup-win32-x64-msvc\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:29:\"@rollup/rollup-win32-x64-msvc\";s:10:\"\0*\0version\";s:6:\"4.62.5\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:168:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@rollup\\rollup-win32-x64-msvc\";}s:18:\"@tailwindcss/forms\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:18:\"@tailwindcss/forms\";s:10:\"\0*\0version\";s:6:\"0.5.11\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:6:\"^0.5.2\";s:7:\"\0*\0path\";s:157:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@tailwindcss\\forms\";}s:13:\"@types/estree\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:13:\"@types/estree\";s:10:\"\0*\0version\";s:5:\"1.0.9\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:152:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@types\\estree\";}s:15:\"@vue/reactivity\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:15:\"@vue/reactivity\";s:10:\"\0*\0version\";s:6:\"3.5.43\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:154:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@vue\\reactivity\";}s:11:\"@vue/shared\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:11:\"@vue/shared\";s:10:\"\0*\0version\";s:6:\"3.5.43\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:150:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\@vue\\shared\";}s:10:\"agent-base\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:10:\"agent-base\";s:10:\"\0*\0version\";s:5:\"6.0.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:149:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\agent-base\";}s:8:\"alpinejs\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:8:\"alpinejs\";s:10:\"\0*\0version\";s:6:\"3.17.4\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:6:\"^3.4.2\";s:7:\"\0*\0path\";s:147:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\alpinejs\";}s:10:\"ansi-regex\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:10:\"ansi-regex\";s:10:\"\0*\0version\";s:5:\"5.0.1\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:149:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\ansi-regex\";}s:11:\"ansi-styles\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:11:\"ansi-styles\";s:10:\"\0*\0version\";s:5:\"4.3.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:150:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\ansi-styles\";}s:11:\"any-promise\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:11:\"any-promise\";s:10:\"\0*\0version\";s:5:\"1.3.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:150:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\any-promise\";}s:8:\"anymatch\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:8:\"anymatch\";s:10:\"\0*\0version\";s:5:\"3.1.3\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:147:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\anymatch\";}s:3:\"arg\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:3:\"arg\";s:10:\"\0*\0version\";s:5:\"5.0.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:142:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\arg\";}s:8:\"asynckit\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:8:\"asynckit\";s:10:\"\0*\0version\";s:5:\"0.4.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:147:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\asynckit\";}s:12:\"autoprefixer\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:12:\"autoprefixer\";s:10:\"\0*\0version\";s:6:\"10.6.1\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:7:\"^10.4.2\";s:7:\"\0*\0path\";s:151:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\autoprefixer\";}s:5:\"axios\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:5:\"axios\";s:10:\"\0*\0version\";s:6:\"1.19.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:7:\"^1.11.0\";s:7:\"\0*\0path\";s:144:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\axios\";}s:24:\"baseline-browser-mapping\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:24:\"baseline-browser-mapping\";s:10:\"\0*\0version\";s:7:\"2.11.25\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:163:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\baseline-browser-mapping\";}s:17:\"binary-extensions\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:17:\"binary-extensions\";s:10:\"\0*\0version\";s:5:\"2.3.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:156:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\binary-extensions\";}s:6:\"braces\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:6:\"braces\";s:10:\"\0*\0version\";s:5:\"3.0.3\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:145:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\braces\";}s:12:\"browserslist\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:12:\"browserslist\";s:10:\"\0*\0version\";s:6:\"4.29.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:151:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\browserslist\";}s:23:\"call-bind-apply-helpers\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:23:\"call-bind-apply-helpers\";s:10:\"\0*\0version\";s:5:\"1.0.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:162:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\call-bind-apply-helpers\";}s:13:\"camelcase-css\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:13:\"camelcase-css\";s:10:\"\0*\0version\";s:5:\"2.0.1\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:152:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\camelcase-css\";}s:12:\"caniuse-lite\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:12:\"caniuse-lite\";s:10:\"\0*\0version\";s:12:\"1.0.30001810\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:151:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\caniuse-lite\";}s:5:\"chalk\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:5:\"chalk\";s:10:\"\0*\0version\";s:5:\"4.1.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:144:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\chalk\";}s:8:\"chokidar\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:8:\"chokidar\";s:10:\"\0*\0version\";s:5:\"3.6.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:147:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\chokidar\";}s:5:\"cliui\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:5:\"cliui\";s:10:\"\0*\0version\";s:5:\"8.0.1\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:144:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\cliui\";}s:13:\"color-convert\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:13:\"color-convert\";s:10:\"\0*\0version\";s:5:\"2.0.1\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:152:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\color-convert\";}s:10:\"color-name\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:10:\"color-name\";s:10:\"\0*\0version\";s:5:\"1.1.4\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:149:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\color-name\";}s:15:\"combined-stream\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:15:\"combined-stream\";s:10:\"\0*\0version\";s:5:\"1.0.8\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:154:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\combined-stream\";}s:9:\"commander\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:9:\"commander\";s:10:\"\0*\0version\";s:5:\"4.1.1\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:148:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\commander\";}s:12:\"concurrently\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:12:\"concurrently\";s:10:\"\0*\0version\";s:5:\"9.2.4\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:6:\"^9.0.1\";s:7:\"\0*\0path\";s:151:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\concurrently\";}s:6:\"cssesc\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:6:\"cssesc\";s:10:\"\0*\0version\";s:5:\"3.0.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:145:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\cssesc\";}s:5:\"debug\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:5:\"debug\";s:10:\"\0*\0version\";s:5:\"4.4.3\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:144:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\debug\";}s:14:\"delayed-stream\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:14:\"delayed-stream\";s:10:\"\0*\0version\";s:5:\"1.0.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:153:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\delayed-stream\";}s:10:\"didyoumean\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:10:\"didyoumean\";s:10:\"\0*\0version\";s:5:\"1.2.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:149:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\didyoumean\";}s:3:\"dlv\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:3:\"dlv\";s:10:\"\0*\0version\";s:5:\"1.1.3\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:142:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\dlv\";}s:12:\"dunder-proto\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:12:\"dunder-proto\";s:10:\"\0*\0version\";s:5:\"1.0.1\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:151:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\dunder-proto\";}s:20:\"electron-to-chromium\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:20:\"electron-to-chromium\";s:10:\"\0*\0version\";s:7:\"1.5.433\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:159:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\electron-to-chromium\";}s:11:\"emoji-regex\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:11:\"emoji-regex\";s:10:\"\0*\0version\";s:5:\"8.0.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:150:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\emoji-regex\";}s:18:\"es-define-property\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:18:\"es-define-property\";s:10:\"\0*\0version\";s:5:\"1.0.1\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:157:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\es-define-property\";}s:9:\"es-errors\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:9:\"es-errors\";s:10:\"\0*\0version\";s:5:\"1.3.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:148:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\es-errors\";}s:15:\"es-object-atoms\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:15:\"es-object-atoms\";s:10:\"\0*\0version\";s:5:\"1.1.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:154:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\es-object-atoms\";}s:18:\"es-set-tostringtag\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:18:\"es-set-tostringtag\";s:10:\"\0*\0version\";s:5:\"2.1.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:157:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\es-set-tostringtag\";}s:7:\"esbuild\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:7:\"esbuild\";s:10:\"\0*\0version\";s:6:\"0.28.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:146:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\esbuild\";}s:8:\"escalade\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:8:\"escalade\";s:10:\"\0*\0version\";s:5:\"3.2.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:147:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\escalade\";}s:9:\"fast-glob\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:9:\"fast-glob\";s:10:\"\0*\0version\";s:5:\"3.3.3\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:148:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\fast-glob\";}s:5:\"fastq\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:5:\"fastq\";s:10:\"\0*\0version\";s:6:\"1.20.3\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:144:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\fastq\";}s:4:\"fdir\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:4:\"fdir\";s:10:\"\0*\0version\";s:5:\"6.5.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:143:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\fdir\";}s:10:\"fill-range\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:10:\"fill-range\";s:10:\"\0*\0version\";s:5:\"7.1.1\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:149:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\fill-range\";}s:16:\"follow-redirects\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:16:\"follow-redirects\";s:10:\"\0*\0version\";s:6:\"1.16.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:155:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\follow-redirects\";}s:9:\"form-data\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:9:\"form-data\";s:10:\"\0*\0version\";s:5:\"4.0.6\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:148:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\form-data\";}s:11:\"fraction.js\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:11:\"fraction.js\";s:10:\"\0*\0version\";s:5:\"5.3.4\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:150:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\fraction.js\";}s:8:\"fsevents\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:8:\"fsevents\";s:10:\"\0*\0version\";s:5:\"2.3.3\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:147:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\fsevents\";}s:13:\"function-bind\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:13:\"function-bind\";s:10:\"\0*\0version\";s:5:\"1.1.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:152:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\function-bind\";}s:15:\"get-caller-file\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:15:\"get-caller-file\";s:10:\"\0*\0version\";s:5:\"2.0.5\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:154:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\get-caller-file\";}s:13:\"get-intrinsic\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:13:\"get-intrinsic\";s:10:\"\0*\0version\";s:5:\"1.3.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:152:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\get-intrinsic\";}s:9:\"get-proto\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:9:\"get-proto\";s:10:\"\0*\0version\";s:5:\"1.0.1\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:148:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\get-proto\";}s:11:\"glob-parent\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:11:\"glob-parent\";s:10:\"\0*\0version\";s:5:\"6.0.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:150:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\glob-parent\";}s:4:\"gopd\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:4:\"gopd\";s:10:\"\0*\0version\";s:5:\"1.2.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:143:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\gopd\";}s:8:\"has-flag\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:8:\"has-flag\";s:10:\"\0*\0version\";s:5:\"4.0.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:147:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\has-flag\";}s:11:\"has-symbols\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:11:\"has-symbols\";s:10:\"\0*\0version\";s:5:\"1.1.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:150:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\has-symbols\";}s:15:\"has-tostringtag\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:15:\"has-tostringtag\";s:10:\"\0*\0version\";s:5:\"1.0.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:154:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\has-tostringtag\";}s:6:\"hasown\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:6:\"hasown\";s:10:\"\0*\0version\";s:5:\"2.0.4\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:145:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\hasown\";}s:17:\"https-proxy-agent\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:17:\"https-proxy-agent\";s:10:\"\0*\0version\";s:5:\"5.0.1\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:156:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\https-proxy-agent\";}s:14:\"is-binary-path\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:14:\"is-binary-path\";s:10:\"\0*\0version\";s:5:\"2.1.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:153:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\is-binary-path\";}s:14:\"is-core-module\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:14:\"is-core-module\";s:10:\"\0*\0version\";s:6:\"2.17.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:153:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\is-core-module\";}s:10:\"is-extglob\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:10:\"is-extglob\";s:10:\"\0*\0version\";s:5:\"2.1.1\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:149:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\is-extglob\";}s:23:\"is-fullwidth-code-point\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:23:\"is-fullwidth-code-point\";s:10:\"\0*\0version\";s:5:\"3.0.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:162:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\is-fullwidth-code-point\";}s:7:\"is-glob\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:7:\"is-glob\";s:10:\"\0*\0version\";s:5:\"4.0.3\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:146:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\is-glob\";}s:9:\"is-number\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:9:\"is-number\";s:10:\"\0*\0version\";s:5:\"7.0.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:148:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\is-number\";}s:4:\"jiti\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:4:\"jiti\";s:10:\"\0*\0version\";s:6:\"1.21.7\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:143:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\jiti\";}s:19:\"laravel-vite-plugin\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:19:\"laravel-vite-plugin\";s:10:\"\0*\0version\";s:5:\"2.1.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:6:\"^2.0.0\";s:7:\"\0*\0path\";s:158:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\laravel-vite-plugin\";}s:9:\"lilconfig\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:9:\"lilconfig\";s:10:\"\0*\0version\";s:5:\"3.1.3\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:148:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\lilconfig\";}s:17:\"lines-and-columns\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:17:\"lines-and-columns\";s:10:\"\0*\0version\";s:5:\"1.2.4\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:156:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\lines-and-columns\";}s:15:\"math-intrinsics\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:15:\"math-intrinsics\";s:10:\"\0*\0version\";s:5:\"1.1.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:154:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\math-intrinsics\";}s:6:\"merge2\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:6:\"merge2\";s:10:\"\0*\0version\";s:5:\"1.4.1\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:145:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\merge2\";}s:10:\"micromatch\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:10:\"micromatch\";s:10:\"\0*\0version\";s:5:\"4.0.8\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:149:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\micromatch\";}s:7:\"mime-db\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:7:\"mime-db\";s:10:\"\0*\0version\";s:6:\"1.52.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:146:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\mime-db\";}s:10:\"mime-types\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:10:\"mime-types\";s:10:\"\0*\0version\";s:6:\"2.1.35\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:149:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\mime-types\";}s:17:\"mini-svg-data-uri\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:17:\"mini-svg-data-uri\";s:10:\"\0*\0version\";s:5:\"1.4.4\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:156:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\mini-svg-data-uri\";}s:2:\"ms\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:2:\"ms\";s:10:\"\0*\0version\";s:5:\"2.1.3\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:141:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\ms\";}s:2:\"mz\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:2:\"mz\";s:10:\"\0*\0version\";s:5:\"2.7.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:141:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\mz\";}s:6:\"nanoid\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:6:\"nanoid\";s:10:\"\0*\0version\";s:6:\"3.3.18\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:145:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\nanoid\";}s:13:\"node-releases\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:13:\"node-releases\";s:10:\"\0*\0version\";s:6:\"2.0.56\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:152:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\node-releases\";}s:14:\"normalize-path\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:14:\"normalize-path\";s:10:\"\0*\0version\";s:5:\"3.0.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:153:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\normalize-path\";}s:13:\"object-assign\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:13:\"object-assign\";s:10:\"\0*\0version\";s:5:\"4.1.1\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:152:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\object-assign\";}s:11:\"object-hash\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:11:\"object-hash\";s:10:\"\0*\0version\";s:5:\"3.0.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:150:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\object-hash\";}s:10:\"path-parse\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:10:\"path-parse\";s:10:\"\0*\0version\";s:5:\"1.0.7\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:149:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\path-parse\";}s:10:\"picocolors\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:10:\"picocolors\";s:10:\"\0*\0version\";s:5:\"1.1.1\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:149:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\picocolors\";}s:9:\"picomatch\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:9:\"picomatch\";s:10:\"\0*\0version\";s:5:\"4.0.7\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:148:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\picomatch\";}s:7:\"pirates\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:7:\"pirates\";s:10:\"\0*\0version\";s:5:\"4.0.7\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:146:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\pirates\";}s:7:\"postcss\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:7:\"postcss\";s:10:\"\0*\0version\";s:6:\"8.5.26\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:7:\"^8.4.31\";s:7:\"\0*\0path\";s:146:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\postcss\";}s:14:\"postcss-import\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:14:\"postcss-import\";s:10:\"\0*\0version\";s:6:\"15.1.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:153:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\postcss-import\";}s:10:\"postcss-js\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:10:\"postcss-js\";s:10:\"\0*\0version\";s:5:\"4.1.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:149:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\postcss-js\";}s:19:\"postcss-load-config\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:19:\"postcss-load-config\";s:10:\"\0*\0version\";s:5:\"6.0.1\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:158:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\postcss-load-config\";}s:14:\"postcss-nested\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:14:\"postcss-nested\";s:10:\"\0*\0version\";s:5:\"6.2.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:153:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\postcss-nested\";}s:23:\"postcss-selector-parser\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:23:\"postcss-selector-parser\";s:10:\"\0*\0version\";s:5:\"6.1.4\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:162:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\postcss-selector-parser\";}s:20:\"postcss-value-parser\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:20:\"postcss-value-parser\";s:10:\"\0*\0version\";s:5:\"4.2.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:159:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\postcss-value-parser\";}s:14:\"proxy-from-env\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:14:\"proxy-from-env\";s:10:\"\0*\0version\";s:5:\"2.1.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:153:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\proxy-from-env\";}s:15:\"queue-microtask\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:15:\"queue-microtask\";s:10:\"\0*\0version\";s:5:\"1.2.3\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:154:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\queue-microtask\";}s:10:\"read-cache\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:10:\"read-cache\";s:10:\"\0*\0version\";s:5:\"1.0.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:149:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\read-cache\";}s:8:\"readdirp\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:8:\"readdirp\";s:10:\"\0*\0version\";s:5:\"3.6.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:147:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\readdirp\";}s:17:\"require-directory\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:17:\"require-directory\";s:10:\"\0*\0version\";s:5:\"2.1.1\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:156:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\require-directory\";}s:7:\"resolve\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:7:\"resolve\";s:10:\"\0*\0version\";s:7:\"1.22.12\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:146:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\resolve\";}s:7:\"reusify\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:7:\"reusify\";s:10:\"\0*\0version\";s:5:\"1.1.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:146:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\reusify\";}s:6:\"rollup\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:6:\"rollup\";s:10:\"\0*\0version\";s:6:\"4.62.5\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:145:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\rollup\";}s:12:\"run-parallel\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:12:\"run-parallel\";s:10:\"\0*\0version\";s:5:\"1.2.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:151:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\run-parallel\";}s:4:\"rxjs\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:4:\"rxjs\";s:10:\"\0*\0version\";s:5:\"7.8.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:143:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\rxjs\";}s:11:\"shell-quote\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:11:\"shell-quote\";s:10:\"\0*\0version\";s:5:\"1.9.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:150:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\shell-quote\";}s:13:\"source-map-js\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:13:\"source-map-js\";s:10:\"\0*\0version\";s:5:\"1.2.1\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:152:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\source-map-js\";}s:12:\"string-width\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:12:\"string-width\";s:10:\"\0*\0version\";s:5:\"4.2.3\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:151:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\string-width\";}s:10:\"strip-ansi\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:10:\"strip-ansi\";s:10:\"\0*\0version\";s:5:\"6.0.1\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:149:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\strip-ansi\";}s:7:\"sucrase\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:7:\"sucrase\";s:10:\"\0*\0version\";s:6:\"3.35.1\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:146:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\sucrase\";}s:14:\"supports-color\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:14:\"supports-color\";s:10:\"\0*\0version\";s:5:\"8.1.1\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:153:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\supports-color\";}s:31:\"supports-preserve-symlinks-flag\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:31:\"supports-preserve-symlinks-flag\";s:10:\"\0*\0version\";s:5:\"1.0.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:170:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\supports-preserve-symlinks-flag\";}s:11:\"tailwindcss\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:11:\"tailwindcss\";s:10:\"\0*\0version\";s:6:\"3.4.19\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:6:\"^3.1.0\";s:7:\"\0*\0path\";s:150:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\tailwindcss\";}s:7:\"thenify\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:7:\"thenify\";s:10:\"\0*\0version\";s:5:\"3.3.1\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:146:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\thenify\";}s:11:\"thenify-all\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:11:\"thenify-all\";s:10:\"\0*\0version\";s:5:\"1.6.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:150:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\thenify-all\";}s:10:\"tinyglobby\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:10:\"tinyglobby\";s:10:\"\0*\0version\";s:6:\"0.2.17\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:149:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\tinyglobby\";}s:14:\"to-regex-range\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:14:\"to-regex-range\";s:10:\"\0*\0version\";s:5:\"5.0.1\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:153:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\to-regex-range\";}s:9:\"tree-kill\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:9:\"tree-kill\";s:10:\"\0*\0version\";s:5:\"1.2.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:148:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\tree-kill\";}s:20:\"ts-interface-checker\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:20:\"ts-interface-checker\";s:10:\"\0*\0version\";s:6:\"0.1.13\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:159:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\ts-interface-checker\";}s:5:\"tslib\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:5:\"tslib\";s:10:\"\0*\0version\";s:5:\"2.8.1\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:144:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\tslib\";}s:22:\"update-browserslist-db\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:22:\"update-browserslist-db\";s:10:\"\0*\0version\";s:5:\"1.3.3\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:161:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\update-browserslist-db\";}s:14:\"util-deprecate\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:14:\"util-deprecate\";s:10:\"\0*\0version\";s:5:\"1.0.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:153:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\util-deprecate\";}s:4:\"vite\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:4:\"vite\";s:10:\"\0*\0version\";s:5:\"7.3.6\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:1;s:13:\"\0*\0constraint\";s:6:\"^7.0.7\";s:7:\"\0*\0path\";s:143:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\vite\";}s:23:\"vite-plugin-full-reload\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:23:\"vite-plugin-full-reload\";s:10:\"\0*\0version\";s:5:\"1.2.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:162:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\vite-plugin-full-reload\";}s:9:\"wrap-ansi\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:9:\"wrap-ansi\";s:10:\"\0*\0version\";s:5:\"7.0.0\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:148:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\wrap-ansi\";}s:4:\"y18n\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:4:\"y18n\";s:10:\"\0*\0version\";s:5:\"5.0.8\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:143:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\y18n\";}s:5:\"yargs\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:5:\"yargs\";s:10:\"\0*\0version\";s:6:\"17.7.2\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:144:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\yargs\";}s:12:\"yargs-parser\";O:22:\"Laravel\\Roster\\Package\":7:{s:7:\"\0*\0name\";s:12:\"yargs-parser\";s:10:\"\0*\0version\";s:6:\"21.1.1\";s:9:\"\0*\0source\";r:1210;s:6:\"\0*\0dev\";b:1;s:9:\"\0*\0direct\";b:0;s:13:\"\0*\0constraint\";s:0:\"\";s:7:\"\0*\0path\";s:151:\"D:\\Documents\\UNPA\\Sistemas 2026\\Laboratorio de Desarrollo de Aplicaciones\\powertech-backend-laravel\\powertech-backend-laravel\\node_modules\\yargs-parser\";}}s:11:\"\0*\0packages\";O:32:\"Laravel\\Roster\\PackageCollection\":2:{s:8:\"\0*\0items\";a:192:{i:0;r:1207;i:1;r:1215;i:2;r:1223;i:3;r:1231;i:4;r:1239;i:5;r:1247;i:6;r:1255;i:7;r:1263;i:8;r:1271;i:9;r:1279;i:10;r:1287;i:11;r:1295;i:12;r:1303;i:13;r:1311;i:14;r:1319;i:15;r:1327;i:16;r:1335;i:17;r:1343;i:18;r:1351;i:19;r:1359;i:20;r:1367;i:21;r:1375;i:22;r:1383;i:23;r:1391;i:24;r:1399;i:25;r:1407;i:26;r:1415;i:27;r:1423;i:28;r:1431;i:29;r:1439;i:30;r:1447;i:31;r:1455;i:32;r:1463;i:33;r:1471;i:34;r:1479;i:35;r:1487;i:36;r:1495;i:37;r:1503;i:38;r:1511;i:39;r:1519;i:40;r:1527;i:41;r:1535;i:42;r:1543;i:43;r:1551;i:44;r:1559;i:45;r:1567;i:46;r:1575;i:47;r:1583;i:48;r:1591;i:49;r:1599;i:50;r:1607;i:51;r:1615;i:52;r:1623;i:53;r:1631;i:54;r:1639;i:55;r:1647;i:56;r:1655;i:57;r:1663;i:58;r:1671;i:59;r:1679;i:60;r:1687;i:61;r:1695;i:62;r:1703;i:63;r:1711;i:64;r:1719;i:65;r:1727;i:66;r:1735;i:67;r:1743;i:68;r:1751;i:69;r:1759;i:70;r:1767;i:71;r:1775;i:72;r:1783;i:73;r:1791;i:74;r:1799;i:75;r:1807;i:76;r:1815;i:77;r:1823;i:78;r:1831;i:79;r:1839;i:80;r:1847;i:81;r:1855;i:82;r:1863;i:83;r:1871;i:84;r:1879;i:85;r:1887;i:86;r:1895;i:87;r:1903;i:88;r:1911;i:89;r:1919;i:90;r:1927;i:91;r:1935;i:92;r:1943;i:93;r:1951;i:94;r:1959;i:95;r:1967;i:96;r:1975;i:97;r:1983;i:98;r:1991;i:99;r:1999;i:100;r:2007;i:101;r:2015;i:102;r:2023;i:103;r:2031;i:104;r:2039;i:105;r:2047;i:106;r:2055;i:107;r:2063;i:108;r:2071;i:109;r:2079;i:110;r:2087;i:111;r:2095;i:112;r:2103;i:113;r:2111;i:114;r:2119;i:115;r:2127;i:116;r:2135;i:117;r:2143;i:118;r:2151;i:119;r:2159;i:120;r:2167;i:121;r:2175;i:122;r:2183;i:123;r:2191;i:124;r:2199;i:125;r:2207;i:126;r:2215;i:127;r:2223;i:128;r:2231;i:129;r:2239;i:130;r:2247;i:131;r:2255;i:132;r:2263;i:133;r:2271;i:134;r:2279;i:135;r:2287;i:136;r:2295;i:137;r:2303;i:138;r:2311;i:139;r:2319;i:140;r:2327;i:141;r:2335;i:142;r:2343;i:143;r:2351;i:144;r:2359;i:145;r:2367;i:146;r:2375;i:147;r:2383;i:148;r:2391;i:149;r:2399;i:150;r:2407;i:151;r:2415;i:152;r:2423;i:153;r:2431;i:154;r:2439;i:155;r:2447;i:156;r:2455;i:157;r:2463;i:158;r:2471;i:159;r:2479;i:160;r:2487;i:161;r:2495;i:162;r:2503;i:163;r:2511;i:164;r:2519;i:165;r:2527;i:166;r:2535;i:167;r:2543;i:168;r:2551;i:169;r:2559;i:170;r:2567;i:171;r:2575;i:172;r:2583;i:173;r:2591;i:174;r:2599;i:175;r:2607;i:176;r:2615;i:177;r:2623;i:178;r:2631;i:179;r:2639;i:180;r:2647;i:181;r:2655;i:182;r:2663;i:183;r:2671;i:184;r:2679;i:185;r:2687;i:186;r:2695;i:187;r:2703;i:188;r:2711;i:189;r:2719;i:190;r:2727;i:191;r:2735;}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}s:17:\"\0*\0packageManager\";E:41:\"Laravel\\Roster\\Enums\\JsPackageManager:Npm\";}s:6:\"stacks\";O:30:\"Laravel\\Roster\\Support\\EnumSet\":1:{s:8:\"\0*\0cases\";a:1:{i:0;E:30:\"Laravel\\Roster\\Enums\\Stack:Api\";}}s:21:\"browserTestFrameworks\";O:30:\"Laravel\\Roster\\Support\\EnumSet\":1:{s:8:\"\0*\0cases\";a:0:{}}s:9:\"frontends\";O:30:\"Laravel\\Roster\\Support\\EnumSet\":1:{s:8:\"\0*\0cases\";a:0:{}}s:6:\"agents\";O:30:\"Laravel\\Roster\\Support\\EnumSet\":1:{s:8:\"\0*\0cases\";a:1:{i:0;E:37:\"Laravel\\Roster\\Enums\\Agent:ClaudeCode\";}}s:7:\"editors\";O:30:\"Laravel\\Roster\\Support\\EnumSet\":1:{s:8:\"\0*\0cases\";a:0:{}}}', 1793495038);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carritos`
--

CREATE TABLE `carritos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `session_token` varchar(64) DEFAULT NULL,
  `estado` enum('activo','en_checkout','convertido','abandonado') NOT NULL DEFAULT 'activo',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `carritos`
--

INSERT INTO `carritos` (`id`, `user_id`, `session_token`, `estado`, `created_at`, `updated_at`) VALUES
(9, 9, 'y5CYVjKCTEHcPAC5RB5NsnD2ekUWzsopvmUUv6QI2cOEbIW3ngOQzNsjjspkAGXQ', 'convertido', '2026-10-31 05:59:40', '2026-10-31 06:00:12'),
(10, 9, 'YSZLsZZdjPveu1SQrJVZjmaLLXmICFO7XmLFY8yDm1UdWj4yfLR4qDauOrj7pjDx', 'activo', '2026-10-31 08:31:13', '2026-10-31 08:31:13');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carrito_lineas`
--

CREATE TABLE `carrito_lineas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `carrito_id` bigint(20) UNSIGNED NOT NULL,
  `producto_id` bigint(20) UNSIGNED NOT NULL,
  `cantidad` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `carrito_lineas`
--

INSERT INTO `carrito_lineas` (`id`, `carrito_id`, `producto_id`, `cantidad`, `created_at`, `updated_at`) VALUES
(11, 9, 1, 1, '2026-10-31 05:59:50', '2026-10-31 05:59:50');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `orden` int(11) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`, `slug`, `orden`, `activo`, `created_at`, `updated_at`) VALUES
(1, 'Almacenamiento', 'almacenamiento', 0, 1, '2026-09-23 08:33:17', '2026-09-23 19:16:42'),
(2, 'Procesador', 'procesador', 0, 1, '2026-09-23 08:33:22', '2026-09-23 08:33:22'),
(3, 'Placa de video', 'placa-de-video', 0, 1, '2026-09-23 08:33:26', '2026-09-23 08:33:26'),
(4, 'RAM', 'ram', 0, 1, '2026-09-23 08:33:33', '2026-09-23 08:33:33'),
(5, 'Fuente de alimentación', 'fuente-de-alimentacion', 0, 1, '2026-09-23 08:33:40', '2026-09-23 08:33:40'),
(6, 'Gabinete', 'gabinete', 0, 1, '2026-09-23 08:33:43', '2026-09-23 09:13:45'),
(7, 'Mouse', 'mouse', 0, 1, '2026-09-23 09:44:10', '2026-09-23 09:44:10'),
(8, 'Teclado', 'teclado', 0, 1, '2026-09-23 09:44:14', '2026-09-23 09:44:14'),
(9, 'Mousepad', 'mousepad', 0, 1, '2026-09-23 09:44:19', '2026-09-23 09:44:19'),
(10, 'Refrigeración', 'refrigeracion', 0, 1, '2026-09-23 09:44:27', '2026-09-24 08:26:50'),
(11, 'Cable', 'cable', 0, 1, '2026-09-23 09:44:37', '2026-09-23 09:44:37'),
(12, 'Parlante', 'parlante', 0, 1, '2026-09-24 11:46:57', '2026-09-24 11:46:57'),
(13, 'Monitor', 'monitor', 0, 1, '2026-09-25 06:14:59', '2026-09-25 06:14:59'),
(14, 'PC Pre-armada', 'pc-pre-armada', 0, 1, '2026-09-25 06:16:29', '2026-09-25 06:16:29'),
(15, 'Notebook', 'notebook', 0, 1, '2026-09-25 06:31:43', '2026-09-25 06:31:43');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `razon_social` varchar(150) NOT NULL,
  `tipo_documento_id` bigint(20) UNSIGNED NOT NULL,
  `nro_doc` varchar(20) NOT NULL,
  `condicion_iva_id` bigint(20) UNSIGNED NOT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id`, `user_id`, `razon_social`, `tipo_documento_id`, `nro_doc`, `condicion_iva_id`, `telefono`, `activo`, `created_at`, `updated_at`) VALUES
(2, 9, 'Fontanet Patricio Rogelio', 3, '14514514', 3, '2974121415', 1, '2026-09-23 06:19:47', '2026-09-23 06:19:47'),
(3, 16, 'Simpson Homero', 3, '12312332', 3, NULL, 1, '2026-09-25 20:42:03', '2026-09-25 20:42:03'),
(4, 17, 'Simpson Bart', 3, '12345676', 3, NULL, 1, '2026-09-25 20:46:30', '2026-09-25 20:46:30'),
(8, 20, 'Palito Ortega SRL', 1, '23100000059', 1, '2974128821', 1, '2026-10-16 03:39:21', '2026-10-16 03:39:21'),
(9, 21, 'Pappo\'s Blues SRL', 1, '24100000012', 4, '2975128821', 1, '2026-10-16 03:43:56', '2026-10-16 03:43:56'),
(10, 22, 'Martínez Javier', 3, '44144744', 3, '2975129921', 1, '2026-10-16 03:47:42', '2026-10-16 03:47:42'),
(11, 23, 'Pertusi Horacio Demián', 3, '61159951', 3, '2976159951', 1, '2026-10-16 04:03:32', '2026-10-16 04:03:32'),
(12, 24, 'Martínez Andrés Ciro', 3, '44453354', 3, '297453354', 1, '2026-10-16 04:49:43', '2026-10-16 04:49:43');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comprobantes`
--

CREATE TABLE `comprobantes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `venta_id` bigint(20) UNSIGNED NOT NULL,
  `tipo_comprobante_id` bigint(20) UNSIGNED NOT NULL,
  `punto_venta_id` bigint(20) UNSIGNED NOT NULL,
  `numero` int(11) NOT NULL,
  `comprobante_asociado_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cliente_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tipo_documento_id` bigint(20) UNSIGNED NOT NULL,
  `nro_doc` varchar(20) NOT NULL,
  `condicion_iva_id` bigint(20) UNSIGNED NOT NULL,
  `fecha_emision` datetime NOT NULL,
  `neto` decimal(12,2) NOT NULL,
  `iva` decimal(12,2) NOT NULL,
  `total` decimal(12,2) NOT NULL,
  `cae` varchar(14) DEFAULT NULL,
  `cae_vencimiento` date DEFAULT NULL,
  `estado` enum('pendiente','autorizado','rechazado') NOT NULL DEFAULT 'pendiente',
  `motivo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `comprobantes`
--

INSERT INTO `comprobantes` (`id`, `venta_id`, `tipo_comprobante_id`, `punto_venta_id`, `numero`, `comprobante_asociado_id`, `cliente_id`, `tipo_documento_id`, `nro_doc`, `condicion_iva_id`, `fecha_emision`, `neto`, `iva`, `total`, `cae`, `cae_vencimiento`, `estado`, `motivo`, `created_at`, `updated_at`) VALUES
(2, 5, 4, 1, 1, NULL, 2, 3, '14514514', 3, '2026-09-26 23:59:04', 24793.39, 5206.61, 30000.00, '95206081829857', '2026-10-06', 'autorizado', NULL, '2026-09-27 02:59:04', '2026-09-27 02:59:04'),
(3, 4, 4, 1, 2, NULL, 2, 3, '14514514', 3, '2026-09-27 00:00:25', 375000.00, 0.00, 375000.00, '12132322395628', '2026-10-07', 'autorizado', NULL, '2026-09-27 03:00:25', '2026-09-27 03:00:25'),
(6, 7, 4, 1, 3, NULL, 2, 3, '14514514', 3, '2026-09-27 01:38:11', 35433.19, 9566.96, 45000.15, '66007347466755', '2026-10-07', 'autorizado', NULL, '2026-09-27 04:38:11', '2026-09-27 04:38:11'),
(8, 8, 4, 1, 4, NULL, 2, 3, '14514514', 3, '2026-09-27 01:40:17', 1158802.48, 243348.52, 1402151.00, '79408384791666', '2026-10-07', 'autorizado', NULL, '2026-09-27 04:40:17', '2026-09-27 04:40:17'),
(11, 14, 4, 1, 5, NULL, 2, 3, '14514514', 3, '2026-10-01 01:34:53', 50000.00, 0.00, 50000.00, '97028902203233', '2026-10-11', 'autorizado', NULL, '2026-10-01 04:34:53', '2026-10-01 04:34:53'),
(13, 15, 4, 1, 6, NULL, 2, 3, '14514514', 3, '2026-10-05 06:14:29', 15000.00, 0.00, 15000.00, '56127616389477', '2026-10-15', 'autorizado', NULL, '2026-10-05 09:14:29', '2026-10-05 09:14:29'),
(14, 18, 4, 1, 7, NULL, 2, 3, '14514514', 3, '2026-10-07 00:19:31', 100000.00, 0.00, 100000.00, '50301864547749', '2026-10-17', 'autorizado', NULL, '2026-10-07 03:19:31', '2026-10-07 03:19:31'),
(15, 18, 6, 1, 1, 14, 2, 3, '14514514', 3, '2026-10-07 00:20:26', 100000.00, 0.00, 100000.00, '38783921757477', '2026-10-17', 'autorizado', 'falla en el producto', '2026-10-07 03:20:26', '2026-10-07 03:20:26'),
(17, 9, 4, 1, 8, NULL, 2, 3, '14514514', 3, '2026-10-07 23:11:02', 100000.00, 0.00, 100000.00, '61164754009699', '2026-10-17', 'autorizado', NULL, '2026-10-08 02:11:02', '2026-10-08 02:11:02'),
(18, 17, 4, 1, 9, NULL, 2, 3, '14514514', 3, '2026-10-09 00:55:13', 50000.00, 0.00, 50000.00, '10652916595972', '2026-10-19', 'autorizado', NULL, '2026-10-09 03:55:13', '2026-10-09 03:55:13'),
(19, 20, 4, 1, 10, NULL, 2, 3, '14514514', 3, '2026-10-09 00:58:36', 100000.00, 0.00, 100000.00, '51640345405104', '2026-10-19', 'autorizado', NULL, '2026-10-09 03:58:36', '2026-10-09 03:58:36'),
(20, 20, 6, 1, 2, 19, 2, 3, '14514514', 3, '2026-10-09 00:59:14', 100000.00, 0.00, 100000.00, '44016391815820', '2026-10-19', 'autorizado', 'no funciona', '2026-10-09 03:59:14', '2026-10-09 03:59:14'),
(22, 21, 4, 1, 11, NULL, 2, 3, '14514514', 3, '2026-10-09 17:52:16', 100000.00, 0.00, 100000.00, '22571413256626', '2026-10-19', 'autorizado', NULL, '2026-10-09 20:52:16', '2026-10-09 20:52:16'),
(23, 25, 4, 1, 12, NULL, 2, 3, '14514514', 3, '2026-10-09 20:11:07', 241732.76, 38267.84, 280000.60, '96766508865596', '2026-10-19', 'autorizado', NULL, '2026-10-09 23:11:07', '2026-10-09 23:11:07'),
(25, 27, 4, 1, 13, NULL, 2, 3, '14514514', 3, '2026-10-29 22:58:50', 37190.08, 7809.92, 45000.00, '17445458964792', '2026-11-08', 'autorizado', NULL, '2026-10-30 01:58:50', '2026-10-30 01:58:50'),
(26, 28, 4, 1, 14, NULL, 2, 3, '14514514', 3, '2026-10-29 23:20:28', 7438016.53, 1561983.47, 9000000.00, '24621521097866', '2026-11-08', 'autorizado', NULL, '2026-10-30 02:20:28', '2026-10-30 02:20:28'),
(27, 29, 4, 1, 15, NULL, 2, 3, '14514514', 3, '2026-10-31 03:00:12', 42500.00, 0.00, 42500.00, '42146493756710', '2026-11-10', 'autorizado', NULL, '2026-10-31 06:00:12', '2026-10-31 06:00:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comprobante_lineas`
--

CREATE TABLE `comprobante_lineas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `comprobante_id` bigint(20) UNSIGNED NOT NULL,
  `producto_id` bigint(20) UNSIGNED NOT NULL,
  `descripcion` varchar(150) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(12,2) NOT NULL,
  `alicuota_iva` decimal(4,2) NOT NULL,
  `neto` decimal(12,2) NOT NULL,
  `iva` decimal(12,2) NOT NULL,
  `total` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `comprobante_lineas`
--

INSERT INTO `comprobante_lineas` (`id`, `comprobante_id`, `producto_id`, `descripcion`, `cantidad`, `precio_unitario`, `alicuota_iva`, `neto`, `iva`, `total`, `created_at`, `updated_at`) VALUES
(2, 2, 11, 'Notebook HP 4 RAM 1 TB Intel Celeron 14″ W10', 2, 15000.00, 21.00, 24793.39, 5206.61, 30000.00, '2026-09-27 02:59:04', '2026-09-27 02:59:04'),
(3, 3, 3, 'Ryzen 5 5400G', 5, 75000.00, 0.00, 375000.00, 0.00, 375000.00, '2026-09-27 03:00:25', '2026-09-27 03:00:25'),
(6, 6, 7, 'RAM 8GB', 1, 45000.15, 27.00, 35433.19, 9566.96, 45000.15, '2026-09-27 04:38:11', '2026-09-27 04:38:11'),
(8, 8, 9, 'M280', 1, 1402151.00, 21.00, 1158802.48, 243348.52, 1402151.00, '2026-09-27 04:40:17', '2026-09-27 04:40:17'),
(11, 11, 8, 'RM850e Full Modular Cybenetics Gold', 1, 50000.00, 0.00, 50000.00, 0.00, 50000.00, '2026-10-01 04:34:53', '2026-10-01 04:34:53'),
(13, 13, 4, 'G203 RGB LightSync', 1, 30000.00, 0.00, 15000.00, 0.00, 15000.00, '2026-10-05 09:14:29', '2026-10-05 09:14:29'),
(14, 14, 8, 'RM850e Full Modular Cybenetics Gold', 2, 50000.00, 0.00, 100000.00, 0.00, 100000.00, '2026-10-07 03:19:31', '2026-10-07 03:19:31'),
(15, 15, 8, 'RM850e Full Modular Cybenetics Gold', 2, 50000.00, 0.00, 100000.00, 0.00, 100000.00, '2026-10-07 03:20:26', '2026-10-07 03:20:26'),
(17, 17, 8, 'RM850e Full Modular Cybenetics Gold', 2, 50000.00, 0.00, 100000.00, 0.00, 100000.00, '2026-10-08 02:11:02', '2026-10-08 02:11:02'),
(18, 18, 8, 'RM850e Full Modular Cybenetics Gold', 1, 50000.00, 0.00, 50000.00, 0.00, 50000.00, '2026-10-09 03:55:13', '2026-10-09 03:55:13'),
(19, 19, 2, 'Nvidia RTX 5060 8GB', 1, 150000.00, 0.00, 100000.00, 0.00, 100000.00, '2026-10-09 03:58:36', '2026-10-09 03:58:36'),
(20, 20, 2, 'Nvidia RTX 5060 8GB', 1, 150000.00, 0.00, 100000.00, 0.00, 100000.00, '2026-10-09 03:59:14', '2026-10-09 03:59:14'),
(22, 22, 2, 'Nvidia RTX 5060 8GB', 1, 150000.00, 0.00, 100000.00, 0.00, 100000.00, '2026-10-09 20:52:16', '2026-10-09 20:52:16'),
(23, 23, 7, 'RAM 8GB', 4, 45000.15, 27.00, 141732.76, 38267.84, 180000.60, '2026-10-09 23:11:07', '2026-10-09 23:11:07'),
(24, 23, 2, 'Nvidia RTX 5060 8GB', 1, 150000.00, 0.00, 100000.00, 0.00, 100000.00, '2026-10-09 23:11:07', '2026-10-09 23:11:07'),
(26, 25, 11, 'Notebook HP 4 RAM 1 TB Intel Celeron 14″ W10', 3, 15000.00, 21.00, 37190.08, 7809.92, 45000.00, '2026-10-30 01:58:50', '2026-10-30 01:58:50'),
(27, 26, 10, 'PC completa', 6, 1500000.00, 21.00, 7438016.53, 1561983.47, 9000000.00, '2026-10-30 02:20:28', '2026-10-30 02:20:28'),
(28, 27, 1, 'Ryzen 5 5600X', 1, 50000.00, 0.00, 42500.00, 0.00, 42500.00, '2026-10-31 06:00:12', '2026-10-31 06:00:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `condiciones_iva`
--

CREATE TABLE `condiciones_iva` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `codigo_afip` smallint(6) NOT NULL,
  `nombre` varchar(60) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `condiciones_iva`
--

INSERT INTO `condiciones_iva` (`id`, `codigo_afip`, `nombre`, `created_at`, `updated_at`) VALUES
(1, 1, 'IVA Responsable Inscripto', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(2, 4, 'IVA Sujeto Exento', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(3, 5, 'Consumidor Final', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(4, 6, 'Responsable Monotributo', '2026-09-22 03:05:45', '2026-09-22 03:05:45');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `direcciones`
--

CREATE TABLE `direcciones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `direccionable_type` varchar(100) NOT NULL,
  `direccionable_id` bigint(20) UNSIGNED NOT NULL,
  `tipo` enum('fiscal','envio','particular') NOT NULL,
  `calle` varchar(120) NOT NULL,
  `numero` varchar(10) DEFAULT NULL,
  `piso_depto` varchar(20) DEFAULT NULL,
  `codigo_postal` varchar(10) NOT NULL,
  `localidad` varchar(80) NOT NULL,
  `provincia` varchar(60) NOT NULL,
  `es_predeterminada` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `direcciones`
--

INSERT INTO `direcciones` (`id`, `direccionable_type`, `direccionable_id`, `tipo`, `calle`, `numero`, `piso_depto`, `codigo_postal`, `localidad`, `provincia`, `es_predeterminada`, `created_at`, `updated_at`) VALUES
(1, 'App\\Models\\Empleado', 1, 'particular', 'Calle Falsa', '123', NULL, '9011', 'Caleta Olivia', 'Santa Cruz', 1, '2026-09-23 01:03:17', '2026-09-23 01:03:17'),
(2, 'App\\Models\\Empleado', 2, 'particular', 'Calle Falsa', '123', NULL, '9011', 'Caleta Olivia', 'Santa Cruz', 1, '2026-09-23 03:14:18', '2026-09-23 03:14:18'),
(3, 'App\\Models\\Empleado', 3, 'particular', 'Calle Falsa', '123', NULL, '9011', 'Caleta Olivia', 'Santa Cruz', 1, '2026-09-23 03:27:42', '2026-09-23 03:27:42'),
(4, 'App\\Models\\Empleado', 4, 'particular', 'Calle Falsa', '123', NULL, '9011', 'Caleta Olivia', 'Santa Cruz', 1, '2026-09-23 04:44:54', '2026-09-23 04:44:54'),
(5, 'App\\Models\\Empleado', 5, 'particular', 'Calle Falsa', '123', NULL, '9011', 'Caleta Olivia', 'Santa Cruz', 1, '2026-09-23 05:08:04', '2026-09-23 05:08:04'),
(6, 'App\\Models\\Empleado', 6, 'particular', 'Calle Falsa', '123', NULL, '9011', 'Caleta Olivia', 'Santa Cruz', 1, '2026-09-23 05:53:03', '2026-09-23 05:53:03'),
(7, 'App\\Models\\Proveedor', 1, 'fiscal', 'Calle Falsa', '123', NULL, '9011', 'Caleta Olivia', 'Santa Cruz', 1, '2026-09-23 08:49:20', '2026-09-23 08:49:20'),
(8, 'App\\Models\\Proveedor', 2, 'fiscal', 'Calle Falsa', '123', NULL, '9011', 'Caleta Olivia', 'Santa Cruz', 1, '2026-09-23 08:56:11', '2026-09-23 08:56:11'),
(9, 'App\\Models\\Proveedor', 3, 'fiscal', 'Calle Falsa', '123', NULL, '9011', 'Caleta Olivia', 'Santa Cruz', 1, '2026-09-23 09:00:23', '2026-09-23 09:00:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleados`
--

CREATE TABLE `empleados` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `legajo` varchar(20) NOT NULL,
  `tipo_documento_id` bigint(20) UNSIGNED NOT NULL,
  `nro_doc` varchar(20) NOT NULL,
  `cuil` varchar(13) NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `fecha_ingreso` date NOT NULL,
  `fecha_baja` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `empleados`
--

INSERT INTO `empleados` (`id`, `user_id`, `legajo`, `tipo_documento_id`, `nro_doc`, `cuil`, `fecha_nacimiento`, `telefono`, `fecha_ingreso`, `fecha_baja`, `created_at`, `updated_at`) VALUES
(1, 2, 'EMP001', 3, '12345678', '20329851657', '1950-01-23', '2974121212', '2026-09-22', NULL, '2026-09-23 01:03:17', '2026-09-23 01:03:17'),
(2, 3, 'EMP002', 3, '12345677', '27280335148', '1949-01-17', '2974123456', '2026-09-22', NULL, '2026-09-23 03:14:18', '2026-09-23 03:14:18'),
(3, 4, 'EMP003', 3, '12345676', '20100000017', '1961-09-15', '2974124578', '2026-09-22', NULL, '2026-09-23 03:27:42', '2026-09-25 03:10:12'),
(4, 6, 'EMP004', 3, '12354687', '20100000025', '1961-11-20', '2971447758', '2026-09-22', NULL, '2026-09-23 04:44:54', '2026-09-23 04:44:54'),
(5, 7, 'EMP005', 3, '32653222', '20100000033', '1959-08-06', '2974717171', '2026-09-22', NULL, '2026-09-23 05:08:04', '2026-09-23 05:08:04'),
(6, 8, 'EMP006', 3, '12451425', '20100000041', '1987-06-24', '2977989898', '2026-09-22', NULL, '2026-09-23 05:53:03', '2026-09-23 19:20:44'),
(7, 10, 'EMP007', 3, '36256256', '27100000054', '1981-09-23', NULL, '2026-09-23', NULL, '2026-09-23 10:01:51', '2026-09-23 10:01:51'),
(8, 11, 'EMP008', 3, '85456456', '27100000062', '1951-10-23', NULL, '2026-09-23', NULL, '2026-09-23 10:05:00', '2026-09-23 10:05:00'),
(9, 12, 'EMP009', 3, '36236236', '23100000016', '1961-08-22', NULL, '2026-09-23', NULL, '2026-09-23 10:07:02', '2026-10-09 20:49:21'),
(10, 13, 'EMP010', 3, '99478459', '23100000024', '1959-08-11', NULL, '2026-09-23', NULL, '2026-09-23 10:09:15', '2026-09-23 10:09:15'),
(11, 14, 'EMP011', 3, '66256237', '23100000032', '1962-06-25', NULL, '2026-09-23', '2026-10-16', '2026-09-23 10:10:52', '2026-10-16 03:53:11'),
(12, 15, 'EMP012', 3, '77485485', '24100000055', '1953-05-17', NULL, '2026-09-24', NULL, '2026-09-24 08:32:30', '2026-09-24 08:32:30');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `envios`
--

CREATE TABLE `envios` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `venta_id` bigint(20) UNSIGNED NOT NULL,
  `calle` varchar(120) NOT NULL,
  `numero` varchar(10) DEFAULT NULL,
  `piso_depto` varchar(20) DEFAULT NULL,
  `codigo_postal` varchar(10) NOT NULL,
  `localidad` varchar(80) NOT NULL,
  `provincia` varchar(60) NOT NULL,
  `costo` decimal(12,2) DEFAULT NULL,
  `proveedor_envio` varchar(60) DEFAULT NULL,
  `envio_externo_id` varchar(60) DEFAULT NULL,
  `tracking_url` varchar(255) DEFAULT NULL,
  `estado` varchar(40) NOT NULL,
  `fecha_despacho` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2026_01_01_000000_create_password_reset_tokens_table', 1),
(2, '2026_01_01_000101_create_roles_table', 1),
(3, '2026_01_01_000102_create_permisos_table', 1),
(4, '2026_01_01_000103_create_rol_permiso_table', 1),
(5, '2026_01_01_000104_create_tipos_documento_table', 1),
(6, '2026_01_01_000105_create_condiciones_iva_table', 1),
(7, '2026_01_01_000106_create_alicuotas_iva_table', 1),
(8, '2026_01_01_000107_create_tipos_comprobante_table', 1),
(9, '2026_01_01_000108_create_puntos_venta_table', 1),
(10, '2026_01_01_000109_create_users_table', 1),
(11, '2026_01_01_000110_create_proveedores_table', 1),
(12, '2026_01_01_000111_create_empleados_table', 1),
(13, '2026_01_01_000112_create_clientes_table', 1),
(14, '2026_01_01_000113_create_direcciones_table', 1),
(15, '2026_01_01_000114_create_categorias_table', 1),
(16, '2026_01_01_000115_create_productos_table', 1),
(17, '2026_01_01_000116_create_numeradores_table', 1),
(18, '2026_01_01_000117_create_promociones_table', 1),
(19, '2026_01_01_000118_create_promocion_productos_table', 1),
(20, '2026_01_01_000119_create_carritos_table', 1),
(21, '2026_01_01_000120_create_carrito_lineas_table', 1),
(22, '2026_01_01_000121_create_ventas_table', 1),
(23, '2026_01_01_000122_create_venta_lineas_table', 1),
(24, '2026_01_01_000123_create_promociones_aplicadas_table', 1),
(25, '2026_01_01_000124_create_pagos_table', 1),
(26, '2026_01_01_000125_create_comprobantes_table', 1),
(27, '2026_01_01_000126_create_comprobante_lineas_table', 1),
(28, '2026_01_01_000127_create_afip_solicitudes_table', 1),
(29, '2026_01_01_000128_create_envios_table', 1),
(30, '2026_01_01_000129_create_ordenes_compra_table', 1),
(31, '2026_01_01_000130_create_orden_compra_lineas_table', 1),
(32, '2026_01_01_000131_create_movimientos_stock_table', 1),
(33, '2026_09_22_022930_create_cache_table', 2),
(34, '2026_09_22_235306_create_failed_jobs_table', 3),
(35, '2026_09_22_235306_create_jobs_table', 3),
(36, '2026_09_22_235307_create_job_batches_table', 3),
(37, '2026_09_29_000001_agregar_serie_orden_compra_a_numeradores', 4),
(38, '2026_09_29_000002_agregar_permisos_de_compras', 4),
(39, '2026_10_02_000001_agregar_permiso_recepcion_compras', 5),
(40, '2026_01_02_000100_agregar_agrupacion_a_ordenes_compra', 6);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientos_stock`
--

CREATE TABLE `movimientos_stock` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `producto_id` bigint(20) UNSIGNED NOT NULL,
  `tipo` enum('inicial','compra','reserva','entrega','liberacion','devolucion','ajuste') NOT NULL,
  `cantidad_fisico` int(11) NOT NULL DEFAULT 0,
  `cantidad_comprometido` int(11) NOT NULL DEFAULT 0,
  `stock_fisico_resultante` int(10) UNSIGNED NOT NULL,
  `stock_comprometido_resultante` int(10) UNSIGNED NOT NULL,
  `origen_type` varchar(100) DEFAULT NULL,
  `origen_id` bigint(20) UNSIGNED DEFAULT NULL,
  `usuario_id` bigint(20) UNSIGNED DEFAULT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `movimientos_stock`
--

INSERT INTO `movimientos_stock` (`id`, `producto_id`, `tipo`, `cantidad_fisico`, `cantidad_comprometido`, `stock_fisico_resultante`, `stock_comprometido_resultante`, `origen_type`, `origen_id`, `usuario_id`, `motivo`, `created_at`) VALUES
(1, 1, 'inicial', 10, 0, 10, 0, NULL, NULL, 1, NULL, '2026-09-23 09:03:02'),
(2, 2, 'inicial', 5, 0, 5, 0, NULL, NULL, 1, NULL, '2026-09-23 09:09:02'),
(3, 3, 'inicial', 3, 0, 3, 0, NULL, NULL, 2, NULL, '2026-09-23 10:22:46'),
(4, 4, 'inicial', 20, 0, 20, 0, NULL, NULL, 2, NULL, '2026-09-23 10:27:18'),
(5, 5, 'inicial', 8, 0, 8, 0, NULL, NULL, 2, NULL, '2026-09-23 10:29:33'),
(6, 7, 'inicial', 20, 0, 20, 0, NULL, NULL, 2, NULL, '2026-09-24 08:38:55'),
(7, 5, 'reserva', 0, 1, 8, 1, 'App\\Models\\Venta', 1, 4, NULL, '2026-09-25 00:44:17'),
(8, 6, 'reserva', 0, 1, 0, 1, 'App\\Models\\Venta', 2, 4, NULL, '2026-09-25 01:19:41'),
(9, 2, 'reserva', 0, 1, 5, 1, 'App\\Models\\Venta', 3, 4, NULL, '2026-09-25 02:58:51'),
(10, 5, 'reserva', 0, 1, 8, 2, 'App\\Models\\Venta', 3, 4, NULL, '2026-09-25 02:58:51'),
(11, 3, 'reserva', 0, 5, 3, 5, 'App\\Models\\Venta', 4, 4, NULL, '2026-09-25 03:06:44'),
(12, 8, 'inicial', 2, 0, 2, 0, NULL, NULL, 2, NULL, '2026-09-25 03:41:15'),
(13, 9, 'inicial', 10, 0, 10, 0, NULL, NULL, 2, NULL, '2026-09-25 03:43:05'),
(14, 10, 'inicial', 3, 0, 3, 0, NULL, NULL, 2, NULL, '2026-09-25 06:18:43'),
(15, 11, 'inicial', 2, 0, 2, 0, NULL, NULL, 2, NULL, '2026-09-25 06:32:42'),
(16, 11, 'reserva', 0, 2, 2, 2, 'App\\Models\\Venta', 5, 4, NULL, '2026-09-25 06:39:46'),
(18, 7, 'reserva', 0, 1, 20, 1, 'App\\Models\\Venta', 7, 4, NULL, '2026-09-27 04:37:09'),
(22, 9, 'reserva', 0, 1, 10, 1, 'App\\Models\\Venta', 8, 4, NULL, '2026-09-27 04:39:12'),
(25, 9, 'entrega', -1, -1, 9, 0, 'App\\Models\\Venta', 8, 3, NULL, '2026-09-27 04:40:57'),
(26, 8, 'reserva', 0, 2, 2, 2, 'App\\Models\\Venta', 9, 4, NULL, '2026-10-01 02:45:58'),
(27, 8, 'reserva', 0, 4, 2, 6, 'App\\Models\\Venta', 10, 4, NULL, '2026-10-01 02:47:39'),
(28, 8, 'reserva', 0, 2, 2, 8, 'App\\Models\\Venta', 11, 4, NULL, '2026-10-01 02:49:26'),
(29, 8, 'reserva', 0, 1, 2, 9, 'App\\Models\\Venta', 12, 4, NULL, '2026-10-01 04:13:03'),
(30, 8, 'reserva', 0, 1, 2, 10, 'App\\Models\\Venta', 13, 4, NULL, '2026-10-01 04:17:19'),
(31, 8, 'reserva', 0, 1, 2, 11, 'App\\Models\\Venta', 14, 4, NULL, '2026-10-01 04:17:37'),
(32, 4, 'reserva', 0, 1, 20, 1, 'App\\Models\\Venta', 15, 4, NULL, '2026-10-01 04:32:06'),
(36, 8, 'entrega', -1, -1, 1, 10, 'App\\Models\\Venta', 14, 3, NULL, '2026-10-01 04:35:08'),
(37, 8, 'reserva', 0, 1, 1, 11, 'App\\Models\\Venta', 16, 4, NULL, '2026-10-01 04:56:19'),
(38, 8, 'reserva', 0, 1, 1, 12, 'App\\Models\\Venta', 17, 4, NULL, '2026-10-01 06:20:13'),
(39, 8, 'reserva', 0, 2, 1, 14, 'App\\Models\\Venta', 18, 4, NULL, '2026-10-01 06:21:23'),
(40, 11, 'compra', 2, 0, 4, 2, 'App\\Models\\OrdenCompra', 4, 2, '0000000001', '2026-10-03 04:36:11'),
(41, 8, 'compra', 10, 0, 11, 14, 'App\\Models\\OrdenCompra', 8, 2, '0000000002', '2026-10-03 04:37:29'),
(42, 8, 'compra', 6, 0, 17, 14, 'App\\Models\\OrdenCompra', 9, 2, '0000000003', '2026-10-03 04:42:41'),
(43, 6, 'compra', 10, 0, 10, 1, 'App\\Models\\OrdenCompra', 2, 2, '0000000004', '2026-10-03 04:53:58'),
(44, 3, 'compra', 5, 0, 8, 5, 'App\\Models\\OrdenCompra', 1, 2, '0000000005', '2026-10-03 05:37:48'),
(46, 4, 'entrega', -1, -1, 19, 0, 'App\\Models\\Venta', 15, 3, NULL, '2026-10-05 09:15:07'),
(47, 2, 'reserva', 0, 1, 5, 2, 'App\\Models\\Venta', 19, 4, NULL, '2026-10-07 03:18:11'),
(48, 2, 'liberacion', 0, -1, 5, 1, 'App\\Models\\Venta', 19, 4, NULL, '2026-10-07 03:18:27'),
(50, 8, 'entrega', -2, -2, 15, 12, 'App\\Models\\Venta', 18, 3, NULL, '2026-10-07 03:19:38'),
(51, 8, 'devolucion', 2, 0, 17, 12, 'App\\Models\\Comprobante', 15, 4, NULL, '2026-10-07 03:20:26'),
(52, 7, 'entrega', -1, -1, 19, 0, 'App\\Models\\Venta', 7, 2, NULL, '2026-10-08 01:54:51'),
(55, 10, 'compra', 1, 0, 4, 0, 'App\\Models\\OrdenCompra', 11, 2, NULL, '2026-10-08 05:24:57'),
(56, 8, 'entrega', -1, -1, 16, 11, 'App\\Models\\Venta', 17, 3, NULL, '2026-10-09 03:55:18'),
(57, 2, 'reserva', 0, 1, 5, 2, 'App\\Models\\Venta', 20, 4, NULL, '2026-10-09 03:57:27'),
(58, 2, 'entrega', -1, -1, 4, 1, 'App\\Models\\Venta', 20, 4, NULL, '2026-10-09 03:58:58'),
(59, 2, 'devolucion', 1, 0, 5, 1, 'App\\Models\\Comprobante', 20, 4, NULL, '2026-10-09 03:59:14'),
(60, 7, 'compra', 2, 0, 21, 0, 'App\\Models\\OrdenCompra', 12, 2, NULL, '2026-10-09 04:27:56'),
(61, 2, 'reserva', 0, 1, 5, 2, 'App\\Models\\Venta', 21, 12, NULL, '2026-10-09 20:50:40'),
(63, 2, 'entrega', -1, -1, 4, 1, 'App\\Models\\Venta', 21, 3, NULL, '2026-10-09 20:52:28'),
(64, 4, 'reserva', 0, 2, 19, 2, 'App\\Models\\Venta', 22, 4, NULL, '2026-10-09 21:13:41'),
(65, 7, 'reserva', 0, 2, 21, 2, 'App\\Models\\Venta', 23, 4, NULL, '2026-10-09 21:14:45'),
(66, 7, 'liberacion', 0, -2, 21, 0, 'App\\Models\\Venta', 23, 4, NULL, '2026-10-09 21:15:02'),
(67, 7, 'reserva', 0, 3, 21, 3, 'App\\Models\\Venta', 24, 4, NULL, '2026-10-09 21:16:18'),
(68, 7, 'reserva', 0, 4, 21, 7, 'App\\Models\\Venta', 25, 4, NULL, '2026-10-09 23:08:38'),
(69, 2, 'reserva', 0, 1, 4, 2, 'App\\Models\\Venta', 25, 4, NULL, '2026-10-09 23:08:38'),
(70, 7, 'entrega', -4, -4, 17, 3, 'App\\Models\\Venta', 25, 3, NULL, '2026-10-09 23:12:09'),
(71, 2, 'entrega', -1, -1, 3, 1, 'App\\Models\\Venta', 25, 3, NULL, '2026-10-09 23:12:09'),
(72, 3, 'compra', 5, 0, 13, 5, 'App\\Models\\OrdenCompra', 16, 2, '0000000006', '2026-10-17 09:48:34'),
(73, 1, 'compra', 2, 0, 12, 0, 'App\\Models\\OrdenCompra', 16, 2, '0000000006', '2026-10-17 09:48:34'),
(74, 11, 'compra', 3, 0, 7, 2, 'App\\Models\\OrdenCompra', 17, 2, '0000000007', '2026-10-17 09:49:05'),
(75, 10, 'compra', 2, 0, 6, 0, 'App\\Models\\OrdenCompra', 18, 2, '0000000008', '2026-10-17 09:49:23'),
(76, 11, 'reserva', 0, 5, 7, 7, 'App\\Models\\Venta', 26, 4, NULL, '2026-10-18 04:45:59'),
(77, 11, 'compra', 3, 0, 10, 7, 'App\\Models\\OrdenCompra', 21, 2, '0000000009', '2026-10-18 04:48:52'),
(78, 3, 'compra', 1, 0, 14, 5, 'App\\Models\\OrdenCompra', 22, 2, '0000000009', '2026-10-18 04:49:05'),
(79, 11, 'reserva', 0, 3, 10, 10, 'App\\Models\\Venta', 27, 4, NULL, '2026-10-30 01:58:16'),
(81, 11, 'entrega', -3, -3, 7, 7, 'App\\Models\\Venta', 27, 3, NULL, '2026-10-30 01:58:55'),
(82, 10, 'reserva', 0, 6, 6, 6, 'App\\Models\\Venta', 28, 4, NULL, '2026-10-30 02:19:18'),
(83, 10, 'entrega', -6, -6, 0, 0, 'App\\Models\\Venta', 28, 3, NULL, '2026-10-30 02:20:31'),
(84, 1, 'reserva', 0, 1, 12, 1, 'App\\Models\\Venta', 29, 9, NULL, '2026-10-31 06:00:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `numeradores`
--

CREATE TABLE `numeradores` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `serie` varchar(30) NOT NULL,
  `prefijo` varchar(10) DEFAULT NULL,
  `ultimo_numero` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `numeradores`
--

INSERT INTO `numeradores` (`id`, `serie`, `prefijo`, `ultimo_numero`, `updated_at`) VALUES
(1, 'venta', 'V', 29, '2026-10-31 06:00:12'),
(2, 'orden_compra', 'OC', 19, '2026-10-30 02:19:18');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ordenes_compra`
--

CREATE TABLE `ordenes_compra` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `orden_padre_id` bigint(20) UNSIGNED DEFAULT NULL,
  `numero` varchar(20) NOT NULL,
  `proveedor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `estado` enum('borrador','aprobada','enviada','recibida_parcial','recibida','cancelada') NOT NULL DEFAULT 'borrador',
  `origen` enum('manual','automatica') NOT NULL DEFAULT 'manual',
  `total_estimado` decimal(12,2) DEFAULT NULL,
  `usuario_creo_id` bigint(20) UNSIGNED DEFAULT NULL,
  `usuario_aprobo_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fecha_aprobacion` datetime DEFAULT NULL,
  `fecha_envio` datetime DEFAULT NULL,
  `fecha_entrega_estimada` date DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `ordenes_compra`
--

INSERT INTO `ordenes_compra` (`id`, `orden_padre_id`, `numero`, `proveedor_id`, `estado`, `origen`, `total_estimado`, `usuario_creo_id`, `usuario_aprobo_id`, `fecha_aprobacion`, `fecha_envio`, `fecha_entrega_estimada`, `observaciones`, `created_at`, `updated_at`) VALUES
(1, NULL, 'OC-0000001', 2, 'recibida', 'automatica', 250000.00, NULL, 2, '2026-09-30 23:33:08', '2026-09-30 23:34:30', '2026-10-07', 'Generada automáticamente por reposición de stock.', '2026-10-01 02:14:50', '2026-10-03 05:37:48'),
(2, NULL, 'OC-0000002', 3, 'recibida_parcial', 'automatica', 156.00, NULL, 2, '2026-09-30 23:35:13', '2026-09-30 23:35:15', '2026-10-03', 'Generada automáticamente por reposición de stock.', '2026-10-01 02:14:50', '2026-10-03 04:53:58'),
(3, NULL, 'OC-0000003', 11, 'cancelada', 'automatica', 20000.00, NULL, NULL, NULL, NULL, NULL, 'Generada automáticamente por reposición de stock.\n[30/09/2026 23:36] Cancelada por : ya no la vamos a vender', '2026-10-01 02:14:50', '2026-10-01 02:36:08'),
(4, NULL, 'OC-0000004', 11, 'recibida', 'automatica', 20000.00, NULL, 2, '2026-10-02 01:34:47', '2026-10-02 01:34:50', NULL, 'Generada automáticamente por reposición de stock.', '2026-10-01 02:41:08', '2026-10-03 04:36:12'),
(5, NULL, 'OC-0000005', 4, 'cancelada', 'automatica', 360000.00, NULL, 2, '2026-10-01 01:21:57', '2026-10-01 01:21:59', '2026-10-05', 'Generada automáticamente por reposición de stock.\n[01/10/2026 01:27] Cancelada por : el proveedor quebró', '2026-10-01 02:45:58', '2026-10-01 04:27:12'),
(6, NULL, 'OC-0000006', 4, 'cancelada', 'automatica', 420000.00, NULL, 2, '2026-10-01 03:20:30', '2026-10-01 03:20:34', '2026-10-06', 'Generada automáticamente por reposición de stock.\n[01/10/2026 03:20] Cancelada por : missclick', '2026-10-01 04:56:19', '2026-10-01 06:20:50'),
(7, NULL, 'OC-0000007', 4, 'cancelada', 'automatica', 420000.00, NULL, 2, '2026-10-01 03:21:05', '2026-10-01 03:21:09', '2026-10-06', 'Generada automáticamente por reposición de stock.\n[01/10/2026 03:21] Cancelada por : otro missclick', '2026-10-01 06:20:51', '2026-10-01 06:21:49'),
(8, NULL, 'OC-0000008', 4, 'recibida', 'automatica', 480000.00, NULL, 2, '2026-10-03 01:37:11', '2026-10-03 01:37:16', '2026-10-06', 'Generada automáticamente por reposición de stock.\n[03/10/2026 01:38] Cerrado con faltante por : missclick. Quedó sin recibir: RM850eCorsair x6.', '2026-10-01 06:21:51', '2026-10-03 04:38:28'),
(9, NULL, 'OC-0000009', 4, 'recibida', 'automatica', 180000.00, NULL, 2, '2026-10-03 01:42:26', '2026-10-03 01:42:30', '2026-10-08', 'Generada automáticamente por reposición de stock.', '2026-10-03 04:41:39', '2026-10-03 04:42:41'),
(10, NULL, 'OC-0000010', 4, 'cancelada', 'manual', 3000000.00, 2, NULL, NULL, NULL, '2026-10-13', '[08/10/2026 01:24] Cancelada por : asdas', '2026-10-08 04:21:06', '2026-10-08 04:24:34'),
(11, NULL, 'OC-0000011', 4, 'recibida', 'manual', 1000001.00, 2, 2, '2026-10-08 02:24:01', '2026-10-08 02:24:40', '2026-10-13', NULL, '2026-10-08 04:24:12', '2026-10-08 05:24:57'),
(12, NULL, 'OC-0000012', 4, 'recibida', 'manual', 82001.30, 2, 2, '2026-10-09 01:25:44', '2026-10-09 01:27:15', '2026-10-14', 'ta caro', '2026-10-09 04:25:37', '2026-10-09 04:27:56'),
(13, NULL, 'OC-0000013', 1, 'enviada', 'automatica', 600000.00, NULL, 2, '2026-10-09 20:03:50', '2026-10-09 20:04:05', '2026-10-16', 'Generada automáticamente por reposición de stock.', '2026-10-09 20:50:42', '2026-10-09 23:04:05'),
(14, NULL, 'OC-0000014', 2, 'enviada', 'manual', 100000.00, 2, 2, '2026-10-09 17:56:02', '2026-10-09 17:56:18', '2026-10-16', NULL, '2026-10-09 20:55:58', '2026-10-09 20:56:18'),
(15, NULL, 'OC-0000015', NULL, 'recibida', 'manual', 2330000.00, 2, 2, '2026-10-17 06:39:20', NULL, '2026-10-24', 'prueba de multi proveedor', '2026-10-17 09:39:07', '2026-10-17 09:49:23'),
(16, 15, 'OC-0000015/1', 2, 'recibida', 'manual', 300000.00, 2, 2, '2026-10-17 06:39:20', '2026-10-17 06:39:31', '2026-10-24', 'Emitida desde el pedido OC-0000015.', '2026-10-17 09:39:20', '2026-10-17 09:48:34'),
(17, 15, 'OC-0000015/2', 11, 'recibida', 'manual', 30000.00, 2, 2, '2026-10-17 06:39:20', '2026-10-17 06:39:31', NULL, 'Emitida desde el pedido OC-0000015.', '2026-10-17 09:39:20', '2026-10-17 09:49:05'),
(18, 15, 'OC-0000015/3', 4, 'recibida', 'manual', 2000000.00, 2, 2, '2026-10-17 06:39:20', '2026-10-17 06:39:31', '2026-10-22', 'Emitida desde el pedido OC-0000015.', '2026-10-17 09:39:20', '2026-10-17 09:49:23'),
(19, NULL, 'OC-0000016', 11, 'cancelada', 'automatica', 20000.00, NULL, NULL, NULL, NULL, NULL, 'Generada automáticamente por reposición de stock.\n[18/10/2026 02:48] Cancelada por : ya pedí', '2026-10-18 04:45:59', '2026-10-18 05:48:15'),
(20, NULL, 'OC-0000017', NULL, 'recibida', 'manual', 80000.00, 2, 2, '2026-10-18 01:47:55', NULL, '2026-10-25', NULL, '2026-10-18 04:47:52', '2026-10-18 04:49:05'),
(21, 20, 'OC-0000017/1', 11, 'recibida', 'manual', 30000.00, 2, 2, '2026-10-18 01:47:55', '2026-10-18 01:48:40', NULL, 'Emitida desde el pedido OC-0000017.', '2026-10-18 04:47:55', '2026-10-18 04:48:52'),
(22, 20, 'OC-0000017/2', 2, 'recibida', 'manual', 50000.00, 2, 2, '2026-10-18 01:47:55', '2026-10-18 01:48:40', '2026-10-25', 'Emitida desde el pedido OC-0000017.', '2026-10-18 04:47:55', '2026-10-18 04:49:05'),
(23, NULL, 'OC-0000018', 11, 'enviada', 'manual', 10000.00, 2, 2, '2026-10-18 02:09:54', '2026-10-18 02:09:55', NULL, NULL, '2026-10-18 05:08:36', '2026-10-18 05:09:55'),
(24, NULL, 'OC-0000019', 4, 'borrador', 'automatica', 3000000.00, NULL, NULL, NULL, NULL, '2026-11-03', 'Generada automáticamente por reposición de stock.', '2026-10-30 02:19:18', '2026-10-30 02:19:18');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `orden_compra_lineas`
--

CREATE TABLE `orden_compra_lineas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `orden_compra_id` bigint(20) UNSIGNED NOT NULL,
  `producto_id` bigint(20) UNSIGNED NOT NULL,
  `proveedor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cantidad_pedida` int(11) NOT NULL,
  `cantidad_recibida` int(11) NOT NULL DEFAULT 0,
  `costo_unitario` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `orden_compra_lineas`
--

INSERT INTO `orden_compra_lineas` (`id`, `orden_compra_id`, `producto_id`, `proveedor_id`, `cantidad_pedida`, `cantidad_recibida`, `costo_unitario`, `created_at`, `updated_at`) VALUES
(1, 1, 3, 2, 5, 5, 50000.00, '2026-10-01 02:14:50', '2026-10-03 05:37:48'),
(2, 2, 6, 3, 13, 10, 12.00, '2026-10-01 02:14:50', '2026-10-03 04:53:58'),
(3, 3, 11, 11, 2, 0, 10000.00, '2026-10-01 02:14:50', '2026-10-01 02:14:50'),
(4, 4, 11, 11, 2, 2, 10000.00, '2026-10-01 02:41:08', '2026-10-03 04:36:11'),
(5, 5, 8, 4, 12, 0, 30000.00, '2026-10-01 02:45:58', '2026-10-01 04:17:38'),
(6, 6, 8, 4, 14, 0, 30000.00, '2026-10-01 04:56:19', '2026-10-01 06:20:15'),
(7, 7, 8, 4, 14, 0, 30000.00, '2026-10-01 06:20:51', '2026-10-01 06:20:51'),
(8, 8, 8, 4, 16, 10, 30000.00, '2026-10-01 06:21:51', '2026-10-03 04:37:29'),
(9, 9, 8, 4, 6, 6, 30000.00, '2026-10-03 04:41:39', '2026-10-03 04:42:41'),
(10, 10, 10, 4, 3, 0, 1000000.00, '2026-10-08 04:21:06', '2026-10-08 04:21:06'),
(11, 11, 10, 4, 1, 1, 1000001.00, '2026-10-08 04:24:12', '2026-10-08 05:24:57'),
(12, 12, 7, 4, 2, 2, 41000.65, '2026-10-09 04:25:37', '2026-10-09 04:27:56'),
(13, 13, 2, 1, 6, 0, 100000.00, '2026-10-09 20:50:42', '2026-10-09 20:50:42'),
(14, 14, 3, 2, 2, 0, 50000.00, '2026-10-09 20:55:58', '2026-10-09 20:55:58'),
(15, 15, 3, 2, 5, 0, 50000.00, '2026-10-17 09:39:07', '2026-10-17 09:39:07'),
(16, 15, 11, 11, 3, 0, 10000.00, '2026-10-17 09:39:07', '2026-10-17 09:39:07'),
(17, 15, 1, 2, 2, 0, 25000.00, '2026-10-17 09:39:07', '2026-10-17 09:39:07'),
(18, 15, 10, 4, 2, 0, 1000000.00, '2026-10-17 09:39:07', '2026-10-17 09:39:07'),
(19, 16, 3, 2, 5, 5, 50000.00, '2026-10-17 09:39:20', '2026-10-17 09:48:34'),
(20, 16, 1, 2, 2, 2, 25000.00, '2026-10-17 09:39:20', '2026-10-17 09:48:34'),
(21, 17, 11, 11, 3, 3, 10000.00, '2026-10-17 09:39:20', '2026-10-17 09:49:05'),
(22, 18, 10, 4, 2, 2, 1000000.00, '2026-10-17 09:39:20', '2026-10-17 09:49:23'),
(23, 19, 11, 11, 2, 0, 10000.00, '2026-10-18 04:45:59', '2026-10-18 04:45:59'),
(24, 20, 11, 11, 3, 0, 10000.00, '2026-10-18 04:47:52', '2026-10-18 04:47:52'),
(25, 20, 3, 2, 1, 0, 50000.00, '2026-10-18 04:47:52', '2026-10-18 04:47:52'),
(26, 21, 11, 11, 3, 3, 10000.00, '2026-10-18 04:47:55', '2026-10-18 04:48:52'),
(27, 22, 3, 2, 1, 1, 50000.00, '2026-10-18 04:47:55', '2026-10-18 04:49:05'),
(28, 23, 11, 11, 1, 0, 10000.00, '2026-10-18 05:08:36', '2026-10-18 05:08:36'),
(29, 24, 10, 4, 3, 0, 1000000.00, '2026-10-30 02:19:18', '2026-10-30 02:19:18');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `venta_id` bigint(20) UNSIGNED NOT NULL,
  `metodo` enum('efectivo','tarjeta','transferencia') NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `comision` decimal(12,2) DEFAULT NULL,
  `neto_acreditado` decimal(12,2) DEFAULT NULL,
  `cuotas` tinyint(4) DEFAULT NULL,
  `estado` enum('pendiente','acreditado','rechazado','devuelto') NOT NULL DEFAULT 'pendiente',
  `mp_preference_id` varchar(60) DEFAULT NULL,
  `mp_payment_id` varchar(30) DEFAULT NULL,
  `mp_merchant_order_id` varchar(30) DEFAULT NULL,
  `mp_status` varchar(30) DEFAULT NULL,
  `mp_status_detail` varchar(60) DEFAULT NULL,
  `mp_payment_type` varchar(30) DEFAULT NULL,
  `usuario_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fecha` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pagos`
--

INSERT INTO `pagos` (`id`, `venta_id`, `metodo`, `monto`, `comision`, `neto_acreditado`, `cuotas`, `estado`, `mp_preference_id`, `mp_payment_id`, `mp_merchant_order_id`, `mp_status`, `mp_status_detail`, `mp_payment_type`, `usuario_id`, `fecha`, `created_at`, `updated_at`) VALUES
(3, 5, 'efectivo', 30000.00, NULL, NULL, NULL, 'acreditado', NULL, NULL, NULL, NULL, NULL, NULL, 3, '2026-09-26 23:59:04', '2026-09-27 02:59:04', '2026-09-27 02:59:04'),
(4, 4, 'efectivo', 375000.00, NULL, NULL, NULL, 'acreditado', NULL, NULL, NULL, NULL, NULL, NULL, 3, '2026-09-27 00:00:25', '2026-09-27 03:00:25', '2026-09-27 03:00:25'),
(9, 7, 'efectivo', 45000.15, NULL, NULL, NULL, 'acreditado', NULL, NULL, NULL, NULL, NULL, NULL, 3, '2026-09-27 01:38:11', '2026-09-27 04:38:11', '2026-09-27 04:38:11'),
(12, 8, 'efectivo', 1402151.00, NULL, NULL, NULL, 'acreditado', NULL, NULL, NULL, NULL, NULL, NULL, 3, '2026-09-27 01:40:17', '2026-09-27 04:40:17', '2026-09-27 04:40:17'),
(17, 14, 'efectivo', 50000.00, NULL, NULL, NULL, 'acreditado', NULL, NULL, NULL, NULL, NULL, NULL, 3, '2026-10-01 01:34:53', '2026-10-01 04:34:53', '2026-10-01 04:34:53'),
(19, 15, 'efectivo', 15000.00, NULL, NULL, NULL, 'acreditado', NULL, NULL, NULL, NULL, NULL, NULL, 3, '2026-10-05 06:14:29', '2026-10-05 09:14:29', '2026-10-05 09:14:29'),
(20, 18, 'efectivo', 100000.00, NULL, NULL, NULL, 'acreditado', NULL, NULL, NULL, NULL, NULL, NULL, 3, '2026-10-07 00:19:31', '2026-10-07 03:19:31', '2026-10-07 03:19:31'),
(22, 9, 'efectivo', 100000.00, NULL, NULL, NULL, 'acreditado', NULL, NULL, NULL, NULL, NULL, NULL, 3, '2026-10-07 23:11:02', '2026-10-08 02:11:02', '2026-10-08 02:11:02'),
(23, 17, 'efectivo', 50000.00, NULL, NULL, NULL, 'acreditado', NULL, NULL, NULL, NULL, NULL, NULL, 3, '2026-10-09 00:55:13', '2026-10-09 03:55:13', '2026-10-09 03:55:13'),
(24, 20, 'efectivo', 100000.00, NULL, NULL, NULL, 'acreditado', NULL, NULL, NULL, NULL, NULL, NULL, 3, '2026-10-09 00:58:36', '2026-10-09 03:58:36', '2026-10-09 03:58:36'),
(26, 21, 'efectivo', 100000.00, NULL, NULL, NULL, 'acreditado', NULL, NULL, NULL, NULL, NULL, NULL, 3, '2026-10-09 17:52:16', '2026-10-09 20:52:16', '2026-10-09 20:52:16'),
(27, 24, 'efectivo', 130000.45, NULL, NULL, NULL, 'acreditado', NULL, NULL, NULL, NULL, NULL, NULL, 3, '2026-10-09 18:45:55', '2026-10-09 21:45:55', '2026-10-09 21:45:55'),
(28, 25, 'efectivo', 280000.60, NULL, NULL, NULL, 'acreditado', NULL, NULL, NULL, NULL, NULL, NULL, 3, '2026-10-09 20:11:07', '2026-10-09 23:11:07', '2026-10-09 23:11:07'),
(30, 27, 'efectivo', 45000.00, NULL, NULL, NULL, 'acreditado', NULL, NULL, NULL, NULL, NULL, NULL, 3, '2026-10-29 22:58:50', '2026-10-30 01:58:50', '2026-10-30 01:58:50'),
(31, 28, 'efectivo', 9000000.00, NULL, NULL, NULL, 'acreditado', NULL, NULL, NULL, NULL, NULL, NULL, 3, '2026-10-29 23:20:28', '2026-10-30 02:20:28', '2026-10-30 02:20:28'),
(32, 29, 'tarjeta', 42500.00, NULL, NULL, 1, 'acreditado', NULL, 'SIM-FIHGHXEETCMWPUDUHQZW', NULL, 'simulado', NULL, NULL, NULL, '2026-10-31 03:00:12', '2026-10-31 06:00:12', '2026-10-31 06:00:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos`
--

CREATE TABLE `permisos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `clave` varchar(60) NOT NULL,
  `modulo` varchar(40) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `permisos`
--

INSERT INTO `permisos` (`id`, `clave`, `modulo`, `descripcion`, `created_at`, `updated_at`) VALUES
(1, 'producto.ver', 'catalogo', 'Ver el catalogo de productos y su stock disponible.', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(2, 'producto.crear', 'catalogo', 'Dar de alta productos.', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(3, 'producto.editar', 'catalogo', 'Modificar datos de un producto.', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(4, 'producto.baja', 'catalogo', 'Dar de baja o reactivar un producto (activo).', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(5, 'producto.publicar', 'catalogo', 'Publicar o despublicar un producto en la tienda.', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(6, 'categoria.ver', 'catalogo', 'Ver las categorias.', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(7, 'categoria.crear', 'catalogo', 'Crear categorias.', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(8, 'categoria.editar', 'catalogo', 'Modificar categorias.', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(9, 'categoria.baja', 'catalogo', 'Dar de baja o reactivar una categoria.', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(10, 'categoria.eliminar', 'catalogo', 'Eliminar fisicamente una categoria sin productos.', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(11, 'proveedor.ver', 'proveedores', 'Ver proveedores.', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(12, 'proveedor.crear', 'proveedores', 'Dar de alta proveedores.', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(13, 'proveedor.editar', 'proveedores', 'Modificar proveedores.', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(14, 'proveedor.baja', 'proveedores', 'Dar de baja o reactivar un proveedor.', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(15, 'promocion.ver', 'promociones', 'Ver las promociones vigentes.', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(16, 'promocion.crear', 'promociones', 'Crear promociones, con su banner.', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(17, 'promocion.editar', 'promociones', 'Modificar promociones.', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(18, 'promocion.baja', 'promociones', 'Dar de baja o reactivar una promocion.', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(19, 'empleado.ver', 'empleados', 'Ver empleados y sus datos personales y fiscales.', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(20, 'empleado.crear', 'empleados', 'Dar de alta empleados.', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(21, 'empleado.editar', 'empleados', 'Modificar datos de un empleado.', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(22, 'empleado.baja', 'empleados', 'Dar de baja o reactivar un empleado.', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(23, 'empleado.forzar_password', 'empleados', 'Forzar el cambio de contrasena en el proximo ingreso.', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(24, 'cliente.ver', 'clientes', 'Ver clientes', '2026-09-22 03:05:45', '2026-09-24 10:02:42'),
(25, 'cliente.crear', 'clientes', 'Dar de alta clientes', '2026-09-22 03:05:45', '2026-09-24 10:02:42'),
(26, 'cliente.editar', 'clientes', 'Editar clientes y sus direcciones', '2026-09-22 03:05:45', '2026-09-24 10:02:42'),
(27, 'cliente.baja', 'clientes', 'Dar de baja y reactivar clientes', '2026-09-22 03:05:45', '2026-09-24 10:02:42'),
(28, 'compra.ver', 'compras', 'Ver los pedidos a proveedores', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(29, 'compra.crear', 'compras', 'Cargar un pedido a proveedor a mano', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(30, 'compra.aprobar', 'compras', 'Aprobar un pedido para que pueda enviarse', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(31, 'compra.enviar', 'compras', 'Enviarle el remito del pedido al proveedor', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(32, 'compra.recibir', 'compras', 'Registrar la recepción de mercadería de un pedido y cerrarlo con faltante', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(33, 'compra.cancelar', 'compras', 'Cancelar un pedido', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(34, 'stock.ver', 'stock', 'Consultar el histórico de movimientos', '2026-09-22 03:05:45', '2026-09-24 10:02:42'),
(35, 'stock.ajustar', 'stock', 'Registrar ajustes de inventario y carga inicial', '2026-09-22 03:05:45', '2026-09-24 10:02:42'),
(36, 'venta.ver', 'ventas', 'Ver ventas y presupuestos', '2026-09-22 03:05:45', '2026-09-24 10:02:42'),
(38, 'venta.crear', 'ventas', 'Generar presupuestos y ventas', '2026-09-22 03:05:45', '2026-09-24 10:02:42'),
(42, 'venta.entregar', 'ventas', 'Preparar, despachar y entregar mercadería', '2026-09-22 03:05:45', '2026-09-24 10:02:42'),
(43, 'venta.devolver', 'ventas', 'Registrar devoluciones con nota de crédito', '2026-09-22 03:05:45', '2026-09-24 10:02:42'),
(47, 'comprobante.ver', 'facturacion', 'Ver comprobantes emitidos', '2026-09-22 03:05:45', '2026-09-24 10:02:42'),
(48, 'comprobante.reintentar', 'facturacion', 'Reintentar la autorización ante ARCA', '2026-09-22 03:05:45', '2026-09-24 10:02:42'),
(49, 'comprobante.nota_credito', 'facturacion', 'Emitir una nota de credito asociada.', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(53, 'tienda.catalogo.ver', 'tienda', 'Navegar el catalogo publicado y ver disponibilidad.', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(54, 'tienda.carrito', 'tienda', 'Administrar el carrito de compras.', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(55, 'tienda.comprar', 'tienda', 'Confirmar la compra y pagar por Mercado Pago.', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(56, 'tienda.envio.ver', 'tienda', 'Ver el tracking de los envios propios.', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(57, 'tienda.perfil.editar', 'tienda', 'Editar los datos personales propios.', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(58, 'rol.gestionar', 'sistema', 'Administrar roles y su matriz de permisos.', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(59, 'punto_venta.gestionar', 'sistema', 'Administrar los puntos de venta habilitados.', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(60, 'venta.ver_todas', 'ventas', 'Ver las ventas de todos los vendedores', '2026-09-24 10:02:42', '2026-09-24 10:02:42'),
(61, 'venta.editar', 'ventas', 'Editar el detalle de un presupuesto', '2026-09-24 10:02:42', '2026-09-24 10:02:42'),
(62, 'venta.validar', 'ventas', 'Validar un presupuesto y habilitar su cobro', '2026-09-24 10:02:42', '2026-09-24 10:02:42'),
(63, 'venta.anular', 'ventas', 'Cancelar una venta', '2026-09-24 10:02:42', '2026-09-24 10:02:42'),
(64, 'venta.cobrar', 'ventas', 'Registrar el cobro de una venta', '2026-09-24 10:02:42', '2026-09-24 10:02:42'),
(65, 'compra.editar', 'compras', 'Editar el detalle de un pedido en borrador', NULL, NULL),
(66, 'compra.aprobar_propias', 'compras', 'Aprobar pedidos cargados por uno mismo', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `categoria_id` bigint(20) UNSIGNED NOT NULL,
  `alicuota_iva_id` bigint(20) UNSIGNED NOT NULL,
  `proveedor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `codigo` varchar(30) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `marca` varchar(80) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `precio` decimal(12,2) NOT NULL,
  `costo` decimal(12,2) DEFAULT NULL,
  `stock_fisico` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `stock_comprometido` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `stock_disponible` int(11) GENERATED ALWAYS AS (cast(`stock_fisico` as signed) - cast(`stock_comprometido` as signed)) STORED,
  `punto_reposicion` int(11) NOT NULL DEFAULT 0,
  `cantidad_reposicion` int(11) NOT NULL DEFAULT 0,
  `reposicion_automatica` tinyint(1) NOT NULL DEFAULT 1,
  `peso_gramos` int(11) DEFAULT NULL,
  `destacado` tinyint(1) NOT NULL DEFAULT 0,
  `publicado_app` tinyint(1) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `categoria_id`, `alicuota_iva_id`, `proveedor_id`, `codigo`, `nombre`, `marca`, `descripcion`, `imagen`, `precio`, `costo`, `stock_fisico`, `stock_comprometido`, `punto_reposicion`, `cantidad_reposicion`, `reposicion_automatica`, `peso_gramos`, `destacado`, `publicado_app`, `activo`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 2, 'R55600X', 'Ryzen 5 5600X', 'AMD', 'buen procesador', 'productos/lKrsVZfo66UkBiIYIntLsJXfuULo6EyYeWA72LMv.jpg', 50000.00, 25000.00, 12, 1, 5, 6, 1, 250, 0, 1, 1, '2026-09-23 09:03:02', '2026-10-31 06:00:12'),
(2, 3, 1, 1, 'NvRTX5060', 'Nvidia RTX 5060 8GB', 'Nvidia', 'una placa', 'productos/gcpMphOMf9tuNXnn86dhK5OwV4IjLr7G8PmbgNJs.jpg', 150000.00, 100000.00, 3, 1, 3, 6, 1, 500, 0, 1, 1, '2026-09-23 09:09:02', '2026-10-09 23:12:09'),
(3, 2, 1, 2, 'R55400G', 'Ryzen 5 5400G', 'AMD', 'procesador con graficos integrados', 'productos/C4eK3s6iYAL0pQXkqGrgGAxU1ysqrGuJNfOPpEMf.jpg', 75000.00, 50000.00, 14, 5, 1, 2, 1, 250, 0, 1, 1, '2026-09-23 10:22:46', '2026-11-01 00:09:59'),
(4, 7, 1, 3, 'G203', 'G203 RGB LightSync', 'Logitech', 'mouse de 6 botones', 'productos/fCgnxW0GRM6Xe9dfvKR5Gpv5xr85mlfNZXDeGs1W.jpg', 30000.00, 25000.00, 19, 2, 12, 15, 1, 150, 0, 1, 1, '2026-09-23 10:27:18', '2026-10-09 21:13:41'),
(5, 3, 1, 1, 'NvRTX5070', 'Nvidia RTX 5070 12GB', 'Nvidia', 'placota', 'productos/NQIFHFFalpXZ1h8kg48tUJ5xy3afjUlWF9tlI6Wp.jpg', 1300000.00, 1000000.00, 8, 2, 5, 7, 1, 500, 0, 1, 1, '2026-09-23 10:29:33', '2026-09-25 02:58:51'),
(6, 7, 5, 3, 'G502', 'G502 Wireless', 'Logitech', '3202302', 'productos/fCgnxW0GRM6Xe9dfvKR5Gpv5xr85mlfNZXDeGs1W.jpg', 123.00, 12.00, 10, 1, 5, 7, 1, 159, 0, 1, 1, '2026-09-24 08:24:59', '2026-10-03 04:53:58'),
(7, 4, 6, 4, 'RAM8Corsair', 'RAM 8GB', 'Corsair', 'ramcita', NULL, 45000.15, 41000.65, 17, 3, 10, 5, 1, 150, 0, 1, 1, '2026-09-24 08:38:55', '2026-10-09 23:12:09'),
(8, 5, 1, 4, 'RM850eCorsair', 'RM850e Full Modular Cybenetics Gold', 'Corsair', 'fuente', NULL, 50000.00, 30000.00, 16, 11, 1, 2, 1, 500, 0, 0, 1, '2026-09-25 03:41:15', '2026-10-09 03:55:18'),
(9, 7, 5, 3, 'M280', 'M280', 'Logitech', 'sadasd', NULL, 1402151.00, 233.00, 9, 0, 2, 5, 1, 100, 0, 0, 1, '2026-09-25 03:43:05', '2026-09-27 04:40:57'),
(10, 14, 5, 4, 'PC0001', 'PC completa', 'Corsair', NULL, 'productos/6JZKQ0bRCkxFTocf57VZ1eU4v7jNQB0a3J2eUWtB.webp', 1500000.00, 1000000.00, 0, 0, 1, 2, 1, 10000, 0, 1, 1, '2026-09-25 06:18:43', '2026-10-30 02:20:31'),
(11, 15, 5, 11, '348V5LA', 'Notebook HP 4 RAM 1 TB Intel Celeron 14″ W10', 'HP', NULL, 'productos/99eApAeTcJGBBib7T3KSzVfv89nqwS7peTaPJUM4.png', 15000.00, 10000.00, 7, 7, 1, 1, 1, NULL, 0, 1, 1, '2026-09-25 06:32:42', '2026-10-30 01:58:55');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `promociones`
--

CREATE TABLE `promociones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `tipo` enum('porcentaje','monto_fijo','precio_especial') NOT NULL,
  `valor` decimal(12,2) NOT NULL,
  `banner` varchar(255) DEFAULT NULL,
  `canal` enum('ambos','mostrador','online') NOT NULL DEFAULT 'ambos',
  `prioridad` int(11) NOT NULL DEFAULT 0,
  `acumulable` tinyint(1) NOT NULL DEFAULT 0,
  `vigencia_desde` datetime NOT NULL,
  `vigencia_hasta` datetime NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_por` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `promociones`
--

INSERT INTO `promociones` (`id`, `nombre`, `descripcion`, `tipo`, `valor`, `banner`, `canal`, `prioridad`, `acumulable`, `vigencia_desde`, `vigencia_hasta`, `activo`, `creado_por`, `created_at`, `updated_at`) VALUES
(1, 'Procesadores AMD a mitad de precio', 'Todos los procesadores AMD a mitad de precio', 'porcentaje', 15.00, 'promociones/M6lYJuQBgJQfpE8iXTaAB9J2bX2o7zTrt44NMUeF.png', 'ambos', 0, 0, '2026-09-23 00:00:00', '2026-12-13 23:59:00', 1, 2, '2026-09-23 09:05:01', '2026-10-31 01:43:09'),
(2, 'Descuentos Septiembre 2026', 'placas de video', 'porcentaje', 10.00, 'promociones/ELRlvm7tAaqMCju7moFnvWDdQvqFLTBXGOUPpycG.webp', 'ambos', 0, 0, '2026-09-23 00:00:00', '2026-12-13 23:59:00', 1, 2, '2026-09-23 09:10:12', '2026-10-31 01:43:21'),
(3, 'Descuentos otoño 2026', 'sadasds', 'monto_fijo', 15000.00, 'promociones/zBJ0YWHbBqcsL2XPjtfGTgD3x0K7JMnkfWK8vVEv.jpg', 'ambos', 0, 1, '2026-09-24 00:00:00', '2026-12-13 23:59:00', 1, 2, '2026-09-24 08:28:02', '2026-10-31 01:46:28'),
(4, 'log 2026', NULL, 'porcentaje', 15.00, 'promociones/HpoTUruSghKU6ILcpcxBzSCqD1iN0dwzvPGE5BMM.jpg', 'ambos', 0, 0, '2026-10-03 00:00:00', '2026-11-03 23:59:00', 1, 2, '2026-10-03 09:07:47', '2026-10-31 01:44:25'),
(5, 'rtx 5060 en precio especial', 'precio especial', 'precio_especial', 100000.00, 'promociones/93BDOpTnk0cwIKMZLhjtCnvL2qC4btX20bEikHsp.png', 'ambos', 2, 0, '2026-10-09 00:00:00', '2026-11-09 23:59:00', 1, 2, '2026-10-09 03:50:56', '2026-10-09 03:53:51');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `promociones_aplicadas`
--

CREATE TABLE `promociones_aplicadas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `venta_id` bigint(20) UNSIGNED NOT NULL,
  `venta_linea_id` bigint(20) UNSIGNED NOT NULL,
  `promocion_id` bigint(20) UNSIGNED NOT NULL,
  `importe_descuento` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `promociones_aplicadas`
--

INSERT INTO `promociones_aplicadas` (`id`, `venta_id`, `venta_linea_id`, `promocion_id`, `importe_descuento`, `created_at`) VALUES
(1, 3, 3, 2, 15000.00, '2026-09-25 02:58:51'),
(2, 6, 7, 1, 7500.00, '2026-09-25 06:40:03'),
(3, 15, 16, 3, 15000.00, '2026-10-01 04:32:06'),
(4, 19, 20, 2, 15000.00, '2026-10-07 03:18:11'),
(5, 20, 21, 5, 50000.00, '2026-10-09 03:57:27'),
(6, 21, 22, 5, 50000.00, '2026-10-09 20:50:40'),
(7, 22, 23, 3, 30000.00, '2026-10-09 21:13:41'),
(8, 25, 27, 5, 50000.00, '2026-10-09 23:08:38'),
(9, 29, 31, 1, 7500.00, '2026-10-31 06:00:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `promocion_productos`
--

CREATE TABLE `promocion_productos` (
  `promocion_id` bigint(20) UNSIGNED NOT NULL,
  `producto_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `promocion_productos`
--

INSERT INTO `promocion_productos` (`promocion_id`, `producto_id`) VALUES
(1, 1),
(2, 2),
(3, 4),
(4, 4),
(4, 6),
(4, 9),
(5, 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `razon_social` varchar(150) NOT NULL,
  `cuit` varchar(15) NOT NULL,
  `condicion_iva_id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `contacto` varchar(100) DEFAULT NULL,
  `canal_pedido` enum('email','portal_externo','manual') NOT NULL DEFAULT 'email',
  `portal_url` varchar(255) DEFAULT NULL,
  `plazo_entrega_dias` int(11) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `proveedores`
--

INSERT INTO `proveedores` (`id`, `razon_social`, `cuit`, `condicion_iva_id`, `email`, `telefono`, `contacto`, `canal_pedido`, `portal_url`, `plazo_entrega_dias`, `activo`, `created_at`, `updated_at`) VALUES
(1, 'Nvidia', '27100000011', 3, 'nvidia@mail.com', '2971245781', 'Jensen Huang', 'email', NULL, 7, 1, '2026-09-23 08:49:20', '2026-09-23 08:49:20'),
(2, 'AMD', '27100000038', 3, 'amd@mail.com', '2978595959', 'Lisa Su', 'email', NULL, 7, 1, '2026-09-23 08:56:11', '2026-09-23 08:56:11'),
(3, 'Logitech', '27100000046', 3, 'logitech@mail.com', '2975474747', 'Carlitos', 'email', NULL, 3, 1, '2026-09-23 09:00:23', '2026-09-23 19:20:03'),
(4, 'Corsair', '24100000047', 1, 'corsair@mail.com', '2961456456', 'Corsair Dueño', 'email', NULL, 5, 1, '2026-09-24 08:37:32', '2026-09-24 08:37:32'),
(5, 'MSI', '24100000012', 2, 'msi@mail.com', '2971459489', 'MSI dueño', 'email', NULL, 7, 1, '2026-09-25 06:21:34', '2026-09-25 06:21:34'),
(6, 'Asus', '24100000020', 1, 'asus@mail.com', '2971256254', 'Asus Tado', 'email', NULL, 6, 1, '2026-09-25 06:22:45', '2026-09-25 06:22:45'),
(7, 'Genius', '23100000059', 4, 'genius@mail.com', '2973126456', 'Genio Genio', 'manual', NULL, 6, 1, '2026-09-25 06:25:11', '2026-09-25 06:25:11'),
(8, 'Samsung', '23100000040', 3, 'samsung@mail.com', '5979125125', 'chang chung', 'portal_externo', 'https://www.samsung.com/', 7, 1, '2026-09-25 06:26:19', '2026-09-25 06:26:19'),
(9, 'HyperX', '23100000032', 3, 'hyperx@mail.com', '1452632633', 'hyper dueño', 'email', NULL, 6, 1, '2026-09-25 06:28:40', '2026-09-25 06:28:40'),
(10, 'Lenovo', '23100000024', 2, 'lenovo@mail.com', NULL, 'Lenovin', 'portal_externo', 'https://www.lenovo.com/', 6, 1, '2026-09-25 06:29:35', '2026-09-25 06:29:35'),
(11, 'HP', '23100000016', 2, 'hp@mail.com', NULL, 'el hp', 'email', NULL, NULL, 1, '2026-09-25 06:30:14', '2026-09-25 06:30:14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `puntos_venta`
--

CREATE TABLE `puntos_venta` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `numero` smallint(6) NOT NULL,
  `nombre` varchar(60) NOT NULL,
  `tipo_emision` enum('wsfe','manual') NOT NULL DEFAULT 'wsfe',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `puntos_venta`
--

INSERT INTO `puntos_venta` (`id`, `numero`, `nombre`, `tipo_emision`, `activo`, `created_at`, `updated_at`) VALUES
(1, 1, 'Casa central', 'wsfe', 1, '2026-09-22 03:05:45', '2026-09-22 03:05:45');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `ambito` enum('gestion','tienda') NOT NULL,
  `es_sistema` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre`, `descripcion`, `ambito`, `es_sistema`, `created_at`, `updated_at`) VALUES
(1, 'administrador', 'Acceso completo al sistema, sin restricciones.', 'gestion', 1, '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(2, 'administrativo', 'Datos maestros, promociones, empleados, compras y ajustes de inventario.', 'gestion', 1, '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(3, 'vendedor', 'Presupuestos y ventas de mostrador. Valida presupuestos.', 'gestion', 1, '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(4, 'cajero', 'Cobro de ventas confirmadas.', 'gestion', 1, '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(5, 'cliente', 'Compra en la tienda online.', 'tienda', 1, '2026-09-22 03:05:45', '2026-09-22 03:05:45');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol_permiso`
--

CREATE TABLE `rol_permiso` (
  `rol_id` bigint(20) UNSIGNED NOT NULL,
  `permiso_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `rol_permiso`
--

INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(1, 7),
(1, 8),
(1, 9),
(1, 10),
(1, 11),
(1, 12),
(1, 13),
(1, 14),
(1, 15),
(1, 16),
(1, 17),
(1, 18),
(1, 19),
(1, 20),
(1, 21),
(1, 22),
(1, 23),
(1, 24),
(1, 25),
(1, 26),
(1, 27),
(1, 28),
(1, 29),
(1, 30),
(1, 31),
(1, 32),
(1, 33),
(1, 34),
(1, 35),
(1, 36),
(1, 38),
(1, 42),
(1, 43),
(1, 47),
(1, 48),
(1, 49),
(1, 58),
(1, 59),
(1, 60),
(1, 61),
(1, 62),
(1, 63),
(1, 64),
(1, 65),
(1, 66),
(2, 1),
(2, 2),
(2, 3),
(2, 4),
(2, 5),
(2, 6),
(2, 7),
(2, 8),
(2, 9),
(2, 10),
(2, 11),
(2, 12),
(2, 13),
(2, 14),
(2, 15),
(2, 16),
(2, 17),
(2, 18),
(2, 19),
(2, 20),
(2, 21),
(2, 22),
(2, 23),
(2, 24),
(2, 25),
(2, 26),
(2, 27),
(2, 28),
(2, 29),
(2, 30),
(2, 31),
(2, 32),
(2, 33),
(2, 34),
(2, 35),
(2, 36),
(2, 42),
(2, 43),
(2, 47),
(2, 48),
(2, 60),
(2, 63),
(2, 65),
(2, 66),
(3, 1),
(3, 6),
(3, 15),
(3, 24),
(3, 25),
(3, 26),
(3, 34),
(3, 36),
(3, 38),
(3, 42),
(3, 43),
(3, 61),
(3, 62),
(3, 63),
(4, 24),
(4, 25),
(4, 26),
(4, 36),
(4, 42),
(4, 47),
(4, 60),
(4, 64),
(5, 53),
(5, 54),
(5, 55),
(5, 56),
(5, 57);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_comprobante`
--

CREATE TABLE `tipos_comprobante` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `codigo_afip` smallint(6) NOT NULL,
  `nombre` varchar(60) NOT NULL,
  `letra` enum('A','B') NOT NULL,
  `signo` tinyint(4) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tipos_comprobante`
--

INSERT INTO `tipos_comprobante` (`id`, `codigo_afip`, `nombre`, `letra`, `signo`, `created_at`, `updated_at`) VALUES
(1, 1, 'Factura A', 'A', 1, '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(2, 2, 'Nota de Debito A', 'A', 1, '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(3, 3, 'Nota de Credito A', 'A', -1, '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(4, 6, 'Factura B', 'B', 1, '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(5, 7, 'Nota de Debito B', 'B', 1, '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(6, 8, 'Nota de Credito B', 'B', -1, '2026-09-22 03:05:45', '2026-09-22 03:05:45');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_documento`
--

CREATE TABLE `tipos_documento` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `codigo_afip` smallint(6) NOT NULL,
  `nombre` varchar(60) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tipos_documento`
--

INSERT INTO `tipos_documento` (`id`, `codigo_afip`, `nombre`, `created_at`, `updated_at`) VALUES
(1, 80, 'CUIT', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(2, 86, 'CUIL', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(3, 96, 'DNI', '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(4, 99, 'Consumidor final / Sin identificar', '2026-09-22 03:05:45', '2026-09-22 03:05:45');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `rol_id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `nombres` varchar(80) NOT NULL,
  `apellido` varchar(80) NOT NULL,
  `proveedor_auth` enum('local','google') NOT NULL DEFAULT 'local',
  `google_id` varchar(50) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `ultimo_acceso_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `rol_id`, `email`, `password`, `nombres`, `apellido`, `proveedor_auth`, `google_id`, `activo`, `email_verified_at`, `ultimo_acceso_at`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 1, 'admin@powertech.com.ar', '$2y$12$ZG6DzkMjEMokDoGO1mlLd.85/bM5aY00Bnc4D5C1njK/t7ojDSx22', 'Administrador', 'PowerTech', 'local', NULL, 1, '2026-09-22 03:05:45', '2026-09-30 03:17:07', NULL, '2026-09-22 03:05:45', '2026-09-22 03:05:45'),
(2, 2, 'flaco@mail.com', '$2y$12$rdv6WgDR2LBy9PngO434iuOQpxembgwz4jzVadSKxGUL7D0emcUg2', 'Luis Alberto', 'Spinetta', 'local', NULL, 1, '2026-09-23 03:23:17', '2026-10-31 21:45:14', NULL, '2026-09-23 01:03:17', '2026-09-23 03:31:51'),
(3, 4, 'indio@mail.com', '$2y$12$rdv6WgDR2LBy9PngO434iuOQpxembgwz4jzVadSKxGUL7D0emcUg2', 'Carlos Alberto', 'Solari', 'local', NULL, 1, '2026-09-23 03:23:17', '2026-10-30 02:19:29', NULL, '2026-09-23 03:14:18', '2026-09-23 03:23:17'),
(4, 3, 'pelado@mail.com', '$2y$12$rdv6WgDR2LBy9PngO434iuOQpxembgwz4jzVadSKxGUL7D0emcUg2', 'Gustavo Edgardo', 'Cordera', 'local', NULL, 1, '2026-09-23 03:30:34', '2026-10-30 02:18:33', 'B2ZzxUWAtL7PkzytxkmhgoB9dwYGLfZJu4JmBZL0YtPfxM7pGCbGVjpIc5AK', '2026-09-23 03:27:42', '2026-09-25 03:10:12'),
(6, 3, 'musso@mail.com', '$2y$12$KVsI5dZQfQbwTupSPr10Ue3WuRwK6LoutsDRxB4YvLAAkQSpqtkLu', 'Roberto Daniel', 'Musso', 'local', NULL, 1, '2026-09-23 03:23:17', '2026-09-23 03:35:07', NULL, '2026-09-23 04:44:54', '2026-09-23 04:44:54'),
(7, 4, 'pepe.argento@mail.com', '$2y$12$KVsI5dZQfQbwTupSPr10Ue3WuRwK6LoutsDRxB4YvLAAkQSpqtkLu', 'Jose Pepe', 'Argento', 'local', NULL, 1, '2026-09-23 03:23:17', '2026-09-23 03:35:07', NULL, '2026-09-23 05:08:04', '2026-09-23 05:08:04'),
(8, 2, 'messi@mail.com', '$2y$12$rWDma91tFYgjuq4EMtJx5.5kxn8KtFz7dy1LMEjWXWM7pM3AV/DFa', 'Lionel Andrés', 'Messi', 'local', NULL, 1, '2026-09-23 05:54:57', '2026-09-24 08:40:48', NULL, '2026-09-23 05:53:03', '2026-09-23 19:20:44'),
(9, 5, 'fontanet@mail.com', '$2y$12$Dsq8E6NyD.c3vG59bW5R2eKOrf8lqeJVN1la7V3y2t4jVRTKZ/F46', 'Patricio Rogelio', 'Fontanet', 'local', NULL, 1, '2026-09-23 06:20:02', '2026-10-31 23:15:49', NULL, '2026-09-23 06:19:47', '2026-09-23 06:20:02'),
(10, 4, 'chano@mail.com', '$2y$12$F0Vs5.6.XuOskkoRB2NeTux82PsMfWNpllStzf8M6oyCu2nldFTGq', 'Santiago Moreno', 'Charpentier', 'local', NULL, 1, '2026-09-23 10:02:30', '2026-09-23 10:02:31', NULL, '2026-09-23 10:01:51', '2026-09-23 10:02:30'),
(11, 2, 'charly@mail.com', '$2y$12$NHLk/ZG0Mu3jM8u40GFPQO3P.nizl6YNnJqrRvhJIWLoz73bdO9v2', 'Carlos Alberto', 'García', 'local', NULL, 1, '2026-09-23 10:05:16', '2026-09-23 10:05:17', NULL, '2026-09-23 10:05:00', '2026-09-23 10:05:17'),
(12, 3, 'calamaro@mail.com', '$2y$12$UA8oH.AW41GA9CNa6lEGLOAH2P3tiDIkoX.SDn5ZDvcqXMN7vM6Pq', 'Andrés', 'Calamaro', 'local', NULL, 1, '2026-09-23 10:07:38', '2026-10-09 20:49:40', NULL, '2026-09-23 10:07:02', '2026-10-09 20:49:21'),
(13, 3, 'cerati@mail.com', '$2y$12$k05TnHGLzgLjBAaYEKaonuq3TYxfIrusFnvCmY9.YkHH/QKP2qRV2', 'Gustavo Adrián', 'Cerati', 'local', NULL, 1, '2026-09-23 10:09:32', '2026-09-23 10:09:32', NULL, '2026-09-23 10:09:15', '2026-09-23 10:09:32'),
(14, 2, 'iorio@mail.com', '$2y$12$C6CTm.Li9xbOZNco7htn2elmypTjtBcmLhS31dUg5coqvbYUdiLUG', 'Ricardo Horacio', 'Iorio', 'local', NULL, 0, '2026-09-23 10:11:09', '2026-09-23 10:11:10', NULL, '2026-09-23 10:10:52', '2026-10-16 03:53:11'),
(15, 4, 'nuca@mail.com', '$2y$12$L3yet1yknceg6vBhSnJVjuhAO3LIOcO2VuaKxXDFA7lRtY9yBCZLK', 'Luca George', 'Prodan', 'local', NULL, 1, '2026-09-24 08:33:11', '2026-09-24 08:33:11', NULL, '2026-09-24 08:32:30', '2026-09-24 08:33:11'),
(16, 5, 'homero@mail.com', '$2y$12$tFJPuxHepmlKkSbkiv.h7.Wxz.eqlirUMIWlFVSOkySF5puETKS.i', 'Homero', 'Simpson', 'local', NULL, 1, '2026-09-25 20:42:28', '2026-09-25 20:42:04', NULL, '2026-09-25 20:42:02', '2026-09-25 20:42:28'),
(17, 5, 'bart@mail.com', '$2y$12$ESo5NRHxGS3R9X52yNvo5ON//04F17QvEIRQvSUg7xb4/In9l8UjS', 'Bart', 'Simpson', 'local', NULL, 1, '2026-09-25 20:46:42', '2026-09-25 20:46:30', NULL, '2026-09-25 20:46:30', '2026-09-25 20:46:42'),
(18, 5, 'lisa@mail.com', '$2y$12$berI2SphbKf44x0zfwYicu45gQEg04dehdjC.8xgqZJLsauwBIxLi', 'Lisa', 'Simpson', 'local', NULL, 1, '2026-10-09 04:31:27', '2026-10-09 04:31:11', NULL, '2026-10-09 04:31:11', '2026-10-09 04:31:27'),
(19, 5, 'cj@mail.com', '$2y$12$CcR5NnT.oPDgmlf/FPIfrOA3tHsOOfPnfm2CVBVamcGrkBNMn8FYC', 'Carl', 'Jonhson', 'local', NULL, 1, '2026-10-09 22:55:45', '2026-10-09 22:54:41', NULL, '2026-10-09 22:54:41', '2026-10-09 22:55:45'),
(20, 5, 'palito@mail.com', '$2y$12$jYaKb5gKw.h365Elq9Dmzu4eGNEFvhrxV4Q9y9DL5HZfPhPLr/eiG', 'Ramón Bautista', 'Ortega', 'local', NULL, 1, '2026-10-16 03:51:08', '2026-10-16 03:51:09', NULL, '2026-10-16 03:39:21', '2026-10-16 03:51:09'),
(21, 5, 'pappo@mail.com', NULL, 'Norberto Aníbal', 'Napolitano', 'local', NULL, 1, NULL, NULL, NULL, '2026-10-16 03:43:56', '2026-10-16 03:43:56'),
(22, 5, 'manal@mail.com', NULL, 'Javier', 'Martínez', 'local', NULL, 1, NULL, NULL, NULL, '2026-10-16 03:47:42', '2026-10-16 03:47:42'),
(23, 5, 'ciro77@mail.com', NULL, 'Horacio Demián', 'Pertusi', 'local', NULL, 1, NULL, NULL, NULL, '2026-10-16 04:03:32', '2026-10-16 04:03:32'),
(24, 5, 'ciro.piojoso@mail.com', NULL, 'Andrés Ciro', 'Martínez', 'local', NULL, 1, NULL, NULL, NULL, '2026-10-16 04:49:43', '2026-10-16 04:49:43');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

CREATE TABLE `ventas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `numero` int(10) UNSIGNED NOT NULL,
  `canal` enum('mostrador','online') NOT NULL,
  `cliente_id` bigint(20) UNSIGNED DEFAULT NULL,
  `usuario_id` bigint(20) UNSIGNED DEFAULT NULL,
  `estado` enum('presupuesto','confirmada','pagada','pendiente_stock','en_preparacion','despachada','lista_retiro','entregada','devuelta_parcial','devuelta','vencida','cancelada') NOT NULL,
  `modo_entrega` enum('retiro','envio') NOT NULL DEFAULT 'retiro',
  `validez_hasta` date DEFAULT NULL,
  `usuario_valido_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fecha_validacion` datetime DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `descuento_promociones` decimal(12,2) NOT NULL DEFAULT 0.00,
  `costo_envio` decimal(12,2) DEFAULT NULL,
  `neto_gravado` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_iva` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `observaciones` text DEFAULT NULL,
  `fecha` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `ventas`
--

INSERT INTO `ventas` (`id`, `numero`, `canal`, `cliente_id`, `usuario_id`, `estado`, `modo_entrega`, `validez_hasta`, `usuario_valido_id`, `fecha_validacion`, `subtotal`, `descuento_promociones`, `costo_envio`, `neto_gravado`, `total_iva`, `total`, `observaciones`, `fecha`, `created_at`, `updated_at`) VALUES
(1, 1, 'mostrador', 2, 4, 'confirmada', 'envio', NULL, 4, '2026-09-24 21:44:17', 1300000.00, 0.00, 15000.00, 1312396.69, 2603.31, 1315000.00, 'prueba 2', '2026-09-24 21:44:17', '2026-09-25 00:44:17', '2026-09-25 00:44:17'),
(2, 2, 'mostrador', 2, 4, 'confirmada', 'envio', NULL, 4, '2026-09-24 22:19:41', 123.00, 0.00, 15000.00, 12498.34, 2624.66, 15123.00, NULL, '2026-09-24 22:19:41', '2026-09-25 01:19:41', '2026-09-25 01:19:41'),
(3, 3, 'mostrador', 2, 4, 'confirmada', 'envio', NULL, 4, '2026-09-24 23:58:51', 1450000.00, 15000.00, 15000.00, 1447396.69, 2603.31, 1450000.00, NULL, '2026-09-24 23:58:51', '2026-09-25 02:58:51', '2026-09-25 02:58:51'),
(4, 4, 'mostrador', 2, 4, 'en_preparacion', 'retiro', NULL, 4, '2026-09-25 00:06:44', 375000.00, 0.00, 0.00, 375000.00, 0.00, 375000.00, NULL, '2026-09-25 00:06:44', '2026-09-25 03:06:44', '2026-10-03 05:37:48'),
(5, 5, 'mostrador', 2, 4, 'lista_retiro', 'retiro', NULL, 4, '2026-09-25 03:39:46', 30000.00, 0.00, 0.00, 24793.39, 5206.61, 30000.00, NULL, '2026-09-25 03:39:46', '2026-09-25 06:39:46', '2026-10-08 01:54:57'),
(6, 6, 'mostrador', 2, 4, 'presupuesto', 'retiro', '2026-10-02', NULL, NULL, 50000.00, 7500.00, 0.00, 42500.00, 0.00, 42500.00, NULL, '2026-09-25 03:40:03', '2026-09-25 06:40:03', '2026-09-25 06:40:03'),
(7, 7, 'mostrador', 2, 4, 'entregada', 'retiro', '2026-10-04', 4, '2026-09-27 01:37:09', 45000.15, 0.00, 0.00, 35433.19, 9566.96, 45000.15, NULL, '2026-09-27 01:37:04', '2026-09-27 04:37:04', '2026-10-08 01:54:51'),
(8, 8, 'mostrador', 2, 4, 'entregada', 'retiro', '2026-10-04', 4, '2026-09-27 01:39:12', 1402151.00, 0.00, 0.00, 1158802.48, 243348.52, 1402151.00, NULL, '2026-09-27 01:39:09', '2026-09-27 04:39:09', '2026-09-27 04:40:57'),
(9, 9, 'mostrador', 2, 4, 'en_preparacion', 'retiro', NULL, 4, '2026-09-30 23:45:58', 100000.00, 0.00, 0.00, 100000.00, 0.00, 100000.00, NULL, '2026-09-30 23:45:58', '2026-10-01 02:45:58', '2026-10-08 02:11:02'),
(10, 10, 'mostrador', 2, 4, 'confirmada', 'retiro', NULL, 4, '2026-09-30 23:47:39', 200000.00, 0.00, 0.00, 200000.00, 0.00, 200000.00, NULL, '2026-09-30 23:47:39', '2026-10-01 02:47:39', '2026-10-01 02:47:39'),
(11, 11, 'mostrador', 2, 4, 'confirmada', 'retiro', NULL, 4, '2026-09-30 23:49:25', 100000.00, 0.00, 0.00, 100000.00, 0.00, 100000.00, NULL, '2026-09-30 23:49:25', '2026-10-01 02:49:25', '2026-10-01 02:49:25'),
(12, 12, 'mostrador', 2, 4, 'confirmada', 'retiro', NULL, 4, '2026-10-01 01:13:03', 50000.00, 0.00, 0.00, 50000.00, 0.00, 50000.00, NULL, '2026-10-01 01:13:03', '2026-10-01 04:13:03', '2026-10-01 04:13:03'),
(13, 13, 'mostrador', 2, 4, 'confirmada', 'retiro', NULL, 4, '2026-10-01 01:17:19', 50000.00, 0.00, 0.00, 50000.00, 0.00, 50000.00, NULL, '2026-10-01 01:17:19', '2026-10-01 04:17:19', '2026-10-01 04:17:19'),
(14, 14, 'mostrador', 2, 4, 'entregada', 'retiro', NULL, 4, '2026-10-01 01:17:37', 50000.00, 0.00, 0.00, 50000.00, 0.00, 50000.00, NULL, '2026-10-01 01:17:37', '2026-10-01 04:17:37', '2026-10-01 04:35:08'),
(15, 15, 'mostrador', 2, 4, 'entregada', 'retiro', NULL, 4, '2026-10-01 01:32:06', 30000.00, 15000.00, 0.00, 15000.00, 0.00, 15000.00, NULL, '2026-10-01 01:32:06', '2026-10-01 04:32:06', '2026-10-05 09:15:07'),
(16, 16, 'mostrador', 2, 4, 'confirmada', 'retiro', NULL, 4, '2026-10-01 01:56:19', 50000.00, 0.00, 0.00, 50000.00, 0.00, 50000.00, NULL, '2026-10-01 01:56:19', '2026-10-01 04:56:19', '2026-10-01 04:56:19'),
(17, 17, 'mostrador', 2, 4, 'entregada', 'retiro', NULL, 4, '2026-10-01 03:20:13', 50000.00, 0.00, 0.00, 50000.00, 0.00, 50000.00, NULL, '2026-10-01 03:20:13', '2026-10-01 06:20:13', '2026-10-09 03:55:19'),
(18, 18, 'mostrador', 2, 4, 'devuelta', 'retiro', NULL, 4, '2026-10-01 03:21:23', 100000.00, 0.00, 0.00, 100000.00, 0.00, 100000.00, NULL, '2026-10-01 03:21:23', '2026-10-01 06:21:23', '2026-10-07 03:20:26'),
(19, 19, 'mostrador', 2, 4, 'cancelada', 'retiro', NULL, 4, '2026-10-07 00:18:11', 150000.00, 15000.00, 0.00, 135000.00, 0.00, 135000.00, '[07/10/2026 00:18] Cancelada: missclick', '2026-10-07 00:18:11', '2026-10-07 03:18:11', '2026-10-07 03:18:27'),
(20, 20, 'mostrador', 2, 4, 'devuelta', 'retiro', NULL, 4, '2026-10-09 00:57:27', 150000.00, 50000.00, 0.00, 100000.00, 0.00, 100000.00, NULL, '2026-10-09 00:57:27', '2026-10-09 03:57:27', '2026-10-09 03:59:14'),
(21, 21, 'mostrador', 2, 12, 'entregada', 'retiro', NULL, 12, '2026-10-09 17:50:40', 150000.00, 50000.00, 0.00, 100000.00, 0.00, 100000.00, NULL, '2026-10-09 17:50:40', '2026-10-09 20:50:40', '2026-10-09 20:52:28'),
(22, 22, 'mostrador', 2, 4, 'confirmada', 'retiro', NULL, 4, '2026-10-09 18:13:41', 60000.00, 30000.00, 0.00, 30000.00, 0.00, 30000.00, NULL, '2026-10-09 18:13:41', '2026-10-09 21:13:41', '2026-10-09 21:13:41'),
(23, 23, 'mostrador', NULL, 4, 'cancelada', 'retiro', NULL, 4, '2026-10-09 18:14:45', 90000.30, 0.00, 0.00, 70866.38, 19133.92, 90000.30, '[09/10/2026 18:15] Cancelada: error', '2026-10-09 18:14:45', '2026-10-09 21:14:45', '2026-10-09 21:15:02'),
(24, 24, 'mostrador', 2, 4, 'confirmada', 'retiro', NULL, 4, '2026-10-09 18:16:18', 135000.45, 0.00, 0.00, 106299.57, 28700.88, 135000.45, NULL, '2026-10-09 18:16:18', '2026-10-09 21:16:18', '2026-10-09 21:16:18'),
(25, 25, 'mostrador', 2, 4, 'entregada', 'retiro', NULL, 4, '2026-10-09 20:08:38', 330000.60, 50000.00, 0.00, 241732.76, 38267.84, 280000.60, NULL, '2026-10-09 20:08:38', '2026-10-09 23:08:38', '2026-10-09 23:12:09'),
(26, 26, 'mostrador', 2, 4, 'confirmada', 'retiro', NULL, 4, '2026-10-18 01:45:59', 75000.00, 0.00, 0.00, 61983.47, 13016.53, 75000.00, 'kpo', '2026-10-18 01:45:59', '2026-10-18 04:45:59', '2026-10-18 04:45:59'),
(27, 27, 'mostrador', 2, 4, 'entregada', 'retiro', NULL, 4, '2026-10-29 22:58:16', 45000.00, 0.00, 0.00, 37190.08, 7809.92, 45000.00, NULL, '2026-10-29 22:58:16', '2026-10-30 01:58:16', '2026-10-30 01:58:55'),
(28, 28, 'mostrador', 2, 4, 'entregada', 'retiro', NULL, 4, '2026-10-29 23:19:18', 9000000.00, 0.00, 0.00, 7438016.53, 1561983.47, 9000000.00, NULL, '2026-10-29 23:19:18', '2026-10-30 02:19:18', '2026-10-30 02:20:31'),
(29, 29, 'online', 2, NULL, 'lista_retiro', 'retiro', NULL, NULL, NULL, 50000.00, 7500.00, 0.00, 42500.00, 0.00, 42500.00, NULL, '2026-10-31 03:00:12', '2026-10-31 06:00:12', '2026-10-31 06:01:04');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `venta_lineas`
--

CREATE TABLE `venta_lineas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `venta_id` bigint(20) UNSIGNED NOT NULL,
  `producto_id` bigint(20) UNSIGNED NOT NULL,
  `descripcion` varchar(150) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `cantidad_devuelta` int(11) NOT NULL DEFAULT 0,
  `precio_unitario` decimal(12,2) NOT NULL,
  `descuento_promocion` decimal(12,2) NOT NULL DEFAULT 0.00,
  `alicuota_iva` decimal(4,2) NOT NULL,
  `costo_unitario` decimal(12,2) DEFAULT NULL,
  `neto` decimal(12,2) NOT NULL,
  `iva` decimal(12,2) NOT NULL,
  `total` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `venta_lineas`
--

INSERT INTO `venta_lineas` (`id`, `venta_id`, `producto_id`, `descripcion`, `cantidad`, `cantidad_devuelta`, `precio_unitario`, `descuento_promocion`, `alicuota_iva`, `costo_unitario`, `neto`, `iva`, `total`, `created_at`, `updated_at`) VALUES
(1, 1, 5, 'Nvidia RTX 5070 12GB', 1, 0, 1300000.00, 0.00, 0.00, 1000000.00, 1300000.00, 0.00, 1300000.00, '2026-09-25 00:44:17', '2026-09-25 00:44:17'),
(2, 2, 6, 'G502 Wireless', 1, 0, 123.00, 0.00, 21.00, 12.00, 101.65, 21.35, 123.00, '2026-09-25 01:19:41', '2026-09-25 01:19:41'),
(3, 3, 2, 'Nvidia RTX 5060 8GB', 1, 0, 150000.00, 15000.00, 0.00, 100000.00, 135000.00, 0.00, 135000.00, '2026-09-25 02:58:51', '2026-09-25 02:58:51'),
(4, 3, 5, 'Nvidia RTX 5070 12GB', 1, 0, 1300000.00, 0.00, 0.00, 1000000.00, 1300000.00, 0.00, 1300000.00, '2026-09-25 02:58:51', '2026-09-25 02:58:51'),
(5, 4, 3, 'Ryzen 5 5400G', 5, 0, 75000.00, 0.00, 0.00, 50000.00, 375000.00, 0.00, 375000.00, '2026-09-25 03:06:44', '2026-09-25 03:06:44'),
(6, 5, 11, 'Notebook HP 4 RAM 1 TB Intel Celeron 14″ W10', 2, 0, 15000.00, 0.00, 21.00, 10000.00, 24793.39, 5206.61, 30000.00, '2026-09-25 06:39:46', '2026-09-25 06:39:46'),
(7, 6, 1, 'Ryzen 5 5600X', 1, 0, 50000.00, 7500.00, 0.00, 25000.00, 42500.00, 0.00, 42500.00, '2026-09-25 06:40:03', '2026-09-25 06:40:03'),
(8, 7, 7, 'RAM 8GB', 1, 0, 45000.15, 0.00, 27.00, 41000.65, 35433.19, 9566.96, 45000.15, '2026-09-27 04:37:04', '2026-09-27 04:37:04'),
(9, 8, 9, 'M280', 1, 0, 1402151.00, 0.00, 21.00, 233.00, 1158802.48, 243348.52, 1402151.00, '2026-09-27 04:39:09', '2026-09-27 04:39:09'),
(10, 9, 8, 'RM850e Full Modular Cybenetics Gold', 2, 0, 50000.00, 0.00, 0.00, 30000.00, 100000.00, 0.00, 100000.00, '2026-10-01 02:45:58', '2026-10-01 02:45:58'),
(11, 10, 8, 'RM850e Full Modular Cybenetics Gold', 4, 0, 50000.00, 0.00, 0.00, 30000.00, 200000.00, 0.00, 200000.00, '2026-10-01 02:47:39', '2026-10-01 02:47:39'),
(12, 11, 8, 'RM850e Full Modular Cybenetics Gold', 2, 0, 50000.00, 0.00, 0.00, 30000.00, 100000.00, 0.00, 100000.00, '2026-10-01 02:49:25', '2026-10-01 02:49:25'),
(13, 12, 8, 'RM850e Full Modular Cybenetics Gold', 1, 0, 50000.00, 0.00, 0.00, 30000.00, 50000.00, 0.00, 50000.00, '2026-10-01 04:13:03', '2026-10-01 04:13:03'),
(14, 13, 8, 'RM850e Full Modular Cybenetics Gold', 1, 0, 50000.00, 0.00, 0.00, 30000.00, 50000.00, 0.00, 50000.00, '2026-10-01 04:17:19', '2026-10-01 04:17:19'),
(15, 14, 8, 'RM850e Full Modular Cybenetics Gold', 1, 0, 50000.00, 0.00, 0.00, 30000.00, 50000.00, 0.00, 50000.00, '2026-10-01 04:17:37', '2026-10-01 04:17:37'),
(16, 15, 4, 'G203 RGB LightSync', 1, 0, 30000.00, 15000.00, 0.00, 25000.00, 15000.00, 0.00, 15000.00, '2026-10-01 04:32:06', '2026-10-01 04:32:06'),
(17, 16, 8, 'RM850e Full Modular Cybenetics Gold', 1, 0, 50000.00, 0.00, 0.00, 30000.00, 50000.00, 0.00, 50000.00, '2026-10-01 04:56:19', '2026-10-01 04:56:19'),
(18, 17, 8, 'RM850e Full Modular Cybenetics Gold', 1, 0, 50000.00, 0.00, 0.00, 30000.00, 50000.00, 0.00, 50000.00, '2026-10-01 06:20:13', '2026-10-01 06:20:13'),
(19, 18, 8, 'RM850e Full Modular Cybenetics Gold', 2, 2, 50000.00, 0.00, 0.00, 30000.00, 100000.00, 0.00, 100000.00, '2026-10-01 06:21:23', '2026-10-07 03:20:26'),
(20, 19, 2, 'Nvidia RTX 5060 8GB', 1, 0, 150000.00, 15000.00, 0.00, 100000.00, 135000.00, 0.00, 135000.00, '2026-10-07 03:18:11', '2026-10-07 03:18:11'),
(21, 20, 2, 'Nvidia RTX 5060 8GB', 1, 1, 150000.00, 50000.00, 0.00, 100000.00, 100000.00, 0.00, 100000.00, '2026-10-09 03:57:27', '2026-10-09 03:59:14'),
(22, 21, 2, 'Nvidia RTX 5060 8GB', 1, 0, 150000.00, 50000.00, 0.00, 100000.00, 100000.00, 0.00, 100000.00, '2026-10-09 20:50:40', '2026-10-09 20:50:40'),
(23, 22, 4, 'G203 RGB LightSync', 2, 0, 30000.00, 30000.00, 0.00, 25000.00, 30000.00, 0.00, 30000.00, '2026-10-09 21:13:41', '2026-10-09 21:13:41'),
(24, 23, 7, 'RAM 8GB', 2, 0, 45000.15, 0.00, 27.00, 41000.65, 70866.38, 19133.92, 90000.30, '2026-10-09 21:14:45', '2026-10-09 21:14:45'),
(25, 24, 7, 'RAM 8GB', 3, 0, 45000.15, 0.00, 27.00, 41000.65, 106299.57, 28700.88, 135000.45, '2026-10-09 21:16:18', '2026-10-09 21:16:18'),
(26, 25, 7, 'RAM 8GB', 4, 0, 45000.15, 0.00, 27.00, 41000.65, 141732.76, 38267.84, 180000.60, '2026-10-09 23:08:38', '2026-10-09 23:08:38'),
(27, 25, 2, 'Nvidia RTX 5060 8GB', 1, 0, 150000.00, 50000.00, 0.00, 100000.00, 100000.00, 0.00, 100000.00, '2026-10-09 23:08:38', '2026-10-09 23:08:38'),
(28, 26, 11, 'Notebook HP 4 RAM 1 TB Intel Celeron 14″ W10', 5, 0, 15000.00, 0.00, 21.00, 10000.00, 61983.47, 13016.53, 75000.00, '2026-10-18 04:45:59', '2026-10-18 04:45:59'),
(29, 27, 11, 'Notebook HP 4 RAM 1 TB Intel Celeron 14″ W10', 3, 0, 15000.00, 0.00, 21.00, 10000.00, 37190.08, 7809.92, 45000.00, '2026-10-30 01:58:16', '2026-10-30 01:58:16'),
(30, 28, 10, 'PC completa', 6, 0, 1500000.00, 0.00, 21.00, 1000000.00, 7438016.53, 1561983.47, 9000000.00, '2026-10-30 02:19:18', '2026-10-30 02:19:18'),
(31, 29, 1, 'Ryzen 5 5600X', 1, 0, 50000.00, 7500.00, 0.00, 25000.00, 42500.00, 0.00, 42500.00, '2026-10-31 06:00:12', '2026-10-31 06:00:12');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `afip_solicitudes`
--
ALTER TABLE `afip_solicitudes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `afip_solicitudes_comprobante_id_created_at_index` (`comprobante_id`,`created_at`);

--
-- Indices de la tabla `alicuotas_iva`
--
ALTER TABLE `alicuotas_iva`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `alicuotas_iva_codigo_afip_unique` (`codigo_afip`);

--
-- Indices de la tabla `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indices de la tabla `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indices de la tabla `carritos`
--
ALTER TABLE `carritos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `carritos_session_token_index` (`session_token`),
  ADD KEY `carritos_user_id_estado_index` (`user_id`,`estado`);

--
-- Indices de la tabla `carrito_lineas`
--
ALTER TABLE `carrito_lineas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `carrito_lineas_carrito_id_producto_id_unique` (`carrito_id`,`producto_id`),
  ADD KEY `carrito_lineas_producto_id_foreign` (`producto_id`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `categorias_nombre_unique` (`nombre`),
  ADD UNIQUE KEY `categorias_slug_unique` (`slug`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clientes_tipo_documento_id_nro_doc_unique` (`tipo_documento_id`,`nro_doc`),
  ADD UNIQUE KEY `clientes_user_id_unique` (`user_id`),
  ADD KEY `clientes_condicion_iva_id_foreign` (`condicion_iva_id`);

--
-- Indices de la tabla `comprobantes`
--
ALTER TABLE `comprobantes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `comprobantes_correlativo_unique` (`tipo_comprobante_id`,`punto_venta_id`,`numero`),
  ADD KEY `comprobantes_venta_id_foreign` (`venta_id`),
  ADD KEY `comprobantes_punto_venta_id_foreign` (`punto_venta_id`),
  ADD KEY `comprobantes_comprobante_asociado_id_foreign` (`comprobante_asociado_id`),
  ADD KEY `comprobantes_cliente_id_foreign` (`cliente_id`),
  ADD KEY `comprobantes_tipo_documento_id_foreign` (`tipo_documento_id`),
  ADD KEY `comprobantes_condicion_iva_id_foreign` (`condicion_iva_id`);

--
-- Indices de la tabla `comprobante_lineas`
--
ALTER TABLE `comprobante_lineas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `comprobante_lineas_comprobante_id_foreign` (`comprobante_id`),
  ADD KEY `comprobante_lineas_producto_id_foreign` (`producto_id`);

--
-- Indices de la tabla `condiciones_iva`
--
ALTER TABLE `condiciones_iva`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `condiciones_iva_codigo_afip_unique` (`codigo_afip`);

--
-- Indices de la tabla `direcciones`
--
ALTER TABLE `direcciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `direcciones_direccionable_index` (`direccionable_type`,`direccionable_id`);

--
-- Indices de la tabla `empleados`
--
ALTER TABLE `empleados`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `empleados_user_id_unique` (`user_id`),
  ADD UNIQUE KEY `empleados_legajo_unique` (`legajo`),
  ADD KEY `empleados_tipo_documento_id_foreign` (`tipo_documento_id`);

--
-- Indices de la tabla `envios`
--
ALTER TABLE `envios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `envios_venta_id_unique` (`venta_id`);

--
-- Indices de la tabla `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indices de la tabla `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indices de la tabla `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `movimientos_stock`
--
ALTER TABLE `movimientos_stock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `movimientos_stock_usuario_id_foreign` (`usuario_id`),
  ADD KEY `movimientos_stock_producto_id_created_at_index` (`producto_id`,`created_at`),
  ADD KEY `movimientos_stock_origen_index` (`origen_type`,`origen_id`);

--
-- Indices de la tabla `numeradores`
--
ALTER TABLE `numeradores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numeradores_serie_unique` (`serie`);

--
-- Indices de la tabla `ordenes_compra`
--
ALTER TABLE `ordenes_compra`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ordenes_compra_numero_unique` (`numero`),
  ADD KEY `ordenes_compra_proveedor_id_foreign` (`proveedor_id`),
  ADD KEY `ordenes_compra_usuario_creo_id_foreign` (`usuario_creo_id`),
  ADD KEY `ordenes_compra_usuario_aprobo_id_foreign` (`usuario_aprobo_id`),
  ADD KEY `ordenes_compra_estado_proveedor_id_index` (`estado`,`proveedor_id`),
  ADD KEY `ordenes_compra_orden_padre_id_foreign` (`orden_padre_id`);

--
-- Indices de la tabla `orden_compra_lineas`
--
ALTER TABLE `orden_compra_lineas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `orden_compra_lineas_orden_compra_id_foreign` (`orden_compra_id`),
  ADD KEY `orden_compra_lineas_producto_id_index` (`producto_id`),
  ADD KEY `orden_compra_lineas_proveedor_id_foreign` (`proveedor_id`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pagos_mp_payment_id_unique` (`mp_payment_id`),
  ADD KEY `pagos_venta_id_foreign` (`venta_id`),
  ADD KEY `pagos_usuario_id_fecha_index` (`usuario_id`,`fecha`);

--
-- Indices de la tabla `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indices de la tabla `permisos`
--
ALTER TABLE `permisos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permisos_clave_unique` (`clave`),
  ADD KEY `permisos_modulo_index` (`modulo`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `productos_codigo_unique` (`codigo`),
  ADD KEY `productos_categoria_id_foreign` (`categoria_id`),
  ADD KEY `productos_alicuota_iva_id_foreign` (`alicuota_iva_id`),
  ADD KEY `productos_proveedor_id_foreign` (`proveedor_id`),
  ADD KEY `productos_marca_index` (`marca`),
  ADD KEY `productos_stock_disponible_index` (`stock_disponible`);

--
-- Indices de la tabla `promociones`
--
ALTER TABLE `promociones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `promociones_creado_por_foreign` (`creado_por`),
  ADD KEY `promociones_activo_vigencia_desde_vigencia_hasta_index` (`activo`,`vigencia_desde`,`vigencia_hasta`);

--
-- Indices de la tabla `promociones_aplicadas`
--
ALTER TABLE `promociones_aplicadas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `promociones_aplicadas_venta_id_foreign` (`venta_id`),
  ADD KEY `promociones_aplicadas_venta_linea_id_foreign` (`venta_linea_id`),
  ADD KEY `promociones_aplicadas_promocion_id_foreign` (`promocion_id`);

--
-- Indices de la tabla `promocion_productos`
--
ALTER TABLE `promocion_productos`
  ADD PRIMARY KEY (`promocion_id`,`producto_id`),
  ADD KEY `promocion_productos_producto_id_foreign` (`producto_id`);

--
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `proveedores_cuit_unique` (`cuit`),
  ADD KEY `proveedores_condicion_iva_id_foreign` (`condicion_iva_id`);

--
-- Indices de la tabla `puntos_venta`
--
ALTER TABLE `puntos_venta`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `puntos_venta_numero_unique` (`numero`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_nombre_unique` (`nombre`);

--
-- Indices de la tabla `rol_permiso`
--
ALTER TABLE `rol_permiso`
  ADD PRIMARY KEY (`rol_id`,`permiso_id`),
  ADD KEY `rol_permiso_permiso_id_foreign` (`permiso_id`);

--
-- Indices de la tabla `tipos_comprobante`
--
ALTER TABLE `tipos_comprobante`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tipos_comprobante_codigo_afip_unique` (`codigo_afip`);

--
-- Indices de la tabla `tipos_documento`
--
ALTER TABLE `tipos_documento`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tipos_documento_codigo_afip_unique` (`codigo_afip`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_google_id_unique` (`google_id`),
  ADD KEY `users_rol_id_foreign` (`rol_id`);

--
-- Indices de la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ventas_numero_unique` (`numero`),
  ADD KEY `ventas_cliente_id_foreign` (`cliente_id`),
  ADD KEY `ventas_usuario_valido_id_foreign` (`usuario_valido_id`),
  ADD KEY `ventas_usuario_id_fecha_index` (`usuario_id`,`fecha`),
  ADD KEY `ventas_estado_fecha_index` (`estado`,`fecha`);

--
-- Indices de la tabla `venta_lineas`
--
ALTER TABLE `venta_lineas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `venta_lineas_venta_id_foreign` (`venta_id`),
  ADD KEY `venta_lineas_producto_id_foreign` (`producto_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `afip_solicitudes`
--
ALTER TABLE `afip_solicitudes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de la tabla `alicuotas_iva`
--
ALTER TABLE `alicuotas_iva`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `carritos`
--
ALTER TABLE `carritos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `carrito_lineas`
--
ALTER TABLE `carrito_lineas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `comprobantes`
--
ALTER TABLE `comprobantes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de la tabla `comprobante_lineas`
--
ALTER TABLE `comprobante_lineas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de la tabla `condiciones_iva`
--
ALTER TABLE `condiciones_iva`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `direcciones`
--
ALTER TABLE `direcciones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `empleados`
--
ALTER TABLE `empleados`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `envios`
--
ALTER TABLE `envios`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT de la tabla `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT de la tabla `movimientos_stock`
--
ALTER TABLE `movimientos_stock`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT de la tabla `numeradores`
--
ALTER TABLE `numeradores`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `ordenes_compra`
--
ALTER TABLE `ordenes_compra`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de la tabla `orden_compra_lineas`
--
ALTER TABLE `orden_compra_lineas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT de la tabla `permisos`
--
ALTER TABLE `permisos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `promociones`
--
ALTER TABLE `promociones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `promociones_aplicadas`
--
ALTER TABLE `promociones_aplicadas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `puntos_venta`
--
ALTER TABLE `puntos_venta`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `tipos_comprobante`
--
ALTER TABLE `tipos_comprobante`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `tipos_documento`
--
ALTER TABLE `tipos_documento`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de la tabla `venta_lineas`
--
ALTER TABLE `venta_lineas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `afip_solicitudes`
--
ALTER TABLE `afip_solicitudes`
  ADD CONSTRAINT `afip_solicitudes_comprobante_id_foreign` FOREIGN KEY (`comprobante_id`) REFERENCES `comprobantes` (`id`);

--
-- Filtros para la tabla `carritos`
--
ALTER TABLE `carritos`
  ADD CONSTRAINT `carritos_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `carrito_lineas`
--
ALTER TABLE `carrito_lineas`
  ADD CONSTRAINT `carrito_lineas_carrito_id_foreign` FOREIGN KEY (`carrito_id`) REFERENCES `carritos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `carrito_lineas_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD CONSTRAINT `clientes_condicion_iva_id_foreign` FOREIGN KEY (`condicion_iva_id`) REFERENCES `condiciones_iva` (`id`),
  ADD CONSTRAINT `clientes_tipo_documento_id_foreign` FOREIGN KEY (`tipo_documento_id`) REFERENCES `tipos_documento` (`id`),
  ADD CONSTRAINT `clientes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `comprobantes`
--
ALTER TABLE `comprobantes`
  ADD CONSTRAINT `comprobantes_cliente_id_foreign` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  ADD CONSTRAINT `comprobantes_comprobante_asociado_id_foreign` FOREIGN KEY (`comprobante_asociado_id`) REFERENCES `comprobantes` (`id`),
  ADD CONSTRAINT `comprobantes_condicion_iva_id_foreign` FOREIGN KEY (`condicion_iva_id`) REFERENCES `condiciones_iva` (`id`),
  ADD CONSTRAINT `comprobantes_punto_venta_id_foreign` FOREIGN KEY (`punto_venta_id`) REFERENCES `puntos_venta` (`id`),
  ADD CONSTRAINT `comprobantes_tipo_comprobante_id_foreign` FOREIGN KEY (`tipo_comprobante_id`) REFERENCES `tipos_comprobante` (`id`),
  ADD CONSTRAINT `comprobantes_tipo_documento_id_foreign` FOREIGN KEY (`tipo_documento_id`) REFERENCES `tipos_documento` (`id`),
  ADD CONSTRAINT `comprobantes_venta_id_foreign` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`);

--
-- Filtros para la tabla `comprobante_lineas`
--
ALTER TABLE `comprobante_lineas`
  ADD CONSTRAINT `comprobante_lineas_comprobante_id_foreign` FOREIGN KEY (`comprobante_id`) REFERENCES `comprobantes` (`id`),
  ADD CONSTRAINT `comprobante_lineas_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `empleados`
--
ALTER TABLE `empleados`
  ADD CONSTRAINT `empleados_tipo_documento_id_foreign` FOREIGN KEY (`tipo_documento_id`) REFERENCES `tipos_documento` (`id`),
  ADD CONSTRAINT `empleados_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `envios`
--
ALTER TABLE `envios`
  ADD CONSTRAINT `envios_venta_id_foreign` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`);

--
-- Filtros para la tabla `movimientos_stock`
--
ALTER TABLE `movimientos_stock`
  ADD CONSTRAINT `movimientos_stock_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`),
  ADD CONSTRAINT `movimientos_stock_usuario_id_foreign` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `ordenes_compra`
--
ALTER TABLE `ordenes_compra`
  ADD CONSTRAINT `ordenes_compra_orden_padre_id_foreign` FOREIGN KEY (`orden_padre_id`) REFERENCES `ordenes_compra` (`id`),
  ADD CONSTRAINT `ordenes_compra_proveedor_id_foreign` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`),
  ADD CONSTRAINT `ordenes_compra_usuario_aprobo_id_foreign` FOREIGN KEY (`usuario_aprobo_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `ordenes_compra_usuario_creo_id_foreign` FOREIGN KEY (`usuario_creo_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `orden_compra_lineas`
--
ALTER TABLE `orden_compra_lineas`
  ADD CONSTRAINT `orden_compra_lineas_orden_compra_id_foreign` FOREIGN KEY (`orden_compra_id`) REFERENCES `ordenes_compra` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orden_compra_lineas_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`),
  ADD CONSTRAINT `orden_compra_lineas_proveedor_id_foreign` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`);

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `pagos_usuario_id_foreign` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `pagos_venta_id_foreign` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`);

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `productos_alicuota_iva_id_foreign` FOREIGN KEY (`alicuota_iva_id`) REFERENCES `alicuotas_iva` (`id`),
  ADD CONSTRAINT `productos_categoria_id_foreign` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`),
  ADD CONSTRAINT `productos_proveedor_id_foreign` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`);

--
-- Filtros para la tabla `promociones`
--
ALTER TABLE `promociones`
  ADD CONSTRAINT `promociones_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `promociones_aplicadas`
--
ALTER TABLE `promociones_aplicadas`
  ADD CONSTRAINT `promociones_aplicadas_promocion_id_foreign` FOREIGN KEY (`promocion_id`) REFERENCES `promociones` (`id`),
  ADD CONSTRAINT `promociones_aplicadas_venta_id_foreign` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`),
  ADD CONSTRAINT `promociones_aplicadas_venta_linea_id_foreign` FOREIGN KEY (`venta_linea_id`) REFERENCES `venta_lineas` (`id`);

--
-- Filtros para la tabla `promocion_productos`
--
ALTER TABLE `promocion_productos`
  ADD CONSTRAINT `promocion_productos_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`),
  ADD CONSTRAINT `promocion_productos_promocion_id_foreign` FOREIGN KEY (`promocion_id`) REFERENCES `promociones` (`id`);

--
-- Filtros para la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD CONSTRAINT `proveedores_condicion_iva_id_foreign` FOREIGN KEY (`condicion_iva_id`) REFERENCES `condiciones_iva` (`id`);

--
-- Filtros para la tabla `rol_permiso`
--
ALTER TABLE `rol_permiso`
  ADD CONSTRAINT `rol_permiso_permiso_id_foreign` FOREIGN KEY (`permiso_id`) REFERENCES `permisos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rol_permiso_rol_id_foreign` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_rol_id_foreign` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`);

--
-- Filtros para la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD CONSTRAINT `ventas_cliente_id_foreign` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  ADD CONSTRAINT `ventas_usuario_id_foreign` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `ventas_usuario_valido_id_foreign` FOREIGN KEY (`usuario_valido_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `venta_lineas`
--
ALTER TABLE `venta_lineas`
  ADD CONSTRAINT `venta_lineas_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`),
  ADD CONSTRAINT `venta_lineas_venta_id_foreign` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
