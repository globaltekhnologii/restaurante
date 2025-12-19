<?php
require_once 'config.php';
checkSuperAdminAuth();

require_once '../../core/version.php';
require_once '../../core/update_manager.php';

$conn = getDBConnection();
$message = '';
$message_type = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'check_updates') {
        $updateManager = new UpdateManager();
        $updateInfo = $updateManager->buscarActualizaciones();
        
        if ($updateInfo) {
            // Guardar en BD
            $stmt = $conn->prepare("INSERT INTO system_updates 
                (version, descripcion, tipo, archivo_url, fecha_publicacion, estado) 
                VALUES (?, ?, ?, ?, ?, 'disponible')");
            
            $stmt->bind_param("sssss", 
                $updateInfo['version'],
                $updateInfo['descripcion'],
                $updateInfo['tipo'],
                $updateInfo['archivo_url'],
                $updateInfo['fecha_publicacion']
            );
            
            if ($stmt->execute()) {
                $message = "Se encontró una nueva versión: " . $updateInfo['version'];
                $message_type = "success";
            }
            
            $stmt->close();
        } else {
            $message = "No hay actualizaciones disponibles. Versión actual: " . SYSTEM_VERSION;
            $message_type = "info";
        }
    }
    elseif ($action === 'apply_update') {
        $update_id = intval($_POST['update_id']);
        
        // Obtener información de la actualización
        $stmt = $conn->prepare("SELECT * FROM system_updates WHERE id = ?");
        $stmt->bind_param("i", $update_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $update = $result->fetch_assoc();
        $stmt->close();
        
        if ($update) {
            $updateManager = new UpdateManager();
            
            // Actualizar estado a "descargando"
            $conn->query("UPDATE system_updates SET estado = 'descargando' WHERE id = $update_id");
            
            // Descargar
            $filepath = $updateManager->descargarUpdate($update['archivo_url'], $update['version']);
            
            if ($filepath) {
                // Verificar integridad
                if ($updateManager->verificarIntegridad($filepath, $update['checksum'])) {
                    // Actualizar estado a "aplicando"
                    $conn->query("UPDATE system_updates SET estado = 'aplicando' WHERE id = $update_id");
                    
                    // Aplicar actualización
                    if ($updateManager->aplicarUpdate($filepath, $update['version'])) {
                        $message = "Actualización aplicada exitosamente a versión " . $update['version'];
                        $message_type = "success";
                    } else {
                        $message = "Error al aplicar la actualización";
                        $message_type = "error";
                    }
                } else {
                    $message = "Error: El archivo descargado no pasó la verificación de integridad";
                    $message_type = "error";
                    $conn->query("UPDATE system_updates SET estado = 'fallido' WHERE id = $update_id");
                }
            } else {
                $message = "Error al descargar la actualización";
                $message_type = "error";
                $conn->query("UPDATE system_updates SET estado = 'fallido' WHERE id = $update_id");
            }
        }
    }
}

// Obtener historial de actualizaciones
$updates = [];
$result = $conn->query("SELECT * FROM system_updates ORDER BY fecha_publicacion DESC LIMIT 10");
while ($row = $result->fetch_assoc()) {
    $updates[] = $row;
}

$conn->close();
$current_admin = getCurrentSuperAdmin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Actualizaciones - Super Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f3f4f6;
            color: #1f2937;
        }
        
        .navbar { background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .navbar-brand { font-size: 1.5rem; font-weight: bold; color: #3b82f6; }
        .navbar-menu { display: flex; gap: 2rem; align-items: center; }
        .navbar-menu a { text-decoration: none; color: #6b7280; font-weight: 500; transition: color 0.2s; }
        .navbar-menu a:hover, .navbar-menu a.active { color: #3b82f6; }
        .user-info { display: flex; align-items: center; gap: 0.5rem; color: #6b7280; }
        .dropdown { position: relative; display: inline-block; }
        .dropbtn { background: none; border: none; cursor: pointer; color: #6b7280; }
        .dropdown-content { display: none; position: absolute; right: 0; background-color: #f9f9f9; min-width: 160px; box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2); z-index: 1; }
        .dropdown-content a { color: black; padding: 12px 16px; text-decoration: none; display: block; }
        .dropdown:hover .dropdown-content { display: block; }
        .dropdown-content a:hover { background-color: #f1f1f1; }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .version-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-size: 1.1em;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(102,126,234,0.3);
        }
        
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: #22c55e;
            color: white;
        }
        
        .btn-warning {
            background: #f59e0b;
            color: white;
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .message.success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #22c55e;
        }
        
        .message.error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        
        .message.info {
            background: #dbeafe;
            color: #1e40af;
            border-left: 4px solid #3b82f6;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            text-align: left;
            padding: 0.75rem;
            background: #f9fafb;
            font-weight: 600;
            font-size: 0.875rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        td {
            padding: 0.75rem;
            border-top: 1px solid #e5e7eb;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .badge-primary {
            background: #dbeafe;
            color: #1e3a8a;
        }
        
        .badge-dark {
            background: #e5e7eb;
            color: #1f2937;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }
        
        .empty-state-icon {
            font-size: 4em;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .info-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .info-box h3 {
            color: #1e40af;
            margin-bottom: 0.5rem;
        }
        
        .info-box p {
            color: #1e40af;
            font-size: 0.9em;
        }
    </style>
</head>
<body>

    <?php include 'includes/navbar.php'; ?>

    <div class="container">
        <div class="header">
            <div>
                <h1>Gestión de Actualizaciones</h1>
                <div class="version-badge" style="margin-top: 1rem;">
                    📦 Versión Actual: <?php echo SYSTEM_VERSION; ?>
                </div>
            </div>
            <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="check_updates">
                <button type="submit" class="btn btn-primary">
                    🔍 Buscar Actualizaciones
                </button>
            </form>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="info-box">
            <h3>ℹ️ Información del Sistema de Actualizaciones</h3>
            <p>
                Este sistema busca actualizaciones desde un servidor remoto (GitHub Releases por defecto).
                Antes de aplicar cualquier actualización, se crea automáticamente un backup completo del sistema.
            </p>
            <p style="margin-top: 0.5rem;">
                <strong>Configuración:</strong> Edita la URL del servidor en <code>core/update_manager.php</code>
            </p>
        </div>

        <div class="card">
            <h2 style="margin-bottom: 1rem;">Historial de Actualizaciones</h2>
            
            <?php if (count($updates) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Versión</th>
                            <th>Tipo</th>
                            <th>Descripción</th>
                            <th>Fecha Publicación</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($updates as $update): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($update['version']); ?></strong></td>
                                <td>
                                    <?php
                                    $tipo_badges = [
                                        'critico' => 'badge-danger',
                                        'seguridad' => 'badge-warning',
                                        'feature' => 'badge-primary',
                                        'bugfix' => 'badge-info'
                                    ];
                                    $badge_class = $tipo_badges[$update['tipo']] ?? 'badge-dark';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo ucfirst($update['tipo']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars(substr($update['descripcion'] ?? 'Sin descripción', 0, 100)); ?></td>
                                <td><?php echo formatDateES($update['fecha_publicacion']); ?></td>
                                <td>
                                    <?php
                                    $estado_badges = [
                                        'disponible' => 'badge-info',
                                        'descargando' => 'badge-warning',
                                        'aplicando' => 'badge-warning',
                                        'exitoso' => 'badge-success',
                                        'fallido' => 'badge-danger'
                                    ];
                                    $estado_badge = $estado_badges[$update['estado']] ?? 'badge-dark';
                                    ?>
                                    <span class="badge <?php echo $estado_badge; ?>">
                                        <?php echo ucfirst($update['estado']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($update['estado'] === 'disponible'): ?>
                                        <form method="POST" style="display:inline;" 
                                              onsubmit="return confirm('¿Estás seguro de aplicar esta actualización? Se creará un backup automático.');">
                                            <input type="hidden" name="action" value="apply_update">
                                            <input type="hidden" name="update_id" value="<?php echo $update['id']; ?>">
                                            <button type="submit" class="btn btn-success" style="padding: 0.5rem 1rem; font-size: 12px;">
                                                ⬇️ Aplicar
                                            </button>
                                        </form>
                                    <?php elseif ($update['estado'] === 'exitoso'): ?>
                                        <span style="color: #22c55e;">✅ Aplicado</span>
                                    <?php elseif ($update['estado'] === 'fallido'): ?>
                                        <span style="color: #ef4444;">❌ Falló</span>
                                    <?php else: ?>
                                        <span style="color: #f59e0b;">⏳ Procesando...</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🔍</div>
                    <h3>No hay actualizaciones registradas</h3>
                    <p>Haz clic en "Buscar Actualizaciones" para verificar si hay nuevas versiones disponibles</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
