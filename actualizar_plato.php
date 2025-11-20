<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== TRUE) {
    header("Location: login.php");
    exit;
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "menu_restaurante";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Recibir datos del formulario
$id = $_POST['id'];
$nombre = $_POST['nombre'];
$descripcion = $_POST['descripcion'];
$precio = $_POST['precio'];
$categoria = $_POST['categoria'];
$imagen_actual = $_POST['imagen_actual'];

// Checkboxes (si no están marcados, valor = 0)
$popular = isset($_POST['popular']) ? 1 : 0;
$nuevo = isset($_POST['nuevo']) ? 1 : 0;
$vegano = isset($_POST['vegano']) ? 1 : 0;

// Manejar la subida de nueva imagen
$imagen_ruta = $imagen_actual; // Por defecto mantener la actual

if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
    $directorio_destino = "imagenes_platos/";
    
    // Crear directorio si no existe
    if (!file_exists($directorio_destino)) {
        mkdir($directorio_destino, 0777, true);
    }
    
    $nombre_archivo = basename($_FILES['imagen']['name']);
    $ruta_completa = $directorio_destino . time() . "_" . $nombre_archivo;
    
    // Verificar tipo de archivo
    $tipo_permitido = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    $extension = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));
    
    if (in_array($extension, $tipo_permitido)) {
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_completa)) {
            // Eliminar imagen antigua si existe
            if (!empty($imagen_actual) && file_exists($imagen_actual)) {
                unlink($imagen_actual);
            }
            $imagen_ruta = $ruta_completa;
        }
    }
}

// Actualizar plato en la base de datos
$stmt = $conn->prepare("UPDATE platos SET nombre = ?, descripcion = ?, precio = ?, imagen_ruta = ?, categoria = ?, popular = ?, nuevo = ?, vegano = ? WHERE id = ?");
$stmt->bind_param("ssdssiiii", $nombre, $descripcion, $precio, $imagen_ruta, $categoria, $popular, $nuevo, $vegano, $id);

if ($stmt->execute()) {
    // Redirigir con mensaje de éxito
    header("Location: editar_plato.php?id=" . $id . "&updated=1");
} else {
    echo "Error al actualizar: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>