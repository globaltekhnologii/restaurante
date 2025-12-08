<?php
// Obtener número de pedido
$numero_pedido = isset($_GET['numero']) ? htmlspecialchars($_GET['numero']) : '';

if (empty($numero_pedido)) {
    header("Location: index.php");
    exit;
}

// Usar configuración centralizada
require_once 'config.php';
$conn = getDatabaseConnection();

$stmt = $conn->prepare("SELECT * FROM pedidos WHERE numero_pedido = ?");
$stmt->bind_param("s", $numero_pedido);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: index.php");
    exit;
}

$pedido = $result->fetch_assoc();
$stmt->close();

// Obtener items del pedido
$stmt_items = $conn->prepare("SELECT * FROM pedidos_items WHERE pedido_id = ?");
$stmt_items->bind_param("i", $pedido['id']);
$stmt_items->execute();
$items = $stmt_items->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_items->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido Confirmado - Restaurante El Sabor</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .confirmation-card {
            background: white;
            border-radius: 20px;
            padding: 50px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
            animation: slideUp 0.6s ease;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .success-icon {
            font-size: 6em;
            margin-bottom: 20px;
            animation: bounce 1s ease infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        h1 {
            color: #28a745;
            font-size: 2.5em;
            margin-bottom: 15px;
        }
        
        .order-number {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 30px 0;
            border-left: 4px solid #667eea;
        }
        
        .order-number strong {
            color: #667eea;
            font-size: 1.5em;
        }
        
        .order-details {
            text-align: left;
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .items-list {
            margin: 20px 0;
            text-align: left;
        }
        
        .item {
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .total {
            font-size: 1.5em;
            font-weight: 700;
            color: #667eea;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #667eea;
        }
        
        .message {
            color: #666;
            font-size: 1.1em;
            margin: 20px 0;
            line-height: 1.6;
        }
        
        .btn {
            display: inline-block;
            padding: 15px 40px;
            margin: 10px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1em;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102,126,234,0.4);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        @media (max-width: 600px) {
            .confirmation-card {
                padding: 30px 20px;
            }
            
            h1 {
                font-size: 2em;
            }
        }
    </style>
</head>
<body>

    <div class="confirmation-card">
        <div class="success-icon">✅</div>
        <h1>¡Pedido Confirmado!</h1>
        
        <div class="order-number">
            <p>Tu número de pedido es:</p>
            <strong><?php echo $numero_pedido; ?></strong>
        </div>
        
        <div class="order-details">
            <h3 style="margin-bottom: 15px; color: #333;">📋 Detalles del Pedido</h3>
            
            <div class="detail-row">
                <span><strong>Cliente:</strong></span>
                <span><?php echo htmlspecialchars($pedido['nombre_cliente']); ?></span>
            </div>
            
            <div class="detail-row">
                <span><strong>Teléfono:</strong></span>
                <span><?php echo htmlspecialchars($pedido['telefono']); ?></span>
            </div>
            
            <div class="detail-row">
                <span><strong>Dirección:</strong></span>
                <span><?php echo htmlspecialchars($pedido['direccion']); ?></span>
            </div>
            
            <div class="detail-row">
                <span><strong>Estado:</strong></span>
                <span style="color: #ffc107; font-weight: 600;">⏳ Pendiente</span>
            </div>
            
            <div class="items-list">
                <h4 style="margin: 15px 0 10px 0; color: #333;">Productos:</h4>
                <?php foreach ($items as $item): ?>
                <div class="item">
                    <div style="display: flex; justify-content: space-between;">
                        <span><?php echo htmlspecialchars($item['plato_nombre']); ?> x <?php echo $item['cantidad']; ?></span>
                        <span>$<?php echo number_format($item['subtotal'], 2); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="total">
                Total: $<?php echo number_format($pedido['total'], 2); ?>
            </div>
        </div>
        
        <p class="message">
            📱 Te contactaremos pronto al <strong><?php echo htmlspecialchars($pedido['telefono']); ?></strong> 
            para confirmar tu pedido. Tiempo estimado de entrega: <strong>30-45 minutos</strong>.
        </p>
        
        <div>
            <a href="mis_pedidos.php?telefono=<?php echo urlencode($pedido['telefono']); ?>" class="btn btn-primary">
                Ver Mis Pedidos
            </a>
            <a href="index.php" class="btn btn-secondary">
                Volver al Inicio
            </a>
        </div>
    </div>

    <script>
        // Limpiar carrito después de confirmar pedido
        localStorage.removeItem('carrito');
    </script>

</body>
</html>