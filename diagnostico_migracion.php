<?php
/**
 * SCRIPT DE DIAGNÓSTICO Y REPARACIÓN POST-MIGRACIÓN
 * Ejecutar este script para diagnosticar y solucionar problemas de login
 */

// Mostrar errores para diagnóstico
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Diagnóstico Post-Migración</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        h2 { color: #667eea; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; }
        .btn { display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background: #5568d3; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>";

echo "<h1>🔍 Diagnóstico Post-Migración Multi-Tenencia</h1>";

$conn = getDatabaseConnection();
$problemas = [];
$soluciones = [];

// ============================================
// DIAGNÓSTICO 1: Verificar columna tenant_id en usuarios
// ============================================
echo "<div class='section'>";
echo "<h2>1. Verificar Estructura de Tabla 'usuarios'</h2>";

$result = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'tenant_id'");
if ($result->num_rows > 0) {
    echo "<p class='success'>✅ La columna 'tenant_id' existe en la tabla 'usuarios'</p>";
} else {
    echo "<p class='error'>❌ ERROR: La columna 'tenant_id' NO existe en la tabla 'usuarios'</p>";
    $problemas[] = "Columna tenant_id faltante en usuarios";
    $soluciones[] = "Ejecutar nuevamente el script de migración";
}
echo "</div>";

// ============================================
// DIAGNÓSTICO 2: Verificar usuarios sin tenant_id
// ============================================
echo "<div class='section'>";
echo "<h2>2. Verificar Usuarios sin tenant_id</h2>";

$result = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE tenant_id IS NULL");
$row = $result->fetch_assoc();
$usuarios_sin_tenant = $row['total'];

if ($usuarios_sin_tenant > 0) {
    echo "<p class='error'>❌ PROBLEMA: Hay {$usuarios_sin_tenant} usuario(s) sin tenant_id asignado</p>";
    $problemas[] = "Usuarios sin tenant_id";
    
    // Mostrar usuarios afectados
    $result = $conn->query("SELECT id, usuario, nombre, rol FROM usuarios WHERE tenant_id IS NULL");
    echo "<table>";
    echo "<tr><th>ID</th><th>Usuario</th><th>Nombre</th><th>Rol</th></tr>";
    while ($user = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['usuario']}</td>";
        echo "<td>{$user['nombre']}</td>";
        echo "<td>{$user['rol']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p class='warning'>⚠️ Estos usuarios necesitan ser asignados a un tenant para poder hacer login.</p>";
    $soluciones[] = "Asignar tenant_id = 1 a todos los usuarios existentes";
} else {
    echo "<p class='success'>✅ Todos los usuarios tienen tenant_id asignado</p>";
}
echo "</div>";

// ============================================
// DIAGNÓSTICO 3: Verificar tenant principal
// ============================================
echo "<div class='section'>";
echo "<h2>3. Verificar Tenant Principal</h2>";

$result = $conn->query("SELECT * FROM saas_tenants WHERE id = 1");
if ($result->num_rows > 0) {
    $tenant = $result->fetch_assoc();
    echo "<p class='success'>✅ El tenant principal existe</p>";
    echo "<table>";
    echo "<tr><th>Campo</th><th>Valor</th></tr>";
    echo "<tr><td>ID</td><td>{$tenant['id']}</td></tr>";
    echo "<tr><td>Nombre</td><td>{$tenant['restaurant_name']}</td></tr>";
    echo "<tr><td>Estado</td><td>{$tenant['status']}</td></tr>";
    echo "</table>";
} else {
    echo "<p class='error'>❌ ERROR: No existe el tenant principal (id = 1)</p>";
    $problemas[] = "Tenant principal faltante";
    $soluciones[] = "Crear tenant principal en saas_tenants";
}
echo "</div>";

// ============================================
// DIAGNÓSTICO 4: Verificar distribución de usuarios por tenant
// ============================================
echo "<div class='section'>";
echo "<h2>4. Distribución de Usuarios por Tenant</h2>";

$result = $conn->query("SELECT tenant_id, COUNT(*) as total FROM usuarios GROUP BY tenant_id");
echo "<table>";
echo "<tr><th>Tenant ID</th><th>Total Usuarios</th></tr>";
while ($row = $result->fetch_assoc()) {
    $tenant_id = $row['tenant_id'] ?? 'NULL';
    echo "<tr><td>{$tenant_id}</td><td>{$row['total']}</td></tr>";
}
echo "</table>";
echo "</div>";

// ============================================
// DIAGNÓSTICO 5: Probar consulta de login
// ============================================
echo "<div class='section'>";
echo "<h2>5. Simular Consulta de Login</h2>";

echo "<p>Ingresa un usuario para probar:</p>";
echo "<form method='POST' style='margin: 20px 0;'>";
echo "<input type='text' name='test_usuario' placeholder='Usuario' style='padding: 10px; margin-right: 10px;' required>";
echo "<button type='submit' name='test_login' class='btn'>Probar Login</button>";
echo "</form>";

if (isset($_POST['test_login'])) {
    $test_usuario = $_POST['test_usuario'];
    $stmt = $conn->prepare("SELECT id, usuario, rol, nombre, tenant_id FROM usuarios WHERE usuario = ? AND activo = 1");
    $stmt->bind_param("s", $test_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "<p class='success'>✅ Usuario encontrado:</p>";
        echo "<table>";
        echo "<tr><th>Campo</th><th>Valor</th></tr>";
        echo "<tr><td>ID</td><td>{$user['id']}</td></tr>";
        echo "<tr><td>Usuario</td><td>{$user['usuario']}</td></tr>";
        echo "<tr><td>Nombre</td><td>{$user['nombre']}</td></tr>";
        echo "<tr><td>Rol</td><td>{$user['rol']}</td></tr>";
        echo "<tr><td>Tenant ID</td><td>" . ($user['tenant_id'] ?? '<span class="error">NULL</span>') . "</td></tr>";
        echo "</table>";
        
        if ($user['tenant_id'] === null) {
            echo "<p class='error'>❌ PROBLEMA: Este usuario no tiene tenant_id asignado</p>";
            $problemas[] = "Usuario '{$test_usuario}' sin tenant_id";
        }
    } else {
        echo "<p class='error'>❌ Usuario no encontrado o está inactivo</p>";
    }
    $stmt->close();
}
echo "</div>";

// ============================================
// RESUMEN Y SOLUCIONES
// ============================================
echo "<div class='section'>";
echo "<h2>📊 Resumen del Diagnóstico</h2>";

if (empty($problemas)) {
    echo "<p class='success' style='font-size: 1.2em;'>✅ ¡No se encontraron problemas! El sistema debería funcionar correctamente.</p>";
} else {
    echo "<p class='error' style='font-size: 1.2em;'>❌ Se encontraron " . count($problemas) . " problema(s):</p>";
    echo "<ul>";
    foreach ($problemas as $problema) {
        echo "<li class='error'>{$problema}</li>";
    }
    echo "</ul>";
    
    echo "<h3>🔧 Soluciones Recomendadas:</h3>";
    echo "<ol>";
    foreach ($soluciones as $solucion) {
        echo "<li>{$solucion}</li>";
    }
    echo "</ol>";
}
echo "</div>";

// ============================================
// BOTÓN DE REPARACIÓN AUTOMÁTICA
// ============================================
if (!empty($problemas)) {
    echo "<div class='section'>";
    echo "<h2>🔧 Reparación Automática</h2>";
    echo "<p>Click en el botón para aplicar las correcciones automáticamente:</p>";
    
    echo "<form method='POST' onsubmit='return confirm(\"¿Estás seguro de aplicar las correcciones automáticas?\");'>";
    echo "<button type='submit' name='reparar' class='btn btn-danger'>🔧 Reparar Problemas Automáticamente</button>";
    echo "</form>";
    
    if (isset($_POST['reparar'])) {
        echo "<h3>Aplicando correcciones...</h3>";
        
        // Corrección 1: Asignar tenant_id = 1 a usuarios sin tenant
        if ($usuarios_sin_tenant > 0) {
            $conn->query("UPDATE usuarios SET tenant_id = 1 WHERE tenant_id IS NULL");
            echo "<p class='success'>✅ Asignado tenant_id = 1 a {$usuarios_sin_tenant} usuario(s)</p>";
        }
        
        // Corrección 2: Crear tenant principal si no existe
        $result = $conn->query("SELECT id FROM saas_tenants WHERE id = 1");
        if ($result->num_rows == 0) {
            $conn->query("INSERT INTO saas_tenants (id, restaurant_name, owner_email, owner_password, status, created_at) 
                         VALUES (1, 'Tenant Principal', 'admin@sistema.com', 'N/A', 'active', NOW())");
            echo "<p class='success'>✅ Creado tenant principal</p>";
        }
        
        echo "<p class='success' style='font-size: 1.2em; margin-top: 20px;'>✅ Correcciones aplicadas. <a href='diagnostico_migracion.php'>Recargar página</a> para verificar.</p>";
    }
    
    echo "</div>";
}

// ============================================
// ACCIONES ADICIONALES
// ============================================
echo "<div class='section'>";
echo "<h2>🔗 Acciones</h2>";
echo "<a href='login.php' class='btn'>🔐 Ir a Login</a>";
echo "<a href='diagnostico_migracion.php' class='btn'>🔄 Recargar Diagnóstico</a>";
echo "</div>";

$conn->close();

echo "</body></html>";
?>
