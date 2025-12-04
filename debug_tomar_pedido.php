<?php
// debug_tomar_pedido.php - Debug para tomar pedido
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Tomar Pedido</h1>";
echo "<pre>";

session_start();

echo "1. SESIÓN:\n";
echo "   - Loggedin: " . (isset($_SESSION['loggedin']) ? 'SI' : 'NO') . "\n";
echo "   - User ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NO') . "\n";
echo "   - Rol: " . (isset($_SESSION['rol']) ? $_SESSION['rol'] : 'NO') . "\n";
echo "   - Nombre: " . (isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'NO') . "\n\n";

echo "2. PARÁMETROS:\n";
echo "   - mesa_id: " . (isset($_GET['mesa_id']) ? $_GET['mesa_id'] : 'NO RECIBIDO') . "\n\n";

require_once 'config.php';
$conn = getDatabaseConnection();

echo "3. CONEXIÓN BD: OK\n\n";

// Verificar tabla platos
echo "4. TABLA PLATOS:\n";
$result = $conn->query("SELECT COUNT(*) as count FROM platos WHERE disponible = 1");
$count = $result->fetch_assoc()['count'];
echo "   - Platos disponibles: $count\n\n";

// Verificar tabla mesas
echo "5. TABLA MESAS:\n";
$result = $conn->query("SELECT COUNT(*) as count FROM mesas");
$count = $result->fetch_assoc()['count'];
echo "   - Total mesas: $count\n";

$result = $conn->query("SELECT COUNT(*) as count FROM mesas WHERE estado = 'disponible'");
$count = $result->fetch_assoc()['count'];
echo "   - Mesas disponibles: $count\n\n";

// Si hay mesa_id, verificarla
if (isset($_GET['mesa_id'])) {
    $mesa_id = intval($_GET['mesa_id']);
    echo "6. VERIFICAR MESA $mesa_id:\n";
    
    $stmt = $conn->prepare("SELECT * FROM mesas WHERE id = ?");
    $stmt->bind_param("i", $mesa_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $mesa = $result->fetch_assoc();
        echo "   - Mesa encontrada: SI\n";
        echo "   - Número: " . $mesa['numero_mesa'] . "\n";
        echo "   - Estado: " . $mesa['estado'] . "\n";
        echo "   - Disponible: " . ($mesa['estado'] === 'disponible' ? 'SI' : 'NO') . "\n";
    } else {
        echo "   - Mesa encontrada: NO\n";
    }
    $stmt->close();
}

echo "\n7. RESULTADO:\n";
if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'mesero') {
    echo "   - Puede acceder: SI\n";
    echo "   - <a href='tomar_pedido_mesero.php" . (isset($_GET['mesa_id']) ? "?mesa_id=" . $_GET['mesa_id'] : "") . "'>Ir a Tomar Pedido</a>\n";
} else {
    echo "   - Puede acceder: NO (rol incorrecto)\n";
}

echo "</pre>";

$conn->close();
?>
