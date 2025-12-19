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

// Usar configuración centralizada
require_once 'config.php';
require_once 'file_upload_helper.php';
require_once 'includes/csrf_helper.php';
require_once 'includes/sanitize_helper.php';

$conn = getDatabaseConnection();

// Verificar que se recibieron los datos por POST
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: admin.php");
    exit;
}

// Validar Token CSRF
verificarTokenOError();

// Recibir y sanitizar datos del formulario
$nombre = cleanString($_POST['nombre']);
$descripcion = cleanHtml($_POST['descripcion']); // Permitir HTML básico en descripción
$precio = cleanFloat($_POST['precio']);
$categoria = cleanString($_POST['categoria']);

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
    closeDatabaseConnection($conn);
    header("Location: admin.php?error=" . urlencode($mensaje_error));
    exit;
}

// Validar imagen subida con helper seguro
$validacion = validarImagenSubida($_FILES['imagen']);

if (!$validacion['valido']) {
    closeDatabaseConnection($conn);
    header("Location: admin.php?error=" . urlencode($validacion['error']));
    exit;
}

// Mover archivo a destino
$resultado = moverArchivoSubido($_FILES['imagen'], 'imagenes_platos/');

if (!$resultado['exito']) {
    closeDatabaseConnection($conn);
    header("Location: admin.php?error=" . urlencode($resultado['error']));
    exit;
}

$imagen_ruta = $resultado['ruta'];

// ============================================
// VALIDACIÓN DE LÍMITES DEL PLAN (Tenant ID)
// ============================================
if (file_exists(__DIR__ . '/tenant_config.php')) {
    require_once __DIR__ . '/tenant_config.php';
    require_once __DIR__ . '/includes/tenant_limits.php';
    
    // Verificar límite de platos
    $limitCheck = checkCanAddMenuItem();
    
    if (!$limitCheck['allowed']) {
        // Eliminar imagen subida
        eliminarArchivoSeguro($imagen_ruta);
        closeDatabaseConnection($conn);
        header("Location: admin.php?error=" . urlencode($limitCheck['message']));
        exit;
    }
    
    // Verificar límite de almacenamiento
    if (file_exists($imagen_ruta)) {
        $fileSize = filesize($imagen_ruta);
        $storageCheck = checkStorageLimit($fileSize);
        
        if (!$storageCheck['allowed']) {
            // Eliminar imagen subida
            eliminarArchivoSeguro($imagen_ruta);
            closeDatabaseConnection($conn);
            header("Location: admin.php?error=" . urlencode($storageCheck['message']));
            exit;
        }
    }
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
    closeDatabaseConnection($conn);
    
    // Redirigir con mensaje de éxito
    header("Location: admin.php?success=1&nuevo_id=" . $nuevo_id);
    exit;
    
} else {
    // Error en la inserción
    error_log("Error al insertar plato: " . $stmt->error);
    
    // Eliminar la imagen si se subió pero falló la inserción
    eliminarArchivoSeguro($imagen_ruta);
    
    $stmt->close();
    closeDatabaseConnection($conn);
    
    // Redirigir con mensaje de error
    header("Location: admin.php?error=Error al guardar el plato en la base de datos. Intenta nuevamente.");
    exit;
}
?>