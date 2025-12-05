-- Script para crear tabla de pagos y configuración de métodos de pago
-- Sistema de Pagos para Colombia

-- Tabla de pagos
CREATE TABLE IF NOT EXISTS pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    numero_transaccion VARCHAR(50) UNIQUE,
    metodo_pago ENUM('efectivo', 'nequi', 'daviplata', 'dale', 'bancolombia', 'otro') DEFAULT 'efectivo',
    referencia_pago VARCHAR(100), -- Número de transacción de la billetera
    monto DECIMAL(10,2) NOT NULL,
    fecha_pago DATETIME DEFAULT CURRENT_TIMESTAMP,
    usuario_id INT, -- Quien registró el pago
    notas TEXT,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    INDEX idx_pedido (pedido_id),
    INDEX idx_fecha (fecha_pago)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Agregar columna 'pagado' a tabla pedidos si no existe
ALTER TABLE pedidos 
ADD COLUMN IF NOT EXISTS pagado TINYINT(1) DEFAULT 0 AFTER estado;

-- Tabla de configuración de métodos de pago
CREATE TABLE IF NOT EXISTS metodos_pago_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    metodo VARCHAR(50) NOT NULL UNIQUE,
    nombre_display VARCHAR(100) NOT NULL,
    numero_cuenta VARCHAR(100), -- Número de Nequi, Daviplata, etc.
    nombre_titular VARCHAR(100),
    qr_imagen VARCHAR(255), -- Ruta al código QR
    activo TINYINT(1) DEFAULT 1,
    orden INT DEFAULT 0,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertar métodos de pago por defecto
INSERT INTO metodos_pago_config (metodo, nombre_display, activo, orden) VALUES
('efectivo', 'Efectivo', 1, 1),
('nequi', 'Nequi', 1, 2),
('daviplata', 'Daviplata', 1, 3),
('dale', 'Dale', 1, 4),
('bancolombia', 'Bancolombia Ahorros', 1, 5)
ON DUPLICATE KEY UPDATE nombre_display = VALUES(nombre_display);
