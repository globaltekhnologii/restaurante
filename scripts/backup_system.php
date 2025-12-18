<?php
// scripts/backup_system.php
// Ejecutar vía CLI o Cron: php scripts/backup_system.php

require_once __DIR__ . '/../config.php';

// Configuración
$backupDir = __DIR__ . '/../backups';
$date = date('Y-m-d_H-i-s');
$dbFile = $backupDir . "/db_backup_$date.sql";
$zipFile = $backupDir . "/full_backup_$date.zip";

// Asegurar directorio
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0755, true);
}

echo "🚀 Iniciando Backup System [$date]...\n";

// 1. Backup Base de Datos
echo "  -> Exportando Base de Datos...\n";
// Nota: mysqldump debe estar en el PATH o especificar ruta completa
// Intentamos ubicar mysqldump en rutas comunes de XAMPP si no está en PATH
$mysqldump = 'mysqldump';
$possiblePaths = [
    'C:\xampp\mysql\bin\mysqldump.exe',
    'C:\wamp\bin\mysql\mysql5.7.26\bin\mysqldump.exe', // Ejemplo
];

foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        $mysqldump = '"' . $path . '"';
        break;
    }
}

$cmd = "$mysqldump --user=" . DB_USER . " --password=" . DB_PASSWORD . " --host=" . DB_HOST . " " . DB_NAME . " > \"$dbFile\"";
exec($cmd, $output, $returnVar);

if ($returnVar !== 0) {
    echo "  ❌ Error al exportar base de datos (Return code: $returnVar). Output:\n";
    print_r($output);
    // Intentar sin password si está vacío
    if (empty(DB_PASS)) {
         $cmd = "$mysqldump --user=" . DB_USER . " --host=" . DB_HOST . " " . DB_NAME . " > \"$dbFile\"";
         exec($cmd, $output, $returnVar);
    }
} 

if ($returnVar === 0) {
    echo "  ✅ DB Backup creado: " . basename($dbFile) . "\n";
} else {
    echo "  ⚠️ Falló respaldo DB, continuando con archivos...\n";
}

// 2. Comprimir Archivos Críticos (Código + Imágenes)
echo "  -> Comprimiendo archivos...\n";
$zip = new ZipArchive();
if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
    // Agregar archivo SQL
    if (file_exists($dbFile)) {
        $zip->addFile($dbFile, basename($dbFile));
    }
    
    // Agregar carpeta imagenes_platos
    $rootPath = realpath(__DIR__ . '/../imagenes_platos');
    if ($rootPath && is_dir($rootPath)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootPath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = 'imagenes_platos/' . substr($filePath, strlen($rootPath) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
    }
    
    // Agregar config.php, .htaccess
    if (file_exists(__DIR__ . '/../config.php')) $zip->addFile(__DIR__ . '/../config.php', 'config.php');
    if (file_exists(__DIR__ . '/../.htaccess')) $zip->addFile(__DIR__ . '/../.htaccess', '.htaccess');

    $zip->close();
    echo "  ✅ Backup ZIP creado: " . basename($zipFile) . "\n";
    
    // Eliminar SQL suelto para ahorrar espacio
    if (file_exists($dbFile)) unlink($dbFile);
} else {
    echo "  ❌ Error al crear ZIP.\n";
}

// 3. Rotación (Eliminar backups > 30 días)
echo "  -> Limpiando backups antiguos...\n";
$files = glob($backupDir . "/*.zip");
$now = time();
$deleted = 0;

if ($files) {
    foreach ($files as $file) {
        if (is_file($file)) {
            if ($now - filemtime($file) >= 60 * 60 * 24 * 30) { // 30 días
                unlink($file);
                $deleted++;
            }
        }
    }
}
echo "  ✅ Se eliminaron $deleted backups antiguos.\n";

echo "🏁 Proceso finalizado.\n";
?>
