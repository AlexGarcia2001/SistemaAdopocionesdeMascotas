-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 05-08-2025 a las 03:18:04
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `adopciones_mascotas_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `actividades`
--

CREATE TABLE `actividades` (
  `id_actividad` int(11) NOT NULL,
  `nombre_actividad` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_hora` datetime NOT NULL,
  `id_refugio` int(11) DEFAULT NULL,
  `tipo_actividad` varchar(255) DEFAULT NULL,
  `estado` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `actividades`
--

INSERT INTO `actividades` (`id_actividad`, `nombre_actividad`, `descripcion`, `fecha_hora`, `id_refugio`, `tipo_actividad`, `estado`) VALUES
(8, 'Jornada de Curaciones y Vacunaciones para cuidado', 'Aquí tenemos una actividad de fines de semanas donde les enseñaremos a vacunar a los animales para así su mascota no llegue a riesgos mayores y con mayores cuidados.', '2025-10-14 11:00:00', 5, 'Voluntariado', 'Pendiente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `donaciones`
--

CREATE TABLE `donaciones` (
  `id_donacion` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_refugio` int(11) DEFAULT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `moneda` varchar(10) NOT NULL DEFAULT '',
  `fecha_donacion` datetime NOT NULL DEFAULT current_timestamp(),
  `metodo_pago` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `donaciones`
--

INSERT INTO `donaciones` (`id_donacion`, `id_usuario`, `id_refugio`, `cantidad`, `moneda`, `fecha_donacion`, `metodo_pago`) VALUES
(6, 17, 20, 100.00, 'USD', '2025-05-20 11:30:00', 'Transferencia Bancaria');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `eventos`
--

CREATE TABLE `eventos` (
  `id_evento` int(11) NOT NULL,
  `nombre_evento` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_hora` datetime NOT NULL,
  `ubicacion` varchar(255) NOT NULL,
  `organizador` varchar(255) DEFAULT NULL,
  `cupo_maximo` int(11) DEFAULT NULL,
  `estado` enum('programado','cancelado','completado') NOT NULL DEFAULT 'programado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `eventos`
--

INSERT INTO `eventos` (`id_evento`, `nombre_evento`, `descripcion`, `fecha_hora`, `ubicacion`, `organizador`, `cupo_maximo`, `estado`) VALUES
(6, 'Feria de Adopción de Verano', 'Gran feria para encontrar hogar a nuestras mascotas y darles el mejor cariño del mundo', '2025-10-20 09:00:00', 'Parque Central de las Americas', 'Refugio de Animales', 30, 'programado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `galeriamultimedia`
--

CREATE TABLE `galeriamultimedia` (
  `id_multimedia` int(11) NOT NULL,
  `id_mascota` int(11) NOT NULL,
  `tipo_archivo` enum('imagen','video') NOT NULL,
  `url_archivo` varchar(255) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `fecha_subida` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `galeriamultimedia`
--

INSERT INTO `galeriamultimedia` (`id_multimedia`, `id_mascota`, `tipo_archivo`, `url_archivo`, `descripcion`, `fecha_subida`) VALUES
(81, 35, 'imagen', 'https://www.thesprucepets.com/thmb/VkoI1kidVIiQQnAezIYE_IPU-D8=/2781x0/filters:no_upscale():strip_icc()/pitbull-dog-breeds-4843994-hero-db6922b6c8294b45b19c07aff5865790.jpg', 'Foto de Poli', '2025-07-31 17:32:49'),
(99, 21, 'imagen', 'https://www.muyinteresante.com/wp-content/uploads/sites/5/2022/10/13/6347a69ccf292.jpeg', 'Foto de Luli', '2025-08-03 14:56:10'),
(101, 40, 'imagen', 'https://cdn.download.ams.birds.cornell.edu/api/v1/asset/612451296/320', 'Foto de Julito', '2025-08-03 21:52:37'),
(103, 38, 'imagen', 'https://cdn.shopify.com/s/files/1/0799/5301/files/blog_3310653f-96ba-4138-a5a2-725a2dbdf039_1024x1024.jpg?v=1657919619', 'Foto de Max', '2025-08-04 18:58:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historialmedico`
--

CREATE TABLE `historialmedico` (
  `id_historial` int(11) NOT NULL,
  `id_mascota` int(11) NOT NULL,
  `fecha_visita` date NOT NULL,
  `tipo_visita` varchar(255) DEFAULT NULL,
  `diagnostico` text NOT NULL,
  `tratamiento` text DEFAULT NULL,
  `vacunas_aplicadas` varchar(500) DEFAULT NULL,
  `proxima_cita` date DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `id_veterinario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `historialmedico`
--

INSERT INTO `historialmedico` (`id_historial`, `id_mascota`, `fecha_visita`, `tipo_visita`, `diagnostico`, `tratamiento`, `vacunas_aplicadas`, `proxima_cita`, `observaciones`, `id_veterinario`) VALUES
(8, 35, '2025-04-11', 'Consulta Profesional', 'Aparentemente enfermo por parvo-virus y posible virus de piel pero salió bien.', 'Curación con una vacuna y adecuada servicio medico profesional.', 'Rabia y Moquillo', '2025-11-09', 'Vamos a ver por que no se sabe si es adecuada.', 34);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mascotas`
--

CREATE TABLE `mascotas` (
  `id_mascota` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `especie` varchar(50) NOT NULL,
  `raza` varchar(100) DEFAULT NULL,
  `edad` int(11) DEFAULT NULL COMMENT 'Edad en meses o años',
  `sexo` enum('Macho','Hembra','Desconocido') NOT NULL,
  `tamano` enum('Pequeño','Mediano','Grande') DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_rescate` date DEFAULT NULL,
  `estado_adopcion` enum('Disponible','En Proceso','Adoptado','No Disponible') NOT NULL DEFAULT 'Disponible',
  `id_usuario` int(11) DEFAULT NULL,
  `id_refugio` int(11) DEFAULT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `mascotas`
--

INSERT INTO `mascotas` (`id_mascota`, `nombre`, `especie`, `raza`, `edad`, `sexo`, `tamano`, `descripcion`, `fecha_rescate`, `estado_adopcion`, `id_usuario`, `id_refugio`, `estado`) VALUES
(21, 'Luli', 'Perro', 'Husky Siberiano', 3, 'Hembra', 'Grande', 'Perrita educada con gran corazon y inteligencia. ', '2024-11-18', 'Adoptado', 16, 5, 'activo'),
(35, 'Poli', 'Perro', 'Pitbull', 3, 'Hembra', 'Grande', 'Una perrita super entretenida y super tranquila y con grandes rasgos femeninos.', '2024-07-27', 'Adoptado', 16, 5, 'activo'),
(38, 'Max', 'Perro', 'Siberiano', 4, 'Macho', 'Grande', 'Un siberiano grande y jugueton con gran pureza. ', '2024-07-17', 'Adoptado', 16, 5, 'activo'),
(40, 'Julito', 'Pajaro', 'Perico', 3, 'Macho', 'Mediano', 'Pájaro con muy buena actitud y muy tierno.', '2024-09-23', 'Adoptado', 16, 5, 'activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id_notificacion` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `mensaje` text NOT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp(),
  `leida` tinyint(1) NOT NULL DEFAULT 0,
  `tipo_notificacion` enum('General','Sistema','Adopcion','Evento','Donacion','Recordatorio') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `notificaciones`
--

INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `mensaje`, `fecha_hora`, `leida`, `tipo_notificacion`) VALUES
(24, 34, '¡Felicitaciones, Jeremy! Tu solicitud de adopción para \'Luli\' ha sido APROBADA.', '2025-08-03 14:55:36', 1, ''),
(25, 34, '¡Felicitaciones, Jeremy! Tu solicitud de adopción para \'Julito\' ha sido APROBADA.', '2025-08-03 21:55:00', 1, ''),
(26, 17, '¡Felicitaciones, Flor! Tu solicitud de adopción para \'Max\' ha sido APROBADA.', '2025-08-04 19:02:03', 0, '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `participacionevento`
--

CREATE TABLE `participacionevento` (
  `id_participacion` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_evento` int(11) NOT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `refugios`
--

CREATE TABLE `refugios` (
  `id_refugio` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `ciudad` varchar(100) NOT NULL,
  `pais` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `latitud` decimal(10,8) DEFAULT NULL,
  `longitud` decimal(11,8) DEFAULT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `refugios`
--

INSERT INTO `refugios` (`id_refugio`, `nombre`, `direccion`, `ciudad`, `pais`, `telefono`, `email`, `latitud`, `longitud`, `estado`) VALUES
(5, 'Refugio Estrella Surd', 'Bulevar Fuerzas Armadas', 'Tegucigalpa', 'Honduras', '99263744', 'refugio.estrellasur@gmail.com', 14.08134921, -87.19578701, 'activo'),
(20, 'Refugio Poli', 'La Loma', 'Tegucigalpa', 'Honduras', '98764995', 'refugio.poli@gmail.com', 14.10168418, -87.17630809, 'activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL,
  `nombre_rol` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id_rol`, `nombre_rol`, `descripcion`, `estado`) VALUES
(1, 'Administrador', 'Control total del sistema de adopciones.', 'activo'),
(2, 'Usuario Normal', 'Usuario estándar con permisos para solicitar adopciones, ver mascotas, etc.', 'activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seguimientos_post_adopcion`
--

CREATE TABLE `seguimientos_post_adopcion` (
  `id_seguimiento` int(11) NOT NULL,
  `id_mascota` int(11) NOT NULL,
  `id_usuario_adoptante` int(11) NOT NULL,
  `fecha_seguimiento` date NOT NULL,
  `tipo_seguimiento` enum('Visita Domiciliaria','Llamada Telefónica','Reporte Online','Otro') NOT NULL,
  `observaciones` text DEFAULT NULL,
  `estado_mascota` enum('Excelente','Bueno','Regular','Preocupante') NOT NULL,
  `recomendaciones` text DEFAULT NULL,
  `creado_por_id_usuario` int(11) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `seguimientos_post_adopcion`
--

INSERT INTO `seguimientos_post_adopcion` (`id_seguimiento`, `id_mascota`, `id_usuario_adoptante`, `fecha_seguimiento`, `tipo_seguimiento`, `observaciones`, `estado_mascota`, `recomendaciones`, `creado_por_id_usuario`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(2, 35, 34, '2025-10-18', 'Llamada Telefónica', 'El perrito tiene una buenísima condición física.', 'Excelente', 'Solamente alimentarlo muy bien para así se vuelva más fuerte y saludable.', 16, '2025-08-02 22:59:00', '2025-08-02 22:59:24');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudesadopcion`
--

CREATE TABLE `solicitudesadopcion` (
  `id_solicitud` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_mascota` int(11) NOT NULL,
  `fecha_solicitud` datetime NOT NULL DEFAULT current_timestamp(),
  `estado_solicitud` enum('Pendiente','Aprobada','Rechazada','Cancelada') NOT NULL DEFAULT 'Pendiente',
  `motivo` varchar(255) DEFAULT NULL,
  `fecha_aprobacion_rechazo` datetime DEFAULT NULL,
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `solicitudesadopcion`
--

INSERT INTO `solicitudesadopcion` (`id_solicitud`, `id_usuario`, `id_mascota`, `fecha_solicitud`, `estado_solicitud`, `motivo`, `fecha_aprobacion_rechazo`, `observaciones`) VALUES
(27, 34, 35, '2025-07-31 00:00:00', 'Aprobada', 'Hola me encantaria adoptarlo.', '2025-08-03 02:30:32', 'Felicidades tienes lo necesario para adoptarlo.'),
(39, 34, 21, '2025-08-03 00:00:00', 'Aprobada', 'Quisiera adoptar el animalito por fa.', '2025-08-03 22:55:36', 'Claro es todo suyo.'),
(41, 17, 40, '2025-08-05 00:00:00', 'Pendiente', NULL, NULL, NULL),
(42, 34, 40, '2025-08-05 00:00:00', 'Pendiente', 'Quisiera adoptarlo.', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nombre_usuario` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `id_rol` int(11) NOT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre_usuario`, `apellido`, `email`, `password`, `telefono`, `direccion`, `id_rol`, `fecha_registro`, `estado`) VALUES
(16, 'Alex', 'Garcia', 'alex.garcia@gmail.com', '$2y$10$0PoCNtGi7LUWJukXVIMFKOQAn5WVhvqJwPcdsncdVoHl2imzrabcu', '95543944', 'Centro America Oeste', 1, '2025-07-12 13:28:07', 'activo'),
(17, 'Flor', 'Consuelo', 'flor.cruz@gmail.com', '$2y$10$ZuCUI/0BR1Ffm3PYjmPUwOSUhKlte0Uz/HKqOnwbqKrMJJu4OlvAi', NULL, NULL, 2, '2025-08-04 18:54:02', 'activo'),
(34, 'Jeremy', 'Garcia', 'jeremy.garcia@gmail.com', '$2y$10$0DVbP9m2onmDWMyZR.AemuFTU7q2LoaVtaryq2PWOBdejn2NfXgqy', NULL, NULL, 2, '2025-08-04 16:18:48', 'activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `voluntarios`
--

CREATE TABLE `voluntarios` (
  `id_voluntario` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_actividad` int(11) NOT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `actividades`
--
ALTER TABLE `actividades`
  ADD PRIMARY KEY (`id_actividad`),
  ADD KEY `id_refugio` (`id_refugio`);

--
-- Indices de la tabla `donaciones`
--
ALTER TABLE `donaciones`
  ADD PRIMARY KEY (`id_donacion`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_refugio` (`id_refugio`);

--
-- Indices de la tabla `eventos`
--
ALTER TABLE `eventos`
  ADD PRIMARY KEY (`id_evento`);

--
-- Indices de la tabla `galeriamultimedia`
--
ALTER TABLE `galeriamultimedia`
  ADD PRIMARY KEY (`id_multimedia`),
  ADD KEY `id_mascota` (`id_mascota`);

--
-- Indices de la tabla `historialmedico`
--
ALTER TABLE `historialmedico`
  ADD PRIMARY KEY (`id_historial`),
  ADD KEY `id_mascota` (`id_mascota`),
  ADD KEY `id_veterinario` (`id_veterinario`);

--
-- Indices de la tabla `mascotas`
--
ALTER TABLE `mascotas`
  ADD PRIMARY KEY (`id_mascota`),
  ADD KEY `id_refugio` (`id_refugio`),
  ADD KEY `fk_mascota_usuario` (`id_usuario`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id_notificacion`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `participacionevento`
--
ALTER TABLE `participacionevento`
  ADD PRIMARY KEY (`id_participacion`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_evento` (`id_evento`);

--
-- Indices de la tabla `refugios`
--
ALTER TABLE `refugios`
  ADD PRIMARY KEY (`id_refugio`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_rol`),
  ADD UNIQUE KEY `nombre_rol` (`nombre_rol`);

--
-- Indices de la tabla `seguimientos_post_adopcion`
--
ALTER TABLE `seguimientos_post_adopcion`
  ADD PRIMARY KEY (`id_seguimiento`),
  ADD KEY `id_mascota` (`id_mascota`),
  ADD KEY `id_usuario_adoptante` (`id_usuario_adoptante`),
  ADD KEY `creado_por_id_usuario` (`creado_por_id_usuario`);

--
-- Indices de la tabla `solicitudesadopcion`
--
ALTER TABLE `solicitudesadopcion`
  ADD PRIMARY KEY (`id_solicitud`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_mascota` (`id_mascota`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_Usuarios_Roles` (`id_rol`);

--
-- Indices de la tabla `voluntarios`
--
ALTER TABLE `voluntarios`
  ADD PRIMARY KEY (`id_voluntario`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_actividad` (`id_actividad`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `actividades`
--
ALTER TABLE `actividades`
  MODIFY `id_actividad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `donaciones`
--
ALTER TABLE `donaciones`
  MODIFY `id_donacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `eventos`
--
ALTER TABLE `eventos`
  MODIFY `id_evento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de la tabla `galeriamultimedia`
--
ALTER TABLE `galeriamultimedia`
  MODIFY `id_multimedia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT de la tabla `historialmedico`
--
ALTER TABLE `historialmedico`
  MODIFY `id_historial` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `mascotas`
--
ALTER TABLE `mascotas`
  MODIFY `id_mascota` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id_notificacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de la tabla `participacionevento`
--
ALTER TABLE `participacionevento`
  MODIFY `id_participacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `refugios`
--
ALTER TABLE `refugios`
  MODIFY `id_refugio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `seguimientos_post_adopcion`
--
ALTER TABLE `seguimientos_post_adopcion`
  MODIFY `id_seguimiento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `solicitudesadopcion`
--
ALTER TABLE `solicitudesadopcion`
  MODIFY `id_solicitud` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT de la tabla `voluntarios`
--
ALTER TABLE `voluntarios`
  MODIFY `id_voluntario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `actividades`
--
ALTER TABLE `actividades`
  ADD CONSTRAINT `actividades_ibfk_1` FOREIGN KEY (`id_refugio`) REFERENCES `refugios` (`id_refugio`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `donaciones`
--
ALTER TABLE `donaciones`
  ADD CONSTRAINT `donaciones_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `donaciones_ibfk_2` FOREIGN KEY (`id_refugio`) REFERENCES `refugios` (`id_refugio`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `galeriamultimedia`
--
ALTER TABLE `galeriamultimedia`
  ADD CONSTRAINT `galeriamultimedia_ibfk_1` FOREIGN KEY (`id_mascota`) REFERENCES `mascotas` (`id_mascota`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `historialmedico`
--
ALTER TABLE `historialmedico`
  ADD CONSTRAINT `historialmedico_ibfk_1` FOREIGN KEY (`id_mascota`) REFERENCES `mascotas` (`id_mascota`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `historialmedico_ibfk_2` FOREIGN KEY (`id_veterinario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `mascotas`
--
ALTER TABLE `mascotas`
  ADD CONSTRAINT `fk_mascota_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `mascotas_ibfk_1` FOREIGN KEY (`id_refugio`) REFERENCES `refugios` (`id_refugio`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `participacionevento`
--
ALTER TABLE `participacionevento`
  ADD CONSTRAINT `participacionevento_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `participacionevento_ibfk_2` FOREIGN KEY (`id_evento`) REFERENCES `eventos` (`id_evento`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `seguimientos_post_adopcion`
--
ALTER TABLE `seguimientos_post_adopcion`
  ADD CONSTRAINT `seguimientos_post_adopcion_ibfk_1` FOREIGN KEY (`id_mascota`) REFERENCES `mascotas` (`id_mascota`) ON DELETE CASCADE,
  ADD CONSTRAINT `seguimientos_post_adopcion_ibfk_2` FOREIGN KEY (`id_usuario_adoptante`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `seguimientos_post_adopcion_ibfk_3` FOREIGN KEY (`creado_por_id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `solicitudesadopcion`
--
ALTER TABLE `solicitudesadopcion`
  ADD CONSTRAINT `solicitudesadopcion_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `solicitudesadopcion_ibfk_2` FOREIGN KEY (`id_mascota`) REFERENCES `mascotas` (`id_mascota`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_Usuarios_Roles` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `voluntarios`
--
ALTER TABLE `voluntarios`
  ADD CONSTRAINT `voluntarios_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `voluntarios_ibfk_2` FOREIGN KEY (`id_actividad`) REFERENCES `actividades` (`id_actividad`) ON DELETE NO ACTION ON UPDATE NO ACTION;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
