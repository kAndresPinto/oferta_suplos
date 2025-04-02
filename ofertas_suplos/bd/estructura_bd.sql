-- phpMyAdmin SQL Dump
-- Versión: 5.2.1
-- https://www.phpmyadmin.net/
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- Estructura de tabla para la tabla `actividades`
CREATE TABLE `actividades` (
  `id` int(11) NOT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `nombre` varchar(255) DEFAULT NULL,
  `codigo_segmento` varchar(20) DEFAULT NULL,
  `nombre_segmento` varchar(100) DEFAULT NULL,
  `codigo_familia` varchar(20) DEFAULT NULL,
  `nombre_familia` varchar(100) DEFAULT NULL,
  `codigo_clase` varchar(20) DEFAULT NULL,
  `nombre_clase` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Estructura de tabla para la tabla `documentos`
CREATE TABLE `documentos` (
  `id` int(11) NOT NULL,
  `oferta_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `archivo` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Estructura de tabla para la tabla `ofertas`
CREATE TABLE `ofertas` (
  `id` int(11) NOT NULL,
  `objeto` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `moneda` enum('COP','USD','EUR') DEFAULT 'COP',
  `presupuesto` decimal(12,2) DEFAULT NULL,
  `actividad` varchar(50) DEFAULT NULL,
  `actividad_id` int(11) DEFAULT NULL,
  `fecha_inicio` datetime DEFAULT NULL,
  `fecha_cierre` datetime DEFAULT NULL,
  `estado` enum('ACTIVO','PUBLICADO','EVALUACION') DEFAULT 'ACTIVO',
  `creador` varchar(100) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Índices
ALTER TABLE `actividades`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `documentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oferta_id` (`oferta_id`);

ALTER TABLE `ofertas`
  ADD PRIMARY KEY (`id`);

-- --------------------------------------------------------
-- AUTO_INCREMENT
ALTER TABLE `actividades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `documentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `ofertas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------
-- Relaciones
ALTER TABLE `documentos`
  ADD CONSTRAINT `documentos_ibfk_1` FOREIGN KEY (`oferta_id`) REFERENCES `ofertas` (`id`);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;