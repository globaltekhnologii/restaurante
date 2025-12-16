<?php
// API para buscar cliente por número de teléfono
// Retorna datos del último pedido para autocompletar formulario

header('Content-Type: application/json');
require_once '../config.php';

try {
    $telefono = $_GET['telefono'] ?? $_POST['telefono'] ?? '';
    
    // Validar teléfono
    $telefono = preg_replace('/[^0-9]/', '', $telefono); // Solo números
    
    if (strlen($telefono) < 10) {
        echo json_encode([
            'found' => false,
            'message' => 'Teléfono debe tener al menos 10 dígitos'
        ]);
        exit;
    }
    
    $conn = getDatabaseConnection();
    
    // Buscar el último pedido de este teléfono
    $sql = "SELECT 
                nombre_cliente,
                telefono,
                tipo_documento,
                numero_documento,
                email,
                direccion,
                ciudad_entrega
            FROM pedidos 
            WHERE telefono = ? 
            ORDER BY fecha_pedido DESC 
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $telefono);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Cliente encontrado
        echo json_encode([
            'found' => true,
            'message' => '¡Bienvenido de nuevo! Verifica tus datos',
            'data' => [
                'nombre' => $row['nombre_cliente'] ?? '',
                'telefono' => $row['telefono'] ?? '',
                'tipo_documento' => $row['tipo_documento'] ?? '',
                'numero_documento' => $row['numero_documento'] ?? '',
                'email' => $row['email'] ?? '',
                'direccion' => $row['direccion'] ?? '',
                'ciudad_entrega' => $row['ciudad_entrega'] ?? ''
            ]
        ]);
    } else {
        // Cliente nuevo
        echo json_encode([
            'found' => false,
            'message' => 'Cliente nuevo, completa tus datos'
        ]);
    }
    
    $conn->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'found' => false,
        'error' => 'Error al buscar cliente',
        'message' => $e->getMessage()
    ]);
}
?>
