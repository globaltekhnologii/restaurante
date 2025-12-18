<?php
// Script para configurar Bold en PRODUCCIÓN
echo "<h2>⚠️ Configuración Bold - PRODUCCIÓN</h2>";
echo "<p style='color: red; font-weight: bold;'>ADVERTENCIA: Los pagos serán REALES</p>";

$envFile = __DIR__ . '/.env.bold';

// Solicitar llave secreta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $secretKey = $_POST['secret_key'] ?? '';
    
    if (empty($secretKey)) {
        echo "<p style='color: red;'>❌ Debes proporcionar la llave secreta</p>";
    } else {
        $config = "# Credenciales Bold - PRODUCCIÓN
# ⚠️ ESTE ARCHIVO NO SE SUBE A GITHUB
# ⚠️ PAGOS REALES - USAR CON PRECAUCIÓN

# Llaves de Producción
BOLD_PUBLIC_KEY=teNcT5WmC-ax0ihGzVSL4BLO3z134DMPmwGY_ufODzk
BOLD_SECRET_KEY=$secretKey
BOLD_MODE=production
BOLD_API_URL=https://api.bold.co/v1
BOLD_CHECKOUT_URL=https://checkout.bold.co
BOLD_RETURN_URL=http://localhost/Restaurante/pago_confirmacion.php
BOLD_WEBHOOK_URL=http://localhost/Restaurante/api/webhook_bold.php
";
        
        if (file_put_contents($envFile, $config)) {
            echo "<p style='color: green;'>✅ Configuración de PRODUCCIÓN guardada</p>";
            echo "<h3>⚠️ IMPORTANTE:</h3>";
            echo "<ul>";
            echo "<li>Los pagos serán REALES</li>";
            echo "<li>Se cobrará dinero real de las tarjetas</li>";
            echo "<li>Usa montos pequeños para pruebas</li>";
            echo "</ul>";
            echo "<br><a href='test_bold_api.php' style='padding: 10px 20px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px;'>Probar Conexión</a>";
        } else {
            echo "<p style='color: red;'>❌ Error al guardar configuración</p>";
        }
    }
} else {
    // Mostrar formulario
    ?>
    <form method="POST" style="max-width: 600px; margin: 20px 0;">
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Llave Pública (ya configurada):</label>
            <input type="text" value="teNcT5WmC-ax0ihGzVSL4BLO3z134DMPmwGY_ufODzk" disabled style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
        </div>
        
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Llave Secreta de PRODUCCIÓN:</label>
            <input type="text" name="secret_key" required placeholder="Pega aquí tu llave secreta de producción" style="width: 100%; padding: 10px; border: 2px solid #dc3545; border-radius: 4px;">
            <small style="color: #666;">Esta llave NO se subirá a GitHub</small>
        </div>
        
        <button type="submit" style="padding: 12px 24px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;">
            ⚠️ Configurar PRODUCCIÓN
        </button>
    </form>
    <?php
}
?>
