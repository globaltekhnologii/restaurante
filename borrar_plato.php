<?php
// ============================================
// BORRAR PLATO - Elimina un plato de la base de datos
// ============================================

session_start();

// Verificar autenticación
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== TRUE) {
    header("Location: login.php");
    exit;
}

// Usar configuración centralizada
require_once 'config.php';
$conn = getDatabaseConnection();

// Verificar que se recibió el ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: admin.php?error=ID no válido");
    exit;
}

$id_plato = intval($_GET['id']);

// Verificar si se confirmó la eliminación
if (!isset($_GET['confirmar'])) {
    // Obtener información del plato para mostrar confirmación
    $stmt = $conn->prepare("SELECT nombre, imagen_ruta FROM platos WHERE id = ?");
    $stmt->bind_param("i", $id_plato);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        // El plato no existe
        $stmt->close();
        $conn->close();
        header("Location: admin.php?error=Plato no encontrado");
        exit;
    }
    
    $plato = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    // Mostrar página de confirmación
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
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
                animation: slideIn 0.4s ease;
            }
            
            @keyframes slideIn {
                from { opacity: 0; transform: translateY(-30px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            .warning-icon {
                font-size: 5em;
                margin-bottom: 20px;
                animation: shake 0.5s ease;
            }
            
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-10px); }
                75% { transform: translateX(10px); }
            }
            
            h2 {
                color: #dc3545;
                margin-bottom: 15px;
                font-size: 2em;
            }
            
            .plato-info {
                background: #f8f9fa;
                padding: 20px;
                border-radius: 10px;
                margin: 25px 0;
                border-left: 4px solid #dc3545;
            }
            
            .plato-info strong {
                color: #333;
                font-size: 1.2em;
            }
            
            .plato-imagen {
                max-width: 200px;
                max-height: 150px;
                border-radius: 8px;
                margin: 15px auto;
                display: block;
                box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            }
            
            .warning-text {
                color: #666;
                margin-bottom: 30px;
                line-height: 1.6;
            }
            
            .button-group {
                display: flex;
                gap: 15px;
                justify-content: center;
            }
            
            .btn {
                padding: 14px 30px;
                border: none;
                border-radius: 8px;
                font-size: 1.1em;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s;
                text-decoration: none;
                display: inline-block;
            }
            
            .btn-danger {
                background: #dc3545;
                color: white;
            }
            
            .btn-danger:hover {
                background: #c82333;
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(220,53,69,0.4);
            }
            
            .btn-secondary {
                background: #6c757d;
                color: white;
            }
            
            .btn-secondary:hover {
                background: #5a6268;
                transform: translateY(-2px);
            }
            
            @media (max-width: 480px) {
                .confirm-container { padding: 25px; }
                .button-group { flex-direction: column; }
                .btn { width: 100%; }
            }
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
            
            <p class="warning-text">
                Esta acción <strong>no se puede deshacer</strong>. El plato y su imagen serán eliminados permanentemente de la base de datos.
            </p>
            
            <div class="button-group">
                <a href="borrar_plato.php?id=<?php echo $id_plato; ?>&confirmar=si" class="btn btn-danger">
                    🗑️ Sí, Eliminar
                </a>
                <a href="admin.php" class="btn btn-secondary">
                    ↩️ Cancelar
                </a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Si llegamos aquí, es porque se confirmó la eliminación
if ($_GET['confirmar'] == 'si') {
    
    // Primero obtener la ruta de la imagen para eliminarla
    $stmt = $conn->prepare("SELECT imagen_ruta FROM platos WHERE id = ?");
    $stmt->bind_param("i", $id_plato);
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
    
    // Eliminar el plato de la base de datos
    $stmt = $conn->prepare("DELETE FROM platos WHERE id = ?");
    $stmt->bind_param("i", $id_plato);
    
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
        // Error al eliminar
        error_log("Error al eliminar plato ID " . $id_plato . ": " . $stmt->error);
        $stmt->close();
        $conn->close();
        
        header("Location: admin.php?error=Error al eliminar el plato");
        exit;
    }
    
} else {
    // Si no se confirmó correctamente, redirigir
    $conn->close();
    header("Location: admin.php");
    exit;
}
?>