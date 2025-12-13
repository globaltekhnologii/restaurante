<?php
session_start();
require_once '../auth_helper.php';
require_once '../config.php';

// Verificar permisos (solo admin puede respaldar)
verificarSesion();
if ($_SESSION['rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}

header('Content-Type: application/json');

try {
    // 1. Configuración de Rutas
    $backupDir = '../backups'; // Carpeta local para guardar
    if (!file_exists($backupDir)) {
        if (!mkdir($backupDir, 0777, true)) {
            throw new Exception("No se pudo crear el directorio de respaldos");
        }
    }
    
    // Proteger directorio (crear .htaccess si no existe)
    $htaccess = $backupDir . '/.htaccess';
    if (!file_exists($htaccess)) {
        file_put_contents($htaccess, "Order Deny,Allow\nDeny from all");
    }

    $fecha = date('Y-m-d_H-i-s');
    $filename = "Respaldo_Restaurante_{$fecha}";
    $sqlFile = "{$backupDir}/{$filename}.sql";
    $zipFile = "{$backupDir}/{$filename}.zip";

    // 2. Generar Dump de Base de Datos (MySQL)
    $dbHost = DB_HOST;
    $dbUser = DB_USER;
    $dbPass = DB_PASSWORD;
    $dbName = DB_NAME;
    
    // Ruta a mysqldump (Intentar rutas comunes de XAMPP)
    $mysqldump = 'mysqldump'; // Si está en PATH
    if (file_exists('C:/xampp/mysql/bin/mysqldump.exe')) {
        $mysqldump = '"C:/xampp/mysql/bin/mysqldump.exe"';
    }

    $command = "{$mysqldump} --user={$dbUser} --password={$dbPass} --host={$dbHost} {$dbName} > \"{$sqlFile}\"";
    
    // Ejecutar comando
    system($command, $returnVar);
    
    if ($returnVar !== 0 || !file_exists($sqlFile)) {
        throw new Exception("Error al exportar base de datos. Código: $returnVar");
    }

    // 3. Crear ZIP (SQL + Imágenes)
    $zip = new ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE) !== TRUE) {
        throw new Exception("No se pudo crear el archivo ZIP");
    }

    // Agregar SQL
    $zip->addFile($sqlFile, "database_backup.sql");

    // Agregar Imágenes (Recursivo)
    $source = realpath('../img');
    if (is_dir($source)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = 'img/' . substr($filePath, strlen($source) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
    }

    // Agregar Publicidad si existe
    $sourcePub = realpath('../publicidad');
    if (is_dir($sourcePub)) {
         $filesPub = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourcePub),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($filesPub as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = 'publicidad/' . substr($filePath, strlen($sourcePub) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
    }

    $zip->close();

    // 4. Limpieza (Borrar SQL temporal)
    unlink($sqlFile);

    echo json_encode([
        'success' => true, 
        'mensaje' => 'Respaldo generado exitosamente',
        'archivo' => basename($zipFile),
        'ruta_completa' => realpath($zipFile)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
