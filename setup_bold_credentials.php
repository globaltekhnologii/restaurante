<?php
// Script para configurar credenciales Bold de Sandbox
echo "<h2>🔧 Configuración de Bold - Ambiente de Pruebas</h2>";

$envFile = __DIR__ . '/.env.bold';

$config = "# Credenciales Bold - SANDBOX (PRUEBAS)
# ⚠️ ESTE ARCHIVO NO SE SUBE A GITHUB

# Llaves de Prueba
BOLD_PUBLIC_KEY=GWDdqE-dXzkok30vYjiuFF8vreZww_MK_bTLaZbsXW4
BOLD_SECRET_KEY=wUQgkX2EACazJxi8vnPpNQ
BOLD_MODE=sandbox
BOLD_API_URL=https://api-sandbox.bold.co/v1
BOLD_CHECKOUT_URL=https://checkout-sandbox.bold.co
BOLD_RETURN_URL=http://localhost/Restaurante/pago_confirmacion.php
BOLD_WEBHOOK_URL=http://localhost/Restaurante/api/webhook_bold.php
";

if (file_put_contents($envFile, $config)) {
    echo "<p style='color: green;'>✅ Archivo .env.bold creado exitosamente!</p>";
    echo "<h3>Configuración guardada:</h3>";
    echo "<ul>";
    echo "<li>Modo: <strong>Sandbox (Pruebas)</strong></li>";
    echo "<li>Public Key: GWDdqE-dXzkok30vYjiuFF8vreZww_MK_bTLaZbsXW4</li>";
    echo "<li>Secret Key: wUQgkX2EACazJxi8vnPpNQ</li>";
    echo "</ul>";
    
    echo "<h3>✅ Próximos pasos:</h3>";
    echo "<ol>";
    echo "<li>Ejecutar migración de base de datos (si no lo hiciste)</li>";
    echo "<li>Probar integración con Bold</li>";
    echo "</ol>";
    
    echo "<br><a href='setup_bold_database.php' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;'>Ejecutar Migración BD</a>";
    echo " <a href='test_bold_connection.php' style='padding: 10px 20px; background: #51cf66; color: white; text-decoration: none; border-radius: 5px; margin-left: 10px;'>Probar Conexión Bold</a>";
} else {
    echo "<p style='color: red;'>❌ Error al crear archivo .env.bold</p>";
    echo "<p>Verifica los permisos de escritura en el directorio.</p>";
}
?>
