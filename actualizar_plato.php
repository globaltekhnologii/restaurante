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

// Usar configuración centralizada
require_once 'config.php';
require_once 'file_upload_helper.php';

$conn = getDatabaseConnection();

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
    closeDatabaseConnection($conn);
    header("Location: editar_plato.php?id=" . $id . "&error=" . urlencode($mensaje_error));
    exit;
}

// Manejar la imagen (opcional en actualización)
$imagen_ruta = $imagen_actual; // Por defecto mantener la imagen actual

if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
    
    // Validar imagen subida con helper seguro
    $validacion = validarImagenSubida($_FILES['imagen']);
    
    if (!$validacion['valido']) {
        closeDatabaseConnection($conn);
        header("Location: editar_plato.php?id={$id}&error=" . urlencode($validacion['error']));
        exit;
    }
    
    // Mover archivo a destino
    $resultado = moverArchivoSubido($_FILES['imagen'], 'imagenes_platos/');
    
    if (!$resultado['exito']) {
        closeDatabaseConnection($conn);
        header("Location: editar_plato.php?id={$id}&error=" . urlencode($resultado['error']));
        exit;
    }
    
    // Eliminar imagen antigua si existe
    if (!empty($imagen_actual)) {
        eliminarArchivoSeguro($imagen_actual);
    }
    
    // Actualizar la ruta de la nueva imagen
    $imagen_ruta = $resultado['ruta'];
}

// Preparar y ejecutar la consulta de actualización
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
    closeDatabaseConnection($conn);
    
    // Redirigir con mensaje de éxito
    header("Location: editar_plato.php?id=" . $id . "&updated=1");
    exit;
    
} else {
    // Error en la actualización
    error_log("Error al actualizar plato ID " . $id . ": " . $stmt->error);
    $stmt->close();
    closeDatabaseConnection($conn);
    
    // Redirigir con mensaje de error
    header("Location: editar_plato.php?id=" . $id . "&error=Error al actualizar el plato. Intenta nuevamente.");
    exit;
}
?>