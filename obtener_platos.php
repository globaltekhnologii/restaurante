<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Importante: Permite que cualquier app (tu frontend) pida datos

include 'conexion.php';

// 1. Preparamos la consulta SQL ("Traeme todo de la tabla platos")
$sql = "SELECT * FROM platos";
$resultado = $conexion->query($sql);

$platos = []; // Creamos una lista vacía

// 2. Si hay resultados, los recorremos uno por uno
if ($resultado->num_rows > 0) {
    while($fila = $resultado->fetch_assoc()) {
        // Agregamos cada plato a nuestra lista
        $platos[] = $fila;
    }
}

// 3. Imprimimos la lista completa en formato JSON
echo json_encode($platos);
?>