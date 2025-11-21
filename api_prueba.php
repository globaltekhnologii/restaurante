<?php
header('Content-Type: application/json');

// 1. Intentamos incluir la conexión. Si falla, el código de conexion.php nos avisará.
include 'conexion.php';

$respuesta = [
    "estado" => "exito",
    "mensaje" => "¡Conexión a la base de datos exitosa!",
    "base_de_datos" => "platofacil"
];

echo json_encode($respuesta);
?>