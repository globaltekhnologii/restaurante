<?php
session_start();

// Verificar sesión y rol de chef
require_once 'auth_helper.php';
verificarSesion();
verificarRolORedirect(['chef'], 'login.php');

require_once 'config.php';
$conn = getDatabaseConnection();

// Obtener información del chef
$chef_nombre = $_SESSION['nombre'];

// Obtener estadísticas
$stats = [];

// Pedidos pendientes
$stats['pendientes'] = $conn->query("SELECT COUNT(*) as count FROM pedidos WHERE estado IN ('confirmado', 'preparando')")->fetch_assoc()['count'];

// Pedidos del día
$hoy = date('Y-m-d');
$stats['hoy'] = $conn->query("SELECT COUNT(*) as count FROM pedidos WHERE DATE(fecha_pedido) = '$hoy'")->fetch_assoc()['count'];

// Pedidos completados hoy
$stats['completados_hoy'] = $conn->query("SELECT COUNT(*) as count FROM pedidos WHERE DATE(fecha_pedido) = '$hoy' AND estado = 'entregado'")->fetch_assoc()['count'];

// Pedidos en preparación
$stats['preparando'] = $conn->query("SELECT COUNT(*) as count FROM pedidos WHERE estado = 'preparando'")->fetch_assoc()['count'];

// Pedidos confirmados (esperando preparación)
$stats['confirmados'] = $conn->query("SELECT COUNT(*) as count FROM pedidos WHERE estado = 'confirmado'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Chef - Restaurante El Sabor</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        
        /* Navbar - Color naranja para chef */
        .chef-navbar {
            background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .chef-navbar h1 { 
            font-size: 1.5em; 
            font-weight: 600;
        }
        
        .navbar-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .navbar-actions span {
            font-size: 0.9em;
            opacity: 0.9;
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
            transform: translateY(-2px);
        }
        
        /* Container */
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .stat-card h3 {
            font-size: 0.85em;
            color: #666;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .stat-card .number {
            font-size: 2em;
            font-weight: bold;
            color: #ed8936;
        }
        
        /* Section */
        .section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .section h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #ed8936;
        }
        
        /* Pedidos Grid */
        .pedidos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .pedido-card {
            background: white;
            border: 3px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        .pedido-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .pedido-card.confirmado {
            border-color: #4299e1;
            background: #ebf8ff;
        }
        
        .pedido-card.preparando {
            border-color: #ed8936;
            background: #fffaf0;
        }
        
        .pedido-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .pedido-numero {
            font-size: 1.2em;
            font-weight: bold;
        }
        
        .pedido-tiempo {
            font-size: 0.9em;
            color: #666;
        }
        
        .pedido-items {
            margin: 15px 0;
        }
        
        .pedido-item {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .pedido-item:last-child {
            border-bottom: none;
        }
        
        .item-nombre {
            font-weight: 600;
        }
        
        .item-cantidad {
            color: #ed8936;
            font-weight: bold;
        }
        
        .pedido-footer {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #e0e0e0;
        }
        
        .pedido-mesa {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 10px;
        }
        
        /* Buttons */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 0.95em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            width: 100%;
            text-align: center;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
            color: white;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        /* Badges */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75em;
            font-weight: bold;
        }
        
        .badge-confirmado { background: #4299e1; color: white; }
        .badge-preparando { background: #ed8936; color: white; }
        
        /* Messages */
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .message-success {
            background: #d4edda;
            border-left: 4px solid #28a745;
            color: #155724;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state .emoji {
            font-size: 4em;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <div class="chef-navbar">
        <h1>👨‍🍳 Panel de Cocina 
            <span id="badge-new_orders" class="badge" style="display:none;">0</span>
        </h1>
        <div class="navbar-actions">
            <span>👤 <?php echo htmlspecialchars($chef_nombre); ?></span>
            <a href="index.php" target="_blank">👁️ Ver Menú</a>
            <a href="logout.php">🚪 Salir</a>
        </div>
    </div>

    <div class="container">
        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>🔥 Pedidos Pendientes</h3>
                <div class="number"><?php echo $stats['pendientes']; ?></div>
            </div>
            <div class="stat-card">
                <h3>📋 Pedidos Hoy</h3>
                <div class="number"><?php echo $stats['hoy']; ?></div>
            </div>
            <div class="stat-card">
                <h3>✅ Completados Hoy</h3>
                <div class="number"><?php echo $stats['completados_hoy']; ?></div>
            </div>
            <div class="stat-card">
                <h3>🍳 En Preparación</h3>
                <div class="number"><?php echo $stats['preparando']; ?></div>
            </div>
            <div class="stat-card">
                <h3>⏳ Esperando</h3>
                <div class="number"><?php echo $stats['confirmados']; ?></div>
            </div>
        </div>

        <!-- Mensajes -->
        <?php if(isset($_GET['success'])): ?>
        <div class="message message-success">
            <strong>✅ ¡Éxito!</strong> <?php echo htmlspecialchars($_GET['success']); ?>
        </div>
        <?php endif; ?>

        <!-- Pedidos Activos -->
        <div class="section">
            <h2>🔥 Pedidos Activos</h2>
            
            <div class="pedidos-grid">
                <?php
                $sql = "SELECT p.*, m.numero_mesa, u.nombre as mesero_nombre 
                        FROM pedidos p 
                        LEFT JOIN mesas m ON p.mesa_id = m.id 
                        LEFT JOIN usuarios u ON p.usuario_id = u.id 
                        WHERE p.estado IN ('confirmado', 'preparando') 
                        ORDER BY p.fecha_pedido ASC";
                $result = $conn->query($sql);
                
                if ($result->num_rows > 0) {
                    while($pedido = $result->fetch_assoc()) {
                        $estado_class = strtolower($pedido['estado']);
                        $badge_class = 'badge-' . $estado_class;
                        
                        // Calcular tiempo transcurrido
                        $tiempo_pedido = strtotime($pedido['fecha_pedido']);
                        $tiempo_actual = time();
                        $minutos = floor(($tiempo_actual - $tiempo_pedido) / 60);
                        
                        echo '<div class="pedido-card ' . $estado_class . '">';
                        echo '<div class="pedido-header">';
                        echo '<div class="pedido-numero">🧾 ' . htmlspecialchars($pedido['numero_pedido']) . '</div>';
                        echo '<div class="pedido-tiempo">⏱️ ' . $minutos . ' min</div>';
                        echo '</div>';
                        
                        echo '<div><span class="badge ' . $badge_class . '">' . ucfirst($pedido['estado']) . '</span></div>';
                        
                        // Obtener items del pedido
                        $pedido_id = $pedido['id'];
                        $items_sql = "SELECT * FROM pedidos_items WHERE pedido_id = $pedido_id";
                        $items_result = $conn->query($items_sql);
                        
                        echo '<div class="pedido-items">';
                        while($item = $items_result->fetch_assoc()) {
                            echo '<div class="pedido-item">';
                            echo '<span class="item-cantidad">' . $item['cantidad'] . 'x</span> ';
                            echo '<span class="item-nombre">' . htmlspecialchars($item['nombre_plato']) . '</span>';
                            echo '</div>';
                        }
                        echo '</div>';
                        
                        echo '<div class="pedido-footer">';
                        echo '<div class="pedido-mesa">';
                        if ($pedido['numero_mesa']) {
                            echo '🪑 ' . htmlspecialchars($pedido['numero_mesa']) . ' | 👤 ' . htmlspecialchars($pedido['mesero_nombre']);
                        } else {
                            echo '🏠 Domicilio | 📞 ' . htmlspecialchars($pedido['telefono']);
                        }
                        echo '</div>';
                        
                        if ($pedido['estado'] === 'confirmado') {
                            echo '<a href="iniciar_preparacion.php?id=' . $pedido['id'] . '" class="btn btn-primary" style="margin-bottom: 5px;">🍳 Comenzar Preparación</a>';
                        } else if ($pedido['estado'] === 'preparando') {
                            echo '<a href="marcar_listo.php?id=' . $pedido['id'] . '" class="btn btn-success" style="margin-bottom: 5px;">✅ Marcar como Listo</a>';
                        }
                        
                        echo '<a href="ver_ticket.php?id=' . $pedido['id'] . '&print=true" target="_blank" class="btn" style="background: #333; color: white; font-size: 0.9em;">🖨️ Imprimir Comanda</a>';
                        
                        echo '</div>';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="empty-state">';
                    echo '<div class="emoji">😌</div>';
                    echo '<h3>No hay pedidos pendientes</h3>';
                    echo '<p>¡Buen trabajo! Todos los pedidos están al día.</p>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Incluir sistema de notificaciones -->
    <script src="js/notifications.js"></script>
    <script>
        // Configurar notificaciones para el chef
        const notificationConfig = {
            apiUrl: 'api/check_updates.php',
            pollInterval: 5000, // 5 segundos
            soundEnabled: true
        };
        
        // Auto-ocultar mensajes
        setTimeout(function() {
            const messages = document.querySelectorAll('.message');
            messages.forEach(msg => {
                msg.style.transition = 'opacity 0.5s ease';
                msg.style.opacity = '0';
                setTimeout(() => msg.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>
<?php $conn->close(); ?>
