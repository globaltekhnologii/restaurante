<?php
session_start();

// Verificar sesión y rol de cajero
require_once 'auth_helper.php';
verificarSesion();
verificarRolORedirect(['cajero'], 'login.php');

require_once 'config.php';
require_once 'includes/info_negocio.php';

$cajero_nombre = $_SESSION['nombre'];
$conn = getDatabaseConnection();

// Obtener estadísticas del día
$hoy = date('Y-m-d');

$stats = [];

// Total de pagos hoy
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM pagos WHERE DATE(fecha_pago) = ?");
$stmt->bind_param("s", $hoy);
$stmt->execute();
$stats['pagos_hoy'] = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// Total cobrado hoy
$stmt = $conn->prepare("SELECT SUM(monto) as total FROM pagos WHERE DATE(fecha_pago) = ?");
$stmt->bind_param("s", $hoy);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stats['total_cobrado'] = $result['total'] ?: 0;
$stmt->close();

// Pedidos pendientes de pago
$stats['pendientes_pago'] = $conn->query("SELECT COUNT(*) as count FROM pedidos WHERE pagado = 0 AND estado IN ('listo', 'entregado', 'en_camino')")->fetch_assoc()['count'];

// Total en efectivo hoy
$stmt = $conn->prepare("SELECT SUM(monto) as total FROM pagos WHERE DATE(fecha_pago) = ? AND metodo_pago = 'efectivo'");
$stmt->bind_param("s", $hoy);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stats['efectivo_hoy'] = $result['total'] ?: 0;
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Caja - <?php echo htmlspecialchars($info_negocio['nombre_restaurante']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        
        /* Navbar */
        .cajero-navbar {
            background: linear-gradient(135deg, #2e7d32 0%, #388e3c 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .cajero-navbar h1 {
            font-size: 1.8em;
            font-weight: 600;
        }
        
        .navbar-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .navbar-actions span {
            font-weight: 500;
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
            max-width: 1600px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid #4caf50;
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 10px;
            font-weight: 500;
        }
        
        .stat-card .number {
            font-size: 2em;
            font-weight: bold;
            color: #2e7d32;
        }
        
        .stat-card.efectivo {
            border-left-color: #4caf50;
        }
        
        .stat-card.efectivo .number {
            color: #4caf50;
        }
        
        .stat-card.pendiente {
            border-left-color: #ff9800;
        }
        
        .stat-card.pendiente .number {
            color: #ff9800;
        }
        
        /* Section */
        .section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .section h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #4caf50;
        }
        
        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table thead {
            background: #f5f5f5;
        }
        
        table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #666;
            border-bottom: 2px solid #e0e0e0;
        }
        
        table td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        table tbody tr:hover {
            background: #f9f9f9;
        }
        
        /* Buttons */
        .btn {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 0.9em;
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 0.85em;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #2196f3 0%, #1976d2 100%);
            color: white;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #4caf50 0%, #388e3c 100%);
            color: white;
        }
        
        .btn-info {
            background: linear-gradient(135deg, #00bcd4 0%, #0097a7 100%);
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
        
        .badge-success {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .badge-warning {
            background: #fff3e0;
            color: #e65100;
        }
        
        .badge-pendiente {
            background: #fff3e0;
            color: #f57c00;
        }
        
        .badge-confirmado {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .badge-preparando {
            background: #fff9c4;
            color: #f57f17;
        }
        
        .badge-listo {
            background: #e8f5e9;
            color: #388e3c;
        }
        
        .badge-entregado {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        
        .badge-en_camino {
            background: #e1f5fe;
            color: #0277bd;
        }
        
        /* Messages */
        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .message-success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #4caf50;
        }
        
        .message-error {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #f44336;
        }
        
        /* Empty State */
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
    <link rel="stylesheet" href="css/auto_refresh.css">
</head>
<body>
    <!-- Navbar -->
    <div class="cajero-navbar">
        <h1>💰 Panel de Caja</h1>
        <div class="navbar-actions">
            <span>👤 <?php echo htmlspecialchars($cajero_nombre); ?></span>
            <a href="reportes.php">📊 Reportes</a>
            <a href="cierre_caja.php">📊 Cierre de Caja</a>
            <a href="index.php" target="_blank">👁️ Ver Menú</a>
            <a href="logout.php">🚪 Salir</a>
        </div>
    </div>

    <!-- Indicador de actualización -->
    <div id="refresh-indicator" class="refresh-indicator"></div>
    
    <!-- Controles de auto-refresh -->
    <div class="auto-refresh-controls">
        <button id="btn-toggle-refresh" class="btn-auto-refresh active" onclick="toggleAutoRefresh()">
            <span id="refresh-icon">▶️</span>
            <span id="refresh-text">Auto-actualización activa</span>
        </button>
        <button id="btn-toggle-sound" class="btn-sound-toggle" onclick="toggleSound()" title="Activar/Desactivar sonido">
            🔔
        </button>
    </div>

    <div class="container">
        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>💵 Pagos Hoy</h3>
                <div class="number"><?php echo $stats['pagos_hoy']; ?></div>
            </div>
            <div class="stat-card efectivo">
                <h3>💰 Total Cobrado</h3>
                <div class="number">$<?php echo number_format($stats['total_cobrado'], 0, ',', '.'); ?></div>
            </div>
            <div class="stat-card pendiente">
                <h3>⏳ Pendientes de Pago</h3>
                <div class="number"><?php echo $stats['pendientes_pago']; ?></div>
            </div>
            <div class="stat-card efectivo">
                <h3>💵 Efectivo Hoy</h3>
                <div class="number">$<?php echo number_format($stats['efectivo_hoy'], 0, ',', '.'); ?></div>
            </div>
        </div>

        <?php if(isset($_GET['success'])): ?>
        <div class="message message-success">
            <strong>✅ ¡Éxito!</strong> <?php echo htmlspecialchars($_GET['success']); ?>
        </div>
        <?php endif; ?>

        <?php if(isset($_GET['error'])): ?>
        <div class="message message-error">
            <strong>❌ Error:</strong> <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
        <?php endif; ?>

        <!-- Pedidos Pendientes de Pago -->
        <div class="section">
            <h2>💳 Pedidos Pendientes de Pago</h2>
            
            <table id="tabla-pedidos">
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>Tipo</th>
                        <th>Cliente</th>
                        <th>Mesero/Domiciliario</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Pago</th>
                        <th>Hora</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="tab-pedidos">
                    <?php
                    $sql = "SELECT p.*, 
                                   m.numero_mesa,
                                   u.nombre as mesero_nombre,
                                   d.nombre as domiciliario_nombre
                            FROM pedidos p 
                            LEFT JOIN mesas m ON p.mesa_id = m.id 
                            LEFT JOIN usuarios u ON p.usuario_id = u.id
                            LEFT JOIN usuarios d ON p.domiciliario_id = d.id
                            WHERE p.pagado = 0 
                            AND p.estado IN ('pendiente', 'confirmado', 'preparando', 'listo', 'entregado', 'en_camino')
                            ORDER BY p.fecha_pedido DESC";
                    
                    $result = $conn->query($sql);
                    
                    if ($result->num_rows > 0) {
                        while($pedido = $result->fetch_assoc()) {
                            $estado_class = 'badge-' . strtolower($pedido['estado']);
                            
                            // Lógica para tipo de pedido
                            if ($pedido['origen'] === 'chatbot') {
                                $tipo = '🤖 Chatbot';
                            } elseif ($pedido['tipo_pedido'] == 'domicilio') {
                                $tipo = '🏍️ Domicilio';
                            } else {
                                $tipo = '🪑 Mesa ' . $pedido['numero_mesa'];
                            }
                            
                            $responsable = $pedido['tipo_pedido'] == 'domicilio' ? $pedido['domiciliario_nombre'] : $pedido['mesero_nombre'];
                            
                            if ($pedido['origen'] === 'chatbot') {
                                $responsable = '🤖 IA';
                            }
                            
                            echo "<tr>";
                            echo "<td><strong>" . htmlspecialchars($pedido['numero_pedido']) . "</strong></td>";
                            echo "<td>" . $tipo . "</td>";
                            echo "<td>" . htmlspecialchars($pedido['nombre_cliente']) . "</td>";
                            echo "<td>" . htmlspecialchars($responsable ?: 'N/A') . "</td>";
                            echo "<td><strong>$" . number_format($pedido['total'], 0, ',', '.') . "</strong></td>";
                            echo "<td><span class='badge " . $estado_class . "'>" . ucfirst($pedido['estado']) . "</span></td>";
                            echo "<td><span class='badge badge-warning'>Pendiente</span></td>";
                            echo "<td>" . date('H:i', strtotime($pedido['fecha_pedido'])) . "</td>";
                            echo "<td>";
                            echo "<a href='ver_pedido.php?id=" . $pedido['id'] . "' class='btn btn-small btn-primary'>👁️ Ver</a> ";
                            echo "<a href='registrar_pago.php?pedido_id=" . $pedido['id'] . "' class='btn btn-small btn-success'>💰 Cobrar</a> ";
                            echo "<a href='ver_factura.php?id=" . $pedido['id'] . "' target='_blank' class='btn btn-small btn-info'>📄 Factura</a>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='9' class='empty-state'>";
                        echo "<div class='emoji'>🎉</div>";
                        echo "<h3>¡Todo al día!</h3>";
                        echo "<p>No hay pedidos pendientes de pago</p>";
                        echo "</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
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
    
    <!-- Auto-Refresh System -->
    <script src="js/auto_refresh.js"></script>
    <script>
        let pedidosRefresh;
        let lastPedidosCount = 0;
        
        // Función para renderizar pedidos del cajero
        function renderPedidosCajero(data) {
            if (!data.pedidos || data.pedidos.length === 0) {
                return `<tr><td colspan="9" class="empty-state">
                    <div class="emoji">🎉</div>
                    <h3>¡Todo al día!</h3>
                    <p>No hay pedidos pendientes de pago</p>
                </td></tr>`;
            }
            
            let html = '';
            data.pedidos.forEach(pedido => {
                const estadoClass = `badge-${pedido.estado}`;
                
                let tipo = '';
                if (pedido.origen === 'chatbot') {
                    tipo = '🤖 Chatbot';
                } else {
                    tipo = pedido.tipo_pedido === 'domicilio' ? '🏍️ Domicilio' : `🪑 Mesa ${pedido.mesa}`;
                }
                
                let responsable = pedido.tipo_pedido === 'domicilio' ? pedido.domiciliario : pedido.mesero;
                if (pedido.origen === 'chatbot') responsable = '🤖 IA';
                
                html += `<tr>
                    <td><strong>${pedido.numero_pedido}</strong></td>
                    <td>${tipo}</td>
                    <td>${pedido.nombre_cliente}</td>
                    <td>${responsable}</td>
                    <td><strong>$${formatNumber(pedido.total)}</strong></td>
                    <td><span class="badge ${estadoClass}">${capitalize(pedido.estado)}</span></td>
                    <td><span class="badge badge-warning">Pendiente</span></td>
                    <td>${pedido.hora}</td>
                    <td>
                        <a href="ver_pedido.php?id=${pedido.id}" class="btn btn-small btn-primary">👁️ Ver</a>
                        <a href="registrar_pago.php?pedido_id=${pedido.id}" class="btn btn-small btn-success">💰 Cobrar</a>
                        <a href="ver_factura.php?id=${pedido.id}" target="_blank" class="btn btn-small btn-info">📄 Factura</a>
                    </td>
                </tr>`;
            });
            
            return html;
        }
        
        function formatNumber(num) {
            return Math.round(num).toLocaleString('es-CO');
        }
        
        function capitalize(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }
        
        // Inicializar auto-refresh
        document.addEventListener('DOMContentLoaded', function() {
            pedidosRefresh = new AutoRefresh({
                endpoint: 'api/get_pedidos_cajero.php',
                targetElement: '#tab-pedidos',
                interval: 10000, // 10 segundos
                renderFunction: renderPedidosCajero,
                onNewItems: function(newItems) {
                    ToastNotification.show(
                        `💰 ${newItems.length} nuevo(s) pedido(s) por cobrar`,
                        'new',
                        4000
                    );
                    NotificationSound.play('new_order');
                },
                onUpdate: function(data) {
                    if (data.total_pedidos !== lastPedidosCount) {
                        lastPedidosCount = data.total_pedidos;
                    }
                }
            });
            
            pedidosRefresh.start();
            console.log('✅ Auto-refresh iniciado para panel de cajero');
        });
        
        // Funciones de control
        function toggleAutoRefresh() {
            if (pedidosRefresh.isPaused) {
                pedidosRefresh.resume();
                document.getElementById('btn-toggle-refresh').classList.add('active');
                document.getElementById('btn-toggle-refresh').classList.remove('paused');
                document.getElementById('refresh-icon').textContent = '▶️';
                document.getElementById('refresh-text').textContent = 'Auto-actualización activa';
            } else {
                pedidosRefresh.pause();
                document.getElementById('btn-toggle-refresh').classList.remove('active');
                document.getElementById('btn-toggle-refresh').classList.add('paused');
                document.getElementById('refresh-icon').textContent = '⏸️';
                document.getElementById('refresh-text').textContent = 'Auto-actualización pausada';
            }
        }
        
        function toggleSound() {
            const enabled = NotificationSound.toggle();
            const btn = document.getElementById('btn-toggle-sound');
            if (enabled) {
                btn.classList.remove('muted');
                btn.textContent = '🔔';
                ToastNotification.show('Sonido activado', 'success', 2000);
            } else {
                btn.classList.add('muted');
                btn.textContent = '🔕';
                ToastNotification.show('Sonido desactivado', 'info', 2000);
            }
        }
        
        // Inicializar estado del botón de sonido
        if (!NotificationSound.isEnabled()) {
            document.getElementById('btn-toggle-sound').classList.add('muted');
            document.getElementById('btn-toggle-sound').textContent = '🔕';
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
