<?php
// debug_cajero.php - Verificar estado del usuario cajero
require_once 'config.php';

$conn = getDatabaseConnection();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Debug Cajero</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5;}";
echo ".info{background:#e3f2fd;padding:15px;margin:10px 0;border-radius:5px;}";
echo ".success{background:#e8f5e9;padding:15px;margin:10px 0;border-radius:5px;}";
echo ".error{background:#ffebee;padding:15px;margin:10px 0;border-radius:5px;}";
echo "table{border-collapse:collapse;width:100%;background:white;margin:10px 0;}";
echo "th,td{border:1px solid #ddd;padding:12px;text-align:left;}";
echo "th{background:#4caf50;color:white;}";
echo "</style></head><body>";
echo "<h1>🔍 Diagnóstico de Usuario Cajero</h1>";

try {
    // 1. Verificar si existe el usuario cajero
    echo "<h2>1️⃣ Verificar existencia del usuario</h2>";
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE usuario = ?");
    $usuario = 'cajero';
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo "<div class='error'>❌ <strong>ERROR:</strong> No existe el usuario 'cajero' en la base de datos</div>";
        echo "<div class='info'>Debes ejecutar <a href='crear_cajero.php'>crear_cajero.php</a> primero</div>";
    } else {
        $user = $result->fetch_assoc();
        echo "<div class='success'>✅ Usuario 'cajero' encontrado en la base de datos</div>";
        
        echo "<h2>2️⃣ Información del usuario</h2>";
        echo "<table>";
        echo "<tr><th>Campo</th><th>Valor</th></tr>";
        echo "<tr><td>ID</td><td>" . $user['id'] . "</td></tr>";
        echo "<tr><td>Usuario</td><td><strong>" . htmlspecialchars($user['usuario']) . "</strong></td></tr>";
        echo "<tr><td>Nombre</td><td>" . htmlspecialchars($user['nombre']) . "</td></tr>";
        echo "<tr><td>Rol</td><td><strong>" . htmlspecialchars($user['rol']) . "</strong></td></tr>";
        echo "<tr><td>Activo</td><td>" . ($user['activo'] ? '✅ Sí' : '❌ No') . "</td></tr>";
        echo "<tr><td>Contraseña (hash)</td><td><code>" . substr($user['clave'], 0, 30) . "...</code></td></tr>";
        echo "</table>";
        
        // Verificar si el rol es 'cajero'
        if ($user['rol'] !== 'cajero') {
            echo "<div class='error'>⚠️ <strong>ADVERTENCIA:</strong> El rol del usuario no es 'cajero', es '" . htmlspecialchars($user['rol']) . "'</div>";
        }
        
        // Verificar si está activo
        if (!$user['activo']) {
            echo "<div class='error'>⚠️ <strong>ADVERTENCIA:</strong> El usuario está INACTIVO</div>";
        }
        
        // 3. Probar verificación de contraseña
        echo "<h2>3️⃣ Prueba de verificación de contraseña</h2>";
        $password_test = 'cajero123';
        
        echo "<div class='info'>Probando con contraseña: <strong>cajero123</strong></div>";
        
        // Método 1: password_verify
        if (password_verify($password_test, $user['clave'])) {
            echo "<div class='success'>✅ password_verify() funciona correctamente</div>";
        } else {
            echo "<div class='error'>❌ password_verify() falló</div>";
            
            // Verificar si es texto plano
            if ($password_test === $user['clave']) {
                echo "<div class='info'>⚠️ La contraseña está guardada en TEXTO PLANO (no hasheada)</div>";
                echo "<div class='info'>El sistema debería hashearla automáticamente en el próximo login</div>";
            } else {
                echo "<div class='error'>❌ La contraseña no coincide ni como hash ni como texto plano</div>";
            }
        }
        
        // 4. Verificar redirección
        echo "<h2>4️⃣ Verificación de redirección</h2>";
        echo "<div class='info'>Según verificar_login.php, el cajero debería redirigir a: <strong>cajero.php</strong></div>";
        
        // Verificar que cajero.php existe
        if (file_exists('cajero.php')) {
            echo "<div class='success'>✅ El archivo cajero.php existe</div>";
        } else {
            echo "<div class='error'>❌ El archivo cajero.php NO existe</div>";
        }
    }
    
    $stmt->close();
    
    // 5. Resumen y acciones recomendadas
    echo "<h2>5️⃣ Acciones recomendadas</h2>";
    echo "<div class='info'>";
    echo "<ol>";
    echo "<li>Si el usuario no existe o está inactivo, ejecuta <a href='crear_cajero.php'>crear_cajero.php</a></li>";
    echo "<li>Si la contraseña no funciona, intenta recrear el usuario</li>";
    echo "<li>Si todo parece correcto aquí pero no puedes entrar, revisa los logs del servidor</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<div style='margin-top:20px;'>";
    echo "<a href='login.php' style='background:#4caf50;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>🔐 Ir a Login</a> ";
    echo "<a href='crear_cajero.php' style='background:#2196f3;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin-left:10px;'>🔄 Recrear Usuario</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}

$conn->close();
echo "</body></html>";
?>
