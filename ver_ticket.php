<?php
session_start();
require_once 'auth_helper.php';
verificarSesion();
// Acceso permitido a todos los roles logueados

require_once 'config.php';

if (!isset($_GET['id'])) {
    die("ID de pedido no especificado");
}

$pedido_id = intval($_GET['id']);
$conn = getDatabaseConnection();

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
    <title>Ticket Cocina #<?php echo $pedido['numero_pedido']; ?></title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            width: 80mm;
            margin: 0;
            padding: 5px;
            font-size: 14px;
            color: black;
            background: white;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px dashed black;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        
        .title {
            font-size: 18px;
            font-weight: bold;
            margin: 5px 0;
        }
        
        .meta {
            font-size: 12px;
            margin-bottom: 5px;
        }
        
        .items {
            width: 100%;
            border-collapse: collapse;
        }
        
        .items th {
            text-align: left;
            border-bottom: 1px solid black;
        }
        
        .items td {
            padding: 5px 0;
            vertical-align: top;
        }
        
        .qty {
            font-weight: bold;
            font-size: 16px;
            width: 30px;
        }
        
        .name {
            font-weight: bold;
        }
        
        .notes {
            margin-top: 15px;
            border-top: 2px dashed black;
            padding-top: 10px;
            font-weight: bold;
        }
        
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
            border-top: 1px solid black;
            padding-top: 5px;
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
    <a href="#" onclick="window.print(); return false;" class="btn-print no-print">🖨️ IMPRIMIR</a>

    <div class="header">
        <div class="title">COCINA</div>
        <div class="meta">Pedido: #<?php echo htmlspecialchars($pedido['numero_pedido']); ?></div>
        <div class="meta">Fecha: <?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?></div>
        
        <?php if ($pedido['numero_mesa']): ?>
            <div class="title">MESA: <?php echo htmlspecialchars($pedido['numero_mesa']); ?></div>
        <?php else: ?>
            <div class="title">DOMICILIO</div>
        <?php endif; ?>
        
        <?php if ($pedido['mesero_nombre']): ?>
            <div class="meta">Mesero: <?php echo htmlspecialchars($pedido['mesero_nombre']); ?></div>
        <?php endif; ?>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th>Cant</th>
                <th>Producto</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td class="qty"><?php echo $item['cantidad']; ?></td>
                <td class="name"><?php echo htmlspecialchars($item['nombre_plato']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if (!empty($pedido['notas'])): ?>
    <div class="notes">
        NOTAS:<br>
        <?php echo nl2br(htmlspecialchars($pedido['notas'])); ?>
    </div>
    <?php endif; ?>

    <div class="footer">
        --- Fin de Comanda ---
    </div>

    <script>
        // Auto-imprimir si se pasa el parámetro ?print=true
        if (new URLSearchParams(window.location.search).get('print') === 'true') {
            window.print();
        }
    </script>
</body>
</html>
