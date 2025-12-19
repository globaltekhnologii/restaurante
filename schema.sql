-- ESQUEMA COMPLETO DEL SISTEMA DE RESTAURANTE
-- Versión Consolidada para VPS (Corregida v2)
-- --------------------------------------------------------

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "-05:00";

-- --------------------------------------------------------
-- 1. Tabla: configuracion_sistema
-- Nota: Campos TEXT no pueden tener DEFAULT en algunas versiones de MySQL/MariaDB.
-- Se han ajustado a VARCHAR o eliminado el DEFAULT.
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `configuracion_sistema` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_restaurante` varchar(100) NOT NULL DEFAULT 'Restaurante El Sabor',
  `logo_url` varchar(255) DEFAULT 'img/logo_default.png',
  `direccion` varchar(255) DEFAULT 'Calle Principal #123',
  `telefono` varchar(50) DEFAULT '555-0123',
  `email` varchar(100) DEFAULT 'contacto@restaurante.com',
  `sitio_web` varchar(100) DEFAULT 'www.restaurante.com',
  `facebook` varchar(100) DEFAULT '',
  `instagram` varchar(100) DEFAULT '',
  `horario_atencion` varchar(255) DEFAULT 'Lunes a Domingo: 12:00 PM - 10:00 PM', -- Cambiado a VARCHAR para permitir DEFAULT
  `pais` varchar(50) DEFAULT 'Colombia',
  `departamento` varchar(50) DEFAULT 'Cundinamarca',
  `ciudad` varchar(50) DEFAULT 'Bogotá',
  `moneda` varchar(10) DEFAULT 'COP',
  `impuesto_porcentaje` decimal(5,2) DEFAULT 0.00,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `horario_apertura_domicilios` time DEFAULT '09:00:00',
  `horario_cierre_domicilios` time DEFAULT '22:00:00',
  `domicilios_habilitados` tinyint(1) DEFAULT 1,
  `latitud_restaurante` decimal(10,8) DEFAULT NULL COMMENT 'Latitud del restaurante',
  `longitud_restaurante` decimal(10,8) DEFAULT NULL COMMENT 'Longitud del restaurante',
  `nit` varchar(20) DEFAULT '',
  `mensaje_pie_factura` text, -- Quitado DEFAULT, se insertará valor inicial luego
  `dias_laborales` text,      -- Quitado DEFAULT
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar datos por defecto para configuración (usando ON DUPLICATE KEY UPDATE para evitar errores si ya existe)
INSERT INTO `configuracion_sistema` (`id`, `mensaje_pie_factura`, `dias_laborales`) VALUES 
(1, '¡Gracias por su compra!', '["1","2","3","4","5","6","7"]')
ON DUPLICATE KEY UPDATE id=id;

-- --------------------------------------------------------
-- 2. Tabla: usuarios
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario` varchar(50) NOT NULL,
  `clave` varchar(255) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `rol` enum('admin','mesero','chef','domiciliario','cajero') NOT NULL DEFAULT 'mesero',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `activo` tinyint(1) DEFAULT 1,
  `email` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `menu_permisos` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario` (`usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuarios por defecto (admin / 1234)
INSERT INTO `usuarios` (`usuario`, `clave`, `nombre`, `rol`) VALUES
('admin', '$2y$10$X8wX8wX8wX8wX8wX8wX8wO9t5g5g5g5g5g5g5g5g5g5g5g5g5g5', 'Administrador', 'admin')
ON DUPLICATE KEY UPDATE usuario=usuario;


-- --------------------------------------------------------
-- 3. Tabla: mesas
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mesas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero_mesa` varchar(10) NOT NULL,
  `capacidad` int(11) NOT NULL DEFAULT 4,
  `estado` enum('disponible','ocupada','reservada','mantenimiento') NOT NULL DEFAULT 'disponible',
  `ubicacion` varchar(50) DEFAULT NULL,
  `mesero_asignado` int(11) DEFAULT NULL,
  `pedido_actual` int(11) DEFAULT NULL,
  `fecha_ocupacion` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_mesa` (`numero_mesa`),
  KEY `mesero_asignado` (`mesero_asignado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- 4. Tabla: platos
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `platos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `categoria` varchar(50) NOT NULL,
  `disponible` tinyint(1) DEFAULT 1,
  `imagen_ruta` varchar(255) DEFAULT 'img/platos/default.png',
  `popular` tinyint(1) DEFAULT 0,
  `nuevo` tinyint(1) DEFAULT 0,
  `vegano` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- 5. Tabla: clientes
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion_principal` text DEFAULT NULL,
  `ciudad` varchar(50) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `ultimo_pedido` datetime DEFAULT NULL,
  `total_pedidos` int(11) DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `telefono` (`telefono`),
  KEY `idx_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- 6. Tabla: pedidos
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pedidos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero_pedido` varchar(20) DEFAULT NULL,
  `mesa_id` int(11) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `domiciliario_id` int(11) DEFAULT NULL,
  `tipo_pedido` enum('mesa','llevar','domicilio') NOT NULL DEFAULT 'mesa',
  `estado` enum('pendiente','confirmado','preparando','listo','en_camino','entregado','cancelado') NOT NULL DEFAULT 'pendiente',
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fecha_pedido` datetime DEFAULT current_timestamp(),
  `notas` text DEFAULT NULL,
  `nombre_cliente` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion_entrega` text DEFAULT NULL,
  `metodo_pago` varchar(50) DEFAULT NULL,
  `pago_anticipado` tinyint(1) DEFAULT 0,
  `pago_validado` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_pedido` (`numero_pedido`),
  KEY `mesa_id` (`mesa_id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `cliente_id` (`cliente_id`),
  KEY `domiciliario_id` (`domiciliario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- 7. Tabla: pedidos_items
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pedidos_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pedido_id` int(11) NOT NULL,
  `plato_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `notas_item` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pedido_id` (`pedido_id`),
  KEY `plato_id` (`plato_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- 8. Tabla: pagos
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pagos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pedido_id` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `metodo_pago` varchar(50) NOT NULL,
  `referencia` varchar(100) DEFAULT NULL,
  `fecha_pago` datetime DEFAULT current_timestamp(),
  `registrado_por` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pedido_id` (`pedido_id`),
  KEY `registrado_por` (`registrado_por`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- 9. Tabla: config_pagos
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `config_pagos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pasarela` varchar(50) NOT NULL,
  `activa` tinyint(1) DEFAULT 0,
  `modo` enum('sandbox','production') DEFAULT 'sandbox',
  `public_key` varchar(255) DEFAULT NULL,
  `secret_key` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pasarela` (`pasarela`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `config_pagos` (`pasarela`, `activa`, `modo`) VALUES
('bold', 0, 'sandbox'),
('mercadopago', 0, 'sandbox')
ON DUPLICATE KEY UPDATE pasarela=pasarela;


-- --------------------------------------------------------
-- 10. Tabla: proveedores
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `proveedores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `contacto` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- 11. Tabla: ingredientes (Inventario)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ingredientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `unidad_medida` varchar(20) NOT NULL,
  `costo_unitario` decimal(10,2) DEFAULT 0.00,
  `stock_actual` decimal(10,2) DEFAULT 0.00,
  `stock_minimo` decimal(10,2) DEFAULT 0.00,
  `proveedor_id` int(11) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `proveedor_id` (`proveedor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- 12. Tabla: movimientos_inventario
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `movimientos_inventario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ingrediente_id` int(11) NOT NULL,
  `tipo_movimiento` enum('entrada','salida','ajuste','merma') NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `stock_anterior` decimal(10,2) NOT NULL,
  `stock_nuevo` decimal(10,2) NOT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `proveedor_id` int(11) DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `pedido_id` int(11) DEFAULT NULL,
  `fecha_movimiento` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ingrediente` (`ingrediente_id`),
  KEY `idx_tipo` (`tipo_movimiento`),
  KEY `idx_fecha` (`fecha_movimiento`),
  KEY `usuario_id` (`usuario_id`),
  KEY `pedido_id` (`pedido_id`),
  KEY `proveedor_id` (`proveedor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Restaurar verificaciones de claves
SET FOREIGN_KEY_CHECKS = 1;
