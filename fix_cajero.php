<?php
// fix_cajero.php - Corregir rol del usuario cajero
require_once 'config.php';

$conn = getDatabaseConnection();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Corregir Cajero</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5;}";
echo ".success{color:green;padding:10px;background:#e8f5e9;border-radius:5px;margin:10px 0;}";
echo ".error{color:red;padding:10px;background:#ffebee;border-radius:5px;margin:10px 0;}";
echo ".info{color:#1976d2;padding:10px;background:#e3f2fd;border-radius:5px;margin:10px 0;}";
echo "</style></head><body>";
echo "<h1>🔧 Corrigiendo Usuario Cajero</h1>";

try {
    // Actualizar el rol del usuario cajero
    $stmt = $conn->prepare("UPDATE usuarios SET rol = 'cajero' WHERE usuario = 'cajero'");
    
    if ($stmt->execute()) {
        $rows_affected = $stmt->affected_rows;
        
        if ($rows_affected > 0) {
            echo "<div class='success'>✅ <strong>¡Rol actualizado correctamente!</strong></div>";
            echo "<div class='info'>Se actualizó el rol de 'cajero' a: <strong>cajero</strong></div>";
            
            // Verificar el cambio
            $check = $conn->query("SELECT usuario, nombre, rol, activo FROM usuarios WHERE usuario = 'cajero'");
            if ($check && $check->num_rows > 0) {
                $user = $check->fetch_assoc();
                echo "<h2>✅ Usuario Actualizado</h2>";
                echo "<table border='1' cellpadding='10' style='border-collapse:collapse;background:white;'>";
                echo "<tr><th>Campo</th><th>Valor</th></tr>";
                echo "<tr><td>Usuario</td><td><strong>" . htmlspecialchars($user['usuario']) . "</strong></td></tr>";
                echo "<tr><td>Nombre</td><td>" . htmlspecialchars($user['nombre']) . "</td></tr>";
                echo "<tr><td>Rol</td><td><strong style='color:green;'>" . htmlspecialchars($user['rol']) . "</strong></td></tr>";
                echo "<tr><td>Activo</td><td>" . ($user['activo'] ? '✅ Sí' : '❌ No') . "</td></tr>";
                echo "</table>";
            }
            
            echo "<div class='success' style='margin-top:20px;'>";
            echo "<h3>✅ Todo listo</h3>";
            echo "<p>Ahora puedes iniciar sesión con:</p>";
            echo "<ul>";
            echo "<li><strong>Usuario:</strong> cajero</li>";
            echo "<li><strong>Contraseña:</strong> cajero123</li>";
            echo "</ul>";
            echo "</div>";
            
        } else {
            echo "<div class='error'>⚠️ No se encontró el usuario 'cajero' para actualizar</div>";
            echo "<div class='info'>Ejecuta <a href='crear_cajero.php'>crear_cajero.php</a> primero</div>";
        }
    } else {
        throw new Exception("Error al ejecutar UPDATE: " . $stmt->error);
    }
    
    $stmt->close();
    
    echo "<div style='margin-top:20px;'>";
    echo "<a href='login.php' style='background:#4caf50;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>🔐 Ir a Login</a> ";
    echo "<a href='debug_cajero.php' style='background:#2196f3;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin-left:10px;'>🔍 Ver Diagnóstico</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}

$conn->close();
echo "</body></html>";
?>
