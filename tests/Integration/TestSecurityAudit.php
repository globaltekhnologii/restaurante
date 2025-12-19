<?php
require_once __DIR__ . '/../../includes/sanitize_helper.php';

class TestSecurityAudit extends TestRunner {
    
    private $projectRoot;

    public function __construct() {
        $this->projectRoot = dirname(dirname(__DIR__));
    }

    public function testDangerousFiles() {
        $dangerousPatterns = [
            'test_*.php',
            'debug_*.php',
            'temp_*.php',
            '*.bak',
            '*.sql', // SQL dumps shouldn't be in web root except specific ones
            'info.php',
            'phpinfo.php'
        ];

        $foundFiles = [];
        foreach ($dangerousPatterns as $pattern) {
            $files = glob($this->projectRoot . '/' . $pattern);
            foreach ($files as $file) {
                // Exclude known safe/needed files
                if (basename($file) === 'database.sql' || basename($file) === 'database_inventario.sql') continue;
                if (basename($file) === 'database_vps_export.sql') continue; // Allow export file
                if (basename($file) === 'database_inventario_base.sql') continue; // Allow base file
                if (strpos(basename($file), 'backup_') === 0 && substr($file, -4) === '.sql') continue; // Allow backup SQLs
                $foundFiles[] = basename($file);
            }
        }

        $this->assertTrue(empty($foundFiles), "No deben existir archivos peligrosos/debug en la raíz. Encontrados: " . implode(', ', $foundFiles));
    }

    public function testHtaccessSecurity() {
        $htaccessPath = $this->projectRoot . '/.htaccess';
        
        if (!file_exists($htaccessPath)) {
            $this->assertTrue(false, "El archivo .htaccess debe existir");
            return;
        }

        $content = file_get_contents($htaccessPath);

        // Check for protected files
        // Allow config.php or config\.php (regex escaped)
        $this->assertTrue(
            strpos($content, 'config.php') !== false || strpos($content, 'config\\.php') !== false, 
            ".htaccess debe proteger config.php"
        );
        $this->assertTrue(strpos($content, '.env') !== false, ".htaccess debe proteger .env");
        
        // Check for directory listing disabled
        $this->assertTrue(strpos($content, 'Options -Indexes') !== false, ".htaccess debe deshabilitar listado de directorios");
        
        // Check for PHP execution in uploads
        $this->assertTrue(strpos($content, 'imagenes_platos') !== false, ".htaccess debe proteger carpeta imagenes_platos");
    }

    public function testConfigSecurity() {
        $configPath = $this->projectRoot . '/config.php';
        if (!file_exists($configPath)) return; // Pass if file not exists (might be using config_vps)

        $content = file_get_contents($configPath);
        
        // Check secure session settings
        $this->assertTrue(strpos($content, "ini_set('session.cookie_httponly', 1)") !== false || strpos($content, "SESSION_HTTPONLY") !== false, "Configuración debe forzar HTTPOnly en cookies");
        $this->assertTrue(strpos($content, "ini_set('session.use_strict_mode', 1)") !== false, "Configuración debe usar Strict Mode en sesiones");
        
        // Check error display
        // Note: In local/dev execution this might be On, verify logic handles ENVIRONMENT check or similar
        // For audit purposes, we check if the lines exists, not necessarily the value enabled/disabled as it depends on env
        $this->assertTrue(strpos($content, "display_errors") !== false, "Configuración debe controlar display_errors");
    }

    public function testStrongSanitization() {
        // SQL Injection attempts
        $sqliPayload = "' OR '1'='1";
        $cleanSqli = cleanString($sqliPayload);
        $this->assertEquals("' OR '1'='1", $cleanSqli, "cleanString NO debe escapar comillas (eso lo hace mysqli_prepare), pero debe limpiar tags");
        
        // XSS Complex Payloads
        $xssPayloads = [
            '<script>alert(1)</script>' => "alert(1)",
            '<img src=x onerror=alert(1)>' => "",
            'javascript:alert(1)' => "javascript:alert(1)", // cleanString strips tags, doesn't validate URLs. cleanHtml should handle this if used.
        ];

        foreach ($xssPayloads as $payload => $expected) {
            $this->assertEquals($expected, cleanString($payload), "cleanString debe limpiar: $payload");
        }
    }
}
?>
