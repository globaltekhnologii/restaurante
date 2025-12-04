<?php
// fix_domiciliario_rol.php - Corregir rol del domiciliario en BD
require_once 'config.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Corregir Rol Domiciliario</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f7fa}";
echo ".success{background:#d4edda;border-left:4px solid #28a745;color:#155724;padding:15px;margin:10px 0;border-radius:5px}";
echo ".error{background:#f8d7da;border-left:4px solid #dc3545;color:#721c24;padding:15px;margin:10px 0;border-radius:5px}";
echo ".info{background:#d1ecf1;border-left:4px solid #17a2b8;color:#0c5460;padding:15px;margin:10px 0;border-radius:5px}";
echo "</style></head><body>";

echo "<h1>🔧 Corregir Rol del Domiciliario</h1>";

$conn = getDatabaseConnection();

// 1. Verificar estado actual
echo "<h2>1. Estado Actual</h2>";
$result = $conn->query("SELECT id, usuario, nombre, rol, activo FROM usuarios WHERE usuario = 'domiciliario_test'");

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "<div class='info'>";
    echo "<strong>Usuario encontrado:</strong><br>";
    echo "ID: " . $user['id'] . "<br>";
    echo "Usuario: " . $user['usuario'] . "<br>";
    echo "Nombre: " . $user['nombre'] . "<br>";
    echo "Rol actual: '" . $user['rol'] . "' " . (empty($user['rol']) ? "(VACÍO - ESTE ES EL PROBLEMA)" : "") . "<br>";
    echo "Activo: " . ($user['activo'] ? 'Sí' : 'No') . "<br>";
    echo "</div>";
    
    // 2. Actualizar rol
    echo "<h2>2. Actualizando Rol</h2>";
    $stmt = $conn->prepare("UPDATE usuarios SET rol = 'domiciliario' WHERE usuario = 'domiciliario_test'");
    
    if ($stmt->execute()) {
        echo "<div class='success'>✅ Rol actualizado correctamente a 'domiciliario'</div>";
        
        // 3. Verificar actualización
        echo "<h2>3. Verificación</h2>";
        $result = $conn->query("SELECT id, usuario, nombre, rol FROM usuarios WHERE usuario = 'domiciliario_test'");
        $user = $result->fetch_assoc();
        
        echo "<div class='success'>";
        echo "<strong>Datos actualizados:</strong><br>";
        echo "Usuario: " . $user['usuario'] . "<br>";
        echo "Nombre: " . $user['nombre'] . "<br>";
        echo "Rol: " . $user['rol'] . "<br>";
        echo "</div>";
        
        // 4. Actualizar sesión si está activa
        session_start();
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user['id']) {
            $_SESSION['rol'] = 'domiciliario';
            echo "<div class='success'>✅ Sesión actualizada con el nuevo rol</div>";
        }
        
        echo "<hr>";
        echo "<h2>✅ Corrección Completada</h2>";
        echo "<p><a href='domiciliario.php' style='padding:10px 20px;background:#4299e1;color:white;text-decoration:none;border-radius:5px;'>Ir al Panel Domiciliario</a></p>";
        echo "<p><a href='logout.php' style='padding:10px 20px;background:#868e96;color:white;text-decoration:none;border-radius:5px;margin-left:10px;'>Cerrar Sesión y Volver a Entrar</a></p>";
        
    } else {
        echo "<div class='error'>❌ Error al actualizar: " . $stmt->error . "</div>";
    }
    
    $stmt->close();
    
} else {
    echo "<div class='error'>❌ Usuario 'domiciliario_test' no encontrado en la base de datos</div>";
}

$conn->close();

echo "</body></html>";
?>
