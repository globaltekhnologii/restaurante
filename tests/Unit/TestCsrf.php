<?php
// Mockear sesión para pruebas CLI
if (session_status() === PHP_SESSION_NONE) {
    $_SESSION = [];
}

require_once __DIR__ . '/../../includes/csrf_helper.php';

class TestCsrf extends TestRunner {
    public function testGenerarToken() {
        // Limpiar sesión simulada
        $_SESSION['csrf_token'] = null;
        
        $token = generarCsrfToken();
        $this->assertTrue(!empty($token), "El token no debe estar vacío");
        $this->assertEquals($_SESSION['csrf_token'], $token, "El token debe guardarse en sesión");
        
        // Debe persistir
        $token2 = generarCsrfToken();
        $this->assertEquals($token, $token2, "El token debe ser persistente en la misma sesión");
    }

    public function testValidarToken() {
        $_SESSION['csrf_token'] = 'token_secreto';
        
        $this->assertTrue(validarCsrfToken('token_secreto'), "Token correcto debe validar true");
        $this->assertTrue(!validarCsrfToken('token_falso'), "Token incorrecto debe validar false");
        $this->assertTrue(!validarCsrfToken(''), "Token vacío debe validar false");
    }
}
?>
