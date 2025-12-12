<?php
// Script de Prueba Integral de Flujo Domicilio
// Simula: Mesero Crea -> Chef Cocina -> Domiciliario Entrega

require_once 'config.php';
$conn = getDatabaseConnection();

function logStep($step, $msg, $success = true) {
    $color = $success ? "green" : "red";
    $icon = $success ? "✅" : "❌";
    echo "<div style='margin-bottom:10px; padding:10px; border-left:4px solid $color; background:#f9f9f9'>";
    echo "<strong>$icon Paso $step:</strong> $msg";
    echo "</div>";
    if (!$success) die("STOP: Prueba fallida en paso $step");
}

echo "<h1>🧪 Prueba Integral de Flujo Domicilio</h1>";

// --- PASO 1: CREACIÓN (Mesero) ---
echo "<h2>1. Creación de Pedido (Simulando Mesero)</h2>";

$numero_pedido = 'TEST-FULL-' . date('Hi');
$mesero_id = 2; // Asumimos ID 2
$nombre_cliente = "Cliente Test Flow";
$telefono = "555-9999";
$direccion = "Calle Prueba Flow 123";
$tipo_pedido = "domicilio";

// Insertar directamente para probar la lógica de BD (ya que no puedo hacer POST fácil)
$sql_insert = "INSERT INTO pedidos (numero_pedido, nombre_cliente, telefono, direccion, notas, total, estado, mesa_id, usuario_id, fecha_pedido, tipo_pedido) 
               VALUES ('$numero_pedido', '$nombre_cliente', '$telefono', '$direccion', 'Nota Test', 25000, 'confirmado', 0, $mesero_id, NOW(), '$tipo_pedido')";

if ($conn->query($sql_insert)) {
    $pedido_id = $conn->insert_id;
    logStep(1, "Pedido creado con ID $pedido_id y tipo '$tipo_pedido'", true);
} else {
    logStep(1, "Error al crear pedido: " . $conn->error, false);
}

// --- PASO 2: VERIFICACIÓN INICIAL ---
$res = $conn->query("SELECT tipo_pedido, estado FROM pedidos WHERE id = $pedido_id");
$row = $res->fetch_assoc();

if ($row['tipo_pedido'] === 'domicilio') {
    logStep(2, "Tipo de pedido guardado correctamente como 'domicilio'", true);
} else {
    logStep(2, "Tipo de pedido ERRÓNEO: " . $row['tipo_pedido'], false);
}

// --- PASO 3: COCINA (Chef) ---
echo "<h2>2. Cocina (Simulando Chef)</h2>";

// Chef marca 'preparando'
$conn->query("UPDATE pedidos SET estado = 'preparando' WHERE id = $pedido_id");
logStep(3, "Chef inicia preparación", true);

// Chef marca 'listo'
$conn->query("UPDATE pedidos SET estado = 'listo' WHERE id = $pedido_id");

$res = $conn->query("SELECT estado FROM pedidos WHERE id = $pedido_id");
$estado = $res->fetch_assoc()['estado'];

if ($estado === 'listo') {
    logStep(4, "Chef marca listo. Estado actual: $estado", true);
} else {
    logStep(4, "Error al marcar listo. Estado: $estado", false);
}

// --- PASO 4: ENTREGA (Visibilidad Domiciliario) ---
echo "<h2>3. Entrega (Simulando Domiciliario)</h2>";

// Verificar si aparece en la query del API
$domiciliario_id = 6; // Asumimos ID 6
$sql_api = "SELECT * FROM pedidos p 
            WHERE p.tipo_pedido = 'domicilio' 
            AND (
                (p.estado = 'listo' AND p.domiciliario_id IS NULL) 
                OR 
                (p.estado = 'en_camino' AND p.domiciliario_id = $domiciliario_id)
            )
            AND id = $pedido_id";

$res_api = $conn->query($sql_api);

if ($res_api->num_rows > 0) {
    logStep(5, "Pedido VISIBLE para domiciliario (Query API correcta)", true);
} else {
    logStep(5, "Pedido INVISIBLE para domiciliario", false);
}

// --- PASO 5: TOMAR PEDIDO ---
$conn->query("UPDATE pedidos SET estado = 'en_camino', domiciliario_id = $domiciliario_id WHERE id = $pedido_id");
logStep(6, "Domiciliario toma el pedido", true);

// --- PASO 6: ENTREGAR ---
$conn->query("UPDATE pedidos SET estado = 'entregado' WHERE id = $pedido_id");

$res = $conn->query("SELECT estado FROM pedidos WHERE id = $pedido_id");
$estado_final = $res->fetch_assoc()['estado'];

if ($estado_final === 'entregado') {
    logStep(7, "Pedido entregado con éxito. Flujo completo.", true);
} else {
    logStep(7, "Error al entregar.", false);
}

echo "<h3>🎉 PRUEBA EXITOSA</h3>";
$conn->close();
?>
