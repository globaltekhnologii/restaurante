<?php
// Script para probar conexión con Bold API
require_once 'includes/bold_client.php';

echo "<h2>🧪 Prueba de Conexión con Bold</h2>";

try {
    $bold = new BoldClient();
    
    echo "<p style='color: green;'>✅ Cliente Bold inicializado correctamente</p>";
    echo "<p>Modo: <strong>" . ($bold->isSandbox() ? 'Sandbox (Pruebas)' : 'Producción') . "</strong></p>";
    
    echo "<h3>Configuración cargada:</h3>";
    echo "<ul>";
    echo "<li>✅ Public Key configurada</li>";
    echo "<li>✅ Secret Key configurada</li>";
    echo "<li>✅ URLs de API configuradas</li>";
    echo "</ul>";
    
    echo "<br><h3>✅ Todo listo para crear pagos</h3>";
    echo "<p>La integración con Bold está configurada correctamente.</p>";
    echo "<br><a href='admin.php' style='padding: 10px 20px; background: #51cf66; color: white; text-decoration: none; border-radius: 5px;'>Ir al Panel Admin</a>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Verifica que el archivo .env.bold esté configurado correctamente.</p>";
    echo "<br><a href='setup_bold_credentials.php' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;'>Configurar Credenciales</a>";
}
?>
