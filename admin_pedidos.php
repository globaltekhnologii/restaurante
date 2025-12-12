<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== TRUE) {
    header("Location: login.php");
    exit;
}

// Usar configuración centralizada
require_once 'config.php';
$conn = getDatabaseConnection();

// Filtros
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$filtro_fecha = isset($_GET['fecha']) ? $_GET['fecha'] : '';

// Construir consulta
$sql = "SELECT * FROM pedidos WHERE 1=1";
$params = [];
$types = "";

if (!empty($filtro_estado)) {
    $sql .= " AND estado = ?";
    $params[] = $filtro_estado;
    $types .= "s";
}

if (!empty($filtro_fecha)) {
    $sql .= " AND DATE(fecha_pedido) = ?";
    $params[] = $filtro_fecha;
    $types .= "s";
}

$sql .= " ORDER BY fecha_pedido DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

$pedidos = $result->fetch_all(MYSQLI_ASSOC);

// Estadísticas
$stats = [];
$stats['total'] = $conn->query("SELECT COUNT(*) as count FROM pedidos")->fetch_assoc()['count'];
$stats['pendientes'] = $conn->query("SELECT COUNT(*) as count FROM pedidos WHERE estado = 'pendiente'")->fetch_assoc()['count'];
$stats['en_proceso'] = $conn->query("SELECT COUNT(*) as count FROM pedidos WHERE estado IN ('confirmado', 'preparando', 'listo', 'en_camino')")->fetch_assoc()['count'];
$stats['entregados'] = $conn->query("SELECT COUNT(*) as count FROM pedidos WHERE estado = 'entregado'")->fetch_assoc()['count'];
$stats['hoy'] = $conn->query("SELECT COUNT(*) as count FROM pedidos WHERE DATE(fecha_pedido) = CURDATE()")->fetch_assoc()['count'];
$stats['total_ventas'] = $conn->query("SELECT SUM(total) as sum FROM pedidos WHERE estado = 'entregado'")->fetch_assoc()['sum'] ?? 0;

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pedidos - Admin</title>
    <link rel="stylesheet" href="style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        
        .admin-navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .admin-navbar h1 { font-size: 1.5em; font-weight: 600; }
        
        .navbar-actions {
            display: flex;
            gap: 15px;
        }
        
        .navbar-actions a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            background: rgba(255,255,255,0.2);
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .navbar-actions a:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        
        .stat-card .number {
            font-size: 2.5em;
            font-weight: bold;
            color: #667eea;
        }
        
        .filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .filters select,
        .filters input {
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 1em;
        }
        
        .filters button {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        
        .pedidos-list {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .pedido-item {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            display: grid;
            grid-template-columns: 150px 1fr 150px 150px 200px;
            gap: 20px;
            align-items: center;
        }
        
        .pedido-item:hover {
            background: #f8f9fa;
        }
        
        .pedido-numero {
            font-weight: 700;
            color: #333;
        }
        
        .pedido-cliente {
            font-size: 0.95em;
            color: #666;
        }
        
        .estado-badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 600;
            text-align: center;
        }
        
        .estado-pendiente { background: #fff3cd; color: #856404; }
        .estado-confirmado { background: #d1ecf1; color: #0c5460; }
        .estado-preparando { background: #d4edda; color: #155724; }
        .estado-listo { background: #b8daff; color: #004085; }
        .estado-en_camino { background: #cce5ff; color: #004085; }
        .estado-entregado { background: #d4edda; color: #155724; }
        .estado-cancelado { background: #f8d7da; color: #721c24; }
        
        .estado-selector {
            padding: 8px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 0.9em;
        }
        
        .btn-details {
            padding: 8px 16px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.9em;
        }
        
        @media (max-width: 968px) {
            .pedido-item {
                grid-template-columns: 1fr;
                gap: 10px;
            }
        }
    </style>
</head>
<body>

    <div class="admin-navbar">
        <h1>📦 Gestión de Pedidos</h1>
        <div class="navbar-actions">
            <a href="admin.php">📋 Panel Admin</a>
            <a href="index.php" target="_blank">👁️ Ver Menú</a>
            <a href="logout.php">🚪 Salir</a>
        </div>
    </div>

    <div class="container">
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>📊 Total Pedidos</h3>
                <div class="number"><?php echo $stats['total']; ?></div>
            </div>
            <div class="stat-card">
                <h3>⏳ Pendientes</h3>
                <div class="number"><?php echo $stats['pendientes']; ?></div>
            </div>
            <div class="stat-card">
                <h3>🔄 En Proceso</h3>
                <div class="number"><?php echo $stats['en_proceso']; ?></div>
            </div>
            <div class="stat-card">
                <h3>✅ Entregados</h3>
                <div class="number"><?php echo $stats['entregados']; ?></div>
            </div>
            <div class="stat-card">
                <h3>📅 Pedidos Hoy</h3>
                <div class="number"><?php echo $stats['hoy']; ?></div>
            </div>
            <div class="stat-card">
                <h3>💰 Ventas Totales</h3>
                <div class="number">$<?php echo number_format($stats['total_ventas'], 2); ?></div>
            </div>
        </div>

        <div class="filters">
            <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; width: 100%;">
                <select name="estado">
                    <option value="">Todos los estados</option>
                    <option value="pendiente" <?php echo $filtro_estado == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                    <option value="confirmado" <?php echo $filtro_estado == 'confirmado' ? 'selected' : ''; ?>>Confirmado</option>
                    <option value="preparando" <?php echo $filtro_estado == 'preparando' ? 'selected' : ''; ?>>Preparando</option>
                    <option value="listo" <?php echo $filtro_estado == 'listo' ? 'selected' : ''; ?>>Listo</option>
                    <option value="en_camino" <?php echo $filtro_estado == 'en_camino' ? 'selected' : ''; ?>>En Camino</option>
                    <option value="entregado" <?php echo $filtro_estado == 'entregado' ? 'selected' : ''; ?>>Entregado</option>
                    <option value="cancelado" <?php echo $filtro_estado == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                </select>
                
                <input type="date" name="fecha" value="<?php echo htmlspecialchars($filtro_fecha); ?>">
                
                <button type="submit">🔍 Filtrar</button>
                <a href="admin_pedidos.php" style="padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 6px;">
                    Limpiar
                </a>
            </form>
        </div>

        <div class="pedidos-list">
            <?php if (empty($pedidos)): ?>
                <div style="padding: 60px; text-align: center; color: #999;">
                    <h3>No hay pedidos</h3>
                    <p>Los pedidos aparecerán aquí cuando los clientes realicen compras.</p>
                </div>
            <?php else: ?>
                <?php foreach ($pedidos as $pedido): ?>
                <div class="pedido-item">
                    <div>
                        <div class="pedido-numero"><?php echo htmlspecialchars($pedido['numero_pedido']); ?></div>
                        <div class="pedido-cliente"><?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?></div>
                    </div>
                    
                    <div>
                        <div style="font-weight: 600;"><?php echo htmlspecialchars($pedido['nombre_cliente']); ?></div>
                        <div class="pedido-cliente">📱 <?php echo htmlspecialchars($pedido['telefono']); ?></div>
                        <div class="pedido-cliente">📍 <?php echo htmlspecialchars(substr($pedido['direccion'], 0, 50)); ?>...</div>
                    </div>
                    
                    <div style="font-weight: 700; font-size: 1.2em; color: #667eea;">
                        $<?php echo number_format($pedido['total'], 2); ?>
                    </div>
                    
                    <div>
                        <select class="estado-selector" onchange="cambiarEstado(<?php echo $pedido['id']; ?>, this.value)">
                            <option value="pendiente" <?php echo $pedido['estado'] == 'pendiente' ? 'selected' : ''; ?>>⏳ Pendiente</option>
                            <option value="confirmado" <?php echo $pedido['estado'] == 'confirmado' ? 'selected' : ''; ?>>✅ Confirmado</option>
                            <option value="preparando" <?php echo $pedido['estado'] == 'preparando' ? 'selected' : ''; ?>>👨‍🍳 Preparando</option>
                            <option value="listo" <?php echo $pedido['estado'] == 'listo' ? 'selected' : ''; ?>>🍽️ Listo</option>
                            <option value="en_camino" <?php echo $pedido['estado'] == 'en_camino' ? 'selected' : ''; ?>>🚚 En Camino</option>
                            <option value="entregado" <?php echo $pedido['estado'] == 'entregado' ? 'selected' : ''; ?>>✅ Entregado</option>
                            <option value="cancelado" <?php echo $pedido['estado'] == 'cancelado' ? 'selected' : ''; ?>>❌ Cancelado</option>
                        </select>
                    </div>
                    
                    <div>
                        <a href="ver_pedido.php?id=<?php echo $pedido['id']; ?>" class="btn-details">
                            Ver Detalles
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

    <script>
        function cambiarEstado(pedidoId, nuevoEstado) {
            if (confirm('¿Cambiar el estado del pedido?')) {
                window.location.href = `cambiar_estado_pedido.php?id=${pedidoId}&estado=${nuevoEstado}`;
            }
        }
        
        // Auto-refresh cada 30 segundos para ver nuevos pedidos
        setTimeout(() => location.reload(), 30000);
    </script>

</body>
</html>
