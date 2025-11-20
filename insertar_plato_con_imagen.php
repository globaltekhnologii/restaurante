<?php
// Archivo: insertar_plato_con_imagen.php

// 1. DATOS DE CONEXIÓN
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "menu_restaurante"; 

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// 2. OBTENER DATOS DEL FORMULARIO
$nombre      = $_POST['nombre'];
$descripcion = $_POST['descripcion'];
$precio      = $_POST['precio'];

// --- PROCESAMIENTO DE LA IMAGEN (Nuevo) ---
$target_dir = "imagenes_platos/"; // Carpeta de destino
$archivo_nombre = basename($_FILES["imagen"]["name"]);
$target_file = $target_dir . $archivo_nombre;
$uploadOk = 1;
$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

// Generar un nombre único para evitar que se sobrescriban archivos con el mismo nombre
$nuevo_nombre_imagen = uniqid() . "." . $imageFileType;
$target_file_final = $target_dir . $nuevo_nombre_imagen;

// 3. MOVER el archivo subido de la carpeta temporal al destino final
if (move_uploaded_file($_FILES["imagen"]["tmp_name"], $target_file_final)) {
    // Si se mueve con éxito, la ruta que guardaremos en DB es el nombre final
    $imagen_ruta_db = $target_file_final; 
} else {
    // Si falla, detenemos el proceso (ajustar mensaje en producción)
    die("Error al subir el archivo. Intenta de nuevo."); 
}

// 4. PREPARAR LA CONSULTA SQL (INSERTAR CON LA RUTA DE IMAGEN)
$sql = "INSERT INTO platos (nombre, descripcion, precio, imagen_ruta) 
        VALUES ('$nombre', '$descripcion', '$precio', '$imagen_ruta_db')";

// 5. EJECUTAR LA CONSULTA
if ($conn->query($sql) === TRUE) {
    echo "<h1>✅ Plato agregado con imagen con éxito.</h1>";
    echo "<p><a href='admin.php'>Volver al formulario</a></p>";
} else {
    echo "Error al agregar el plato: " . $conn->error;
}

$conn->close();
?>