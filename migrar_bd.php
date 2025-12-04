<?php
// migrar_bd.php - Script para actualizar la base de datos

require_once 'config.php';

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>Migración de Base de Datos</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f7fa}";
echo ".success{background:#d4edda;border-left:4px solid #28a745;color:#155724;padding:15px;margin:10px 0;border-radius:5px}";
echo ".error{background:#f8d7da;border-left:4px solid #dc3545;color:#721c24;padding:15px;margin:10px 0;border-radius:5px}";
echo ".info{background:#d1ecf1;border-left:4px solid #17a2b8;color:#0c5460;padding:15px;margin:10px 0;border-radius:5px}";
echo "</style></head><body>";

echo "<h1>🔄 Migración de Base de Datos</h1>";

$conn = getDatabaseConnection();

// 1. Actualizar ENUM de rol en usuarios
echo "<h2>1. Actualizando roles de usuarios...</h2>";
$sql = "ALTER TABLE usuarios MODIFY COLUMN rol ENUM('admin', 'mesero', 'chef', 'domiciliario') DEFAULT 'admin'";
if ($conn->query($sql) === TRUE) {
    echo "<div class='success'>✅ Roles actualizados correctamente</div>";
} else {
    if (strpos($conn->error, "Duplicate") !== false || strpos($conn->error, "already") !== false) {
        echo "<div class='info'>ℹ️ Roles ya estaban actualizados</div>";
    } else {
        echo "<div class='error'>❌ Error: " . $conn->error . "</div>";
    }
}

// 2. Agregar campos a pedidos si no existen
echo "<h2>2. Actualizando tabla pedidos...</h2>";

$campos = [
    "mesa_id" => "ALTER TABLE pedidos ADD COLUMN mesa_id INT AFTER notas",
    "usuario_id" => "ALTER TABLE pedidos ADD COLUMN usuario_id INT COMMENT 'Mesero que tomó el pedido' AFTER mesa_id",
    "domiciliario_id" => "ALTER TABLE pedidos ADD COLUMN domiciliario_id INT COMMENT 'Domiciliario asignado' AFTER usuario_id",
    "hora_salida" => "ALTER TABLE pedidos ADD COLUMN hora_salida DATETIME COMMENT 'Hora en que el domiciliario salió' AFTER domiciliario_id",
    "hora_entrega" => "ALTER TABLE pedidos ADD COLUMN hora_entrega DATETIME COMMENT 'Hora de entrega confirmada' AFTER hora_salida"
];

foreach ($campos as $campo => $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "<div class='success'>✅ Campo '$campo' agregado</div>";
    } else {
        if (strpos($conn->error, "Duplicate") !== false || strpos($conn->error, "already") !== false) {
            echo "<div class='info'>ℹ️ Campo '$campo' ya existe</div>";
        } else {
            echo "<div class='error'>❌ Error en '$campo': " . $conn->error . "</div>";
        }
    }
}

// 3. Agregar índices a pedidos
echo "<h2>3. Agregando índices...</h2>";

$indices = [
    "idx_mesa_id" => "ALTER TABLE pedidos ADD INDEX idx_mesa_id (mesa_id)",
    "idx_usuario_id" => "ALTER TABLE pedidos ADD INDEX idx_usuario_id (usuario_id)",
    "idx_domiciliario_id" => "ALTER TABLE pedidos ADD INDEX idx_domiciliario_id (domiciliario_id)"
];

foreach ($indices as $indice => $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "<div class='success'>✅ Índice '$indice' agregado</div>";
    } else {
        if (strpos($conn->error, "Duplicate") !== false || strpos($conn->error, "already") !== false) {
            echo "<div class='info'>ℹ️ Índice '$indice' ya existe</div>";
        } else {
            echo "<div class='error'>❌ Error en '$indice': " . $conn->error . "</div>";
        }
    }
}

// 4. Crear tabla mesas
echo "<h2>4. Creando tabla mesas...</h2>";

$sql = "CREATE TABLE IF NOT EXISTS mesas (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "<div class='success'>✅ Tabla 'mesas' creada correctamente</div>";
    
    // 5. Insertar mesas de ejemplo
    echo "<h2>5. Insertando mesas de ejemplo...</h2>";
    
    $sql = "INSERT INTO mesas (numero_mesa, capacidad, estado) VALUES
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
    ON DUPLICATE KEY UPDATE numero_mesa = numero_mesa";
    
    if ($conn->query($sql) === TRUE) {
        echo "<div class='success'>✅ 10 mesas insertadas correctamente</div>";
    } else {
        echo "<div class='error'>❌ Error al insertar mesas: " . $conn->error . "</div>";
    }
} else {
    if (strpos($conn->error, "already exists") !== false) {
        echo "<div class='info'>ℹ️ Tabla 'mesas' ya existe</div>";
    } else {
        echo "<div class='error'>❌ Error al crear tabla mesas: " . $conn->error . "</div>";
    }
}

$conn->close();

echo "<hr>";
echo "<h2>✅ Migración Completada</h2>";
echo "<p><a href='login.php' style='padding:10px 20px;background:#667eea;color:white;text-decoration:none;border-radius:5px;'>Ir al Login</a></p>";
echo "<p><a href='test_crear_usuarios.php' style='padding:10px 20px;background:#48bb78;color:white;text-decoration:none;border-radius:5px;margin-left:10px;'>Crear Usuarios de Prueba</a></p>";

echo "</body></html>";
?>
