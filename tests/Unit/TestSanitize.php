<?php
require_once __DIR__ . '/../../includes/sanitize_helper.php';

class TestSanitize extends TestRunner {
    public function testCleanString() {
        $input = "<b>Hola</b> Mundo <script>alert('xss')</script>";
        $expected = "Hola Mundo alert('xss')"; // cleanString usa strip_tags
        $actual = cleanString($input);
        
        $this->assertEquals($expected, $actual, "cleanString debe eliminar HTML tags");
        
        $input2 = "   Espacios   ";
        $this->assertEquals("Espacios", cleanString($input2), "cleanString debe hacer trim");
    }

    public function testCleanEmail() {
        $valid = "test@example.com";
        $this->assertEquals($valid, cleanEmail($valid), "Email válido no debe cambiar");
        
        $invalid = "test@example.com<script>";
        // La función cleanEmail valida PRIMERO. Si no es válido, retorna vacío.
        $sanitized = cleanEmail($invalid);
        $this->assertEquals("", $sanitized, "Email inválido debe retornar vacío");
    }

    public function testCleanInt() {
        $this->assertEquals(123, cleanInt("123"), "String numérico debe ser int");
        $this->assertEquals(10, cleanInt("10abc"), "Debe extraer entero");
        $this->assertEquals(0, cleanInt("abc"), "No numérico debe ser 0");
    }
}
?>
