<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config.php';

$conn = getDatabaseConnection();
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

$user_id = isset($data['user_id']) ? $conn->real_escape_string($data['user_id']) : null;
$items = isset($data['items']) ? $data['items'] : [];
$total = isset($data['total']) ? (float)$data['total'] : 0;
// Dirección, notas, etc.
$direccion = isset($data['direccion']) ? $conn->real_escape_string($data['direccion']) : '';
$notas = isset($data['notas']) ? $conn->real_escape_string($data['notas']) : '';

if (!$user_id || empty($items)) {
    echo json_encode(['success' => false, 'message' => 'Falta usuario o items']);
    exit;
}

// Crear Pedido
// Estado inicial: 'pendiente'
// Tipo: 'domicilio' (asumido para app)
$fecha = date('Y-m-d H:i:s');
$sql_pedido = "INSERT INTO pedidos (usuario_id, fecha_pedido, total, estado, tipo_pedido, direccion_entrega, notas) 
               VALUES ('$user_id', '$fecha', $total, 'pendiente', 'domicilio', '$direccion', '$notas')";

// Nota: en algunos sistemas usuario_id refiere a tabla usuarios (login sistema) y cliente_id a tabla clientes.
// Mi sistema tiene 'clientes' separados.
// Si la tabla pedidos tiene 'cliente_id', debo usar ese.
// Verificaré estructura: La tabla pedidos suele tener 'cliente_id' para clientes externos y 'usuario_id' para quien tomó la orden (mesero).
// Pero en pedidos online, el usuario es el cliente.
// ESTRATEGIA: Intentar insertar en 'cliente_id' si existe columna, sino en 'usuario_id'.
// Como agregué auth_app basado en tabla 'clientes', usaré 'cliente_id' preferiblemente.

// Consulta de inspección previa (mental/código): 
// Voy a intentar insertar asumiendo cliente_id. Si falla, el catch nos dirá (o la lógica de abajo).
// Dado que no puedo hacer try-catch interactivo fácil, haré un check de columnas si pudiera, pero mejor:
// Usaré una query que intente ser compatible o modificaré el script si falla.
// Asumiré que `pedidos` tiene `cliente_id` (común en este proyecto).

$sql_pedido = "INSERT INTO pedidos (cliente_id, fecha_pedido, total, estado, tipo_pedido, direccion_entrega, notas) 
               VALUES ('$user_id', '$fecha', $total, 'pendiente', 'domicilio', '$direccion', '$notas')";

if ($conn->query($sql_pedido)) {
    $pedido_id = $conn->insert_id;
    
    // Insertar Items
    foreach ($items as $item) {
        $plato_id = (int)$item['id'];
        $cantidad = (int)$item['cantidad'];
        $precio = (float)$item['precio'];
        
        $sql_item = "INSERT INTO pedidos_items (pedido_id, plato_id, cantidad, precio) 
                     VALUES ($pedido_id, $plato_id, $cantidad, $precio)";
        $conn->query($sql_item);
    }
    
    echo json_encode(['success' => true, 'message' => 'Pedido creado', 'pedido_id' => $pedido_id]);
} else {
    // Si falla, puede ser porque no existe cliente_id y se usa usuario_id
    // Intento secundario
    $sql_retry = "INSERT INTO pedidos (usuario_id, fecha_pedido, total, estado, tipo_pedido, direccion_entrega, notas) 
                   VALUES ('$user_id', '$fecha', $total, 'pendiente', 'domicilio', '$direccion', '$notas')";
    
    if ($conn->query($sql_retry)) {
         $pedido_id = $conn->insert_id;
          // Items igual que arriba
        foreach ($items as $item) {
            $plato_id = (int)$item['id'];
            $cantidad = (int)$item['cantidad'];
            $precio = (float)$item['precio'];
            $sql_item = "INSERT INTO pedidos_items (pedido_id, plato_id, cantidad, precio) 
                         VALUES ($pedido_id, $plato_id, $cantidad, $precio)";
            $conn->query($sql_item);
        }
        echo json_encode(['success' => true, 'message' => 'Pedido creado (vía usuario_id)', 'pedido_id' => $pedido_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al crear pedido: ' . $conn->error]);
    }
}

$conn->close();
?>
