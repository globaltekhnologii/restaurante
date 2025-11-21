<?php
// Datos de conexión por defecto de XAMPP
$host = "localhost";
$usuario = "root"; // En XAMPP el usuario por defecto es root
$contrasenia = ""; // En XAMPP la contraseña por defecto viene vacía
$base_de_datos = "platofacil";

// Intentar conectar
$conexion = new mysqli($host, $usuario, $contrasenia, $base_de_datos);

// Verificar si hubo error
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Si llegamos aquí, es que conectó bien.
// (No imprimimos nada aquí para no ensuciar las respuestas JSON más adelante)
?>