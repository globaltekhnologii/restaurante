<?php
// Forzar visualización de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Diagnostico de Platos</h1>";

require 'config.php';
$conn = getDatabaseConnection();

echo "Conexion Exitosa.<br>";

// 1. Verificar columnas de la tabla
echo "<h2>1. Estructura de Tabla 'platos'</h2>";
$result = $conn->query("SHOW COLUMNS FROM platos");
if ($result) {
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>" . $row['Field'] . " (" . $row['Type'] . ")</li>";
    }
    echo "</ul>";
} else {
    echo "ERROR al leer columnas: " . $conn->error;
}

// 2. Probar Permisos de Carpeta
echo "<h2>2. Permisos de Carpeta</h2>";
$carpeta = "imagenes_platos/";
if (!file_exists($carpeta)) {
    echo "Carpeta no existe. Intentando crear...<br>";
    if (@mkdir($carpeta, 0777, true)) echo "Creada OK.<br>";
    else echo "ERROR al crear carpeta. Checar permisos.<br>";
}

if (is_writable($carpeta)) {
    echo "Carpeta tiene permisos de ESCRITURA OK.<br>";
} else {
    echo "ERROR: Carpeta NO tiene permisos de escritura.<br>";
}

// 3. Probar Preparación de Consulta
echo "<h2>3. Test de Insercion SQL</h2>";
$sql = "INSERT INTO platos (nombre, descripcion, precio, imagen_ruta, categoria, popular, nuevo, vegano) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if ($stmt) {
    echo "PREPARE OK: La consulta es válida.<br>";
    $stmt->close();
} else {
    echo "PREPARE FALLO: " . $conn->error . "<br>";
    echo "Posible causa: Nombres de columnas incorrectos o tabla faltante.<br>";
}
?>
