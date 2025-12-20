<?php
// DEBUG: Mostrar errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Verificar sesión y rol de mesero
require_once 'auth_helper.php';
verificarSesion();
verificarRolORedirect(['mesero'], 'login.php');

require_once 'config.php';
require_once 'includes/functions_inventario.php';

// Validar que se recibieron los datos
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: mesero.php?error=" . urlencode("Método no permitido"));
    exit;
}

$mesero_id = $_SESSION['user_id'];

// Obtener datos del formulario
$mesa_id = isset($_POST['mesa_id']) ? intval($_POST['mesa_id']) : null;
$tipo_pedido = $_POST['tipo_pedido'];
$nombre_cliente = trim($_POST['nombre_cliente']);
$telefono = trim($_POST['telefono']);
$direccion = isset($_POST['direccion']) ? trim($_POST['direccion']) : '';
$notas = isset($_POST['notas']) ? trim($_POST['notas']) : '';
$items_json = $_POST['items'];

// Validaciones
if (empty($nombre_cliente) || empty($telefono)) {
    header("Location: tomar_pedido_mesero.php?mesa_id=$mesa_id&error=" . urlencode("Nombre y teléfono son obligatorios"));
    exit;
}

if ($tipo_pedido === 'domicilio' && empty($direccion)) {
    header("Location: tomar_pedido_mesero.php?error=" . urlencode("La dirección es obligatoria para pedidos a domicilio"));
    exit;
}

// Decodificar items del carrito
$items = json_decode($items_json, true);

if (empty($items)) {
    header("Location: tomar_pedido_mesero.php?mesa_id=$mesa_id&error=" . urlencode("Debes agregar al menos un plato"));
    exit;
}

$conn = getDatabaseConnection();

// Generar número de pedido único
$numero_pedido = 'PED-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

// Verificar que el número no exista
$stmt = $conn->prepare("SELECT id FROM pedidos WHERE numero_pedido = ?");
$stmt->bind_param("s", $numero_pedido);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    // Si existe, regenerar
    $numero_pedido = 'PED-' . date('Ymd') . '-' . str_pad(rand(10000, 99999), 5, '0', STR_PAD_LEFT);
}
$stmt->close();

// Calcular total
$total = 0;
foreach ($items as $item) {
    $total += $item['precio'] * $item['cantidad'];
}

// Iniciar transacción
$conn->begin_transaction();

// Validar STOCK antes de procesar
$items_validacion = array_map(function($item) {
    return ['plato_id' => $item['id'], 'cantidad' => $item['cantidad']];
}, $items);

$validacion = validarStockPedido($conn, $items_validacion);

if (!$validacion['valido']) {
    $conn->rollback();
    header("Location: tomar_pedido_mesero.php?mesa_id=$mesa_id&error=" . urlencode($validacion['mensaje']));
    exit;
}

try {
    // Insertar pedido
    $tipo_pedido = isset($_POST['tipo_pedido']) ? $_POST['tipo_pedido'] : 'mesa';
    
    $estado = 'confirmado';
    $stmt = $conn->prepare("INSERT INTO pedidos (numero_pedido, nombre_cliente, telefono, direccion, notas, total, estado, mesa_id, usuario_id, fecha_pedido, tipo_pedido) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)");
    $stmt->bind_param("sssssssiis", $numero_pedido, $nombre_cliente, $telefono, $direccion, $notas, $total, $estado, $mesa_id, $mesero_id, $tipo_pedido);
    $stmt->execute();
    $pedido_id = $conn->insert_id;
    $stmt->close();
    
    // Insertar items del pedido
    $stmt = $conn->prepare("INSERT INTO pedidos_items (pedido_id, plato_id, plato_nombre, precio_unitario, cantidad) VALUES (?, ?, ?, ?, ?)");
    
    foreach ($items as $item) {
        $plato_id = $item['id'];
        $plato_nombre = $item['nombre'];
        $precio_unitario = $item['precio'];
        $cantidad = $item['cantidad'];
        
        $stmt->bind_param("iissi", $pedido_id, $plato_id, $plato_nombre, $precio_unitario, $cantidad);
        $stmt->execute();
    }
    $stmt->close();
    
    // Si es pedido en mesa, actualizar estado de la mesa
    if ($mesa_id) {
        $stmt = $conn->prepare("UPDATE mesas SET estado = 'ocupada', pedido_actual = ?, mesero_asignado = ?, fecha_ocupacion = NOW() WHERE id = ?");
        $stmt->bind_param("iii", $pedido_id, $mesero_id, $mesa_id);
        $stmt->execute();
        $stmt->close();
    }

    // Descontar STOCK
    descontarStockPedido($conn, $pedido_id, $items_validacion, $mesero_id);
    
    // Confirmar transacción
    $conn->commit();
    
    $conn->close();
    
    // Redirigir con éxito
    header("Location: mesero.php?success=" . urlencode("Pedido $numero_pedido creado exitosamente"));
    exit;
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    $conn->rollback();
    $conn->close();
    
    header("Location: tomar_pedido_mesero.php?mesa_id=$mesa_id&error=" . urlencode("Error al crear pedido: " . $e->getMessage()));
    exit;
}
?>
