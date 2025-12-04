<?php
// verificar_imagenes.php - Verificar rutas de imágenes
require_once 'config.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Verificar Imágenes</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f7fa}";
echo "table{width:100%;border-collapse:collapse;background:white}";
echo "th,td{padding:10px;border:1px solid #ddd;text-align:left}";
echo "th{background:#667eea;color:white}";
echo "img{max-width:100px;max-height:100px}";
echo "</style></head><body>";

echo "<h1>Verificación de Imágenes de Platos</h1>";

$conn = getDatabaseConnection();

$result = $conn->query("SELECT id, nombre, imagen FROM platos LIMIT 10");

echo "<table>";
echo "<tr><th>ID</th><th>Nombre</th><th>Ruta en BD</th><th>¿Existe?</th><th>Preview</th></tr>";

while ($plato = $result->fetch_assoc()) {
    $imagen = $plato['imagen'];
    $existe = file_exists($imagen) ? '✅ SI' : '❌ NO';
    
    echo "<tr>";
    echo "<td>" . $plato['id'] . "</td>";
    echo "<td>" . htmlspecialchars($plato['nombre']) . "</td>";
    echo "<td><code>" . htmlspecialchars($imagen) . "</code></td>";
    echo "<td>" . $existe . "</td>";
    echo "<td>";
    if ($imagen) {
        echo "<img src='" . htmlspecialchars($imagen) . "' onerror=\"this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22%3E%3Crect fill=%22%23ddd%22 width=%22100%22 height=%22100%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22%3E❌%3C/text%3E%3C/svg%3E'\">";
    } else {
        echo "Sin imagen";
    }
    echo "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<hr>";
echo "<h2>Directorio de Imágenes</h2>";
echo "<p><strong>Carpeta imagenes_platos:</strong></p>";

if (is_dir('imagenes_platos')) {
    echo "<p>✅ La carpeta existe</p>";
    $files = scandir('imagenes_platos');
    echo "<p>Archivos encontrados: " . (count($files) - 2) . "</p>";
    echo "<ul>";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "<li>" . htmlspecialchars($file) . "</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p>❌ La carpeta NO existe</p>";
}

$conn->close();

echo "</body></html>";
?>
