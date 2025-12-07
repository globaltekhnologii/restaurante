<?php
// Script mejorado para ejecutar la migración del sistema de inventario
require_once 'config.php';

$conn = getDatabaseConnection();

echo "🚀 Iniciando migración del Sistema de Inventario...\n\n";

// Crear tabla ingredientes
echo "📦 Creando tabla 'ingredientes'...\n";
$sql = "CREATE TABLE IF NOT EXISTS ingredientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    categoria VARCHAR(50),
    unidad_medida ENUM('kg', 'litros', 'unidades', 'gramos', 'ml') DEFAULT 'unidades',
    stock_actual DECIMAL(10,2) DEFAULT 0,
    stock_minimo DECIMAL(10,2) DEFAULT 0,
    stock_maximo DECIMAL(10,2) DEFAULT 0,
    precio_unitario DECIMAL(10,2) DEFAULT 0,
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_categoria (categoria),
    INDEX idx_activo (activo),
    INDEX idx_stock_actual (stock_actual)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql)) {
    echo "   ✅ Tabla 'ingredientes' creada\n\n";
} else {
    echo "   ❌ Error: " . $conn->error . "\n\n";
}

// Crear tabla proveedores
echo "🏢 Creando tabla 'proveedores'...\n";
$sql = "CREATE TABLE IF NOT EXISTS proveedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    contacto VARCHAR(100),
    telefono VARCHAR(20),
    email VARCHAR(100),
    direccion TEXT,
    notas TEXT,
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql)) {
    echo "   ✅ Tabla 'proveedores' creada\n\n";
} else {
    echo "   ❌ Error: " . $conn->error . "\n\n";
}

// Crear tabla movimientos_inventario
echo "📝 Creando tabla 'movimientos_inventario'...\n";
$sql = "CREATE TABLE IF NOT EXISTS movimientos_inventario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ingrediente_id INT NOT NULL,
    tipo_movimiento ENUM('entrada', 'salida', 'ajuste', 'merma') NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL,
    stock_anterior DECIMAL(10,2) NOT NULL,
    stock_nuevo DECIMAL(10,2) NOT NULL,
    motivo VARCHAR(255),
    proveedor_id INT,
    usuario_id INT NOT NULL,
    pedido_id INT,
    fecha_movimiento DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ingrediente_id) REFERENCES ingredientes(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE SET NULL,
    INDEX idx_ingrediente (ingrediente_id),
    INDEX idx_tipo (tipo_movimiento),
    INDEX idx_fecha (fecha_movimiento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql)) {
    echo "   ✅ Tabla 'movimientos_inventario' creada\n\n";
} else {
    echo "   ❌ Error: " . $conn->error . "\n\n";
}

// Crear tabla recetas
echo "🍽️ Creando tabla 'recetas'...\n";
$sql = "CREATE TABLE IF NOT EXISTS recetas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plato_id INT NOT NULL,
    ingrediente_id INT NOT NULL,
    cantidad_necesaria DECIMAL(10,2) NOT NULL,
    unidad_medida VARCHAR(20),
    FOREIGN KEY (plato_id) REFERENCES platos(id) ON DELETE CASCADE,
    FOREIGN KEY (ingrediente_id) REFERENCES ingredientes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_plato_ingrediente (plato_id, ingrediente_id),
    INDEX idx_plato (plato_id),
    INDEX idx_ingrediente (ingrediente_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql)) {
    echo "   ✅ Tabla 'recetas' creada\n\n";
} else {
    echo "   ❌ Error: " . $conn->error . "\n\n";
}

// Crear tabla proveedor_ingredientes
echo "🔗 Creando tabla 'proveedor_ingredientes'...\n";
$sql = "CREATE TABLE IF NOT EXISTS proveedor_ingredientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proveedor_id INT NOT NULL,
    ingrediente_id INT NOT NULL,
    precio DECIMAL(10,2),
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE CASCADE,
    FOREIGN KEY (ingrediente_id) REFERENCES ingredientes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_proveedor_ingrediente (proveedor_id, ingrediente_id),
    INDEX idx_proveedor (proveedor_id),
    INDEX idx_ingrediente (ingrediente_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql)) {
    echo "   ✅ Tabla 'proveedor_ingredientes' creada\n\n";
} else {
    echo "   ❌ Error: " . $conn->error . "\n\n";
}

// Insertar datos de ejemplo
echo "📊 Insertando datos de ejemplo...\n";

// Ingredientes
$sql = "INSERT INTO ingredientes (nombre, descripcion, categoria, unidad_medida, stock_actual, stock_minimo, stock_maximo, precio_unitario) VALUES
('Tomate', 'Tomate fresco', 'Verduras', 'kg', 10.00, 5.00, 20.00, 2500.00),
('Cebolla', 'Cebolla blanca', 'Verduras', 'kg', 8.00, 3.00, 15.00, 2000.00),
('Ajo', 'Ajo en cabeza', 'Verduras', 'kg', 2.00, 1.00, 5.00, 8000.00),
('Pollo', 'Pechuga de pollo', 'Carnes', 'kg', 15.00, 10.00, 30.00, 12000.00),
('Carne de res', 'Carne molida', 'Carnes', 'kg', 12.00, 8.00, 25.00, 18000.00),
('Arroz', 'Arroz blanco', 'Granos', 'kg', 25.00, 10.00, 50.00, 3000.00),
('Pasta', 'Pasta spaghetti', 'Granos', 'kg', 20.00, 8.00, 40.00, 4000.00),
('Aceite', 'Aceite vegetal', 'Condimentos', 'litros', 5.00, 2.00, 10.00, 8000.00),
('Sal', 'Sal de cocina', 'Condimentos', 'kg', 3.00, 1.00, 5.00, 1500.00),
('Queso', 'Queso mozzarella', 'Lácteos', 'kg', 6.00, 3.00, 12.00, 15000.00)";

if ($conn->query($sql)) {
    echo "   ✅ Ingredientes de ejemplo insertados\n";
} else {
    echo "   ⚠️  Ingredientes ya existen o error: " . $conn->error . "\n";
}

// Proveedores
$sql = "INSERT INTO proveedores (nombre, contacto, telefono, email, direccion) VALUES
('Distribuidora La Cosecha', 'Juan Pérez', '3001234567', 'ventas@lacosecha.com', 'Calle 45 #23-15'),
('Carnes Premium', 'María González', '3109876543', 'info@carnespremium.com', 'Av. 68 #12-34'),
('Granos del Valle', 'Carlos Rodríguez', '3201122334', 'contacto@granosdelvalle.com', 'Carrera 30 #45-67')";

if ($conn->query($sql)) {
    echo "   ✅ Proveedores de ejemplo insertados\n";
} else {
    echo "   ⚠️  Proveedores ya existen o error: " . $conn->error . "\n";
}

// Relación proveedor-ingredientes
$sql = "INSERT INTO proveedor_ingredientes (proveedor_id, ingrediente_id, precio) VALUES
(1, 1, 2500.00),
(1, 2, 2000.00),
(1, 3, 8000.00),
(2, 4, 12000.00),
(2, 5, 18000.00),
(3, 6, 3000.00),
(3, 7, 4000.00)";

if ($conn->query($sql)) {
    echo "   ✅ Relaciones proveedor-ingredientes insertadas\n\n";
} else {
    echo "   ⚠️  Relaciones ya existen o error: " . $conn->error . "\n\n";
}

$conn->close();

echo "═══════════════════════════════════════════════\n";
echo "✅ ¡Migración completada exitosamente!\n";
echo "═══════════════════════════════════════════════\n\n";
echo "📦 Tablas creadas:\n";
echo "   ✓ ingredientes\n";
echo "   ✓ proveedores\n";
echo "   ✓ movimientos_inventario\n";
echo "   ✓ recetas\n";
echo "   ✓ proveedor_ingredientes\n\n";
echo "📊 Datos de ejemplo:\n";
echo "   ✓ 10 ingredientes\n";
echo "   ✓ 3 proveedores\n";
echo "   ✓ 7 relaciones proveedor-ingrediente\n\n";
echo "🎉 Sistema de inventario listo para usar!\n";
?>
