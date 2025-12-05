<?php
// api/get_mesas.php - Endpoint para obtener estado de mesas
session_start();
header('Content-Type: application/json');

require_once '../auth_helper.php';
require_once '../config.php';

// Verificar sesión
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== TRUE) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$conn = getDatabaseConnection();

try {
    // Obtener todas las mesas
    $sql = "SELECT * FROM mesas ORDER BY numero_mesa ASC";
    $result = $conn->query($sql);
    
    $mesas = [];
    while ($row = $result->fetch_assoc()) {
        $mesas[] = [
            'id' => (int)$row['id'],
            'numero_mesa' => $row['numero_mesa'],
            'capacidad' => (int)$row['capacidad'],
            'estado' => $row['estado'],
            'ubicacion' => $row['ubicacion'] ?? ''
        ];
    }
    
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'mesas' => $mesas,
        'total' => count($mesas),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
