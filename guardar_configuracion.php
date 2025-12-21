<?php
session_start();
require_once 'auth_helper.php';
verificarSesion();
verificarRolORedirect(['admin'], 'login.php');

require_once 'config.php';
require_once 'includes/tenant_context.php'; // NUEVO: Soporte multi-tenencia
require_once 'includes/csrf_helper.php';
require_once 'includes/sanitize_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin_configuracion.php");
    exit;
}

// Validar Token CSRF
verificarTokenOError();

$conn = getDatabaseConnection();

// Obtener tenant_id del usuario actual
$tenant_id = getCurrentTenantId();

// Obtener datos del formulario y sanitizar
$nombre_restaurante = cleanString($_POST['nombre_restaurante']);
$pais = cleanString($_POST['pais']);
$departamento = cleanString($_POST['departamento']);
$ciudad = cleanString($_POST['ciudad']);
$direccion = cleanString($_POST['direccion']);
$telefono = cleanString($_POST['telefono']);
$email = cleanEmail($_POST['email']);
$sitio_web = cleanString($_POST['sitio_web']);
$facebook = cleanString($_POST['facebook']);
$instagram = cleanString($_POST['instagram']);
$nit = cleanString($_POST['nit']);
$mensaje_pie_factura = cleanString($_POST['mensaje_pie_factura']);
$horario_atencion = cleanString($_POST['horario_atencion']);
$horario_apertura_domicilios = cleanString($_POST['horario_apertura_domicilios']);
$horario_cierre_domicilios = cleanString($_POST['horario_cierre_domicilios']);
$domicilios_habilitados = isset($_POST['domicilios_habilitados']) ? 1 : 0;

// Manejar subida de logo
$logo_url = null;
if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = 'img/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
    $new_filename = 'logo_negocio.' . $file_extension;
    $target_file = $upload_dir . $new_filename;
    
    if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_file)) {
        $logo_url = $target_file;
    }
}

// Construir consulta SQL
$sql = "UPDATE configuracion_sistema SET 
        nombre_restaurante = ?, 
        pais = ?, 
        departamento = ?, 
        ciudad = ?, 
        direccion = ?, 
        telefono = ?, 
        email = ?, 
        sitio_web = ?, 
        facebook = ?, 
        instagram = ?, 
        nit = ?,
        mensaje_pie_factura = ?,
        horario_atencion = ?,
        horario_apertura_domicilios = ?,
        horario_cierre_domicilios = ?,
        domicilios_habilitados = ?,
        dias_laborales = ?";

$params = [
    $nombre_restaurante, $pais, $departamento, $ciudad, $direccion, 
    $telefono, $email, $sitio_web, $facebook, $instagram, 
    $nit, $mensaje_pie_factura, $horario_atencion,
    $horario_apertura_domicilios, $horario_cierre_domicilios, $domicilios_habilitados
];

// Procesar Días Laborales
$dias_laborales = isset($_POST['dias']) ? json_encode($_POST['dias']) : json_encode([]);
$params[] = $dias_laborales;

$types = "sssssssssssssssis"; // Agregado 's' al final para dias_laborales

if ($logo_url) {
    $sql .= ", logo_url = ?";
    $params[] = $logo_url;
    $types .= "s";
}

$sql .= " WHERE tenant_id = ?"; // CORREGIDO: Filtrar por tenant
$params[] = $tenant_id;
$types .= "i";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    // Invalidar cache de sesión para que se recargue la nueva info
    if (session_status() === PHP_SESSION_NONE) session_start();
    unset($_SESSION['info_negocio']);
    
    header("Location: admin_configuracion.php?success=Configuración guardada correctamente");
} else {
    header("Location: admin_configuracion.php?error=Error al guardar: " . $conn->error);
}

$stmt->close();
$conn->close();
?>
