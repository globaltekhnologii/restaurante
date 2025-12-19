<?php
/**
 * Sistema de Gestión de Actualizaciones
 * Maneja la descarga, verificación y aplicación de actualizaciones
 */

require_once __DIR__ . '/version.php';

class UpdateManager {
    
    private $updateServerUrl;
    private $conn;
    
    public function __construct($updateServerUrl = null) {
        // URL del servidor de actualizaciones (puede ser GitHub Releases, servidor propio, etc.)
        $this->updateServerUrl = $updateServerUrl ?? 'https://api.github.com/repos/tu-usuario/tu-repo/releases/latest';
        // Usar la función global de conexión que ya está disponible
        $this->conn = function_exists('getDBConnection') ? getDBConnection() : getDatabaseConnection();
    }
    
    /**
     * Busca actualizaciones disponibles en el servidor remoto
     * @return array|false Información de la actualización o false si no hay
     */
    public function buscarActualizaciones() {
        try {
            // Configurar contexto para la petición HTTP
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => 'User-Agent: Sistema-Restaurante-SaaS',
                    'timeout' => 10
                ]
            ]);
            
            $response = @file_get_contents($this->updateServerUrl, false, $context);
            
            if ($response === false) {
                error_log("No se pudo conectar al servidor de actualizaciones");
                return false;
            }
            
            $data = json_decode($response, true);
            
            if (!$data || !isset($data['tag_name'])) {
                return false;
            }
            
            // Extraer versión (eliminar 'v' si existe, ej: v2.6.0 -> 2.6.0)
            $newVersion = ltrim($data['tag_name'], 'v');
            
            // Verificar si es una versión más nueva
            if (!isNewerVersion($newVersion)) {
                return false;
            }
            
            return [
                'version' => $newVersion,
                'descripcion' => $data['body'] ?? 'Sin descripción',
                'fecha_publicacion' => $data['published_at'] ?? date('Y-m-d H:i:s'),
                'archivo_url' => $data['zipball_url'] ?? null,
                'tipo' => $this->detectarTipoUpdate($data['body'] ?? '')
            ];
            
        } catch (Exception $e) {
            error_log("Error al buscar actualizaciones: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Detecta el tipo de actualización según la descripción
     */
    private function detectarTipoUpdate($descripcion) {
        $descripcion = strtolower($descripcion);
        
        if (strpos($descripcion, 'security') !== false || strpos($descripcion, 'seguridad') !== false) {
            return 'seguridad';
        }
        if (strpos($descripcion, 'critical') !== false || strpos($descripcion, 'crítico') !== false) {
            return 'critico';
        }
        if (strpos($descripcion, 'feature') !== false || strpos($descripcion, 'nueva') !== false) {
            return 'feature';
        }
        
        return 'bugfix';
    }
    
    /**
     * Descarga el paquete de actualización
     * @return string|false Ruta del archivo descargado o false
     */
    public function descargarUpdate($url, $version) {
        try {
            $tempDir = sys_get_temp_dir();
            $filename = "update_{$version}_" . time() . ".zip";
            $filepath = $tempDir . DIRECTORY_SEPARATOR . $filename;
            
            // Descargar archivo
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => 'User-Agent: Sistema-Restaurante-SaaS',
                    'timeout' => 300 // 5 minutos
                ]
            ]);
            
            $content = @file_get_contents($url, false, $context);
            
            if ($content === false) {
                throw new Exception("No se pudo descargar el archivo");
            }
            
            file_put_contents($filepath, $content);
            
            return $filepath;
            
        } catch (Exception $e) {
            error_log("Error al descargar actualización: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica la integridad del archivo descargado
     */
    public function verificarIntegridad($filepath, $expectedChecksum = null) {
        if (!file_exists($filepath)) {
            return false;
        }
        
        // Si no hay checksum esperado, solo verificar que sea un ZIP válido
        if ($expectedChecksum === null) {
            $zip = new ZipArchive();
            $result = $zip->open($filepath, ZipArchive::CHECKCONS);
            $zip->close();
            return $result === true;
        }
        
        // Verificar checksum SHA256
        $actualChecksum = hash_file('sha256', $filepath);
        return $actualChecksum === $expectedChecksum;
    }
    
    /**
     * Crea un backup completo antes de aplicar la actualización
     */
    public function crearBackupPreUpdate() {
        try {
            $backupDir = __DIR__ . '/../backups';
            if (!file_exists($backupDir)) {
                mkdir($backupDir, 0777, true);
            }
            
            $fecha = date('Y-m-d_H-i-s');
            $filename = "Backup_PreUpdate_{$fecha}";
            $sqlFile = "{$backupDir}/{$filename}.sql";
            $zipFile = "{$backupDir}/{$filename}.zip";
            
            // Exportar base de datos
            $dbHost = DB_HOST;
            $dbUser = DB_USER;
            $dbPass = DB_PASSWORD;
            $dbName = DB_NAME;
            
            $mysqldump = 'mysqldump';
            if (file_exists('C:/xampp/mysql/bin/mysqldump.exe')) {
                $mysqldump = '"C:/xampp/mysql/bin/mysqldump.exe"';
            }
            
            $command = "{$mysqldump} --user={$dbUser} --password={$dbPass} --host={$dbHost} {$dbName} > \"{$sqlFile}\"";
            system($command, $returnVar);
            
            if ($returnVar !== 0 || !file_exists($sqlFile)) {
                throw new Exception("Error al exportar base de datos");
            }
            
            // Crear ZIP con SQL y archivos importantes
            $zip = new ZipArchive();
            if ($zip->open($zipFile, ZipArchive::CREATE) !== TRUE) {
                throw new Exception("No se pudo crear el archivo ZIP");
            }
            
            $zip->addFile($sqlFile, "database_backup.sql");
            $zip->close();
            
            // Limpiar SQL temporal
            unlink($sqlFile);
            
            return $zipFile;
            
        } catch (Exception $e) {
            error_log("Error al crear backup: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Aplica la actualización
     */
    public function aplicarUpdate($zipFilepath, $version) {
        try {
            // 1. Crear backup
            $backupFile = $this->crearBackupPreUpdate();
            if (!$backupFile) {
                throw new Exception("No se pudo crear el backup de seguridad");
            }
            
            // 2. Extraer archivos
            $zip = new ZipArchive();
            if ($zip->open($zipFilepath) !== TRUE) {
                throw new Exception("No se pudo abrir el archivo ZIP");
            }
            
            $extractPath = __DIR__ . '/../temp_update';
            if (!file_exists($extractPath)) {
                mkdir($extractPath, 0777, true);
            }
            
            $zip->extractTo($extractPath);
            $zip->close();
            
            // 3. Ejecutar scripts de migración SQL (si existen)
            $sqlMigrationFile = $extractPath . '/migration.sql';
            if (file_exists($sqlMigrationFile)) {
                $sqlContent = file_get_contents($sqlMigrationFile);
                if ($this->conn->multi_query($sqlContent)) {
                    do {
                        if ($result = $this->conn->store_result()) {
                            $result->free();
                        }
                    } while ($this->conn->more_results() && $this->conn->next_result());
                }
            }
            
            // 4. Copiar archivos nuevos/actualizados
            $this->copiarArchivosRecursivo($extractPath, __DIR__ . '/..');
            
            // 5. Limpiar archivos temporales
            $this->eliminarDirectorio($extractPath);
            unlink($zipFilepath);
            
            // 6. Registrar actualización en BD
            $this->registrarActualizacion($version, 'exitoso', $backupFile);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error al aplicar actualización: " . $e->getMessage());
            $this->registrarActualizacion($version, 'fallido', null, $e->getMessage());
            return false;
        }
    }
    
    /**
     * Copia archivos recursivamente
     */
    private function copiarArchivosRecursivo($src, $dst) {
        $dir = opendir($src);
        @mkdir($dst);
        
        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..') {
                if (is_dir($src . '/' . $file)) {
                    $this->copiarArchivosRecursivo($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        
        closedir($dir);
    }
    
    /**
     * Elimina un directorio recursivamente
     */
    private function eliminarDirectorio($dir) {
        if (!file_exists($dir)) {
            return true;
        }
        
        if (!is_dir($dir)) {
            return unlink($dir);
        }
        
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            
            if (!$this->eliminarDirectorio($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
        
        return rmdir($dir);
    }
    
    /**
     * Registra la actualización en la base de datos
     */
    private function registrarActualizacion($version, $estado, $backupFile = null, $error = null) {
        $stmt = $this->conn->prepare("INSERT INTO system_updates 
            (version, estado, backup_file, error_log, fecha_aplicacion) 
            VALUES (?, ?, ?, ?, NOW())");
        
        $stmt->bind_param("ssss", $version, $estado, $backupFile, $error);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Rollback a una versión anterior usando un backup
     */
    public function rollback($backupId) {
        // TODO: Implementar restauración desde backup
        // Por ahora, el admin debe restaurar manualmente desde admin_respaldos.php
        return false;
    }
    
    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
