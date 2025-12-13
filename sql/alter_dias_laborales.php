<?php
require_once __DIR__ . '/../config.php';
$conn = getDatabaseConnection();

// Verificar si existe la columna
$check = $conn->query("SHOW COLUMNS FROM configuracion_sistema LIKE 'dias_laborales'");

if ($check->num_rows == 0) {
    // Agregar columna (Default: todos los días)
    // 1=Lunes, 7=Domingo (Formato ISO-8601 numérico)
    $default = json_encode(["1","2","3","4","5","6","7"]);
    $sql = "ALTER TABLE configuracion_sistema ADD COLUMN dias_laborales TEXT DEFAULT '$default'";
    
    if ($conn->query($sql)) {
        echo "Columna 'dias_laborales' agregada correctamente.\n";
        // Actualizar fila 1 con default por si acaso
        $conn->query("UPDATE configuracion_sistema SET dias_laborales = '$default' WHERE id = 1");
    } else {
        echo "Error al agregar columna: " . $conn->error . "\n";
    }
} else {
    echo "La columna 'dias_laborales' ya existe.\n";
}

$conn->close();
?>
