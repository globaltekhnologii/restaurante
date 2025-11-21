<?php
// ============================================
// INSERTAR PLATO CON IMAGEN - Crea nuevos platos en el menú
// ============================================

session_start();

// Verificar autenticación
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== TRUE) {
    header("Location: login.php");
    exit;
}

// Configuración de la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "menu_restaurante";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Verificar que se recibieron los datos por POST
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: admin.php");
    exit;
}

// Recibir y sanitizar datos del formulario
$nombre = trim($_POST['nombre']);
$descripcion = trim($_POST['descripcion']);
$precio = floatval($_POST['precio']);
$categoria = trim($_POST['categoria']);

// Checkboxes (0 si no están marcados, 1 si están marcados)
$popular = isset($_POST['popular']) ? 1 : 0;
$nuevo = isset($_POST['nuevo']) ? 1 : 0;
$vegano = isset($_POST['vegano']) ? 1 : 0;

// Validaciones básicas
$errores = [];

if (empty($nombre)) {
    $errores[] = "El nombre del plato es obligatorio.";
}

if (empty($descripcion)) {
    $errores[] = "La descripción es obligatoria.";
}

if ($precio <= 0) {
    $errores[] = "El precio debe ser mayor a 0.";
}

if (empty($categoria)) {
    $errores[] = "Debes seleccionar una categoría.";
}

// Validar que se subió una imagen
if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] == 4) {
    $errores[] = "Debes subir una imagen del plato.";
}

// Si hay errores, redirigir de vuelta con mensaje
if (!empty($errores)) {
    $mensaje_error = implode(" | ", $errores);
    header("Location: admin.php?error=" . urlencode($mensaje_error));
    exit;
}

// Manejar la subida de la imagen
$imagen_ruta = "";

if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
    
    $directorio_destino = "imagenes_platos/";
    
    // Crear directorio si no existe
    if (!file_exists($directorio_destino)) {
        mkdir($directorio_destino, 0777, true);
    }
    
    // Obtener información del archivo
    $nombre_archivo = basename($_FILES['imagen']['name']);
    $extension = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));
    
    // Extensiones permitidas
    $extensiones_permitidas = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    
    // Validar extensión
    if (!in_array($extension, $extensiones_permitidas)) {
        $conn->close();
        header("Location: admin.php?error=Solo se permiten imágenes (JPG, JPEG, PNG, GIF, WEBP)");
        exit;
    }
    
    // Validar tamaño (máximo 5MB)
    if ($_FILES['imagen']['size'] > 5242880) {
        $conn->close();
        header("Location: admin.php?error=La imagen es muy grande. Máximo 5MB");
        exit;
    }
    
    // Validar que es una imagen real
    $check = getimagesize($_FILES['imagen']['tmp_name']);
    if ($check === false) {
        $conn->close();
        header("Location: admin.php?error=El archivo no es una imagen válida");
        exit;
    }
    
    // Generar nombre único para evitar duplicados
    $nuevo_nombre = time() . "_" . uniqid() . "." . $extension;
    $ruta_completa = $directorio_destino . $nuevo_nombre;
    
    // Intentar mover el archivo subido
    if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_completa)) {
        $imagen_ruta = $ruta_completa;
    } else {
        $conn->close();
        header("Location: admin.php?error=Error al subir la imagen. Verifica los permisos de la carpeta.");
        exit;
    }
    
} else {
    // Error en la subida
    $error_code = $_FILES['imagen']['error'];
    $conn->close();
    header("Location: admin.php?error=Error al subir la imagen (Código: " . $error_code . ")");
    exit;
}

// Preparar y ejecutar la consulta de inserción
$stmt = $conn->prepare("INSERT INTO platos (nombre, descripcion, precio, imagen_ruta, categoria, popular, nuevo, vegano) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param("ssdssiiii", 
    $nombre, 
    $descripcion, 
    $precio, 
    $imagen_ruta, 
    $categoria, 
    $popular, 
    $nuevo, 
    $vegano
);

if ($stmt->execute()) {
    // Inserción exitosa
    $nuevo_id = $stmt->insert_id;
    $stmt->close();
    $conn->close();
    
    // Redirigir con mensaje de éxito
    header("Location: admin.php?success=1&nuevo_id=" . $nuevo_id);
    exit;
    
} else {
    // Error en la inserción
    error_log("Error al insertar plato: " . $stmt->error);
    
    // Eliminar la imagen si se subió pero falló la inserción
    if (!empty($imagen_ruta) && file_exists($imagen_ruta)) {
        unlink($imagen_ruta);
    }
    
    $stmt->close();
    $conn->close();
    
    // Redirigir con mensaje de error
    header("Location: admin.php?error=Error al guardar el plato en la base de datos. Intenta nuevamente.");
    exit;
}
?>