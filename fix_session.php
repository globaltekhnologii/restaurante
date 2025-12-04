<?php
// fix_session.php - Arreglar sesión del domiciliario
session_start();

require_once 'config.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Arreglar Sesión</title></head><body>";
echo "<h1>🔧 Arreglar Sesión</h1>";

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    $conn = getDatabaseConnection();
    
    // Obtener datos completos del usuario
    $stmt = $conn->prepare("SELECT usuario, nombre, rol FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Actualizar sesión
        $_SESSION['usuario'] = $user['usuario'];
        $_SESSION['nombre'] = $user['nombre'];
        $_SESSION['rol'] = $user['rol'];
        
        echo "<p style='color: green;'>✅ Sesión actualizada correctamente</p>";
        echo "<p><strong>Usuario:</strong> " . htmlspecialchars($user['usuario']) . "</p>";
        echo "<p><strong>Nombre:</strong> " . htmlspecialchars($user['nombre']) . "</p>";
        echo "<p><strong>Rol:</strong> " . htmlspecialchars($user['rol']) . "</p>";
        echo "<hr>";
        echo "<p><a href='domiciliario.php' style='padding:10px 20px;background:#4299e1;color:white;text-decoration:none;border-radius:5px;'>Ir al Panel Domiciliario</a></p>";
    } else {
        echo "<p style='color: red;'>❌ Usuario no encontrado</p>";
    }
    
    $stmt->close();
    $conn->close();
} else {
    echo "<p style='color: orange;'>⚠️ No hay sesión activa</p>";
    echo "<p><a href='login.php'>Ir al Login</a></p>";
}

echo "</body></html>";
?>
