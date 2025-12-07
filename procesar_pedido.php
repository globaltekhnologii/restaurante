<?php
// ============================================
// PROCESAR PEDIDO - Guarda el pedido en la base de datos
// ============================================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Usar configuración centralizada
require_once 'config.php';
require_once 'config.php';
require_once 'includes/functions_inventario.php';
$conn = getDatabaseConnection();

// Verificar que se recibieron los datos por POST
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: index.php");
    exit;
}

// Recibir y sanitizar datos del formulario
$nombre_cliente = trim($_POST['nombre']);
$telefono = trim($_POST['telefono']);
$direccion = trim($_POST['direccion']);
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$notas = isset($_POST['notas']) ? trim($_POST['notas']) : '';
$total = floatval($_POST['total']);
$carrito_json = $_POST['carrito'];
$carrito = json_decode($carrito_json, true);

// Validaciones básicas
if (empty($nombre_cliente) || empty($telefono) || empty($direccion)) {
    header("Location: checkout.php?error=Faltan datos obligatorios");
    exit;
}

if (empty($carrito) || !is_array($carrito)) {
    header("Location: checkout.php?error=El carrito está vacío");
    exit;
}

// Generar número de pedido único
$numero_pedido = 'PED-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

// ============================================
// INTEGRACIÓN DE CLIENTES
// ============================================
require_once 'includes/clientes_helper.php';

$cliente_id = null;
$cliente_id_form = isset($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : null;

// Si viene un cliente_id del formulario, usarlo
if ($cliente_id_form > 0) {
    $cliente_id = $cliente_id_form;
} 
// Si no, intentar buscar por teléfono o crear nuevo
elseif (!empty($telefono)) {
    // Buscar cliente existente por teléfono
    $cliente_existente = buscarClientePorTelefono($conn, $telefono);
    
    if ($cliente_existente) {
        $cliente_id = $cliente_existente['id'];
    } else {
        // Crear nuevo cliente automáticamente
        $datos_cliente = [
            'nombre' => $nombre_cliente,
            'apellido' => '',
            'telefono' => $telefono,
            'email' => $email,
            'direccion' => $direccion,
            'ciudad' => ''
        ];
        
        $cliente_id = crearClienteAutomatico($conn, $datos_cliente);
    }
}
// ============================================

// Iniciar transacción
// Iniciar transacción
$conn->begin_transaction();

// Validar STOCK y obtener IDs de platos
$items_validacion = [];
foreach ($carrito as $item) {
    // Buscar ID del plato
    $stmt_plato = $conn->prepare("SELECT id FROM platos WHERE nombre = ? LIMIT 1");
    $stmt_plato->bind_param("s", $item['nombre']);
    $stmt_plato->execute();
    $result = $stmt_plato->get_result();
    $plato_id = ($result->num_rows > 0) ? $result->fetch_assoc()['id'] : 0;
    
    if ($plato_id > 0) {
        $items_validacion[] = ['plato_id' => $plato_id, 'cantidad' => (isset($item['cantidad']) ? $item['cantidad'] : 1)];
    }
}

$validacion = validarStockPedido($conn, $items_validacion);

if (!$validacion['valido']) {
    $conn->rollback();
    header("Location: checkout.php?error=" . urlencode($validacion['mensaje']));
    exit;
}

try {
    // Insertar el pedido principal
    $stmt = $conn->prepare("INSERT INTO pedidos (numero_pedido, cliente_id, nombre_cliente, telefono, direccion, email, total, estado, notas) VALUES (?, ?, ?, ?, ?, ?, ?, 'confirmado', ?)");
    
    $stmt->bind_param("sissssds", 
        $numero_pedido,
        $cliente_id,
        $nombre_cliente,
        $telefono,
        $direccion,
        $email,
        $total,
        $notas
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Error al crear el pedido: " . $stmt->error);
    }
    
    $pedido_id = $stmt->insert_id;
    $stmt->close();
    
    // Insertar los items del pedido
    $stmt_items = $conn->prepare("INSERT INTO pedidos_items (pedido_id, plato_id, nombre_plato, precio, cantidad, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
    
    foreach ($carrito as $item) {
        // Buscar el ID del plato por su nombre
        $stmt_plato = $conn->prepare("SELECT id FROM platos WHERE nombre = ? LIMIT 1");
        $stmt_plato->bind_param("s", $item['nombre']);
        $stmt_plato->execute();
        $result = $stmt_plato->get_result();
        
        if ($result->num_rows > 0) {
            $plato = $result->fetch_assoc();
            $plato_id = $plato['id'];
        } else {
            $plato_id = 0; // Si no se encuentra el plato
        }
        $stmt_plato->close();
        
        $subtotal = $item['precio'] * $item['cantidad'];
        
        $stmt_items->bind_param("iisdid",
            $pedido_id,
            $plato_id,
            $item['nombre'],
            $item['precio'],
            $item['cantidad'],
            $subtotal
        );
        
        if (!$stmt_items->execute()) {
            throw new Exception("Error al agregar item: " . $stmt_items->error);
        }
    }
    
    $stmt_items->close();
    
    
    // Fin del bucle foreach ya cerrado en línea 161

    
    // Descontar STOCK
    // Usar usuario ID 1 (admin) o NULL si no hay sesión para pedidos online
    $usuario_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; 
    descontarStockPedido($conn, $pedido_id, $items_validacion, $usuario_id);

    // Confirmar transacción
    $conn->commit();
    
    // Actualizar estadísticas del cliente si existe
    if ($cliente_id) {
        actualizarEstadisticasCliente($conn, $cliente_id);
    }
    
    $conn->close();
    
    // Redirigir a página de confirmación con el número de pedido
    header("Location: confirmacion_pedido.php?numero=" . $numero_pedido);
    exit;
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    $conn->rollback();
    $conn->close();
    
    error_log("Error al procesar pedido: " . $e->getMessage());
    echo "Error: " . $e->getMessage(); // Mostrar error en pantalla también
    // header("Location: checkout.php?error=Error al procesar el pedido. Intenta nuevamente.");
    exit;
}
?>