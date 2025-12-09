<?php
/**
 * Script de Migración: Sistema de Cobro de Domicilio con GPS
 * 
 * Este script crea las tablas y campos necesarios para el sistema
 * de cálculo dinámico de costo de domicilio basado en distancia GPS.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
$conn = getDatabaseConnection();

echo "<h1>🚀 Instalación: Sistema de Cobro de Domicilio GPS</h1>";
echo "<pre>";

$errores = [];
$exitos = [];

// ============================================
// 1. Crear tabla configuracion_domicilios
// ============================================
echo "\n📋 Paso 1: Creando tabla 'configuracion_domicilios'...\n";

$sql_config = "CREATE TABLE IF NOT EXISTS configuracion_domicilios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tarifa_base DECIMAL(10,2) NOT NULL DEFAULT 5000.00 COMMENT 'Tarifa fija mínima en COP',
    costo_por_km DECIMAL(10,2) NOT NULL DEFAULT 1000.00 COMMENT 'Costo adicional por kilómetro',
    distancia_maxima DECIMAL(10,2) DEFAULT 10.00 COMMENT 'Distancia máxima en km',
    usar_rangos BOOLEAN DEFAULT 0 COMMENT '1=usar rangos, 0=usar tarifa base+km',
    activo BOOLEAN DEFAULT 1,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql_config) === TRUE) {
    $exitos[] = "✓ Tabla 'configuracion_domicilios' creada";
    echo "   ✓ Tabla creada exitosamente\n";
    
    // Insertar configuración por defecto
    $sql_insert = "INSERT INTO configuracion_domicilios (id, tarifa_base, costo_por_km, distancia_maxima, usar_rangos, activo) 
                   VALUES (1, 5000.00, 1000.00, 10.00, 0, 1)
                   ON DUPLICATE KEY UPDATE id=id";
    
    if ($conn->query($sql_insert) === TRUE) {
        echo "   ✓ Configuración por defecto insertada\n";
    }
} else {
    $errores[] = "✗ Error creando tabla 'configuracion_domicilios': " . $conn->error;
    echo "   ✗ Error: " . $conn->error . "\n";
}

// ============================================
// 2. Crear tabla rangos_tarifas_domicilio
// ============================================
echo "\n📋 Paso 2: Creando tabla 'rangos_tarifas_domicilio'...\n";

$sql_rangos = "CREATE TABLE IF NOT EXISTS rangos_tarifas_domicilio (
    id INT PRIMARY KEY AUTO_INCREMENT,
    distancia_min DECIMAL(10,2) NOT NULL COMMENT 'Distancia mínima en km',
    distancia_max DECIMAL(10,2) NOT NULL COMMENT 'Distancia máxima en km',
    tarifa DECIMAL(10,2) NOT NULL COMMENT 'Tarifa para este rango en COP',
    activo BOOLEAN DEFAULT 1,
    INDEX idx_distancia (distancia_min, distancia_max)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql_rangos) === TRUE) {
    $exitos[] = "✓ Tabla 'rangos_tarifas_domicilio' creada";
    echo "   ✓ Tabla creada exitosamente\n";
    
    // Insertar rangos por defecto
    $sql_insert_rangos = "INSERT INTO rangos_tarifas_domicilio (distancia_min, distancia_max, tarifa, activo) VALUES
        (0.00, 3.00, 5000.00, 1),
        (3.01, 5.00, 7000.00, 1),
        (5.01, 10.00, 10000.00, 1)
        ON DUPLICATE KEY UPDATE id=id";
    
    if ($conn->query($sql_insert_rangos) === TRUE) {
        echo "   ✓ Rangos por defecto insertados (0-3km, 3-5km, 5-10km)\n";
    }
} else {
    $errores[] = "✗ Error creando tabla 'rangos_tarifas_domicilio': " . $conn->error;
    echo "   ✗ Error: " . $conn->error . "\n";
}

// ============================================
// 3. Agregar campos GPS a tabla pedidos
// ============================================
echo "\n📋 Paso 3: Agregando campos GPS a tabla 'pedidos'...\n";

$campos_pedidos = [
    "latitud_cliente DECIMAL(10, 8) NULL COMMENT 'Latitud del cliente'",
    "longitud_cliente DECIMAL(10, 8) NULL COMMENT 'Longitud del cliente'",
    "distancia_km DECIMAL(10,2) NULL COMMENT 'Distancia calculada en km'",
    "costo_domicilio DECIMAL(10,2) NULL COMMENT 'Costo de domicilio calculado'"
];

foreach ($campos_pedidos as $campo) {
    $nombre_campo = explode(' ', $campo)[0];
    
    // Verificar si el campo ya existe
    $check = $conn->query("SHOW COLUMNS FROM pedidos LIKE '$nombre_campo'");
    
    if ($check->num_rows == 0) {
        $sql_add = "ALTER TABLE pedidos ADD COLUMN $campo";
        if ($conn->query($sql_add) === TRUE) {
            echo "   ✓ Campo '$nombre_campo' agregado\n";
        } else {
            $errores[] = "✗ Error agregando campo '$nombre_campo': " . $conn->error;
            echo "   ✗ Error: " . $conn->error . "\n";
        }
    } else {
        echo "   ℹ Campo '$nombre_campo' ya existe\n";
    }
}

$exitos[] = "✓ Campos GPS agregados a tabla 'pedidos'";

// ============================================
// 4. Agregar campos GPS a configuracion_sistema
// ============================================
echo "\n📋 Paso 4: Agregando campos GPS a tabla 'configuracion_sistema'...\n";

$campos_config = [
    "latitud_restaurante DECIMAL(10, 8) NULL COMMENT 'Latitud del restaurante'",
    "longitud_restaurante DECIMAL(10, 8) NULL COMMENT 'Longitud del restaurante'"
];

foreach ($campos_config as $campo) {
    $nombre_campo = explode(' ', $campo)[0];
    
    // Verificar si el campo ya existe
    $check = $conn->query("SHOW COLUMNS FROM configuracion_sistema LIKE '$nombre_campo'");
    
    if ($check->num_rows == 0) {
        $sql_add = "ALTER TABLE configuracion_sistema ADD COLUMN $campo";
        if ($conn->query($sql_add) === TRUE) {
            echo "   ✓ Campo '$nombre_campo' agregado\n";
        } else {
            $errores[] = "✗ Error agregando campo '$nombre_campo': " . $conn->error;
            echo "   ✗ Error: " . $conn->error . "\n";
        }
    } else {
        echo "   ℹ Campo '$nombre_campo' ya existe\n";
    }
}

$exitos[] = "✓ Campos GPS agregados a tabla 'configuracion_sistema'";

// ============================================
// RESUMEN
// ============================================
echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 RESUMEN DE INSTALACIÓN\n";
echo str_repeat("=", 60) . "\n\n";

if (count($exitos) > 0) {
    echo "✅ EXITOSOS (" . count($exitos) . "):\n";
    foreach ($exitos as $exito) {
        echo "   $exito\n";
    }
    echo "\n";
}

if (count($errores) > 0) {
    echo "❌ ERRORES (" . count($errores) . "):\n";
    foreach ($errores as $error) {
        echo "   $error\n";
    }
    echo "\n";
} else {
    echo "🎉 ¡Instalación completada sin errores!\n\n";
}

echo "📝 PRÓXIMOS PASOS:\n";
echo "   1. Configurar coordenadas GPS del restaurante en panel admin\n";
echo "   2. Ajustar tarifas según necesidad\n";
echo "   3. Probar cálculo de distancia con direcciones reales\n";

echo "\n</pre>";

$conn->close();
?>
