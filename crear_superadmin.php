<?php
/**
 * CREAR SUPER ADMINISTRADOR
 * Ejecutar este script UNA SOLA VEZ para crear el usuario super admin
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Crear Super Admin</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #3b82f6; padding-bottom: 10px; }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0; border-radius: 5px; color: #155724; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 20px 0; border-radius: 5px; color: #721c24; }
        .info { background: #d1ecf1; border-left: 4px solid #0c5460; padding: 15px; margin: 20px 0; border-radius: 5px; color: #0c5460; }
        .btn { display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background: #2563eb; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; }
        code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🔐 Crear Super Administrador</h1>";

$conn = getDatabaseConnection();

// Verificar si la tabla existe
$table_check = $conn->query("SHOW TABLES LIKE 'saas_super_admins'");

if ($table_check->num_rows == 0) {
    echo "<div class='error'>❌ La tabla 'saas_super_admins' no existe. Creándola ahora...</div>";
    
    // Crear la tabla
    $create_table = "CREATE TABLE IF NOT EXISTS `saas_super_admins` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `email` VARCHAR(255) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `name` VARCHAR(255) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `last_login` TIMESTAMP NULL,
        INDEX idx_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($create_table)) {
        echo "<div class='success'>✅ Tabla 'saas_super_admins' creada exitosamente</div>";
    } else {
        echo "<div class='error'>❌ Error al crear tabla: " . $conn->error . "</div>";
        exit;
    }
}

// Verificar si ya existe un super admin
$check = $conn->query("SELECT * FROM saas_super_admins");

if ($check->num_rows > 0) {
    echo "<div class='info'>ℹ️ Ya existen Super Administradores en el sistema:</div>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Email</th><th>Nombre</th><th>Creado</th></tr>";
    while ($admin = $check->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$admin['id']}</td>";
        echo "<td>{$admin['email']}</td>";
        echo "<td>{$admin['name']}</td>";
        echo "<td>" . date('d/m/Y H:i', strtotime($admin['created_at'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div class='info'>";
    echo "<strong>🔑 Credenciales por defecto:</strong><br>";
    echo "Email: <code>admin@saas.com</code><br>";
    echo "Contraseña: <code>admin123</code>";
    echo "</div>";
} else {
    echo "<div class='info'>ℹ️ No hay Super Administradores. Creando uno ahora...</div>";
    
    // Crear super admin por defecto
    $email = 'admin@saas.com';
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $name = 'Super Administrador';
    
    $stmt = $conn->prepare("INSERT INTO saas_super_admins (email, password, name) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $email, $password, $name);
    
    if ($stmt->execute()) {
        echo "<div class='success'>";
        echo "<h2>✅ Super Administrador creado exitosamente!</h2>";
        echo "<p><strong>Credenciales de acceso:</strong></p>";
        echo "<table>";
        echo "<tr><th>Email</th><td><code>admin@saas.com</code></td></tr>";
        echo "<tr><th>Contraseña</th><td><code>admin123</code></td></tr>";
        echo "</table>";
        echo "<p><strong>⚠️ IMPORTANTE:</strong> Cambia esta contraseña después del primer login.</p>";
        echo "</div>";
    } else {
        echo "<div class='error'>❌ Error al crear Super Admin: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

echo "<h2>🚀 Próximos Pasos</h2>";
echo "<ol>";
echo "<li>Ir al login del Super Admin</li>";
echo "<li>Usar las credenciales mostradas arriba</li>";
echo "<li>Crear nuevos restaurantes (tenants)</li>";
echo "<li>Gestionar suscripciones</li>";
echo "</ol>";

echo "<div style='margin-top: 30px;'>";
echo "<a href='ChatbotSaaS/superadmin/login.php' class='btn'>🔐 Ir a Login Super Admin</a>";
echo "<a href='login.php' class='btn' style='background: #6c757d;'>🏠 Login Restaurante</a>";
echo "</div>";

$conn->close();

echo "</div></body></html>";
?>
