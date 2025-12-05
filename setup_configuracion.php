<?php
require_once 'config.php';

// Conectar a la base de datos
$conn = getDatabaseConnection();

// Leer el archivo SQL
$sql_file = 'sql/configuracion.sql';
if (!file_exists($sql_file)) {
    die("Error: No se encuentra el archivo $sql_file");
}

$sql_content = file_get_contents($sql_file);

// Ejecutar múltiples consultas
if ($conn->multi_query($sql_content)) {
    do {
        // Consumir resultados para evitar errores de sincronización
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    
    echo "✅ Tabla 'configuracion_sistema' creada o verificada correctamente.<br>";
} else {
    echo "❌ Error al ejecutar SQL: " . $conn->error . "<br>";
}

$conn->close();
?>
