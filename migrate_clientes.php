<?php
// migrate_clientes.php - Script de migración para sistema de clientes
require_once 'config.php';

echo "=== Iniciando migración del sistema de clientes ===\n\n";

$conn = getDatabaseConnection();

// 1. Crear tabla clientes
echo "1. Creando tabla 'clientes'...\n";
$sql_clientes = "CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100),
    telefono VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(100),
    direccion_principal TEXT,
    ciudad VARCHAR(50),
    notas TEXT,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    ultimo_pedido DATETIME,
    total_pedidos INT DEFAULT 0,
    activo TINYINT(1) DEFAULT 1,
    INDEX idx_telefono (telefono),
    INDEX idx_nombre (nombre),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql_clientes) === TRUE) {
    echo "   ✓ Tabla 'clientes' creada exitosamente\n\n";
} else {
    echo "   ✗ Error: " . $conn->error . "\n\n";
}

// 2. Crear tabla direcciones_clientes
echo "2. Creando tabla 'direcciones_clientes'...\n";
$sql_direcciones = "CREATE TABLE IF NOT EXISTS direcciones_clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    alias VARCHAR(50),
    direccion TEXT NOT NULL,
    ciudad VARCHAR(50),
    referencia TEXT,
    es_principal TINYINT(1) DEFAULT 0,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    INDEX idx_cliente (cliente_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql_direcciones) === TRUE) {
    echo "   ✓ Tabla 'direcciones_clientes' creada exitosamente\n\n";
} else {
    echo "   ✗ Error: " . $conn->error . "\n\n";
}

// 3. Modificar tabla pedidos
echo "3. Modificando tabla 'pedidos'...\n";

// Verificar y agregar cliente_id
$check_cliente_id = "SHOW COLUMNS FROM pedidos LIKE 'cliente_id'";
$result = $conn->query($check_cliente_id);
if ($result->num_rows == 0) {
    $sql = "ALTER TABLE pedidos ADD COLUMN cliente_id INT NULL AFTER id, ADD INDEX idx_cliente (cliente_id)";
    if ($conn->query($sql) === TRUE) {
        echo "   ✓ Columna 'cliente_id' agregada\n";
    } else {
        echo "   ✗ Error al agregar 'cliente_id': " . $conn->error . "\n";
    }
} else {
    echo "   ℹ Columna 'cliente_id' ya existe\n";
}

// Verificar y agregar nombre_cliente
$check_nombre = "SHOW COLUMNS FROM pedidos LIKE 'nombre_cliente'";
$result = $conn->query($check_nombre);
if ($result->num_rows == 0) {
    $sql = "ALTER TABLE pedidos ADD COLUMN nombre_cliente VARCHAR(100) NULL AFTER cliente_id";
    if ($conn->query($sql) === TRUE) {
        echo "   ✓ Columna 'nombre_cliente' agregada\n";
    } else {
        echo "   ✗ Error al agregar 'nombre_cliente': " . $conn->error . "\n";
    }
} else {
    echo "   ℹ Columna 'nombre_cliente' ya existe\n";
}

// Verificar y agregar telefono_cliente
$check_telefono = "SHOW COLUMNS FROM pedidos LIKE 'telefono_cliente'";
$result = $conn->query($check_telefono);
if ($result->num_rows == 0) {
    $sql = "ALTER TABLE pedidos ADD COLUMN telefono_cliente VARCHAR(20) NULL AFTER nombre_cliente";
    if ($conn->query($sql) === TRUE) {
        echo "   ✓ Columna 'telefono_cliente' agregada\n";
    } else {
        echo "   ✗ Error al agregar 'telefono_cliente': " . $conn->error . "\n";
    }
} else {
    echo "   ℹ Columna 'telefono_cliente' ya existe\n";
}

// Intentar agregar clave foránea (opcional)
echo "   - Verificando clave foránea...\n";
$check_fk = "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS 
             WHERE TABLE_NAME = 'pedidos' AND CONSTRAINT_NAME = 'fk_pedidos_cliente' AND TABLE_SCHEMA = DATABASE()";
$result = $conn->query($check_fk);
if ($result->num_rows == 0) {
    $sql_fk = "ALTER TABLE pedidos 
        ADD CONSTRAINT fk_pedidos_cliente 
        FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL";
    
    if ($conn->query($sql_fk) === TRUE) {
        echo "   ✓ Clave foránea agregada\n\n";
    } else {
        echo "   ⚠ No se pudo agregar clave foránea: " . $conn->error . "\n\n";
    }
} else {
    echo "   ℹ Clave foránea ya existe\n\n";
}

// 4. Insertar datos de prueba (opcional)
echo "4. ¿Deseas insertar datos de prueba? (Comentado por seguridad)\n";
/*
$sql_test_data = "INSERT INTO clientes (nombre, apellido, telefono, email, direccion_principal, ciudad) VALUES
    ('Juan', 'Pérez', '3001234567', 'juan.perez@email.com', 'Calle 123 #45-67', 'Bogotá'),
    ('María', 'González', '3109876543', 'maria.gonzalez@email.com', 'Carrera 45 #12-34', 'Medellín'),
    ('Carlos', 'Rodríguez', '3157654321', 'carlos.rodriguez@email.com', 'Avenida 68 #23-45', 'Cali')";

if ($conn->query($sql_test_data) === TRUE) {
    echo "   ✓ Datos de prueba insertados\n\n";
} else {
    echo "   ✗ Error al insertar datos: " . $conn->error . "\n\n";
}
*/

echo "=== Migración completada ===\n";
echo "\nResumen:\n";
echo "- Tabla 'clientes' lista\n";
echo "- Tabla 'direcciones_clientes' lista\n";
echo "- Tabla 'pedidos' actualizada\n";
echo "\n¡Sistema de clientes listo para usar!\n";

$conn->close();
?>
