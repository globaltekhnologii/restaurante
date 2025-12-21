<?php
session_start();
require_once 'auth_helper.php';
verificarSesion();
verificarRolORedirect(['admin'], 'login.php');
require_once 'includes/info_negocio.php';
require_once 'includes/tenant_context.php';
require_once 'config.php';

$conn = getDatabaseConnection();
$tenant_id = getCurrentTenantId();

// Manejo de Descarga / Borrado
if (isset($_GET['action']) && isset($_GET['file'])) {
    $file = basename($_GET['file']);
    $filepath = "backups/" . $file;

    if ($_GET['action'] == 'download' && file_exists($filepath)) {
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="'.$file.'"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    }

    if ($_GET['action'] == 'delete' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        
        // Verificar que el respaldo pertenece al tenant
        $stmt = $conn->prepare("SELECT ruta_archivo FROM respaldos WHERE id = ? AND tenant_id = ?");
        $stmt->bind_param("ii", $id, $tenant_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Eliminar archivo físico
            if (file_exists($row['ruta_archivo'])) {
                unlink($row['ruta_archivo']);
            }
            
            // Eliminar registro de BD
            $stmt_delete = $conn->prepare("DELETE FROM respaldos WHERE id = ? AND tenant_id = ?");
            $stmt_delete->bind_param("ii", $id, $tenant_id);
            $stmt_delete->execute();
        }
        
        header("Location: admin_respaldos.php?deleted=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Respaldos - <?php echo htmlspecialchars($info_negocio['nombre_restaurante']); ?></title>
    <link rel="stylesheet" href="css/admin-modern.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Estilos específicos para respaldos */
        .backup-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            transition: transform 0.2s;
            border-left: 5px solid #667eea;
        }
        .backup-card:hover {
            transform: translateX(5px);
        }
        .backup-info h4 {
            margin: 0 0 5px 0;
            color: #333;
        }
        .backup-info p {
            margin: 0;
            color: #666;
            font-size: 0.9em;
        }
        .backup-actions {
            display: flex;
            gap: 10px;
        }
        .cloud-sync-box {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            color: #0d47a1;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .cloud-icon {
            font-size: 3em;
        }
        .loading-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(255,255,255,0.9);
            display: none;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            z-index: 1000;
        }
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <!-- Navbar (Misma estructura que admin.php) -->
    <div class="admin-navbar">
        <h1>💾 Sistema de Respaldos</h1>
        <div class="navbar-actions">
            <a href="admin.php">Volver al Panel</a>
            <a href="logout.php">Cerrar Sesión</a>
        </div>
    </div>

    <div class="admin-container">
        
        <!-- Caja de Información Híbrida -->
        <div class="cloud-sync-box">
            <div class="cloud-icon">☁️</div>
            <div>
                <h3>Sincronización Híbrida con Google Drive</h3>
                <p>Para asegurar sus datos en la nube, instale <strong>Google Drive para Escritorio</strong> y configure la sincronización de la siguiente carpeta:</p>
                <code style="background: white; padding: 5px 10px; border-radius: 4px; display: block; margin-top: 10px; font-family: monospace;">
                    <?php echo realpath('backups'); ?>
                </code>
            </div>
        </div>

        <!-- Botón Generar -->
        <div style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="margin: 0;">Copias de Seguridad Locales</h2>
            <button onclick="generarRespaldo()" class="btn btn-primary" style="font-size: 1.1em;">
                ⚡ Generar Nuevo Respaldo Ahora
            </button>
        </div>

        <!-- Lista de Respaldos -->
        <div id="backup-list">
            <?php
            // Obtener respaldos del tenant desde la base de datos
            $sql = "SELECT * FROM respaldos WHERE tenant_id = $tenant_id ORDER BY fecha_creacion DESC";
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                while ($backup = $result->fetch_assoc()) {
                    $archivo = basename($backup['nombre_archivo']);
                    $fecha = date("d/m/Y H:i:s", strtotime($backup['fecha_creacion']));
                    $tamano = $backup['tamano_mb'];
                    
                    // Verificar si el archivo existe
                    $archivo_existe = file_exists($backup['ruta_archivo']);
                    $clase_extra = $archivo_existe ? '' : 'style="opacity: 0.5;"';
                    
                    echo "
                    <div class='backup-card' $clase_extra>
                        <div class='backup-info'>
                            <h4>📦 {$archivo}.zip</h4>
                            <p>📅 Fecha: {$fecha} | 💾 Tamaño: {$tamano} MB</p>";
                    
                    if (!empty($backup['descripcion'])) {
                        echo "<p style='font-size: 0.85em; color: #888;'>{$backup['descripcion']}</p>";
                    }
                    
                    if (!$archivo_existe) {
                        echo "<p style='color: #e74a3b; font-weight: bold;'>⚠️ Archivo no encontrado</p>";
                    }
                    
                    echo "
                        </div>
                        <div class='backup-actions'>";
                    
                    if ($archivo_existe) {
                        echo "
                            <a href='admin_respaldos.php?action=download&file={$archivo}.zip' class='btn btn-small' style='background:#4CAF50; color:white;'>⬇️ Descargar</a>
                            <a href='admin_respaldos.php?action=delete&file={$archivo}.zip&id={$backup['id']}' class='btn btn-small' style='background:#f44336; color:white;' onclick='return confirm(\"¿Estás seguro de eliminar este respaldo?\")'>🗑️ Eliminar</a>";
                    } else {
                        echo "<button class='btn btn-small' style='background:#ccc; color:#666;' disabled>Archivo perdido</button>";
                    }
                    
                    echo "
                        </div>
                    </div>";
                }
            } else {
                echo "<p style='text-align:center; color:#999; padding:40px;'>No hay respaldos generados aún. Crea tu primer respaldo.</p>";
            }
            
            $conn->close();
            ?>
        </div>
    </div>

    <!-- Overlay de Carga -->
    <div id="loader" class="loading-overlay">
        <div class="spinner"></div>
        <h3>Generando respaldo completo...</h3>
        <p>Esto puede tardar unos segundos. Por favor espere.</p>
    </div>

    <script>
        function generarRespaldo() {
            if (!confirm('¿Desea generar una nueva copia de seguridad de la base de datos y las imágenes?')) return;

            document.getElementById('loader').style.display = 'flex';

            fetch('api/generar_respaldo.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ Respaldo generado correctamente: ' + data.archivo);
                        location.reload();
                    } else {
                        alert('❌ Error: ' + (data.error || 'Ocurrió un error desconocido'));
                        document.getElementById('loader').style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('❌ Error de conexión al generar respaldo');
                    document.getElementById('loader').style.display = 'none';
                });
        }
    </script>
</body>
</html>
