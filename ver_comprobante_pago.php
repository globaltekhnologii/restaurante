<?php
session_start();
require_once 'auth_helper.php';
verificarSesion();

if (!isset($_GET['pedido_id'])) {
    die("Pedido no especificado");
}

$pedido_id = intval($_GET['pedido_id']);

require_once 'config.php';
$conn = getDatabaseConnection();

// Obtener información del pedido y pago
$sql = "SELECT p.*, pg.*, m.numero_mesa, u.nombre as mesero_nombre,
        mp.nombre_display as metodo_nombre
        FROM pedidos p
        LEFT JOIN pagos pg ON p.id = pg.pedido_id
        LEFT JOIN mesas m ON p.mesa_id = m.id
        LEFT JOIN usuarios u ON p.usuario_id = u.id
        LEFT JOIN metodos_pago_config mp ON pg.metodo_pago = mp.metodo
        WHERE p.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if (!$result) {
    die("Pedido no encontrado");
}

// Obtener items del pedido
$stmt = $conn->prepare("SELECT * FROM pedidos_items WHERE pedido_id = ?");
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$items_result = $stmt->get_result();
$items = [];
while ($row = $items_result->fetch_assoc()) {
    $items[] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Pago - <?php echo $result['numero_pedido']; ?></title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            width: 80mm;
            margin: 0;
            padding: 5px;
            font-size: 12px;
            color: black;
            background: white;
        }
        
        .header {
            text-align: center;
            margin-bottom: 10px;
        }
        
        .logo {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .comprobante-title {
            font-size: 16px;
            font-weight: bold;
            margin: 10px 0;
            text-align: center;
            border-top: 2px dashed black;
            border-bottom: 2px dashed black;
            padding: 5px 0;
        }
        
        .info {
            margin-bottom: 10px;
            border-bottom: 1px dashed black;
            padding-bottom: 5px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }
        
        .items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        
        .items th {
            text-align: left;
            border-bottom: 1px solid black;
            font-size: 11px;
        }
        
        .items td {
            padding: 3px 0;
            vertical-align: top;
        }
        
        .price {
            text-align: right;
        }
        
        .totals {
            margin-top: 10px;
            border-top: 1px dashed black;
            padding-top: 5px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }
        
        .grand-total {
            font-size: 16px;
            font-weight: bold;
            margin-top: 5px;
            border-top: 2px solid black;
            padding-top: 5px;
        }
        
        .pago-info {
            background: #f0f0f0;
            padding: 10px;
            margin: 10px 0;
            border: 2px solid black;
        }
        
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 11px;
            border-top: 1px dashed black;
            padding-top: 10px;
        }

        @media print {
            .no-print { display: none; }
            body { margin: 0; padding: 0; }
        }
        
        .btn-print {
            display: block;
            width: 100%;
            padding: 10px;
            background: #333;
            color: white;
            text-align: center;
            text-decoration: none;
            margin-bottom: 10px;
            font-family: sans-serif;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <a href="#" onclick="window.print(); return false;" class="btn-print no-print">🖨️ IMPRIMIR COMPROBANTE</a>

    <div class="header">
        <div class="logo">RESTAURANTE EL SABOR</div>
        <div>NIT: 900.123.456-7</div>
        <div>Calle 123 # 45-67</div>
        <div>Tel: 300 123 4567</div>
    </div>

    <div class="comprobante-title">
        COMPROBANTE DE PAGO
    </div>

    <div class="info">
        <div class="info-row">
            <span>Pedido:</span>
            <span><strong><?php echo htmlspecialchars($result['numero_pedido']); ?></strong></span>
        </div>
        <div class="info-row">
            <span>Fecha:</span>
            <span><?php echo date('d/m/Y H:i', strtotime($result['fecha_pedido'])); ?></span>
        </div>
        <div class="info-row">
            <span>Cliente:</span>
            <span><?php echo htmlspecialchars($result['nombre_cliente']); ?></span>
        </div>
        <?php if ($result['numero_mesa']): ?>
        <div class="info-row">
            <span>Mesa:</span>
            <span><?php echo htmlspecialchars($result['numero_mesa']); ?></span>
        </div>
        <?php endif; ?>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th>Cant</th>
                <th>Desc</th>
                <th class="price">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): 
                $nombre = $item['plato_nombre'] ?? $item['nombre_plato'] ?? 'Sin nombre';
                $precio = $item['precio_unitario'] ?? $item['precio'] ?? 0;
            ?>
            <tr>
                <td><?php echo $item['cantidad']; ?></td>
                <td><?php echo htmlspecialchars($nombre); ?></td>
                <td class="price">$<?php echo number_format($precio * $item['cantidad'], 0); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals">
        <div class="total-row">
            <span>Subtotal:</span>
            <span>$<?php echo number_format($result['total'], 0); ?></span>
        </div>
        <div class="total-row grand-total">
            <span>TOTAL:</span>
            <span>$<?php echo number_format($result['total'], 0); ?></span>
        </div>
    </div>

    <div class="pago-info">
        <div style="text-align: center; font-weight: bold; margin-bottom: 5px;">
            ✅ PAGO RECIBIDO
        </div>
        <div class="info-row">
            <span>Método:</span>
            <span><strong><?php echo htmlspecialchars($result['metodo_nombre'] ?? $result['metodo_pago']); ?></strong></span>
        </div>
        <div class="info-row">
            <span>Transacción:</span>
            <span><?php echo htmlspecialchars($result['numero_transaccion']); ?></span>
        </div>
        <?php if (!empty($result['referencia_pago'])): ?>
        <div class="info-row">
            <span>Referencia:</span>
            <span><?php echo htmlspecialchars($result['referencia_pago']); ?></span>
        </div>
        <?php endif; ?>
        <div class="info-row">
            <span>Fecha Pago:</span>
            <span><?php echo date('d/m/Y H:i', strtotime($result['fecha_pago'])); ?></span>
        </div>
    </div>

    <div class="actions" style="text-align: center; margin-top: 20px; display: flex; gap: 10px; justify-content: center;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #4caf50; color: white; border: none; border-radius: 5px; cursor: pointer;">🖨️ Imprimir</button>
        
        <?php
        $redirect_url = 'index.php';
        if (isset($_SESSION['rol'])) {
            switch ($_SESSION['rol']) {
                case 'cajero': $redirect_url = 'cajero.php'; break;
                case 'mesero': $redirect_url = 'mesero.php'; break;
                case 'admin': $redirect_url = 'admin_pedidos.php'; break;
                case 'domiciliario': $redirect_url = 'domiciliario.php'; break;
            }
        }
        ?>
        <a href="<?php echo $redirect_url; ?>" style="padding: 10px 20px; background: #2196f3; color: white; border: none; border-radius: 5px; cursor: pointer; text-decoration: none;">← Volver</a>
    </div>

    <div class="footer">
        <p><strong>¡Gracias por su compra!</strong></p>
        <p>Este es un comprobante válido de pago</p>
        <p style="margin-top: 10px;">Restaurante El Sabor - Sistema v2.0</p>
    </div>

    <script>
        if (new URLSearchParams(window.location.search).get('print') === 'true') {
            window.print();
        }
    </script>
</body>
</html>
