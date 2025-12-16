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
$ciudad_entrega = isset($_POST['ciudad_entrega']) ? trim($_POST['ciudad_entrega']) : '';
$tipo_documento = isset($_POST['tipo_documento']) ? trim($_POST['tipo_documento']) : '';
$numero_documento = isset($_POST['numero_documento']) ? trim($_POST['numero_documento']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$notas = isset($_POST['notas']) ? trim($_POST['notas']) : '';
$total = floatval($_POST['total']);
$carrito_json = $_POST['carrito'];
$carrito = json_decode($carrito_json, true);

// Datos GPS del domicilio (si existen)
$latitud_cliente = isset($_POST['latitud_cliente']) && !empty($_POST['latitud_cliente']) ? floatval($_POST['latitud_cliente']) : null;
$longitud_cliente = isset($_POST['longitud_cliente']) && !empty($_POST['longitud_cliente']) ? floatval($_POST['longitud_cliente']) : null;
$distancia_km = isset($_POST['distancia_km']) && !empty($_POST['distancia_km']) ? floatval($_POST['distancia_km']) : null;
$costo_domicilio = isset($_POST['costo_domicilio']) && !empty($_POST['costo_domicilio']) ? floatval($_POST['costo_domicilio']) : null;

// Método de pago seleccionado
$metodo_pago_seleccionado = isset($_POST['metodo_pago_seleccionado']) ? trim($_POST['metodo_pago_seleccionado']) : 'efectivo';

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
    // Obtener tipo de pedido
    $tipo_pedido = isset($_POST['tipo_pedido']) ? $_POST['tipo_pedido'] : 'domicilio';
    
    // Insertar el pedido principal
    // Insertar pedido con datos GPS, documento y ciudad si están disponibles
    $stmt = $conn->prepare("INSERT INTO pedidos (numero_pedido, cliente_id, nombre_cliente, telefono, tipo_documento, numero_documento, direccion, ciudad_entrega, email, total, estado, notas, tipo_pedido, latitud_cliente, longitud_cliente, distancia_km, costo_domicilio) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmado', ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param("sississssdssdddd", 
        $numero_pedido,
        $cliente_id,
        $nombre_cliente,
        $telefono,
        $tipo_documento,
        $numero_documento,
        $direccion,
        $ciudad_entrega,
        $email,
        $total,
        $notas,
        $tipo_pedido,
        $latitud_cliente,
        $longitud_cliente,
        $distancia_km,
        $costo_domicilio
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Error al crear el pedido: " . $stmt->error);
    }
    
    $pedido_id = $stmt->insert_id;
    $stmt->close();
    
    // Insertar los items del pedido
    $stmt_items = $conn->prepare("INSERT INTO pedidos_items (pedido_id, plato_id, plato_nombre, precio_unitario, cantidad, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
    
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
    
    // Redirigir según método de pago
    if ($metodo_pago_seleccionado === 'bold') {
        // Pago con Bold - crear orden de pago
        require_once 'includes/bold_client.php';
        
        try {
            $envFile = __DIR__ . '/.env.bold';
            $config = parse_ini_file($envFile);
            
            $datosOrden = [
                'monto' => $total,
                'descripcion' => 'Pedido #' . $numero_pedido,
                'referencia' => $numero_pedido,
                'url_retorno' => $config['BOLD_RETURN_URL'] . '?pedido_id=' . $pedido_id,
                'url_webhook' => $config['BOLD_WEBHOOK_URL'],
                'cliente_nombre' => $nombre_cliente,
                'cliente_email' => $email,
                'cliente_telefono' => $telefono,
                'tipo_documento' => $tipo_documento,
                'numero_documento' => $numero_documento
            ];
            
            $bold = new BoldClient();
            $respuesta = $bold->crearOrdenPago($datosOrden);
            
            // Guardar transacción en BD
            $conn = getDatabaseConnection();
            $stmt = $conn->prepare("INSERT INTO pagos_bold (pedido_id, bold_transaction_id, bold_order_id, monto, estado, datos_bold) VALUES (?, ?, ?, ?, 'pendiente', ?)");
            
            $transactionId = $respuesta['data']['id'] ?? '';
            $orderId = $respuesta['data']['orderId'] ?? '';
            $datosBold = json_encode($respuesta);
            
            $stmt->bind_param("issds", $pedido_id, $transactionId, $orderId, $total, $datosBold);
            $stmt->execute();
            $conn->close();
            
            // Redirigir a checkout de Bold
            $checkoutUrl = $bold->getCheckoutUrl($orderId);
            header("Location: " . $checkoutUrl);
            exit;
            
        } catch (Exception $e) {
            error_log("Error al crear pago Bold: " . $e->getMessage());
            header("Location: confirmacion_pedido.php?numero=" . $numero_pedido . "&error=pago");
            exit;
        }
    } elseif ($metodo_pago_seleccionado === 'demo') {
        // Pago Demo - Simulador local
        header("Location: pago_demo.php?pedido_id=" . $pedido_id . "&monto=" . $total);
        exit;
    } else {
        // Pago en efectivo - redirigir a confirmación normal
        header("Location: confirmacion_pedido.php?numero=" . $numero_pedido);
        exit;
    }
    
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