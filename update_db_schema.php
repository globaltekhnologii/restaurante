<?php
require_once 'config.php';

echo "Iniciando actualización de base de datos...\n";

$conn = getDatabaseConnection();

// Array de columnas a verificar/agregar
$columns = [
    'horario_apertura_domicilios' => "ADD COLUMN horario_apertura_domicilios TIME DEFAULT '09:00:00'",
    'horario_cierre_domicilios' => "ADD COLUMN horario_cierre_domicilios TIME DEFAULT '22:00:00'",
    'domicilios_habilitados' => "ADD COLUMN domicilios_habilitados TINYINT(1) DEFAULT 1"
];

foreach ($columns as $colName => $alterStatement) {
    // Verificar si la columna existe
    $checkSql = "SHOW COLUMNS FROM configuracion_sistema LIKE '$colName'";
    $result = $conn->query($checkSql);

    if ($result && $result->num_rows == 0) {
        echo "Agregando columna: $colName...\n";
        $alterSql = "ALTER TABLE configuracion_sistema $alterStatement";
        if ($conn->query($alterSql) === TRUE) {
            echo "✅ Columna $colName agregada correctamente.\n";
        } else {
            echo "❌ Error al agregar $colName: " . $conn->error . "\n";
        }
    } else {
        echo "ℹ️ La columna $colName ya existe.\n";
    }
}

closeDatabaseConnection($conn);
echo "Actualización completada.\n";
?>
