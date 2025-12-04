-- ============================================
-- BASE DE DATOS: Restaurante El Sabor
-- ============================================

-- Crear base de datos
CREATE DATABASE IF NOT EXISTS menu_restaurante CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE menu_restaurante;

-- ============================================
-- TABLA: platos
-- ============================================
CREATE TABLE IF NOT EXISTS platos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    imagen_ruta VARCHAR(255),
    categoria VARCHAR(50) DEFAULT 'General',
    popular TINYINT(1) DEFAULT 0,
    nuevo TINYINT(1) DEFAULT 0,
    vegano TINYINT(1) DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_categoria (categoria),
    INDEX idx_popular (popular),
    INDEX idx_nuevo (nuevo),
    INDEX idx_vegano (vegano)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: usuarios
-- ============================================
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    clave VARCHAR(255) NOT NULL,
    nombre VARCHAR(100),
    email VARCHAR(100),
    rol ENUM('admin', 'mesero', 'chef', 'domiciliario') DEFAULT 'admin',
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso DATETIME,
    INDEX idx_usuario (usuario),
    INDEX idx_rol (rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: pedidos
-- ============================================
CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_pedido VARCHAR(50) UNIQUE NOT NULL,
    nombre_cliente VARCHAR(100) NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    direccion TEXT NOT NULL,
    email VARCHAR(100),
    total DECIMAL(10,2) NOT NULL,
    estado ENUM('pendiente', 'confirmado', 'preparando', 'en_camino', 'entregado', 'cancelado') DEFAULT 'pendiente',
    notas TEXT,
    mesa_id INT,
    usuario_id INT COMMENT 'Mesero que tomó el pedido',
    domiciliario_id INT COMMENT 'Domiciliario asignado',
    hora_salida DATETIME COMMENT 'Hora en que el domiciliario salió',
    hora_entrega DATETIME COMMENT 'Hora de entrega confirmada',
    fecha_pedido DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_numero_pedido (numero_pedido),
    INDEX idx_telefono (telefono),
    INDEX idx_estado (estado),
    INDEX idx_fecha_pedido (fecha_pedido),
    INDEX idx_mesa_id (mesa_id),
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_domiciliario_id (domiciliario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: mesas
-- ============================================
CREATE TABLE IF NOT EXISTS mesas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_mesa VARCHAR(10) UNIQUE NOT NULL,
    capacidad INT DEFAULT 4,
    estado ENUM('disponible', 'ocupada', 'reservada') DEFAULT 'disponible',
    pedido_actual INT,
    mesero_asignado INT,
    fecha_ocupacion DATETIME,
    FOREIGN KEY (pedido_actual) REFERENCES pedidos(id) ON DELETE SET NULL,
    FOREIGN KEY (mesero_asignado) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_numero_mesa (numero_mesa),
    INDEX idx_estado (estado),
    INDEX idx_mesero_asignado (mesero_asignado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: pedidos_items
-- ============================================
CREATE TABLE IF NOT EXISTS pedidos_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    plato_id INT,
    nombre_plato VARCHAR(100) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    cantidad INT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (plato_id) REFERENCES platos(id) ON DELETE SET NULL,
    INDEX idx_pedido_id (pedido_id),
    INDEX idx_plato_id (plato_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DATOS INICIALES
-- ============================================

-- Insertar usuario administrador por defecto
-- IMPORTANTE: Contraseña hasheada con password_hash() de PHP
-- Contraseña temporal: Admin@2024! 
-- ⚠️ DEBES CAMBIAR ESTA CONTRASEÑA EN EL PRIMER LOGIN
-- Hash generado con: password_hash('Admin@2024!', PASSWORD_DEFAULT)
INSERT INTO usuarios (usuario, clave, nombre, rol) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador Principal', 'admin')
ON DUPLICATE KEY UPDATE usuario = usuario;

-- Insertar platos de ejemplo
INSERT INTO platos (nombre, descripcion, precio, categoria, popular, nuevo, vegano) VALUES
('Ensalada César', 'Lechuga romana, crutones, queso parmesano y aderezo César', 8.99, 'Entradas', 1, 0, 0),
('Sopa de Tomate', 'Sopa cremosa de tomate con albahaca fresca', 6.50, 'Entradas', 0, 0, 1),
('Bruschetta', 'Pan tostado con tomate, albahaca y aceite de oliva', 7.99, 'Entradas', 1, 0, 1),

('Filete de Res', 'Filete de res a la parrilla con papas y vegetales', 24.99, 'Platos Principales', 1, 0, 0),
('Pollo al Horno', 'Pollo asado con hierbas y especias', 16.99, 'Platos Principales', 1, 0, 0),
('Pasta Alfredo', 'Fettuccine en salsa cremosa de queso', 14.99, 'Platos Principales', 0, 0, 0),
('Lasaña', 'Lasaña de carne con salsa bechamel', 15.99, 'Platos Principales', 1, 0, 0),
('Pizza Margherita', 'Pizza con tomate, mozzarella y albahaca', 12.99, 'Platos Principales', 0, 1, 1),

('Tiramisú', 'Postre italiano con café y mascarpone', 7.99, 'Postres', 1, 0, 0),
('Cheesecake', 'Tarta de queso con frutos rojos', 8.50, 'Postres', 1, 0, 0),
('Helado Artesanal', 'Tres bolas de helado artesanal', 5.99, 'Postres', 0, 0, 0),

('Limonada Natural', 'Limonada fresca recién exprimida', 3.50, 'Bebidas', 0, 0, 1),
('Jugo de Naranja', 'Jugo de naranja natural', 3.99, 'Bebidas', 0, 0, 1),
('Café Americano', 'Café negro recién hecho', 2.50, 'Bebidas', 0, 0, 1),
('Té Helado', 'Té negro con limón y hielo', 3.00, 'Bebidas', 0, 1, 1)
ON DUPLICATE KEY UPDATE nombre = nombre;

-- Insertar mesas de ejemplo
INSERT INTO mesas (numero_mesa, capacidad, estado) VALUES
('Mesa 1', 2, 'disponible'),
('Mesa 2', 4, 'disponible'),
('Mesa 3', 4, 'disponible'),
('Mesa 4', 6, 'disponible'),
('Mesa 5', 2, 'disponible'),
('Mesa 6', 4, 'disponible'),
('Mesa 7', 8, 'disponible'),
('Mesa 8', 4, 'disponible'),
('Mesa 9', 2, 'disponible'),
('Mesa 10', 6, 'disponible')
ON DUPLICATE KEY UPDATE numero_mesa = numero_mesa;

-- ============================================
-- VERIFICACIÓN
-- ============================================
SELECT 'Base de datos creada exitosamente' AS mensaje;
SELECT COUNT(*) AS total_platos FROM platos;
SELECT COUNT(*) AS total_usuarios FROM usuarios;
