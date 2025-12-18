<?php
require_once __DIR__ . '/../../config.php';

class TestDb extends TestRunner {
    public function testConnection() {
        try {
            $conn = getDatabaseConnection();
            $this->assertTrue($conn instanceof mysqli, "getDatabaseConnection debe retornar objeto mysqli");
            $this->assertTrue(!$conn->connect_error, "No debe haber errores de conexión");
            
            // Prueba simple query
            $res = $conn->query("SELECT 1");
            $this->assertTrue((bool)$res, "Debe poder ejecutar SELECT 1");
            
            $conn->close();
        } catch (Exception $e) {
            $this->assertTrue(false, "Excepción de DB: " . $e->getMessage());
        }
    }
}
?>
