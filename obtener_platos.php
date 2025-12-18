<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Importante: Permite que cualquier app (tu frontend) pida datos


require_once 'config.php';
$conn = getDatabaseConnection();

// 1. Preparamos la consulta SQL ("Traeme todo de la tabla platos")
$sql = "SELECT * FROM platos";
$resultado = $conn->query($sql);

$platos = []; // Creamos una lista vacía

// 2. Si hay resultados, los recorremos uno por uno
if ($resultado && $resultado->num_rows > 0) {
    while($fila = $resultado->fetch_assoc()) {
        // Agregamos cada plato a nuestra lista
        $platos[] = $fila;
    }
}

// 3. Imprimimos la lista completa en formato JSON
echo json_encode($platos);

$conn->close();
?>