<?php
// debug_domiciliario.php - Script de depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Panel Domiciliario</h1>";
echo "<pre>";

// 1. Verificar sesión
session_start();
echo "1. SESIÓN:\n";
echo "   - Loggedin: " . (isset($_SESSION['loggedin']) ? 'SI' : 'NO') . "\n";
echo "   - User ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NO EXISTE') . "\n";
echo "   - Usuario: " . (isset($_SESSION['usuario']) ? $_SESSION['usuario'] : 'NO EXISTE') . "\n";
echo "   - Rol: " . (isset($_SESSION['rol']) ? $_SESSION['rol'] : 'NO EXISTE') . "\n";
echo "   - Nombre: " . (isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'NO EXISTE') . "\n\n";

// 2. Verificar auth_helper
echo "2. AUTH_HELPER:\n";
if (file_exists('auth_helper.php')) {
    echo "   - Archivo existe: SI\n";
    require_once 'auth_helper.php';
    echo "   - Cargado correctamente: SI\n\n";
} else {
    echo "   - Archivo existe: NO\n\n";
    die("ERROR: auth_helper.php no existe");
}

// 3. Verificar config
echo "3. CONFIG:\n";
if (file_exists('config.php')) {
    echo "   - Archivo existe: SI\n";
    require_once 'config.php';
    echo "   - Cargado correctamente: SI\n\n";
} else {
    echo "   - Archivo existe: NO\n\n";
    die("ERROR: config.php no existe");
}

// 4. Verificar conexión BD
echo "4. BASE DE DATOS:\n";
try {
    $conn = getDatabaseConnection();
    echo "   - Conexión: OK\n";
    
    // Verificar tabla pedidos
    $result = $conn->query("SHOW TABLES LIKE 'pedidos'");
    echo "   - Tabla pedidos existe: " . ($result->num_rows > 0 ? 'SI' : 'NO') . "\n";
    
    // Verificar columnas nuevas
    $result = $conn->query("SHOW COLUMNS FROM pedidos LIKE 'domiciliario_id'");
    echo "   - Columna domiciliario_id: " . ($result->num_rows > 0 ? 'SI' : 'NO') . "\n";
    
    $result = $conn->query("SHOW COLUMNS FROM pedidos LIKE 'hora_salida'");
    echo "   - Columna hora_salida: " . ($result->num_rows > 0 ? 'SI' : 'NO') . "\n";
    
    $result = $conn->query("SHOW COLUMNS FROM pedidos LIKE 'hora_entrega'");
    echo "   - Columna hora_entrega: " . ($result->num_rows > 0 ? 'SI' : 'NO') . "\n\n";
    
} catch (Exception $e) {
    echo "   - Error: " . $e->getMessage() . "\n\n";
}

// 5. Probar consultas
echo "5. CONSULTAS:\n";
if (isset($_SESSION['user_id'])) {
    $domiciliario_id = $_SESSION['user_id'];
    $hoy = date('Y-m-d');
    
    try {
        // Entregas del día
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM pedidos WHERE domiciliario_id = ? AND DATE(fecha_pedido) = ?");
        $stmt->bind_param("is", $domiciliario_id, $hoy);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_assoc()['count'];
        echo "   - Entregas hoy: $count\n";
        $stmt->close();
        
        // Pedidos listos
        $result = $conn->query("SELECT COUNT(*) as count FROM pedidos WHERE domiciliario_id IS NULL AND estado = 'en_camino' AND direccion IS NOT NULL AND direccion != ''");
        $count = $result->fetch_assoc()['count'];
        echo "   - Pedidos listos: $count\n\n";
        
    } catch (Exception $e) {
        echo "   - Error en consultas: " . $e->getMessage() . "\n\n";
    }
} else {
    echo "   - No se puede probar (no hay sesión)\n\n";
}

echo "6. RESULTADO:\n";
if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'domiciliario') {
    echo "   - El usuario puede acceder al panel\n";
    echo "   - <a href='domiciliario.php'>Ir al Panel Domiciliario</a>\n";
} else {
    echo "   - El usuario NO puede acceder (rol incorrecto o no logueado)\n";
    echo "   - <a href='login.php'>Ir al Login</a>\n";
}

echo "</pre>";

if (isset($conn)) {
    $conn->close();
}
?>
