<?php
// ============================================
// PROCESAR PEDIDO - Guarda el pedido en la base de datos
// ============================================

// Configuración de la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "menu_restaurante";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

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

// Iniciar transacción
$conn->begin_transaction();

try {
    // Insertar el pedido principal
    $stmt = $conn->prepare("INSERT INTO pedidos (numero_pedido, nombre_cliente, telefono, direccion, email, total, estado, notas) VALUES (?, ?, ?, ?, ?, ?, 'pendiente', ?)");
    
    $stmt->bind_param("sssssds", 
        $numero_pedido,
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
    
    // Confirmar transacción
    $conn->commit();
    $conn->close();
    
    // Redirigir a página de confirmación con el número de pedido
    header("Location: confirmacion_pedido.php?numero=" . $numero_pedido);
    exit;
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    $conn->rollback();
    $conn->close();
    
    error_log("Error al procesar pedido: " . $e->getMessage());
    header("Location: checkout.php?error=Error al procesar el pedido. Intenta nuevamente.");
    exit;
}
?>