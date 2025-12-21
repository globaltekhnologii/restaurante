<?php
session_start();
require_once '../auth_helper.php';
require_once '../config.php';
require_once '../includes/tenant_context.php';

verificarSesion();
if ($_SESSION['rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}

header('Content-Type: application/json');

try {
    $conn = getDatabaseConnection();
    $tenant_id = getCurrentTenantId();
    
    // Configuración de Rutas
    $backupDir = realpath('../backups');
    if (!$backupDir) {
        $backupDir = '../backups';
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0777, true);
        }
        $backupDir = realpath($backupDir);
    }
    
    // Proteger directorio
    $htaccess = $backupDir . '/.htaccess';
    if (!file_exists($htaccess)) {
        file_put_contents($htaccess, "Order Deny,Allow\nDeny from all");
    }

    $fecha = date('Y-m-d_H-i-s');
    $filename = "Respaldo_Tenant_{$tenant_id}_{$fecha}";
    $jsonFile = "{$backupDir}/{$filename}.json";
    $zipFile = "{$backupDir}/{$filename}.zip";

    // Exportar datos del tenant en JSON
    $backup_data = [];
    $backup_data['_tenant_id'] = $tenant_id;
    $backup_data['_backup_date'] = date('Y-m-d H:i:s');
    
    // Información del tenant
    $tenant_info = $conn->query("SELECT * FROM saas_tenants WHERE id = $tenant_id")->fetch_assoc();
    $backup_data['_tenant_info'] = $tenant_info;
    
    // Lista de tablas a respaldar
    $tablas = [
        'platos', 'clientes', 'pedidos', 'pedido_items', 'usuarios', 'mesas',
        'ingredientes', 'recetas', 'proveedores', 'movimientos_inventario',
        'configuracion_sistema', 'configuracion_domicilios', 'config_pagos',
        'metodos_pago_config', 'publicidad'
    ];
    
    $tablas_respaldadas = 0;
    foreach ($tablas as $tabla) {
        // Verificar si la tabla existe
        $check = $conn->query("SHOW TABLES LIKE '$tabla'");
        if ($check->num_rows == 0) continue;
        
        // Verificar si tiene columna tenant_id
        $check_column = $conn->query("SHOW COLUMNS FROM $tabla LIKE 'tenant_id'");
        if ($check_column->num_rows == 0) continue;
        
        $sql = "SELECT * FROM $tabla WHERE tenant_id = $tenant_id";
        $result = $conn->query($sql);
        
        if ($result) {
            $backup_data[$tabla] = [];
            while ($row = $result->fetch_assoc()) {
                $backup_data[$tabla][] = $row;
            }
            $tablas_respaldadas++;
        }
    }
    
    // Crear archivo JSON
    file_put_contents($jsonFile, json_encode($backup_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // Crear ZIP
    $zip = new ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        throw new Exception("No se pudo crear el archivo ZIP");
    }

    // Agregar JSON
    $zip->addFile($jsonFile, "backup_data.json");
    $zip->close();

    // Verificar que se creó correctamente
    if (!file_exists($zipFile) || filesize($zipFile) == 0) {
        throw new Exception("El archivo ZIP no se creó correctamente");
    }

    // Registrar en la base de datos
    $tamano_mb = round(filesize($zipFile) / 1024 / 1024, 2);
    $stmt = $conn->prepare("INSERT INTO respaldos (tenant_id, nombre_archivo, ruta_archivo, tamano_mb, descripcion) VALUES (?, ?, ?, ?, ?)");
    $descripcion = "Respaldo de $tablas_respaldadas tablas - " . date('d/m/Y H:i:s');
    $stmt->bind_param("issds", $tenant_id, $filename, $zipFile, $tamano_mb, $descripcion);
    $stmt->execute();

    // Limpieza
    if (file_exists($jsonFile)) {
        unlink($jsonFile);
    }

    echo json_encode([
        'success' => true, 
        'mensaje' => 'Respaldo generado exitosamente',
        'archivo' => basename($zipFile),
        'tamano_mb' => $tamano_mb,
        'tablas_respaldadas' => $tablas_respaldadas,
        'ruta' => $zipFile
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
