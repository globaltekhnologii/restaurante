-- Tabla de configuración del sistema
CREATE TABLE IF NOT EXISTS configuracion_sistema (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_restaurante VARCHAR(100) NOT NULL DEFAULT 'Restaurante El Sabor',
    logo_url VARCHAR(255) DEFAULT 'img/logo_default.png',
    direccion VARCHAR(255) DEFAULT 'Calle Principal #123',
    telefono VARCHAR(50) DEFAULT '555-0123',
    email VARCHAR(100) DEFAULT 'contacto@restaurante.com',
    sitio_web VARCHAR(100) DEFAULT 'www.restaurante.com',
    facebook VARCHAR(100) DEFAULT '',
    instagram VARCHAR(100) DEFAULT '',
    horario_atencion TEXT DEFAULT 'Lunes a Domingo: 12:00 PM - 10:00 PM',
    pais VARCHAR(50) DEFAULT 'Colombia',
    departamento VARCHAR(50) DEFAULT 'Cundinamarca',
    ciudad VARCHAR(50) DEFAULT 'Bogotá',
    moneda VARCHAR(10) DEFAULT 'COP',
    impuesto_porcentaje DECIMAL(5,2) DEFAULT 0.00,
    horario_apertura_domicilios TIME DEFAULT '09:00:00',
    horario_cierre_domicilios TIME DEFAULT '22:00:00',
    domicilios_habilitados TINYINT(1) DEFAULT 1,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insertar configuración por defecto si no existe
INSERT INTO configuracion_sistema (id, nombre_restaurante) 
SELECT 1, 'Restaurante El Sabor' 
WHERE NOT EXISTS (SELECT 1 FROM configuracion_sistema WHERE id = 1);
