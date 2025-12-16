<?php
/**
 * Pago Demo - Simulador de pago para pruebas locales
 * No requiere internet
 */
session_start();
require_once 'config.php';

$pedidoId = $_GET['pedido_id'] ?? null;
$monto = $_GET['monto'] ?? 0;

if (!$pedidoId) {
    header('Location: index.php');
    exit;
}

$conn = getDatabaseConnection();
$stmt = $conn->prepare("SELECT * FROM pedidos WHERE id = ?");
$stmt->bind_param("i", $pedidoId);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();
$conn->close();

if (!$pedido) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pago Demo - Simulador</title>
    <link rel="stylesheet" href="css/admin-modern.css">
    <style>
        .demo-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .demo-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .demo-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .payment-buttons {
            display: grid;
            gap: 15px;
            margin-top: 30px;
        }
        .btn-demo {
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-success:hover {
            background: #218838;
        }
        .btn-failure {
            background: #dc3545;
            color: white;
        }
        .btn-failure:hover {
            background: #c82333;
        }
    </style>
</head>
<body>
    <div class="demo-container">
        <div class="demo-header">
            <h1>🧪 Simulador de Pago Demo</h1>
            <p style="color: #666;">Modo de prueba local - Sin internet requerida</p>
        </div>
        
        <div class="demo-info">
            <h3>Detalles del Pedido</h3>
            <p><strong>Número:</strong> <?php echo htmlspecialchars($pedido['numero_pedido']); ?></p>
            <p><strong>Cliente:</strong> <?php echo htmlspecialchars($pedido['nombre_cliente']); ?></p>
            <p><strong>Total:</strong> $<?php echo number_format($pedido['total'], 0, ',', '.'); ?> COP</p>
        </div>
        
        <div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <p style="margin: 0; color: #856404;">
                ⚠️ <strong>Modo Demo:</strong> Este es un simulador local. Selecciona el resultado que quieres simular:
            </p>
        </div>
        
        <div class="payment-buttons">
            <form action="procesar_pago_demo.php" method="POST">
                <input type="hidden" name="pedido_id" value="<?php echo $pedidoId; ?>">
                <input type="hidden" name="resultado" value="aprobado">
                <button type="submit" class="btn-demo btn-success">
                    ✅ Simular Pago Exitoso
                </button>
            </form>
            
            <form action="procesar_pago_demo.php" method="POST">
                <input type="hidden" name="pedido_id" value="<?php echo $pedidoId; ?>">
                <input type="hidden" name="resultado" value="rechazado">
                <button type="submit" class="btn-demo btn-failure">
                    ❌ Simular Pago Rechazado
                </button>
            </form>
        </div>
        
        <p style="text-align: center; margin-top: 30px; color: #666; font-size: 14px;">
            Este simulador te permite probar el flujo completo sin necesidad de pasarelas de pago reales.
        </p>
    </div>
</body>
</html>
