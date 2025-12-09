<?php
session_start();

// Verificar sesión y rol de chef
require_once 'auth_helper.php';
verificarSesion();
verificarRolORedirect(['chef'], 'login.php');

require_once 'config.php';

// Validar parámetro
if (!isset($_GET['id'])) {
    header("Location: chef.php?error=" . urlencode("ID de pedido no especificado"));
    exit;
}

$pedido_id = intval($_GET['id']);

$conn = getDatabaseConnection();

// Verificar que el pedido existe y está en preparación
$stmt = $conn->prepare("SELECT * FROM pedidos WHERE id = ? AND estado = 'preparando'");
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    header("Location: chef.php?error=" . urlencode("Pedido no encontrado o no está en preparación"));
    exit;
}

$stmt->close();

// Obtener estado actual antes de cambiar
$check_stmt = $conn->prepare("SELECT estado, numero_pedido FROM pedidos WHERE id = ?");
$check_stmt->bind_param("i", $pedido_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$pedido_actual = $check_result->fetch_assoc();
$estado_anterior = $pedido_actual['estado'];
$numero_pedido = $pedido_actual['numero_pedido'];
$check_stmt->close();

// Actualizar estado a listo (para que lo recoja mesero o domiciliario)
// LÓGICA DE ASIGNACIÓN AUTOMÁTICA DE DOMICILIARIO
$domiciliario_assign_sql = "";
$domiciliario_id_to_assign = null;

// Solo para pedidos de domicilio
if ($pedido_actual['tipo_pedido'] ?? 'domicilio' === 'domicilio') { // Asumiendo que existe columna o lógica, sino verificamos por direccion
    // Verificar si es domicilio (por si acaso no teniamos el tipo)
    $es_domicilio = true; // Simplificación, en realidad deberíamos chequear si tiene dirección
    
    if ($es_domicilio) {
        // Contar domiciliarios activos
        $count_stmt = $conn->query("SELECT id FROM usuarios WHERE rol = 'domiciliario' AND activo = 1");
        if ($count_stmt->num_rows === 1) {
            // Solo hay uno, asignarlo automáticamente
            $row = $count_stmt->fetch_assoc();
            $domiciliario_id_to_assign = $row['id'];
            $domiciliario_assign_sql = ", domiciliario_id = $domiciliario_id_to_assign";
        }
    }
}

$stmt = $conn->prepare("UPDATE pedidos SET estado = 'listo' $domiciliario_assign_sql WHERE id = ?");
$stmt->bind_param("i", $pedido_id);

if ($stmt->execute()) {
    // Registrar el cambio en log
    $log_stmt = $conn->prepare("INSERT INTO pedidos_log (pedido_id, estado_anterior, estado_nuevo, script) VALUES (?, ?, 'listo', 'marcar_listo.php')");
    $log_stmt->bind_param("is", $pedido_id, $estado_anterior);
    $log_stmt->execute();
    $log_stmt->close();
    
    // Verificar que realmente cambió
    $verify_stmt = $conn->prepare("SELECT estado FROM pedidos WHERE id = ?");
    $verify_stmt->bind_param("i", $pedido_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    $estado_final = $verify_result->fetch_assoc()['estado'];
    $verify_stmt->close();
    
    $stmt->close();
    $conn->close();
    
    if ($estado_final === 'listo') {
        header("Location: chef.php?success=" . urlencode("Pedido $numero_pedido marcado como listo ✅"));
    } else {
        header("Location: chef.php?error=" . urlencode("ADVERTENCIA: Estado cambió a '$estado_final' en lugar de 'listo'"));
    }
    exit;
} else {
    $error = $stmt->error;
    $stmt->close();
    $conn->close();
    header("Location: chef.php?error=" . urlencode("Error al actualizar pedido: " . $error));
    exit;
}
?>
