<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== TRUE) {
    header("Location: login.php");
    exit;
}

// Usar configuración centralizada
require_once 'config.php';
$conn = getDatabaseConnection();

// Recibir datos del formulario
$nombre = $_POST['nombre'];
$descripcion = $_POST['descripcion'];
$precio = $_POST['precio'];
$categoria = $_POST['categoria'];

// Checkboxes (si no están marcados, valor = 0)
$popular = isset($_POST['popular']) ? 1 : 0;
$nuevo = isset($_POST['nuevo']) ? 1 : 0;
$vegano = isset($_POST['vegano']) ? 1 : 0;

// Manejar la subida de imagen
$imagen_ruta = "";

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
            $imagen_ruta = $ruta_completa;
        }
    }
}

// ============================================
// VALIDACIÓN DE LÍMITES DEL PLAN (Tenant ID)
// ============================================
if (file_exists(__DIR__ . '/tenant_config.php')) {
    require_once __DIR__ . '/tenant_config.php';
    require_once __DIR__ . '/includes/tenant_limits.php';
    
    // Verificar límite de platos
    $limitCheck = checkCanAddMenuItem();
    
    if (!$limitCheck['allowed']) {
        $conn->close();
        header("Location: admin.php?error=" . urlencode($limitCheck['message']));
        exit;
    }
    
    // Si hay imagen, verificar límite de almacenamiento
    if (!empty($imagen_ruta) && file_exists($imagen_ruta)) {
        $fileSize = filesize($imagen_ruta);
        $storageCheck = checkStorageLimit($fileSize);
        
        if (!$storageCheck['allowed']) {
            // Eliminar imagen subida
            unlink($imagen_ruta);
            $conn->close();
            header("Location: admin.php?error=" . urlencode($storageCheck['message']));
            exit;
        }
    }
}

// Preparar consulta SQL con los nuevos campos
$stmt = $conn->prepare("INSERT INTO platos (nombre, descripcion, precio, imagen_ruta, categoria, popular, nuevo, vegano) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssdssiii", $nombre, $descripcion, $precio, $imagen_ruta, $categoria, $popular, $nuevo, $vegano);

if ($stmt->execute()) {
    // Redirigir con mensaje de éxito
    header("Location: admin.php?success=1");
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>