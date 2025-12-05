<?php
session_start();

// Verificar sesión
require_once 'auth_helper.php';
verificarSesion();

require_once 'config.php';

// Obtener ID del pedido
if (!isset($_GET['id'])) {
    header("Location: " . ($_SESSION['rol'] === 'admin' ? 'admin_pedidos.php' : $_SESSION['rol'] . '.php'));
    exit;
}

$pedido_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];
$user_rol = $_SESSION['rol'];

$conn = getDatabaseConnection();

// Obtener pedido con validación de permisos
$sql = "SELECT p.*, m.numero_mesa, u.nombre as mesero_nombre, d.nombre as domiciliario_nombre 
        FROM pedidos p 
        LEFT JOIN mesas m ON p.mesa_id = m.id 
        LEFT JOIN usuarios u ON p.usuario_id = u.id 
        LEFT JOIN usuarios d ON p.domiciliario_id = d.id 
        WHERE p.id = ?";

// Si no es admin, agregar filtros de permiso
if ($user_rol === 'mesero') {
    $sql .= " AND p.usuario_id = ?";
} elseif ($user_rol === 'domiciliario') {
    $sql .= " AND p.domiciliario_id = ?";
}

$stmt = $conn->prepare($sql);

if ($user_rol === 'mesero' || $user_rol === 'domiciliario') {
    $stmt->bind_param("ii", $pedido_id, $user_id);
} else {
    $stmt->bind_param("i", $pedido_id);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    header("Location: " . ($user_rol === 'admin' ? 'admin_pedidos.php' : $user_rol . '.php') . "?error=" . urlencode("Pedido no encontrado o sin permisos"));
    exit;
}

$pedido = $result->fetch_assoc();
$stmt->close();

// Obtener items del pedido
$stmt = $conn->prepare("SELECT * FROM pedidos_items WHERE pedido_id = ?");
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$items_result = $stmt->get_result();
$items = [];
while ($row = $items_result->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();

// Determinar color del estado
$estado_colors = [
    'pendiente' => '#ffd93d',
    'confirmado' => '#4299e1',
    'preparando' => '#ed8936',
    'en_camino' => '#4299e1',
    'entregado' => '#48bb78',
    'cancelado' => '#f44336'
];

$estado_color = $estado_colors[$pedido['estado']] ?? '#999';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido <?php echo htmlspecialchars($pedido['numero_pedido']); ?> - Restaurante El Sabor</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar h1 { font-size: 1.3em; }
        
        .navbar a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            background: rgba(255,255,255,0.2);
            border-radius: 5px;
        }
        
        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .pedido-header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .pedido-numero {
            font-size: 1.8em;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
        }
        
        .pedido-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .meta-item {
            padding: 15px;
            background: #f7fafc;
            border-radius: 8px;
        }
        
        .meta-label {
            font-size: 0.85em;
            color: #666;
            margin-bottom: 5px;
        }
        
        .meta-value {
            font-weight: 600;
            color: #333;
        }
        
        .badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: bold;
            color: white;
        }
        
        .section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .section h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #667eea;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .info-label {
            font-size: 0.85em;
            color: #666;
            font-weight: 600;
        }
        
        .info-value {
            font-size: 1em;
            color: #333;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        thead {
            background: #f7fafc;
        }
        
        th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #666;
            font-size: 0.9em;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .item-nombre {
            font-weight: 600;
        }
        
        .total-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 1.1em;
        }
        
        .total-final {
            font-size: 1.4em;
            font-weight: bold;
            color: #667eea;
            padding-top: 10px;
            border-top: 2px solid #e0e0e0;
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 20px;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -23px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #667eea;
        }
        
        .timeline-item::after {
            content: '';
            position: absolute;
            left: -18px;
            top: 17px;
            width: 2px;
            height: calc(100% - 12px);
            background: #e0e0e0;
        }
        
        .timeline-item:last-child::after {
            display: none;
        }
        
        .timeline-time {
            font-size: 0.85em;
            color: #666;
        }
        
        .timeline-event {
            font-weight: 600;
            color: #333;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>📋 Detalle del Pedido</h1>
        <a href="<?php echo $user_rol === 'admin' ? 'admin_pedidos.php' : $user_rol . '.php'; ?>">← Volver</a>
    </div>

    <div class="container">
        <!-- Header del Pedido -->
        <div class="pedido-header">
            <div class="pedido-numero">
                🧾 <?php echo htmlspecialchars($pedido['numero_pedido']); ?>
            </div>
            <span class="badge" style="background: <?php echo $estado_color; ?>;">
                <?php echo ucfirst(str_replace('_', ' ', $pedido['estado'])); ?>
            </span>
            
            <div class="pedido-meta">
                <div class="meta-item">
                    <div class="meta-label">Fecha y Hora</div>
                    <div class="meta-value"><?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?></div>
                </div>
                <?php if ($pedido['numero_mesa']): ?>
                <div class="meta-item">
                    <div class="meta-label">Mesa</div>
                    <div class="meta-value">🪑 <?php echo htmlspecialchars($pedido['numero_mesa']); ?></div>
                </div>
                <?php endif; ?>
                <?php if ($pedido['mesero_nombre']): ?>
                <div class="meta-item">
                    <div class="meta-label">Mesero</div>
                    <div class="meta-value">👤 <?php echo htmlspecialchars($pedido['mesero_nombre']); ?></div>
                </div>
                <?php endif; ?>
                <?php if ($pedido['domiciliario_nombre']): ?>
                <div class="meta-item">
                    <div class="meta-label">Domiciliario</div>
                    <div class="meta-value">🏍️ <?php echo htmlspecialchars($pedido['domiciliario_nombre']); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Acciones de Impresión -->
        <div class="section" style="padding: 15px; display: flex; gap: 15px; justify-content: flex-end; flex-wrap: wrap;">
            <?php if ($pedido['estado'] === 'entregado' && !$pedido['pagado']): ?>
            <a href="registrar_pago.php?pedido_id=<?php echo $pedido['id']; ?>" class="btn" style="background: #48bb78; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: flex; align-items: center; gap: 8px;">
                💳 Registrar Pago
            </a>
            <?php endif; ?>
            
            <?php if (($user_rol === 'mesero' || $user_rol === 'admin') && ($pedido['estado'] === 'en_camino' || $pedido['estado'] === 'preparando')): ?>
            <a href="cambiar_estado_pedido.php?id=<?php echo $pedido['id']; ?>&estado=entregado&redirect=<?php echo urlencode('ver_pedido.php?id=' . $pedido['id']); ?>" class="btn" style="background: #ed8936; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: flex; align-items: center; gap: 8px;" onclick="return confirm('¿Confirmas que has entregado este pedido a la mesa?');">
                ✅ Marcar como Entregado
            </a>
            <?php endif; ?>
            
            <?php if ($pedido['pagado']): ?>
            <a href="ver_comprobante_pago.php?pedido_id=<?php echo $pedido['id']; ?>&print=true" target="_blank" class="btn" style="background: #48bb78; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: flex; align-items: center; gap: 8px;">
                📄 Ver Comprobante de Pago
            </a>
            <?php endif; ?>
            
            <a href="ver_ticket.php?id=<?php echo $pedido['id']; ?>&print=true" target="_blank" class="btn" style="background: #333; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: flex; align-items: center; gap: 8px;">
                🖨️ Ticket Cocina
            </a>
            <a href="ver_factura.php?id=<?php echo $pedido['id']; ?>&print=true" target="_blank" class="btn" style="background: #2b6cb0; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: flex; align-items: center; gap: 8px;">
                📄 Imprimir Factura
            </a>
        </div>

        <!-- Información del Cliente -->
        <div class="section">
            <h2>👤 Información del Cliente</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Nombre:</span>
                    <span class="info-value"><?php echo htmlspecialchars($pedido['nombre_cliente']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Teléfono:</span>
                    <span class="info-value">📞 <?php echo htmlspecialchars($pedido['telefono']); ?></span>
                </div>
                <?php if ($pedido['direccion']): ?>
                <div class="info-item">
                    <span class="info-label">Dirección:</span>
                    <span class="info-value">📍 <?php echo htmlspecialchars($pedido['direccion']); ?></span>
                </div>
                <?php endif; ?>
                <?php if ($pedido['notas']): ?>
                <div class="info-item" style="grid-column: 1 / -1;">
                    <span class="info-label">Notas Especiales:</span>
                    <span class="info-value">📝 <?php echo htmlspecialchars($pedido['notas']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Items del Pedido -->
        <div class="section">
            <h2>🍽️ Items del Pedido</h2>
            <table>
                <thead>
                    <tr>
                        <th>Plato</th>
                        <th>Precio</th>
                        <th>Cantidad</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td class="item-nombre"><?php echo htmlspecialchars($item['nombre_plato']); ?></td>
                        <td>$<?php echo number_format($item['precio'], 2); ?></td>
                        <td><?php echo $item['cantidad']; ?>x</td>
                        <td><strong>$<?php echo number_format($item['precio'] * $item['cantidad'], 2); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="total-section">
                <div class="total-row total-final">
                    <span>Total:</span>
                    <span>$<?php echo number_format($pedido['total'], 2); ?></span>
                </div>
            </div>
        </div>

        <!-- Timeline -->
        <?php if ($pedido['hora_salida'] || $pedido['hora_entrega']): ?>
        <div class="section">
            <h2>⏱️ Historial</h2>
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-time"><?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?></div>
                    <div class="timeline-event">Pedido creado</div>
                </div>
                <?php if ($pedido['hora_salida']): ?>
                <div class="timeline-item">
                    <div class="timeline-time"><?php echo date('d/m/Y H:i', strtotime($pedido['hora_salida'])); ?></div>
                    <div class="timeline-event">Domiciliario salió a entregar</div>
                </div>
                <?php endif; ?>
                <?php if ($pedido['hora_entrega']): ?>
                <div class="timeline-item">
                    <div class="timeline-time"><?php echo date('d/m/Y H:i', strtotime($pedido['hora_entrega'])); ?></div>
                    <div class="timeline-event">Pedido entregado</div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php $conn->close(); ?>
