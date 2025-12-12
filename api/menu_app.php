<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config.php';

$conn = getDatabaseConnection();

// Obtener todos los platos (sin filtro de disponible ya que esa columna no existe)
$sql = "SELECT * FROM platos ORDER BY nombre";
$result = $conn->query($sql);

$platos = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $platos[] = $row;
    }
}

// Si no hay platos, devolver array vacío pero con success true
if (empty($platos)) {
    echo json_encode([
        'success' => true, 
        'menu' => [],
        'message' => 'No hay platos disponibles'
    ]);
    $conn->close();
    exit;
}

// Agrupar por categorías
$menu = [];
foreach ($platos as $plato) {
    $cat = isset($plato['categoria']) && !empty($plato['categoria']) ? $plato['categoria'] : 'General';
    if (!isset($menu[$cat])) {
        $menu[$cat] = [];
    }
    $menu[$cat][] = $plato;
}

// Transformar a formato Array de Objetos Categoría
$response = [];
foreach ($menu as $catName => $items) {
    $response[] = [
        'id' => md5($catName),
        'nombre' => $catName,
        'platos' => $items
    ];
}

echo json_encode(['success' => true, 'menu' => $response]);

$conn->close();
?>
