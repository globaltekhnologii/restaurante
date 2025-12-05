<?php
// api/clientes.php - API REST para gestión de clientes
header('Content-Type: application/json');
require_once '../config.php';
require_once '../includes/auth_helper.php';

// Verificar autenticación (solo admin y meseros pueden gestionar clientes)
session_start();
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'], ['admin', 'mesero'])) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

$conn = getDatabaseConnection();
$method = $_SERVER['REQUEST_METHOD'];

// Funciones auxiliares
function buscarClientes($conn, $termino) {
    $termino = $conn->real_escape_string($termino);
    $sql = "SELECT * FROM clientes 
            WHERE (nombre LIKE '%$termino%' 
            OR apellido LIKE '%$termino%' 
            OR telefono LIKE '%$termino%'
            OR email LIKE '%$termino%')
            AND activo = 1
            ORDER BY nombre, apellido
            LIMIT 20";
    $result = $conn->query($sql);
    $clientes = [];
    while ($row = $result->fetch_assoc()) {
        $clientes[] = $row;
    }
    return $clientes;
}

function obtenerCliente($conn, $id) {
    $id = (int)$id;
    $sql = "SELECT * FROM clientes WHERE id = $id";
    $result = $conn->query($sql);
    return $result->fetch_assoc();
}

function obtenerDireccionesCliente($conn, $cliente_id) {
    $cliente_id = (int)$cliente_id;
    $sql = "SELECT * FROM direcciones_clientes WHERE cliente_id = $cliente_id ORDER BY es_principal DESC, id";
    $result = $conn->query($sql);
    $direcciones = [];
    while ($row = $result->fetch_assoc()) {
        $direcciones[] = $row;
    }
    return $direcciones;
}

function obtenerHistorialPedidos($conn, $cliente_id) {
    $cliente_id = (int) $cliente_id;
    $sql = "SELECT p.*, u.nombre as nombre_usuario 
            FROM pedidos p
            LEFT JOIN usuarios u ON p.usuario_id = u.id
            WHERE p.cliente_id = $cliente_id
            ORDER BY p.fecha_pedido DESC
            LIMIT 50";
    $result = $conn->query($sql);
    $pedidos = [];
    while ($row = $result->fetch_assoc()) {
        $pedidos[] = $row;
    }
    return $pedidos;
}

// Manejo de solicitudes
switch ($method) {
    case 'GET':
        if (isset($_GET['buscar'])) {
            // Búsqueda de clientes
            $termino = $_GET['buscar'];
            $clientes = buscarClientes($conn, $termino);
            echo json_encode(['success' => true, 'clientes' => $clientes]);
        } elseif (isset($_GET['id'])) {
            // Obtener cliente específico
            $cliente = obtenerCliente($conn, $_GET['id']);
            if ($cliente) {
                $cliente['direcciones'] = obtenerDireccionesCliente($conn, $_GET['id']);
                $cliente['historial'] = obtenerHistorialPedidos($conn, $_GET['id']);
                echo json_encode(['success' => true, 'cliente' => $cliente]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Cliente no encontrado']);
            }
        } elseif (isset($_GET['telefono'])) {
            // Buscar por teléfono exacto
            $telefono = $conn->real_escape_string($_GET['telefono']);
            $sql = "SELECT * FROM clientes WHERE telefono = '$telefono' AND activo = 1";
            $result = $conn->query($sql);
            $cliente = $result->fetch_assoc();
            if ($cliente) {
                $cliente['direcciones'] = obtenerDireccionesCliente($conn, $cliente['id']);
                echo json_encode(['success' => true, 'cliente' => $cliente]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Cliente no encontrado']);
            }
        } else {
            // Listar todos los clientes (con paginación)
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = 50;
            $offset = ($page - 1) * $limit;
            
            $sql = "SELECT * FROM clientes WHERE activo = 1 ORDER BY nombre, apellido LIMIT $limit OFFSET $offset";
            $result = $conn->query($sql);
            $clientes = [];
            while ($row = $result->fetch_assoc()) {
                $clientes[] = $row;
            }
            
            // Contar total
            $total_result = $conn->query("SELECT COUNT(*) as total FROM clientes WHERE activo = 1");
            $total = $total_result->fetch_assoc()['total'];
            
            echo json_encode(['success' => true, 'clientes' => $clientes, 'total' => $total, 'page' => $page]);
        }
        break;
        
    case 'POST':
        // Crear nuevo cliente
        $data = json_decode(file_get_contents('php://input'), true);
        
        $nombre = $conn->real_escape_string($data['nombre']);
        $apellido = $conn->real_escape_string($data['apellido'] ?? '');
        $telefono = $conn->real_escape_string($data['telefono']);
        $email = $conn->real_escape_string($data['email'] ?? '');
        $direccion = $conn->real_escape_string($data['direccion'] ?? '');
        $ciudad = $conn->real_escape_string($data['ciudad'] ?? '');
        $notas = $conn->real_escape_string($data['notas'] ?? '');
        
        $sql = "INSERT INTO clientes (nombre, apellido, telefono, email, direccion_principal, ciudad, notas) 
                VALUES ('$nombre', '$apellido', '$telefono', '$email', '$direccion', '$ciudad', '$notas')";
        
        if ($conn->query($sql)) {
            $cliente_id = $conn->insert_id;
            
            // Si hay dirección adicional, agregarla
            if (!empty($direccion)) {
                $sql_dir = "INSERT INTO direcciones_clientes (cliente_id, alias, direccion, ciudad, es_principal) 
                            VALUES ($cliente_id, 'Principal', '$direccion', '$ciudad', 1)";
                $conn->query($sql_dir);
            }
            
            echo json_encode(['success' => true, 'cliente_id' => $cliente_id, 'message' => 'Cliente creado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear cliente: ' . $conn->error]);
        }
        break;
        
    case 'PUT':
        // Actualizar cliente
        $data = json_decode(file_get_contents('php://input'), true);
        
        $id = (int)$data['id'];
        $nombre = $conn->real_escape_string($data['nombre']);
        $apellido = $conn->real_escape_string($data['apellido'] ?? '');
        $telefono = $conn->real_escape_string($data['telefono']);
        $email = $conn->real_escape_string($data['email'] ?? '');
        $direccion = $conn->real_escape_string($data['direccion'] ?? '');
        $ciudad = $conn->real_escape_string($data['ciudad'] ?? '');
        $notas = $conn->real_escape_string($data['notas'] ?? '');
        
        $sql = "UPDATE clientes 
                SET nombre = '$nombre', 
                    apellido = '$apellido', 
                    telefono = '$telefono', 
                    email = '$email', 
                    direccion_principal = '$direccion', 
                    ciudad = '$ciudad', 
                    notas = '$notas'
                WHERE id = $id";
        
        if ($conn->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Cliente actualizado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar cliente: ' . $conn->error]);
        }
        break;
        
    case 'DELETE':
        // Eliminar cliente (soft delete)
        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int)$data['id'];
        
        $sql = "UPDATE clientes SET activo = 0 WHERE id = $id";
        
        if ($conn->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Cliente eliminado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar cliente: ' . $conn->error]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Método no soportado']);
        break;
}

$conn->close();
?>
