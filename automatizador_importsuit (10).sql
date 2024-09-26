-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 23-07-2024 a las 21:54:38
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
-- Base de datos: `automatizador_importsuit`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `acciones`
--

CREATE TABLE `acciones` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Clave Primaria',
  `block_id` bigint(20) DEFAULT NULL,
  `id_condicion` bigint(20) UNSIGNED DEFAULT NULL,
  `id_disparador` bigint(20) UNSIGNED DEFAULT NULL,
  `id_accion` bigint(20) DEFAULT NULL,
  `tipo` varchar(255) DEFAULT NULL,
  `id_whatsapp_message_template` varchar(255) DEFAULT NULL,
  `asunto` varchar(500) DEFAULT NULL,
  `mensaje` varchar(6500) DEFAULT NULL,
  `opciones` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`opciones`)),
  `tiempo_envio` int(11) DEFAULT NULL,
  `unidad_envio` varchar(255) DEFAULT NULL,
  `tiempo_reenvio` int(11) DEFAULT NULL,
  `unidad_reenvio` varchar(255) DEFAULT NULL,
  `reenvios` int(11) DEFAULT NULL,
  `cambiar_status` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `id_automatizador` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `automatizadores`
--

CREATE TABLE `automatizadores` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Clave Primaria',
  `id_configuracion` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(255) DEFAULT NULL,
  `json_output` longtext DEFAULT NULL,
  `json_bloques` longtext DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `blocks_type`
--

CREATE TABLE `blocks_type` (
  `id` int(10) UNSIGNED NOT NULL,
  `category` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `icon` varchar(255) NOT NULL,
  `value` int(11) NOT NULL,
  `name_tag` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `blocks_type`
--

INSERT INTO `blocks_type` (`id`, `category`, `name`, `description`, `icon`, `value`, `name_tag`) VALUES
(1, 'Disparadores', 'Producto comprado', 'Dispara una acción según el producto comprado', 'fa fa-cart-plus', 1, 'productos'),
(2, 'Disparadores', 'Categoria comprada', 'Dispara una acción según la categoría comprada', 'fas fa-list-alt', 2, 'categorias'),
(3, 'Disparadores', 'Cambio de status de la orden', 'Dispara una acción cuando el producto cambia de status', 'fa fa-exchange-alt', 3, 'status'),
(4, 'Disparadores', 'Una orden presenta una novedad', 'Dispara una acción cuando una orden presenta una novedad', 'fa fa-bell', 4, 'novedad'),
(5, 'Disparadores', 'Departamento del comprador', 'Dispara una acción según el producto comprado', 'fa fa-map-marked-alt', 5, 'provincia'),
(6, 'Disparadores', 'Ciudad', 'Dispara una acción según la ciudad del comprador', 'fa fa-map-marker-alt', 6, 'ciudad'),
(7, 'Acciones', 'Enviar Email', 'Envía un email', 'fa fa-envelope', 7, NULL),
(8, 'Acciones', 'Enviar WHATSAPP', 'Envía un mensaje de whatsapp', 'fa-brands fa-whatsapp', 8, NULL),
(9, 'Acciones', 'Cambiar status de la orden', 'Cambia el status de una orden', 'fa fa-exchange-alt', 9, NULL),
(10, 'Condiciones', 'Decisión(Respuesta Rápida)', 'Usuario responde con un botón de respuesta rápida', 'fa fa-reply', 10, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `condiciones`
--

CREATE TABLE `condiciones` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Clave Primaria',
  `block_id` bigint(20) DEFAULT NULL,
  `id_accion` bigint(20) UNSIGNED DEFAULT NULL,
  `id_condicion` bigint(20) UNSIGNED DEFAULT NULL,
  `id_disparador` bigint(20) UNSIGNED DEFAULT NULL,
  `texto` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `id_automatizador` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuraciones`
--

CREATE TABLE `configuraciones` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Clave Primaria',
  `id_plataforma` bigint(20) DEFAULT NULL,
  `nombre_configuracion` varchar(255) DEFAULT NULL,
  `telefono` bigint(20) DEFAULT NULL,
  `id_telefono` varchar(255) DEFAULT NULL,
  `id_whatsapp` varchar(255) DEFAULT NULL,
  `token` varchar(255) DEFAULT NULL,
  `crm` varchar(255) DEFAULT NULL,
  `webhook_url` varchar(255) DEFAULT NULL,
  `server` varchar(255) DEFAULT NULL,
  `port` varchar(255) DEFAULT NULL,
  `security` varchar(255) DEFAULT NULL,
  `from_name` varchar(255) DEFAULT NULL,
  `from_email` varchar(255) DEFAULT NULL,
  `auth_required` tinyint(1) DEFAULT NULL,
  `usuario` varchar(255) DEFAULT NULL,
  `contrasena` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `disparadores`
--

CREATE TABLE `disparadores` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Clave Primaria',
  `block_id` bigint(20) DEFAULT NULL,
  `id_automatizador` bigint(20) UNSIGNED NOT NULL,
  `tipo` varchar(255) DEFAULT NULL,
  `productos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`productos`)),
  `categorias` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`categorias`)),
  `status` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`status`)),
  `novedad` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`novedad`)),
  `provincia` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`provincia`)),
  `ciudad` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ciudad`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `interacciones_usuarios`
--

CREATE TABLE `interacciones_usuarios` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Clave Primaria',
  `id_automatizador` bigint(20) UNSIGNED NOT NULL,
  `tipo_interaccion` varchar(255) NOT NULL,
  `id_interaccion` bigint(20) NOT NULL,
  `uid_usuario` bigint(20) DEFAULT NULL,
  `json_interaccion` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensajes_usuarios`
--

CREATE TABLE `mensajes_usuarios` (
  `id` bigint(20) UNSIGNED NOT NULL COMMENT 'Clave Primaria',
  `id_automatizador` bigint(20) UNSIGNED NOT NULL,
  `rol` tinyint(1) DEFAULT NULL,
  `uid_whatsapp` varchar(255) DEFAULT NULL,
  `mensaje` varchar(2550) DEFAULT NULL,
  `json_mensaje` varchar(2550) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `acciones`
--
ALTER TABLE `acciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `acciones_id_condicion_foreign` (`id_condicion`),
  ADD KEY `acciones_id_disparador_foreign` (`id_disparador`),
  ADD KEY `acciones_id_automatizador_index` (`id_automatizador`);

--
-- Indices de la tabla `automatizadores`
--
ALTER TABLE `automatizadores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `automatizadores_id_configuracion_foreign` (`id_configuracion`);

--
-- Indices de la tabla `blocks_type`
--
ALTER TABLE `blocks_type`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `condiciones`
--
ALTER TABLE `condiciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `condiciones_id_accion_foreign` (`id_accion`),
  ADD KEY `condiciones_id_condicion_foreign` (`id_condicion`),
  ADD KEY `condiciones_id_disparador_foreign` (`id_disparador`),
  ADD KEY `condiciones_id_automatizador_index` (`id_automatizador`);

--
-- Indices de la tabla `configuraciones`
--
ALTER TABLE `configuraciones`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `disparadores`
--
ALTER TABLE `disparadores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `disparadores_id_automatizador_foreign` (`id_automatizador`);

--
-- Indices de la tabla `interacciones_usuarios`
--
ALTER TABLE `interacciones_usuarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `interacciones_usuarios_id_automatizador_foreign` (`id_automatizador`);

--
-- Indices de la tabla `mensajes_usuarios`
--
ALTER TABLE `mensajes_usuarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mensajes_usuarios_id_automatizador_foreign` (`id_automatizador`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `acciones`
--
ALTER TABLE `acciones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Clave Primaria';

--
-- AUTO_INCREMENT de la tabla `automatizadores`
--
ALTER TABLE `automatizadores`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Clave Primaria';

--
-- AUTO_INCREMENT de la tabla `blocks_type`
--
ALTER TABLE `blocks_type`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `condiciones`
--
ALTER TABLE `condiciones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Clave Primaria';

--
-- AUTO_INCREMENT de la tabla `configuraciones`
--
ALTER TABLE `configuraciones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Clave Primaria';

--
-- AUTO_INCREMENT de la tabla `disparadores`
--
ALTER TABLE `disparadores`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Clave Primaria';

--
-- AUTO_INCREMENT de la tabla `interacciones_usuarios`
--
ALTER TABLE `interacciones_usuarios`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Clave Primaria';

--
-- AUTO_INCREMENT de la tabla `mensajes_usuarios`
--
ALTER TABLE `mensajes_usuarios`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Clave Primaria';

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `acciones`
--
ALTER TABLE `acciones`
  ADD CONSTRAINT `acciones_id_automatizador_foreign` FOREIGN KEY (`id_automatizador`) REFERENCES `automatizadores` (`id`),
  ADD CONSTRAINT `acciones_id_condicion_foreign` FOREIGN KEY (`id_condicion`) REFERENCES `condiciones` (`id`),
  ADD CONSTRAINT `acciones_id_disparador_foreign` FOREIGN KEY (`id_disparador`) REFERENCES `disparadores` (`id`);

--
-- Filtros para la tabla `automatizadores`
--
ALTER TABLE `automatizadores`
  ADD CONSTRAINT `automatizadores_id_configuracion_foreign` FOREIGN KEY (`id_configuracion`) REFERENCES `configuraciones` (`id`);

--
-- Filtros para la tabla `condiciones`
--
ALTER TABLE `condiciones`
  ADD CONSTRAINT `condiciones_id_accion_foreign` FOREIGN KEY (`id_accion`) REFERENCES `acciones` (`id`),
  ADD CONSTRAINT `condiciones_id_automatizador_foreign` FOREIGN KEY (`id_automatizador`) REFERENCES `automatizadores` (`id`),
  ADD CONSTRAINT `condiciones_id_condicion_foreign` FOREIGN KEY (`id_condicion`) REFERENCES `condiciones` (`id`),
  ADD CONSTRAINT `condiciones_id_disparador_foreign` FOREIGN KEY (`id_disparador`) REFERENCES `disparadores` (`id`);

--
-- Filtros para la tabla `disparadores`
--
ALTER TABLE `disparadores`
  ADD CONSTRAINT `disparadores_id_automatizador_foreign` FOREIGN KEY (`id_automatizador`) REFERENCES `automatizadores` (`id`);

--
-- Filtros para la tabla `interacciones_usuarios`
--
ALTER TABLE `interacciones_usuarios`
  ADD CONSTRAINT `interacciones_usuarios_id_automatizador_foreign` FOREIGN KEY (`id_automatizador`) REFERENCES `automatizadores` (`id`);

--
-- Filtros para la tabla `mensajes_usuarios`
--
ALTER TABLE `mensajes_usuarios`
  ADD CONSTRAINT `mensajes_usuarios_id_automatizador_foreign` FOREIGN KEY (`id_automatizador`) REFERENCES `automatizadores` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
