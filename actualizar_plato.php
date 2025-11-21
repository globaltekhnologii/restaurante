<?php
// ============================================
// ACTUALIZAR PLATO - Procesa el formulario de edición
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
$id = intval($_POST['id']);
$nombre = trim($_POST['nombre']);
$descripcion = trim($_POST['descripcion']);
$precio = floatval($_POST['precio']);
$categoria = trim($_POST['categoria']);
$imagen_actual = $_POST['imagen_actual'];

// Checkboxes (0 si no están marcados, 1 si están marcados)
$popular = isset($_POST['popular']) ? 1 : 0;
$nuevo = isset($_POST['nuevo']) ? 1 : 0;
$vegano = isset($_POST['vegano']) ? 1 : 0;

// Validaciones básicas
$errores = [];

if (empty($nombre)) {
    $errores[] = "El nombre del plato es obligatorio.";
}

if ($precio <= 0) {
    $errores[] = "El precio debe ser mayor a 0.";
}

if (empty($categoria)) {
    $errores[] = "Debes seleccionar una categoría.";
}

// Si hay errores, redirigir de vuelta con mensaje
if (!empty($errores)) {
    $mensaje_error = implode(" | ", $errores);
    header("Location: editar_plato.php?id=" . $id . "&error=" . urlencode($mensaje_error));
    exit;
}

// Manejar la subida de nueva imagen (OPCIONAL)
$imagen_ruta = $imagen_actual; // Por defecto mantener la imagen actual

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
    if (in_array($extension, $extensiones_permitidas)) {
        
        // Validar tamaño (máximo 5MB)
        if ($_FILES['imagen']['size'] <= 5242880) {
            
            // Generar nombre único para evitar duplicados
            $nuevo_nombre = time() . "_" . uniqid() . "." . $extension;
            $ruta_completa = $directorio_destino . $nuevo_nombre;
            
            // Intentar mover el archivo subido
            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_completa)) {
                
                // Eliminar imagen antigua si existe y si es diferente
                if (!empty($imagen_actual) && file_exists($imagen_actual) && $imagen_actual != $ruta_completa) {
                    unlink($imagen_actual);
                }
                
                // Actualizar la ruta de la nueva imagen
                $imagen_ruta = $ruta_completa;
                
            } else {
                // Error al mover el archivo (pero no es crítico, seguimos con la imagen actual)
                error_log("Error al subir la nueva imagen para el plato ID: " . $id);
            }
            
        } else {
            // Archivo muy grande (pero no es crítico)
            error_log("Imagen muy grande para el plato ID: " . $id);
        }
        
    } else {
        // Extensión no permitida (pero no es crítico)
        error_log("Extensión no permitida para el plato ID: " . $id);
    }
}

// Preparar y ejecutar la consulta de actualización
// IMPORTANTE: 9 tipos (ssdssiiii) para 9 variables
$stmt = $conn->prepare("UPDATE platos SET nombre = ?, descripcion = ?, precio = ?, imagen_ruta = ?, categoria = ?, popular = ?, nuevo = ?, vegano = ? WHERE id = ?");

$stmt->bind_param("ssdssiiii", 
    $nombre,        // s = string
    $descripcion,   // s = string
    $precio,        // d = double
    $imagen_ruta,   // s = string
    $categoria,     // s = string
    $popular,       // i = integer
    $nuevo,         // i = integer
    $vegano,        // i = integer
    $id             // i = integer
);

if ($stmt->execute()) {
    // Actualización exitosa
    $stmt->close();
    $conn->close();
    
    // Redirigir con mensaje de éxito
    header("Location: editar_plato.php?id=" . $id . "&updated=1");
    exit;
    
} else {
    // Error en la actualización
    error_log("Error al actualizar plato ID " . $id . ": " . $stmt->error);
    $stmt->close();
    $conn->close();
    
    // Redirigir con mensaje de error
    header("Location: editar_plato.php?id=" . $id . "&error=Error al actualizar el plato. Intenta nuevamente.");
    exit;
}
?>