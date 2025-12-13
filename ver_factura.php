<?php
session_start();
require_once 'auth_helper.php';
verificarSesion();

require_once 'config.php';

if (!isset($_GET['id'])) {
    die("ID de pedido no especificado");
}

$pedido_id = intval($_GET['id']);
$conn = getDatabaseConnection();

// Cargar información del negocio
require_once 'includes/info_negocio.php';

// Obtener info del pedido
$sql = "SELECT p.*, m.numero_mesa, u.nombre as mesero_nombre 
        FROM pedidos p 
        LEFT JOIN mesas m ON p.mesa_id = m.id 
        LEFT JOIN usuarios u ON p.usuario_id = u.id 
        WHERE p.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();

if (!$pedido) {
    die("Pedido no encontrado");
}

// Obtener items
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
    <title>Factura #<?php echo $pedido['numero_pedido']; ?> - <?php echo htmlspecialchars($info_negocio['nombre_restaurante']); ?></title>
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
        
        .info {
            margin-bottom: 10px;
            border-bottom: 1px dashed black;
            padding-bottom: 5px;
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
            border-top: 1px solid black;
            padding-top: 5px;
        }
        
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 11px;
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
    <a href="#" onclick="window.print(); return false;" class="btn-print no-print">🖨️ IMPRIMIR FACTURA</a>

    <div class="header">
        <div class="logo"><?php echo htmlspecialchars($info_negocio['nombre_restaurante']); ?></div>
        <?php if (!empty($info_negocio['nit'])): ?>
            <div>NIT: <?php echo htmlspecialchars($info_negocio['nit']); ?></div>
        <?php endif; ?>
        <div><?php echo htmlspecialchars($info_negocio['direccion']); ?></div>
        <div>Tel: <?php echo htmlspecialchars($info_negocio['telefono']); ?></div>
    </div>

    <div class="info">
        <div>Factura: #<?php echo htmlspecialchars($pedido['numero_pedido']); ?></div>
        <div>Fecha: <?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?></div>
        <div>Cliente: <?php echo htmlspecialchars($pedido['nombre_cliente']); ?></div>
        <?php if ($pedido['numero_mesa']): ?>
            <div>Mesa: <?php echo htmlspecialchars($pedido['numero_mesa']); ?></div>
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
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?php echo $item['cantidad']; ?></td>
                <td><?php echo htmlspecialchars($item['plato_nombre']); ?></td>
                <td class="price">$<?php echo number_format($item['precio_unitario'] * $item['cantidad'], 0); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals">
        <div class="total-row">
            <span>Subtotal:</span>
            <span>$<?php echo number_format($pedido['total'], 0); ?></span>
        </div>
        <!-- Aquí se podrían agregar impuestos si fuera necesario -->
        <div class="total-row grand-total">
            <span>TOTAL:</span>
            <span>$<?php echo number_format($pedido['total'], 0); ?></span>
        </div>
    </div>

    <div class="footer">
        <?php if (!empty($info_negocio['mensaje_pie_factura'])): ?>
            <p><?php echo nl2br(htmlspecialchars($info_negocio['mensaje_pie_factura'])); ?></p>
        <?php else: ?>
            <p>¡Gracias por su compra!</p>
        <?php endif; ?>
        <p>Software: Restaurante El Sabor v2.0</p>
    </div>

    <script>
        if (new URLSearchParams(window.location.search).get('print') === 'true') {
            window.print();
        }
    </script>
</body>
</html>
