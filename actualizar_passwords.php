<?php
/**
 * Script para actualizar contraseñas a formato hash seguro
 * IMPORTANTE: Ejecutar este script UNA SOLA VEZ después de corregir verificar_login.php
 * 
 * Este script:
 * 1. Lee todas las contraseñas actuales en texto plano
 * 2. Las convierte a hash usando password_hash()
 * 3. Actualiza la base de datos
 */

session_start();

// Verificar que el usuario sea administrador
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== TRUE) {
    die("Acceso denegado. Debes iniciar sesión como administrador.");
}

// Usar configuración centralizada
require_once 'config.php';
$conn = getDatabaseConnection();

echo "<h2>🔐 Actualización de Contraseñas a Formato Seguro</h2>";
echo "<p>Este script convertirá todas las contraseñas de texto plano a hash seguro.</p>";

// Obtener todos los usuarios
$result = $conn->query("SELECT id, usuario, clave FROM usuarios");

if ($result->num_rows > 0) {
    echo "<h3>Usuarios encontrados:</h3><ul>";
    
    $stmt = $conn->prepare("UPDATE usuarios SET clave = ? WHERE id = ?");
    
    while($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $usuario = $row['usuario'];
        $clave_actual = $row['clave'];
        
        // Verificar si ya está hasheada (las contraseñas hasheadas empiezan con $2y$)
        if (substr($clave_actual, 0, 4) === '$2y$') {
            echo "<li>✅ Usuario '<strong>$usuario</strong>': Ya tiene contraseña hasheada</li>";
            continue;
        }
        
        // Hashear la contraseña
        $clave_hash = password_hash($clave_actual, PASSWORD_DEFAULT);
        
        // Actualizar en la base de datos
        $stmt->bind_param("si", $clave_hash, $id);
        
        if ($stmt->execute()) {
            echo "<li>✅ Usuario '<strong>$usuario</strong>': Contraseña actualizada correctamente</li>";
        } else {
            echo "<li>❌ Usuario '<strong>$usuario</strong>': Error al actualizar - " . $stmt->error . "</li>";
        }
    }
    
    echo "</ul>";
    $stmt->close();
    
    echo "<h3>✅ Proceso completado</h3>";
    echo "<p><strong>IMPORTANTE:</strong> Ahora debes actualizar el archivo <code>verificar_login.php</code> para usar <code>password_verify()</code></p>";
    echo "<p><a href='admin.php'>Volver al panel de administración</a></p>";
    
} else {
    echo "<p>No se encontraron usuarios en la base de datos.</p>";
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizar Contraseñas</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f7fa;
        }
        h2 {
            color: #333;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        ul {
            background: white;
            padding: 20px 40px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        li {
            margin: 10px 0;
            list-style: none;
        }
        code {
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        a {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: transform 0.3s;
        }
        a:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
</html>
