-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 06-10-2026 a las 22:07:58
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
(13, 13, 'FECAESolicitar (SIMULADO)', '{\"CbteTipo\":6,\"PtoVta\":1,\"CbteDesde\":6,\"CbteHasta\":6,\"DocTipo\":96,\"DocNro\":\"14514514\",\"CbteFch\":\"20261005\",\"ImpNeto\":15000,\"ImpIVA\":0,\"ImpTotal\":15000}', '{\"Resultado\":\"A\",\"CAE\":\"56127616389477\",\"CAEFchVto\":\"20261015\",\"Observaciones\":\"Comprobante emitido por el autorizador simulado. Sin validez fiscal.\"}', 'A', NULL, NULL, 1, '2026-10-05 09:14:29');

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
(4, 17, 'Simpson Bart', 3, '12345676', 3, NULL, 1, '2026-09-25 20:46:30', '2026-09-25 20:46:30');

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
(13, 15, 4, 1, 6, NULL, 2, 3, '14514514', 3, '2026-10-05 06:14:29', 15000.00, 0.00, 15000.00, '56127616389477', '2026-10-15', 'autorizado', NULL, '2026-10-05 09:14:29', '2026-10-05 09:14:29');

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
(13, 13, 4, 'G203 RGB LightSync', 1, 30000.00, 0.00, 15000.00, 0.00, 15000.00, '2026-10-05 09:14:29', '2026-10-05 09:14:29');

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
(9, 12, 'EMP009', 3, '36236236', '23100000016', '1961-08-22', NULL, '2026-09-23', NULL, '2026-09-23 10:07:02', '2026-09-23 10:07:02'),
(10, 13, 'EMP010', 3, '99478459', '23100000024', '1959-08-11', NULL, '2026-09-23', NULL, '2026-09-23 10:09:15', '2026-09-23 10:09:15'),
(11, 14, 'EMP011', 3, '66256237', '23100000032', '1962-06-25', NULL, '2026-09-23', NULL, '2026-09-23 10:10:52', '2026-09-23 10:10:52'),
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

--
-- Volcado de datos para la tabla `jobs`
--

INSERT INTO `jobs` (`id`, `queue`, `payload`, `attempts`, `reserved_at`, `available_at`, `created_at`) VALUES
(27, 'reposicion', '{\"uuid\":\"faca36fe-137e-4c98-8ea6-c09e324a0ced\",\"displayName\":\"App\\\\Listeners\\\\EvaluarReposicion\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":26:{s:5:\\\"class\\\";s:31:\\\"App\\\\Listeners\\\\EvaluarReposicion\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:36:\\\"App\\\\Events\\\\MovimientoStockRegistrado\\\":1:{s:10:\\\"movimiento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:26:\\\"App\\\\Models\\\\MovimientoStock\\\";s:2:\\\"id\\\";i:40;s:9:\\\"relations\\\";a:1:{i:0;s:8:\\\"producto\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:2:{i:0;i:10;i:1;i:60;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:14:\\\"shouldBeUnique\\\";b:0;s:29:\\\"shouldBeUniqueUntilProcessing\\\";b:0;s:8:\\\"uniqueId\\\";N;s:9:\\\"uniqueFor\\\";N;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";b:1;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\",\"batchId\":null},\"createdAt\":1790991372,\"delay\":null}', 0, NULL, 1790991372, 1790991372),
(28, 'reposicion', '{\"uuid\":\"7ab459bf-44f2-469f-bbc9-a41df4304618\",\"displayName\":\"App\\\\Listeners\\\\EvaluarReposicion\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":26:{s:5:\\\"class\\\";s:31:\\\"App\\\\Listeners\\\\EvaluarReposicion\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:36:\\\"App\\\\Events\\\\MovimientoStockRegistrado\\\":1:{s:10:\\\"movimiento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:26:\\\"App\\\\Models\\\\MovimientoStock\\\";s:2:\\\"id\\\";i:40;s:9:\\\"relations\\\";a:1:{i:0;s:8:\\\"producto\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:2:{i:0;i:10;i:1;i:60;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:14:\\\"shouldBeUnique\\\";b:0;s:29:\\\"shouldBeUniqueUntilProcessing\\\";b:0;s:8:\\\"uniqueId\\\";N;s:9:\\\"uniqueFor\\\";N;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";b:1;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\",\"batchId\":null},\"createdAt\":1790991372,\"delay\":null}', 0, NULL, 1790991372, 1790991372),
(29, 'reposicion', '{\"uuid\":\"afd0e418-2f12-4db1-9a8b-6a9daa12839e\",\"displayName\":\"App\\\\Listeners\\\\ReponerPorCierre\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":26:{s:5:\\\"class\\\";s:30:\\\"App\\\\Listeners\\\\ReponerPorCierre\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:29:\\\"App\\\\Events\\\\OrdenCompraCerrada\\\":1:{s:5:\\\"orden\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\OrdenCompra\\\";s:2:\\\"id\\\";i:4;s:9:\\\"relations\\\";a:1:{i:0;s:6:\\\"lineas\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:2:{i:0;i:10;i:1;i:60;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:14:\\\"shouldBeUnique\\\";b:0;s:29:\\\"shouldBeUniqueUntilProcessing\\\";b:0;s:8:\\\"uniqueId\\\";N;s:9:\\\"uniqueFor\\\";N;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";b:1;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\",\"batchId\":null},\"createdAt\":1790991372,\"delay\":null}', 0, NULL, 1790991372, 1790991372),
(30, 'reposicion', '{\"uuid\":\"40b621d6-5084-45de-a50c-f8ecd667986f\",\"displayName\":\"App\\\\Listeners\\\\EvaluarReposicion\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":26:{s:5:\\\"class\\\";s:31:\\\"App\\\\Listeners\\\\EvaluarReposicion\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:36:\\\"App\\\\Events\\\\MovimientoStockRegistrado\\\":1:{s:10:\\\"movimiento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:26:\\\"App\\\\Models\\\\MovimientoStock\\\";s:2:\\\"id\\\";i:41;s:9:\\\"relations\\\";a:1:{i:0;s:8:\\\"producto\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:2:{i:0;i:10;i:1;i:60;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:14:\\\"shouldBeUnique\\\";b:0;s:29:\\\"shouldBeUniqueUntilProcessing\\\";b:0;s:8:\\\"uniqueId\\\";N;s:9:\\\"uniqueFor\\\";N;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";b:1;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\",\"batchId\":null},\"createdAt\":1790991449,\"delay\":null}', 0, NULL, 1790991449, 1790991449),
(31, 'reposicion', '{\"uuid\":\"2e32028f-62cc-45ac-83ab-36bbe5c5b9c6\",\"displayName\":\"App\\\\Listeners\\\\EvaluarReposicion\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":26:{s:5:\\\"class\\\";s:31:\\\"App\\\\Listeners\\\\EvaluarReposicion\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:36:\\\"App\\\\Events\\\\MovimientoStockRegistrado\\\":1:{s:10:\\\"movimiento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:26:\\\"App\\\\Models\\\\MovimientoStock\\\";s:2:\\\"id\\\";i:41;s:9:\\\"relations\\\";a:1:{i:0;s:8:\\\"producto\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:2:{i:0;i:10;i:1;i:60;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:14:\\\"shouldBeUnique\\\";b:0;s:29:\\\"shouldBeUniqueUntilProcessing\\\";b:0;s:8:\\\"uniqueId\\\";N;s:9:\\\"uniqueFor\\\";N;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";b:1;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\",\"batchId\":null},\"createdAt\":1790991449,\"delay\":null}', 0, NULL, 1790991449, 1790991449),
(32, 'reposicion', '{\"uuid\":\"e7141060-5fdd-462b-852e-e461be7c894c\",\"displayName\":\"App\\\\Listeners\\\\ReponerPorCierre\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":26:{s:5:\\\"class\\\";s:30:\\\"App\\\\Listeners\\\\ReponerPorCierre\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:29:\\\"App\\\\Events\\\\OrdenCompraCerrada\\\":1:{s:5:\\\"orden\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\OrdenCompra\\\";s:2:\\\"id\\\";i:8;s:9:\\\"relations\\\";a:2:{i:0;s:6:\\\"lineas\\\";i:1;s:15:\\\"lineas.producto\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:2:{i:0;i:10;i:1;i:60;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:14:\\\"shouldBeUnique\\\";b:0;s:29:\\\"shouldBeUniqueUntilProcessing\\\";b:0;s:8:\\\"uniqueId\\\";N;s:9:\\\"uniqueFor\\\";N;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";b:1;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\",\"batchId\":null},\"createdAt\":1790991508,\"delay\":null}', 0, NULL, 1790991508, 1790991508),
(33, 'reposicion', '{\"uuid\":\"40692f03-68d2-4812-848d-d29ddcd21ac3\",\"displayName\":\"App\\\\Listeners\\\\EvaluarReposicion\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":26:{s:5:\\\"class\\\";s:31:\\\"App\\\\Listeners\\\\EvaluarReposicion\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:36:\\\"App\\\\Events\\\\MovimientoStockRegistrado\\\":1:{s:10:\\\"movimiento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:26:\\\"App\\\\Models\\\\MovimientoStock\\\";s:2:\\\"id\\\";i:42;s:9:\\\"relations\\\";a:1:{i:0;s:8:\\\"producto\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:2:{i:0;i:10;i:1;i:60;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:14:\\\"shouldBeUnique\\\";b:0;s:29:\\\"shouldBeUniqueUntilProcessing\\\";b:0;s:8:\\\"uniqueId\\\";N;s:9:\\\"uniqueFor\\\";N;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";b:1;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\",\"batchId\":null},\"createdAt\":1790991761,\"delay\":null}', 0, NULL, 1790991761, 1790991761),
(34, 'reposicion', '{\"uuid\":\"3eef63e4-3fba-49d7-a7aa-ae4a0e551c4b\",\"displayName\":\"App\\\\Listeners\\\\EvaluarReposicion\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":26:{s:5:\\\"class\\\";s:31:\\\"App\\\\Listeners\\\\EvaluarReposicion\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:36:\\\"App\\\\Events\\\\MovimientoStockRegistrado\\\":1:{s:10:\\\"movimiento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:26:\\\"App\\\\Models\\\\MovimientoStock\\\";s:2:\\\"id\\\";i:42;s:9:\\\"relations\\\";a:1:{i:0;s:8:\\\"producto\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:2:{i:0;i:10;i:1;i:60;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:14:\\\"shouldBeUnique\\\";b:0;s:29:\\\"shouldBeUniqueUntilProcessing\\\";b:0;s:8:\\\"uniqueId\\\";N;s:9:\\\"uniqueFor\\\";N;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";b:1;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\",\"batchId\":null},\"createdAt\":1790991761,\"delay\":null}', 0, NULL, 1790991761, 1790991761),
(35, 'reposicion', '{\"uuid\":\"73becc4f-e0ff-49b6-a0a6-158d65ca112d\",\"displayName\":\"App\\\\Listeners\\\\ReponerPorCierre\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":26:{s:5:\\\"class\\\";s:30:\\\"App\\\\Listeners\\\\ReponerPorCierre\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:29:\\\"App\\\\Events\\\\OrdenCompraCerrada\\\":1:{s:5:\\\"orden\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\OrdenCompra\\\";s:2:\\\"id\\\";i:9;s:9:\\\"relations\\\";a:1:{i:0;s:6:\\\"lineas\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:2:{i:0;i:10;i:1;i:60;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:14:\\\"shouldBeUnique\\\";b:0;s:29:\\\"shouldBeUniqueUntilProcessing\\\";b:0;s:8:\\\"uniqueId\\\";N;s:9:\\\"uniqueFor\\\";N;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";b:1;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\",\"batchId\":null},\"createdAt\":1790991761,\"delay\":null}', 0, NULL, 1790991761, 1790991761),
(36, 'reposicion', '{\"uuid\":\"f807feef-bcd4-407d-b2c9-0e598a98bfad\",\"displayName\":\"App\\\\Listeners\\\\EvaluarReposicion\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":26:{s:5:\\\"class\\\";s:31:\\\"App\\\\Listeners\\\\EvaluarReposicion\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:36:\\\"App\\\\Events\\\\MovimientoStockRegistrado\\\":1:{s:10:\\\"movimiento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:26:\\\"App\\\\Models\\\\MovimientoStock\\\";s:2:\\\"id\\\";i:43;s:9:\\\"relations\\\";a:1:{i:0;s:8:\\\"producto\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:2:{i:0;i:10;i:1;i:60;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:14:\\\"shouldBeUnique\\\";b:0;s:29:\\\"shouldBeUniqueUntilProcessing\\\";b:0;s:8:\\\"uniqueId\\\";N;s:9:\\\"uniqueFor\\\";N;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";b:1;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\",\"batchId\":null},\"createdAt\":1790992438,\"delay\":null}', 0, NULL, 1790992438, 1790992438),
(37, 'reposicion', '{\"uuid\":\"f1a57414-cade-42fd-ab0b-2732a15ccd54\",\"displayName\":\"App\\\\Listeners\\\\EvaluarReposicion\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":26:{s:5:\\\"class\\\";s:31:\\\"App\\\\Listeners\\\\EvaluarReposicion\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:36:\\\"App\\\\Events\\\\MovimientoStockRegistrado\\\":1:{s:10:\\\"movimiento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:26:\\\"App\\\\Models\\\\MovimientoStock\\\";s:2:\\\"id\\\";i:43;s:9:\\\"relations\\\";a:1:{i:0;s:8:\\\"producto\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:2:{i:0;i:10;i:1;i:60;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:14:\\\"shouldBeUnique\\\";b:0;s:29:\\\"shouldBeUniqueUntilProcessing\\\";b:0;s:8:\\\"uniqueId\\\";N;s:9:\\\"uniqueFor\\\";N;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";b:1;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\",\"batchId\":null},\"createdAt\":1790992438,\"delay\":null}', 0, NULL, 1790992438, 1790992438),
(38, 'reposicion', '{\"uuid\":\"7db4b7bd-ced1-4e64-b23e-7de1206b07fc\",\"displayName\":\"App\\\\Listeners\\\\EvaluarReposicion\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":26:{s:5:\\\"class\\\";s:31:\\\"App\\\\Listeners\\\\EvaluarReposicion\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:36:\\\"App\\\\Events\\\\MovimientoStockRegistrado\\\":1:{s:10:\\\"movimiento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:26:\\\"App\\\\Models\\\\MovimientoStock\\\";s:2:\\\"id\\\";i:44;s:9:\\\"relations\\\";a:1:{i:0;s:8:\\\"producto\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:2:{i:0;i:10;i:1;i:60;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:14:\\\"shouldBeUnique\\\";b:0;s:29:\\\"shouldBeUniqueUntilProcessing\\\";b:0;s:8:\\\"uniqueId\\\";N;s:9:\\\"uniqueFor\\\";N;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";b:1;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\",\"batchId\":null},\"createdAt\":1790995068,\"delay\":null}', 0, NULL, 1790995068, 1790995068),
(39, 'reposicion', '{\"uuid\":\"828d35f2-5c2f-4fc1-89e3-729802c443a9\",\"displayName\":\"App\\\\Listeners\\\\EvaluarReposicion\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":26:{s:5:\\\"class\\\";s:31:\\\"App\\\\Listeners\\\\EvaluarReposicion\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:36:\\\"App\\\\Events\\\\MovimientoStockRegistrado\\\":1:{s:10:\\\"movimiento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:26:\\\"App\\\\Models\\\\MovimientoStock\\\";s:2:\\\"id\\\";i:44;s:9:\\\"relations\\\";a:1:{i:0;s:8:\\\"producto\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:2:{i:0;i:10;i:1;i:60;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:14:\\\"shouldBeUnique\\\";b:0;s:29:\\\"shouldBeUniqueUntilProcessing\\\";b:0;s:8:\\\"uniqueId\\\";N;s:9:\\\"uniqueFor\\\";N;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";b:1;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\",\"batchId\":null},\"createdAt\":1790995068,\"delay\":null}', 0, NULL, 1790995068, 1790995068),
(40, 'reposicion', '{\"uuid\":\"856971d3-aca1-436f-af5f-802d25600949\",\"displayName\":\"App\\\\Listeners\\\\ReponerPorCierre\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":26:{s:5:\\\"class\\\";s:30:\\\"App\\\\Listeners\\\\ReponerPorCierre\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:29:\\\"App\\\\Events\\\\OrdenCompraCerrada\\\":1:{s:5:\\\"orden\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:22:\\\"App\\\\Models\\\\OrdenCompra\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:1:{i:0;s:6:\\\"lineas\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:2:{i:0;i:10;i:1;i:60;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:14:\\\"shouldBeUnique\\\";b:0;s:29:\\\"shouldBeUniqueUntilProcessing\\\";b:0;s:8:\\\"uniqueId\\\";N;s:9:\\\"uniqueFor\\\";N;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";b:1;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\",\"batchId\":null},\"createdAt\":1790995068,\"delay\":null}', 0, NULL, 1790995068, 1790995068),
(41, 'reposicion', '{\"uuid\":\"fd33aa1b-e078-4ec2-ab13-050aca9e34c2\",\"displayName\":\"App\\\\Listeners\\\\EvaluarReposicion\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":26:{s:5:\\\"class\\\";s:31:\\\"App\\\\Listeners\\\\EvaluarReposicion\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:36:\\\"App\\\\Events\\\\MovimientoStockRegistrado\\\":1:{s:10:\\\"movimiento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:26:\\\"App\\\\Models\\\\MovimientoStock\\\";s:2:\\\"id\\\";i:46;s:9:\\\"relations\\\";a:1:{i:0;s:8:\\\"producto\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:2:{i:0;i:10;i:1;i:60;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:14:\\\"shouldBeUnique\\\";b:0;s:29:\\\"shouldBeUniqueUntilProcessing\\\";b:0;s:8:\\\"uniqueId\\\";N;s:9:\\\"uniqueFor\\\";N;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";b:1;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\",\"batchId\":null},\"createdAt\":1791180907,\"delay\":null}', 0, NULL, 1791180907, 1791180907),
(42, 'reposicion', '{\"uuid\":\"bc359c51-c0f1-4757-be3e-c1b0e4720171\",\"displayName\":\"App\\\\Listeners\\\\EvaluarReposicion\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"10,60\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Events\\\\CallQueuedListener\",\"command\":\"O:36:\\\"Illuminate\\\\Events\\\\CallQueuedListener\\\":26:{s:5:\\\"class\\\";s:31:\\\"App\\\\Listeners\\\\EvaluarReposicion\\\";s:6:\\\"method\\\";s:6:\\\"handle\\\";s:4:\\\"data\\\";a:1:{i:0;O:36:\\\"App\\\\Events\\\\MovimientoStockRegistrado\\\":1:{s:10:\\\"movimiento\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:26:\\\"App\\\\Models\\\\MovimientoStock\\\";s:2:\\\"id\\\";i:46;s:9:\\\"relations\\\";a:1:{i:0;s:8:\\\"producto\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}}s:5:\\\"tries\\\";i:3;s:13:\\\"maxExceptions\\\";N;s:7:\\\"backoff\\\";a:2:{i:0;i:10;i:1;i:60;}s:10:\\\"retryUntil\\\";N;s:7:\\\"timeout\\\";N;s:13:\\\"failOnTimeout\\\";b:0;s:17:\\\"shouldBeEncrypted\\\";b:0;s:14:\\\"shouldBeUnique\\\";b:0;s:29:\\\"shouldBeUniqueUntilProcessing\\\";b:0;s:8:\\\"uniqueId\\\";N;s:9:\\\"uniqueFor\\\";N;s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";b:1;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\",\"batchId\":null},\"createdAt\":1791180907,\"delay\":null}', 0, NULL, 1791180907, 1791180907);

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
(39, '2026_10_02_000001_agregar_permiso_recepcion_compras', 5);

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
(46, 4, 'entrega', -1, -1, 19, 0, 'App\\Models\\Venta', 15, 3, NULL, '2026-10-05 09:15:07');

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
(1, 'venta', 'V', 18, '2026-10-01 06:21:23'),
(2, 'orden_compra', 'OC', 9, '2026-10-03 04:41:39');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ordenes_compra`
--

CREATE TABLE `ordenes_compra` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `numero` varchar(20) NOT NULL,
  `proveedor_id` bigint(20) UNSIGNED NOT NULL,
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

INSERT INTO `ordenes_compra` (`id`, `numero`, `proveedor_id`, `estado`, `origen`, `total_estimado`, `usuario_creo_id`, `usuario_aprobo_id`, `fecha_aprobacion`, `fecha_envio`, `fecha_entrega_estimada`, `observaciones`, `created_at`, `updated_at`) VALUES
(1, 'OC-0000001', 2, 'recibida', 'automatica', 250000.00, NULL, 2, '2026-09-30 23:33:08', '2026-09-30 23:34:30', '2026-10-07', 'Generada automáticamente por reposición de stock.', '2026-10-01 02:14:50', '2026-10-03 05:37:48'),
(2, 'OC-0000002', 3, 'recibida_parcial', 'automatica', 156.00, NULL, 2, '2026-09-30 23:35:13', '2026-09-30 23:35:15', '2026-10-03', 'Generada automáticamente por reposición de stock.', '2026-10-01 02:14:50', '2026-10-03 04:53:58'),
(3, 'OC-0000003', 11, 'cancelada', 'automatica', 20000.00, NULL, NULL, NULL, NULL, NULL, 'Generada automáticamente por reposición de stock.\n[30/09/2026 23:36] Cancelada por : ya no la vamos a vender', '2026-10-01 02:14:50', '2026-10-01 02:36:08'),
(4, 'OC-0000004', 11, 'recibida', 'automatica', 20000.00, NULL, 2, '2026-10-02 01:34:47', '2026-10-02 01:34:50', NULL, 'Generada automáticamente por reposición de stock.', '2026-10-01 02:41:08', '2026-10-03 04:36:12'),
(5, 'OC-0000005', 4, 'cancelada', 'automatica', 360000.00, NULL, 2, '2026-10-01 01:21:57', '2026-10-01 01:21:59', '2026-10-05', 'Generada automáticamente por reposición de stock.\n[01/10/2026 01:27] Cancelada por : el proveedor quebró', '2026-10-01 02:45:58', '2026-10-01 04:27:12'),
(6, 'OC-0000006', 4, 'cancelada', 'automatica', 420000.00, NULL, 2, '2026-10-01 03:20:30', '2026-10-01 03:20:34', '2026-10-06', 'Generada automáticamente por reposición de stock.\n[01/10/2026 03:20] Cancelada por : missclick', '2026-10-01 04:56:19', '2026-10-01 06:20:50'),
(7, 'OC-0000007', 4, 'cancelada', 'automatica', 420000.00, NULL, 2, '2026-10-01 03:21:05', '2026-10-01 03:21:09', '2026-10-06', 'Generada automáticamente por reposición de stock.\n[01/10/2026 03:21] Cancelada por : otro missclick', '2026-10-01 06:20:51', '2026-10-01 06:21:49'),
(8, 'OC-0000008', 4, 'recibida', 'automatica', 480000.00, NULL, 2, '2026-10-03 01:37:11', '2026-10-03 01:37:16', '2026-10-06', 'Generada automáticamente por reposición de stock.\n[03/10/2026 01:38] Cerrado con faltante por : missclick. Quedó sin recibir: RM850eCorsair x6.', '2026-10-01 06:21:51', '2026-10-03 04:38:28'),
(9, 'OC-0000009', 4, 'recibida', 'automatica', 180000.00, NULL, 2, '2026-10-03 01:42:26', '2026-10-03 01:42:30', '2026-10-08', 'Generada automáticamente por reposición de stock.', '2026-10-03 04:41:39', '2026-10-03 04:42:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `orden_compra_lineas`
--

CREATE TABLE `orden_compra_lineas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `orden_compra_id` bigint(20) UNSIGNED NOT NULL,
  `producto_id` bigint(20) UNSIGNED NOT NULL,
  `cantidad_pedida` int(11) NOT NULL,
  `cantidad_recibida` int(11) NOT NULL DEFAULT 0,
  `costo_unitario` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `orden_compra_lineas`
--

INSERT INTO `orden_compra_lineas` (`id`, `orden_compra_id`, `producto_id`, `cantidad_pedida`, `cantidad_recibida`, `costo_unitario`, `created_at`, `updated_at`) VALUES
(1, 1, 3, 5, 5, 50000.00, '2026-10-01 02:14:50', '2026-10-03 05:37:48'),
(2, 2, 6, 13, 10, 12.00, '2026-10-01 02:14:50', '2026-10-03 04:53:58'),
(3, 3, 11, 2, 0, 10000.00, '2026-10-01 02:14:50', '2026-10-01 02:14:50'),
(4, 4, 11, 2, 2, 10000.00, '2026-10-01 02:41:08', '2026-10-03 04:36:11'),
(5, 5, 8, 12, 0, 30000.00, '2026-10-01 02:45:58', '2026-10-01 04:17:38'),
(6, 6, 8, 14, 0, 30000.00, '2026-10-01 04:56:19', '2026-10-01 06:20:15'),
(7, 7, 8, 14, 0, 30000.00, '2026-10-01 06:20:51', '2026-10-01 06:20:51'),
(8, 8, 8, 16, 10, 30000.00, '2026-10-01 06:21:51', '2026-10-03 04:37:29'),
(9, 9, 8, 6, 6, 30000.00, '2026-10-03 04:41:39', '2026-10-03 04:42:41');

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
(19, 15, 'efectivo', 15000.00, NULL, NULL, NULL, 'acreditado', NULL, NULL, NULL, NULL, NULL, NULL, 3, '2026-10-05 06:14:29', '2026-10-05 09:14:29', '2026-10-05 09:14:29');

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
(1, 2, 1, 2, 'R55600X', 'Ryzen 5 5600X', 'AMD', 'buen procesador', 'productos/lKrsVZfo66UkBiIYIntLsJXfuULo6EyYeWA72LMv.jpg', 50000.00, 25000.00, 10, 0, 5, 6, 1, 250, 0, 1, 1, '2026-09-23 09:03:02', '2026-09-23 09:03:02'),
(2, 3, 1, 1, 'NvRTX5060', 'Nvidia RTX 5060 8GB', 'Nvidia', 'una placa', 'productos/gcpMphOMf9tuNXnn86dhK5OwV4IjLr7G8PmbgNJs.jpg', 150000.00, 100000.00, 5, 1, 3, 6, 1, 500, 0, 1, 1, '2026-09-23 09:09:02', '2026-09-25 02:58:51'),
(3, 2, 1, 2, 'R55400G', 'Ryzen 5 5400G', 'AMD', 'procesador con graficos integrados', 'productos/C4eK3s6iYAL0pQXkqGrgGAxU1ysqrGuJNfOPpEMf.jpg', 75000.00, 50000.00, 8, 5, 1, 2, 1, 250, 0, 1, 1, '2026-09-23 10:22:46', '2026-10-03 05:37:48'),
(4, 7, 1, 3, 'G203', 'G203 RGB LightSync', 'Logitech', 'mouse de 6 botones', 'productos/fCgnxW0GRM6Xe9dfvKR5Gpv5xr85mlfNZXDeGs1W.jpg', 30000.00, 25000.00, 19, 0, 12, 15, 1, 150, 0, 1, 1, '2026-09-23 10:27:18', '2026-10-05 09:15:07'),
(5, 3, 1, 1, 'NvRTX5070', 'Nvidia RTX 5070 12GB', 'Nvidia', 'placota', 'productos/NQIFHFFalpXZ1h8kg48tUJ5xy3afjUlWF9tlI6Wp.jpg', 1300000.00, 1000000.00, 8, 2, 5, 7, 1, 500, 0, 1, 1, '2026-09-23 10:29:33', '2026-09-25 02:58:51'),
(6, 7, 5, 3, 'G502', 'G502 Wireless', 'Logitech', '3202302', 'productos/fCgnxW0GRM6Xe9dfvKR5Gpv5xr85mlfNZXDeGs1W.jpg', 123.00, 12.00, 10, 1, 5, 7, 1, 159, 0, 1, 1, '2026-09-24 08:24:59', '2026-10-03 04:53:58'),
(7, 4, 6, 4, 'RAM8Corsair', 'RAM 8GB', 'Corsair', 'ramcita', NULL, 45000.15, 41000.65, 20, 1, 10, 5, 1, 150, 0, 1, 1, '2026-09-24 08:38:55', '2026-09-27 04:37:09'),
(8, 5, 1, 4, 'RM850eCorsair', 'RM850e Full Modular Cybenetics Gold', 'Corsair', 'fuente', NULL, 50000.00, 30000.00, 17, 14, 1, 2, 1, 500, 0, 0, 1, '2026-09-25 03:41:15', '2026-10-03 04:42:41'),
(9, 7, 5, 3, 'M280', 'M280', 'Logitech', 'sadasd', NULL, 1402151.00, 233.00, 9, 0, 2, 5, 1, 100, 0, 0, 1, '2026-09-25 03:43:05', '2026-09-27 04:40:57'),
(10, 14, 5, 4, 'PC0001', 'PC completa', 'Corsair', NULL, 'productos/6JZKQ0bRCkxFTocf57VZ1eU4v7jNQB0a3J2eUWtB.webp', 1500000.00, 1000000.00, 3, 0, 1, 2, 1, 10000, 0, 1, 1, '2026-09-25 06:18:43', '2026-09-25 06:18:43'),
(11, 15, 5, 11, '348V5LA', 'Notebook HP 4 RAM 1 TB Intel Celeron 14″ W10', 'HP', NULL, 'productos/99eApAeTcJGBBib7T3KSzVfv89nqwS7peTaPJUM4.png', 15000.00, 10000.00, 4, 2, 1, 1, 1, NULL, 0, 1, 1, '2026-09-25 06:32:42', '2026-10-03 04:36:11');

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
(1, 'Procesadores AMD a mitad de precio', 'Todos los procesadores AMD a mitad de precio', 'porcentaje', 15.00, 'promociones/Ewp08Y59S1LZrCSWooZW06FFpBWfIjGR3o0UlKdq.jpg', 'ambos', 0, 0, '2026-09-23 00:00:00', '2026-10-23 23:59:00', 1, 2, '2026-09-23 09:05:01', '2026-09-23 19:18:08'),
(2, 'Descuentos Septiembre 2026', 'placas de video', 'porcentaje', 10.00, 'promociones/ELRlvm7tAaqMCju7moFnvWDdQvqFLTBXGOUPpycG.webp', 'ambos', 0, 0, '2026-09-23 00:00:00', '2026-10-23 23:59:00', 1, 2, '2026-09-23 09:10:12', '2026-09-23 09:10:12'),
(3, 'Descuentos otoño 2026', 'sadasds', 'monto_fijo', 15000.00, NULL, 'ambos', 0, 1, '2026-09-24 00:00:00', '2026-10-24 23:59:00', 1, 2, '2026-09-24 08:28:02', '2026-09-24 08:28:02'),
(4, 'log 2026', NULL, 'porcentaje', 15.00, 'promociones/li5R9qisBXTxjlpzKGIemFFcbdTati2dTcnyukVI.jpg', 'ambos', 0, 0, '2026-10-03 00:00:00', '2026-11-03 23:59:00', 1, 2, '2026-10-03 09:07:47', '2026-10-03 09:07:47');

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
(3, 15, 16, 3, 15000.00, '2026-10-01 04:32:06');

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
(4, 9);

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
(2, 2, 'flaco@mail.com', '$2y$12$rdv6WgDR2LBy9PngO434iuOQpxembgwz4jzVadSKxGUL7D0emcUg2', 'Luis Alberto', 'Spinetta', 'local', NULL, 1, '2026-09-23 03:23:17', '2026-10-05 01:51:57', NULL, '2026-09-23 01:03:17', '2026-09-23 03:31:51'),
(3, 4, 'indio@mail.com', '$2y$12$rdv6WgDR2LBy9PngO434iuOQpxembgwz4jzVadSKxGUL7D0emcUg2', 'Carlos Alberto', 'Solari', 'local', NULL, 1, '2026-09-23 03:23:17', '2026-10-05 09:14:05', NULL, '2026-09-23 03:14:18', '2026-09-23 03:23:17'),
(4, 3, 'pelado@mail.com', '$2y$12$rdv6WgDR2LBy9PngO434iuOQpxembgwz4jzVadSKxGUL7D0emcUg2', 'Gustavo Edgardo', 'Cordera', 'local', NULL, 1, '2026-09-23 03:30:34', '2026-10-05 09:13:22', 'SAY5SfE8axq802jHNcQIgy3rFdM1HBKKuEU03NN2PlcLn51dgYJmQSJaaFfd', '2026-09-23 03:27:42', '2026-09-25 03:10:12'),
(6, 3, 'musso@mail.com', '$2y$12$KVsI5dZQfQbwTupSPr10Ue3WuRwK6LoutsDRxB4YvLAAkQSpqtkLu', 'Roberto Daniel', 'Musso', 'local', NULL, 1, '2026-09-23 03:23:17', '2026-09-23 03:35:07', NULL, '2026-09-23 04:44:54', '2026-09-23 04:44:54'),
(7, 4, 'pepe.argento@mail.com', '$2y$12$KVsI5dZQfQbwTupSPr10Ue3WuRwK6LoutsDRxB4YvLAAkQSpqtkLu', 'Jose Pepe', 'Argento', 'local', NULL, 1, '2026-09-23 03:23:17', '2026-09-23 03:35:07', NULL, '2026-09-23 05:08:04', '2026-09-23 05:08:04'),
(8, 2, 'messi@mail.com', '$2y$12$rWDma91tFYgjuq4EMtJx5.5kxn8KtFz7dy1LMEjWXWM7pM3AV/DFa', 'Lionel Andrés', 'Messi', 'local', NULL, 1, '2026-09-23 05:54:57', '2026-09-24 08:40:48', NULL, '2026-09-23 05:53:03', '2026-09-23 19:20:44'),
(9, 5, 'fontanet@mail.com', '$2y$12$Dsq8E6NyD.c3vG59bW5R2eKOrf8lqeJVN1la7V3y2t4jVRTKZ/F46', 'Patricio Rogelio', 'Fontanet', 'local', NULL, 1, '2026-09-23 06:20:02', '2026-10-05 02:26:37', NULL, '2026-09-23 06:19:47', '2026-09-23 06:20:02'),
(10, 4, 'chano@mail.com', '$2y$12$F0Vs5.6.XuOskkoRB2NeTux82PsMfWNpllStzf8M6oyCu2nldFTGq', 'Santiago Moreno', 'Charpentier', 'local', NULL, 1, '2026-09-23 10:02:30', '2026-09-23 10:02:31', NULL, '2026-09-23 10:01:51', '2026-09-23 10:02:30'),
(11, 2, 'charly@mail.com', '$2y$12$NHLk/ZG0Mu3jM8u40GFPQO3P.nizl6YNnJqrRvhJIWLoz73bdO9v2', 'Carlos Alberto', 'García', 'local', NULL, 1, '2026-09-23 10:05:16', '2026-09-23 10:05:17', NULL, '2026-09-23 10:05:00', '2026-09-23 10:05:17'),
(12, 3, 'calamaro@mail.com', '$2y$12$UA8oH.AW41GA9CNa6lEGLOAH2P3tiDIkoX.SDn5ZDvcqXMN7vM6Pq', 'Andrés', 'Calamaro', 'local', NULL, 1, '2026-09-23 10:07:38', '2026-10-05 03:24:14', NULL, '2026-09-23 10:07:02', '2026-09-23 10:07:38'),
(13, 3, 'cerati@mail.com', '$2y$12$k05TnHGLzgLjBAaYEKaonuq3TYxfIrusFnvCmY9.YkHH/QKP2qRV2', 'Gustavo Adrián', 'Cerati', 'local', NULL, 1, '2026-09-23 10:09:32', '2026-09-23 10:09:32', NULL, '2026-09-23 10:09:15', '2026-09-23 10:09:32'),
(14, 2, 'iorio@mail.com', '$2y$12$C6CTm.Li9xbOZNco7htn2elmypTjtBcmLhS31dUg5coqvbYUdiLUG', 'Ricardo Horacio', 'Iorio', 'local', NULL, 1, '2026-09-23 10:11:09', '2026-09-23 10:11:10', NULL, '2026-09-23 10:10:52', '2026-09-23 10:11:10'),
(15, 4, 'nuca@mail.com', '$2y$12$L3yet1yknceg6vBhSnJVjuhAO3LIOcO2VuaKxXDFA7lRtY9yBCZLK', 'Luca George', 'Prodan', 'local', NULL, 1, '2026-09-24 08:33:11', '2026-09-24 08:33:11', NULL, '2026-09-24 08:32:30', '2026-09-24 08:33:11'),
(16, 5, 'homero@mail.com', '$2y$12$tFJPuxHepmlKkSbkiv.h7.Wxz.eqlirUMIWlFVSOkySF5puETKS.i', 'Homero', 'Simpson', 'local', NULL, 1, '2026-09-25 20:42:28', '2026-09-25 20:42:04', NULL, '2026-09-25 20:42:02', '2026-09-25 20:42:28'),
(17, 5, 'bart@mail.com', '$2y$12$ESo5NRHxGS3R9X52yNvo5ON//04F17QvEIRQvSUg7xb4/In9l8UjS', 'Bart', 'Simpson', 'local', NULL, 1, '2026-09-25 20:46:42', '2026-09-25 20:46:30', NULL, '2026-09-25 20:46:30', '2026-09-25 20:46:42');

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
(5, 5, 'mostrador', 2, 4, 'en_preparacion', 'retiro', NULL, 4, '2026-09-25 03:39:46', 30000.00, 0.00, 0.00, 24793.39, 5206.61, 30000.00, NULL, '2026-09-25 03:39:46', '2026-09-25 06:39:46', '2026-09-27 02:59:04'),
(6, 6, 'mostrador', 2, 4, 'presupuesto', 'retiro', '2026-10-02', NULL, NULL, 50000.00, 7500.00, 0.00, 42500.00, 0.00, 42500.00, NULL, '2026-09-25 03:40:03', '2026-09-25 06:40:03', '2026-09-25 06:40:03'),
(7, 7, 'mostrador', 2, 4, 'lista_retiro', 'retiro', '2026-10-04', 4, '2026-09-27 01:37:09', 45000.15, 0.00, 0.00, 35433.19, 9566.96, 45000.15, NULL, '2026-09-27 01:37:04', '2026-09-27 04:37:04', '2026-09-27 04:38:36'),
(8, 8, 'mostrador', 2, 4, 'entregada', 'retiro', '2026-10-04', 4, '2026-09-27 01:39:12', 1402151.00, 0.00, 0.00, 1158802.48, 243348.52, 1402151.00, NULL, '2026-09-27 01:39:09', '2026-09-27 04:39:09', '2026-09-27 04:40:57'),
(9, 9, 'mostrador', 2, 4, 'confirmada', 'retiro', NULL, 4, '2026-09-30 23:45:58', 100000.00, 0.00, 0.00, 100000.00, 0.00, 100000.00, NULL, '2026-09-30 23:45:58', '2026-10-01 02:45:58', '2026-10-01 02:45:58'),
(10, 10, 'mostrador', 2, 4, 'confirmada', 'retiro', NULL, 4, '2026-09-30 23:47:39', 200000.00, 0.00, 0.00, 200000.00, 0.00, 200000.00, NULL, '2026-09-30 23:47:39', '2026-10-01 02:47:39', '2026-10-01 02:47:39'),
(11, 11, 'mostrador', 2, 4, 'confirmada', 'retiro', NULL, 4, '2026-09-30 23:49:25', 100000.00, 0.00, 0.00, 100000.00, 0.00, 100000.00, NULL, '2026-09-30 23:49:25', '2026-10-01 02:49:25', '2026-10-01 02:49:25'),
(12, 12, 'mostrador', 2, 4, 'confirmada', 'retiro', NULL, 4, '2026-10-01 01:13:03', 50000.00, 0.00, 0.00, 50000.00, 0.00, 50000.00, NULL, '2026-10-01 01:13:03', '2026-10-01 04:13:03', '2026-10-01 04:13:03'),
(13, 13, 'mostrador', 2, 4, 'confirmada', 'retiro', NULL, 4, '2026-10-01 01:17:19', 50000.00, 0.00, 0.00, 50000.00, 0.00, 50000.00, NULL, '2026-10-01 01:17:19', '2026-10-01 04:17:19', '2026-10-01 04:17:19'),
(14, 14, 'mostrador', 2, 4, 'entregada', 'retiro', NULL, 4, '2026-10-01 01:17:37', 50000.00, 0.00, 0.00, 50000.00, 0.00, 50000.00, NULL, '2026-10-01 01:17:37', '2026-10-01 04:17:37', '2026-10-01 04:35:08'),
(15, 15, 'mostrador', 2, 4, 'entregada', 'retiro', NULL, 4, '2026-10-01 01:32:06', 30000.00, 15000.00, 0.00, 15000.00, 0.00, 15000.00, NULL, '2026-10-01 01:32:06', '2026-10-01 04:32:06', '2026-10-05 09:15:07'),
(16, 16, 'mostrador', 2, 4, 'confirmada', 'retiro', NULL, 4, '2026-10-01 01:56:19', 50000.00, 0.00, 0.00, 50000.00, 0.00, 50000.00, NULL, '2026-10-01 01:56:19', '2026-10-01 04:56:19', '2026-10-01 04:56:19'),
(17, 17, 'mostrador', 2, 4, 'confirmada', 'retiro', NULL, 4, '2026-10-01 03:20:13', 50000.00, 0.00, 0.00, 50000.00, 0.00, 50000.00, NULL, '2026-10-01 03:20:13', '2026-10-01 06:20:13', '2026-10-01 06:20:13'),
(18, 18, 'mostrador', 2, 4, 'confirmada', 'retiro', NULL, 4, '2026-10-01 03:21:23', 100000.00, 0.00, 0.00, 100000.00, 0.00, 100000.00, NULL, '2026-10-01 03:21:23', '2026-10-01 06:21:23', '2026-10-01 06:21:23');

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
(19, 18, 8, 'RM850e Full Modular Cybenetics Gold', 2, 0, 50000.00, 0.00, 0.00, 30000.00, 100000.00, 0.00, 100000.00, '2026-10-01 06:21:23', '2026-10-01 06:21:23');

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
  ADD KEY `ordenes_compra_estado_proveedor_id_index` (`estado`,`proveedor_id`);

--
-- Indices de la tabla `orden_compra_lineas`
--
ALTER TABLE `orden_compra_lineas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `orden_compra_lineas_orden_compra_id_foreign` (`orden_compra_id`),
  ADD KEY `orden_compra_lineas_producto_id_index` (`producto_id`);

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `alicuotas_iva`
--
ALTER TABLE `alicuotas_iva`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `carritos`
--
ALTER TABLE `carritos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `carrito_lineas`
--
ALTER TABLE `carrito_lineas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `comprobantes`
--
ALTER TABLE `comprobantes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `comprobante_lineas`
--
ALTER TABLE `comprobante_lineas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT de la tabla `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT de la tabla `movimientos_stock`
--
ALTER TABLE `movimientos_stock`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT de la tabla `numeradores`
--
ALTER TABLE `numeradores`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `ordenes_compra`
--
ALTER TABLE `ordenes_compra`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `orden_compra_lineas`
--
ALTER TABLE `orden_compra_lineas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `promociones_aplicadas`
--
ALTER TABLE `promociones_aplicadas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `venta_lineas`
--
ALTER TABLE `venta_lineas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

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
  ADD CONSTRAINT `ordenes_compra_proveedor_id_foreign` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`),
  ADD CONSTRAINT `ordenes_compra_usuario_aprobo_id_foreign` FOREIGN KEY (`usuario_aprobo_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `ordenes_compra_usuario_creo_id_foreign` FOREIGN KEY (`usuario_creo_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `orden_compra_lineas`
--
ALTER TABLE `orden_compra_lineas`
  ADD CONSTRAINT `orden_compra_lineas_orden_compra_id_foreign` FOREIGN KEY (`orden_compra_id`) REFERENCES `ordenes_compra` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orden_compra_lineas_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`);

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
