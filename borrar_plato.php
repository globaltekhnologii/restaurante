<?php
// ============================================
// BORRAR PLATO - Elimina un plato de la base de datos de forma segura
// ============================================

session_start();

// Verificar autenticación
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== TRUE) {
    header("Location: login.php");
    exit;
}

require_once 'config.php';
require_once 'includes/csrf_helper.php';
require_once 'includes/sanitize_helper.php';
require_once 'includes/tenant_context.php'; // NUEVO: Soporte multi-tenencia

$conn = getDatabaseConnection();

// Obtener tenant_id del usuario actual
$tenant_id = getCurrentTenantId();

// === LÓGICA DE PROCESAMIENTO (POST) ===
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validar CSRF
    verificarTokenOError();
    
    // Obtener ID
    $id_plato = cleanInt($_POST['id']);
    
    if ($id_plato <= 0) {
        header("Location: admin.php?error=ID no válido");
        exit;
    }
    
    // Primero obtener la ruta de la imagen para eliminarla (verificando tenant)
    $stmt = $conn->prepare("SELECT imagen_ruta FROM platos WHERE id = ? AND tenant_id = ?");
    $stmt->bind_param("ii", $id_plato, $tenant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $stmt->close();
        $conn->close();
        header("Location: admin.php?error=Plato no encontrado");
        exit;
    }
    
    $plato = $result->fetch_assoc();
    $imagen_ruta = $plato['imagen_ruta'];
    $stmt->close();
    
    // Eliminar el plato de la base de datos (verificando tenant)
    $stmt = $conn->prepare("DELETE FROM platos WHERE id = ? AND tenant_id = ?");
    $stmt->bind_param("ii", $id_plato, $tenant_id);
    
    if ($stmt->execute()) {
        // Eliminar la imagen del servidor si existe
        if (!empty($imagen_ruta) && file_exists($imagen_ruta)) {
            unlink($imagen_ruta);
        }
        
        $stmt->close();
        $conn->close();
        
        // Redirigir con mensaje de éxito
        header("Location: admin.php?deleted=1");
        exit;
    } else {
        error_log("Error al eliminar plato ID " . $id_plato . ": " . $stmt->error);
        $stmt->close();
        $conn->close();
        header("Location: admin.php?error=Error al eliminar el plato");
        exit;
    }
}

// === LÓGICA DE CONFIRMACIÓN (GET o cualquier otro método) ===
// Si se accede por GET, mostrar pantalla de confirmación segura
// Esto sirve si alguien accede directamente al link antiguo o intenta navegar manualmente

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: admin.php");
    exit;
}

$id_plato = cleanInt($_GET['id']);

// Obtener info del plato (verificando tenant)
$stmt = $conn->prepare("SELECT nombre, imagen_ruta FROM platos WHERE id = ? AND tenant_id = ?");
$stmt->bind_param("ii", $id_plato, $tenant_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: admin.php?error=Plato no encontrado");
    exit;
}

$plato = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar Eliminación</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .confirm-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        .warning-icon { font-size: 5em; margin-bottom: 20px; }
        h2 { color: #dc3545; margin-bottom: 15px; }
        .plato-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 25px 0;
            border-left: 4px solid #dc3545;
        }
        .plato-imagen {
            max-width: 200px; max-height: 150px;
            border-radius: 8px; margin: 15px auto;
            display: block; box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .btn {
            padding: 14px 30px; border: none; border-radius: 8px;
            font-size: 1.1em; font-weight: 600; cursor: pointer;
            text-decoration: none; display: inline-block;
            margin: 5px;
        }
        .btn-danger { background: #dc3545; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
    </style>
</head>
<body>
    <div class="confirm-container">
        <div class="warning-icon">⚠️</div>
        <h2>¿Estás Seguro?</h2>
        
        <div class="plato-info">
            <p><strong><?php echo htmlspecialchars($plato['nombre']); ?></strong></p>
            <?php if (!empty($plato['imagen_ruta']) && file_exists($plato['imagen_ruta'])): ?>
                <img src="<?php echo htmlspecialchars($plato['imagen_ruta']); ?>" 
                     alt="<?php echo htmlspecialchars($plato['nombre']); ?>" 
                     class="plato-imagen">
            <?php endif; ?>
        </div>
        
        <p style="color: #666; margin-bottom: 30px;">
            Esta acción <strong>no se puede deshacer</strong>.
        </p>
        
        <form action="borrar_plato.php" method="POST">
            <input type="hidden" name="id" value="<?php echo $id_plato; ?>">
            <?php echo csrf_field(); ?>
            <button type="submit" class="btn btn-danger">🗑️ Sí, Eliminar</button>
            <a href="admin.php" class="btn btn-secondary">↩️ Cancelar</a>
        </form>
    </div>
</body>
</html>
