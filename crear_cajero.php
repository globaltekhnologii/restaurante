<?php
// crear_cajero.php - Script para crear rol de cajero y usuario de prueba
require_once 'config.php';

$conn = getDatabaseConnection();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Crear Cajero</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5;}";
echo ".success{color:green;padding:10px;background:#e8f5e9;border-radius:5px;margin:10px 0;}";
echo ".error{color:red;padding:10px;background:#ffebee;border-radius:5px;margin:10px 0;}";
echo ".info{color:#1976d2;padding:10px;background:#e3f2fd;border-radius:5px;margin:10px 0;}";
echo "</style></head><body>";
echo "<h1>🏦 Configuración del Módulo de Cajero</h1>";

try {
    // 1. Verificar si ya existe un usuario cajero
    $check = $conn->query("SELECT * FROM usuarios WHERE rol = 'cajero' LIMIT 1");
    
    if ($check->num_rows > 0) {
        $cajero = $check->fetch_assoc();
        echo "<div class='info'>✓ Ya existe un usuario cajero: <strong>" . htmlspecialchars($cajero['nombre']) . "</strong></div>";
        echo "<div class='info'>Usuario: <strong>" . htmlspecialchars($cajero['usuario']) . "</strong></div>";
    } else {
        // 2. Crear usuario cajero
        $usuario = 'cajero';
        $password = password_hash('cajero123', PASSWORD_DEFAULT);
        $nombre = 'Cajero Principal';
        $rol = 'cajero';
        
        $stmt = $conn->prepare("INSERT INTO usuarios (usuario, clave, nombre, rol, activo) VALUES (?, ?, ?, ?, 1)");
        $stmt->bind_param("ssss", $usuario, $password, $nombre, $rol);
        
        if ($stmt->execute()) {
            echo "<div class='success'>✅ Usuario cajero creado exitosamente</div>";
            echo "<div class='info'><strong>Credenciales:</strong><br>";
            echo "Usuario: <code>cajero</code><br>";
            echo "Contraseña: <code>cajero123</code></div>";
            echo "<div class='info'>⚠️ <strong>Importante:</strong> Cambia la contraseña después del primer login</div>";
        } else {
            throw new Exception("Error al crear usuario: " . $stmt->error);
        }
        $stmt->close();
    }
    
    // 3. Verificar estructura de la tabla usuarios
    $result = $conn->query("DESCRIBE usuarios");
    echo "<h2>📋 Estructura de tabla 'usuarios'</h2>";
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse;background:white;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 4. Listar todos los usuarios por rol
    echo "<h2>👥 Usuarios del Sistema</h2>";
    $usuarios = $conn->query("SELECT id, usuario, nombre, rol, activo FROM usuarios ORDER BY rol, nombre");
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse;background:white;'>";
    echo "<tr><th>ID</th><th>Usuario</th><th>Nombre</th><th>Rol</th><th>Activo</th></tr>";
    while ($user = $usuarios->fetch_assoc()) {
        $activo_badge = $user['activo'] ? '✅' : '❌';
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td><strong>" . htmlspecialchars($user['usuario']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($user['nombre']) . "</td>";
        echo "<td><span style='background:#e3f2fd;padding:3px 8px;border-radius:3px;'>" . $user['rol'] . "</span></td>";
        echo "<td>" . $activo_badge . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div class='success' style='margin-top:20px;'>";
    echo "<h3>✅ Configuración Completada</h3>";
    echo "<p>El rol de cajero está listo. Ahora puedes:</p>";
    echo "<ol>";
    echo "<li>Iniciar sesión con las credenciales del cajero</li>";
    echo "<li>Acceder al panel de cajero (cajero.php)</li>";
    echo "<li>Gestionar pagos y cierre de caja</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<div style='margin-top:20px;'>";
    echo "<a href='login.php' style='background:#4caf50;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>🔐 Ir a Login</a> ";
    echo "<a href='index.php' style='background:#2196f3;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin-left:10px;'>🏠 Ir al Inicio</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}

$conn->close();
echo "</body></html>";
?>
