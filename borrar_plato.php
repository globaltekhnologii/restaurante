<?php
// Archivo: borrar_plato.php

// 1. DATOS DE CONEXIÓN
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "menu_restaurante"; 

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// 2. OBTENER el ID del plato a borrar
// Usamos $_GET porque el ID viene en la URL desde admin.php
$id_plato = $_GET['id'];

// 3. CONSULTA SQL para BORRAR
// ¡Cuidado! Esta consulta elimina la fila con el ID específico
$sql = "DELETE FROM platos WHERE id = $id_plato";

// 4. EJECUTAR y mostrar resultado
if ($conn->query($sql) === TRUE) {
    echo "<h1>❌ Plato ID: $id_plato eliminado con éxito.</h1>";
    echo "<p><a href='admin.php'>Volver al Panel de Administración</a></p>";
} else {
    echo "Error al eliminar el plato: " . $conn->error;
}

$conn->close();
?>