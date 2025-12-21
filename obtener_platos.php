<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Importante: Permite que cualquier app (tu frontend) pida datos


require_once 'config.php';
require_once 'includes/tenant_context.php'; // NUEVO: Soporte multi-tenencia

$conn = getDatabaseConnection();

// Obtener tenant_id del usuario actual (si está logueado)
// Si no hay sesión, usar tenant 1 por defecto (para compatibilidad)
session_start();
$tenant_id = getCurrentTenantId();

// 1. Preparamos la consulta SQL filtrando por tenant
$sql = "SELECT * FROM platos WHERE tenant_id = $tenant_id";
$resultado = $conn->query($sql);

$platos = []; // Creamos una lista vacía

// 2. Si hay resultados, los recorremos uno por uno
if ($resultado && $resultado->num_rows > 0) {
    while($fila = $resultado->fetch_assoc()) {
        // Agregamos cada plato a nuestra lista
        $platos[] = $fila;
    }
}

// 3. Imprimimos la lista completa en formato JSON
echo json_encode($platos);

$conn->close();
?>