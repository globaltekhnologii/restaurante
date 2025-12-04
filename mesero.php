<?php
session_start();

// Verificar sesión y rol de mesero
require_once 'auth_helper.php';
verificarSesion();
verificarRolORedirect(['mesero'], 'login.php');

require_once 'config.php';
$conn = getDatabaseConnection();

// Obtener información del mesero
$mesero_id = $_SESSION['user_id'];
$mesero_nombre = $_SESSION['nombre'];

// Obtener estadísticas del mesero
$stats = [];

// Pedidos del día del mesero
$hoy = date('Y-m-d');
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM pedidos WHERE usuario_id = ? AND DATE(fecha_pedido) = ?");
$stmt->bind_param("is", $mesero_id, $hoy);
$stmt->execute();
$stats['pedidos_hoy'] = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// Mesas ocupadas asignadas al mesero
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM mesas WHERE mesero_asignado = ? AND estado = 'ocupada'");
$stmt->bind_param("i", $mesero_id);
$stmt->execute();
$stats['mesas_ocupadas'] = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// Total de mesas
$stats['total_mesas'] = $conn->query("SELECT COUNT(*) as count FROM mesas")->fetch_assoc()['count'];

// Mesas disponibles
$stats['mesas_disponibles'] = $conn->query("SELECT COUNT(*) as count FROM mesas WHERE estado = 'disponible'")->fetch_assoc()['count'];

// Pedidos activos del mesero
$stats['pedidos_activos'] = $conn->query("SELECT COUNT(*) as count FROM pedidos WHERE usuario_id = $mesero_id AND estado IN ('pendiente', 'confirmado', 'preparando')")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Mesero - Restaurante El Sabor</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        
        /* Navbar - Color verde para mesero */
        .mesero-navbar {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
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
        
        .mesero-navbar h1 { 
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
            color: #48bb78;
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
            border-bottom: 3px solid #48bb78;
        }
        
        /* Mesas Grid */
        .mesas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .mesa-card {
            background: white;
            border: 3px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .mesa-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .mesa-card.disponible {
            border-color: #48bb78;
            background: #f0fff4;
        }
        
        .mesa-card.disponible:hover {
            background: #e6ffed;
        }
        
        .mesa-card.ocupada {
            border-color: #ed8936;
            background: #fffaf0;
        }
        
        .mesa-card.reservada {
            border-color: #4299e1;
            background: #ebf8ff;
        }
        
        .mesa-numero {
            font-size: 2em;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .mesa-estado {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .mesa-estado.disponible {
            background: #48bb78;
            color: white;
        }
        
        .mesa-estado.ocupada {
            background: #ed8936;
            color: white;
        }
        
        .mesa-estado.reservada {
            background: #4299e1;
            color: white;
        }
        
        .mesa-info {
            font-size: 0.9em;
            color: #666;
        }
        
        /* Buttons */
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(72,187,120,0.4);
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 0.9em;
        }
        
        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        thead {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
        }
        
        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85em;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        /* Badges */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75em;
            font-weight: bold;
        }
        
        .badge-pendiente { background: #ffd93d; color: #333; }
        .badge-confirmado { background: #4299e1; color: white; }
        .badge-preparando { background: #ed8936; color: white; }
        .badge-listo { background: #48bb78; color: white; }
        
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
        
        /* Tabs */
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .tab {
            padding: 12px 24px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            color: #666;
            transition: all 0.3s;
        }
        
        .tab.active {
            color: #48bb78;
            border-bottom-color: #48bb78;
        }
        
        .tab:hover {
            color: #48bb78;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <div class="mesero-navbar">
        <h1>🍽️ Panel de Mesero</h1>
        <div class="navbar-actions">
            <span>👤 <?php echo htmlspecialchars($mesero_nombre); ?></span>
            <a href="index.php" target="_blank">👁️ Ver Menú</a>
            <a href="logout.php">🚪 Salir</a>
        </div>
    </div>

    <div class="container">
        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>📋 Pedidos Hoy</h3>
                <div class="number"><?php echo $stats['pedidos_hoy']; ?></div>
            </div>
            <div class="stat-card">
                <h3>🪑 Mis Mesas Ocupadas</h3>
                <div class="number"><?php echo $stats['mesas_ocupadas']; ?></div>
            </div>
            <div class="stat-card">
                <h3>✅ Mesas Disponibles</h3>
                <div class="number"><?php echo $stats['mesas_disponibles']; ?></div>
            </div>
            <div class="stat-card">
                <h3>🔥 Pedidos Activos</h3>
                <div class="number"><?php echo $stats['pedidos_activos']; ?></div>
            </div>
            <div class="stat-card">
                <h3>🏠 Total Mesas</h3>
                <div class="number"><?php echo $stats['total_mesas']; ?></div>
            </div>
        </div>

        <!-- Mensajes -->
        <?php if(isset($_GET['success'])): ?>
        <div class="message message-success">
            <strong>✅ ¡Éxito!</strong> <?php echo htmlspecialchars($_GET['success']); ?>
        </div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab active" onclick="cambiarTab('mesas')">🪑 Mesas</button>
            <button class="tab" onclick="cambiarTab('pedidos')">📋 Mis Pedidos Activos</button>
            <button class="tab" onclick="cambiarTab('nuevo')">➕ Nuevo Pedido</button>
        </div>

        <!-- Tab: Mesas -->
        <div id="tab-mesas" class="tab-content active">
            <div class="section">
                <h2>🪑 Estado de las Mesas</h2>
                
                <div class="mesas-grid">
                    <?php
                    $sql = "SELECT m.*, p.numero_pedido, p.total, u.nombre as mesero_nombre 
                            FROM mesas m 
                            LEFT JOIN pedidos p ON m.pedido_actual = p.id 
                            LEFT JOIN usuarios u ON m.mesero_asignado = u.id 
                            ORDER BY m.numero_mesa";
                    $result = $conn->query($sql);
                    
                    while($mesa = $result->fetch_assoc()) {
                        $estado_class = $mesa['estado'];
                        $es_mi_mesa = ($mesa['mesero_asignado'] == $mesero_id);
                        
                        echo '<div class="mesa-card ' . $estado_class . '">';
                        echo '<div class="mesa-numero">' . htmlspecialchars($mesa['numero_mesa']) . '</div>';
                        echo '<div class="mesa-estado ' . $estado_class . '">' . ucfirst($mesa['estado']) . '</div>';
                        echo '<div class="mesa-info">';
                        echo '👥 Capacidad: ' . $mesa['capacidad'] . '<br>';
                        
                        if ($mesa['estado'] === 'ocupada') {
                            echo '🧾 Pedido: ' . htmlspecialchars($mesa['numero_pedido']) . '<br>';
                            echo '💰 Total: $' . number_format($mesa['total'], 2) . '<br>';
                            if ($es_mi_mesa) {
                                echo '<span style="color: #48bb78; font-weight: bold;">✓ Tu mesa</span><br>';
                                echo '<a href="#" class="btn btn-small btn-primary" style="margin-top: 10px;" onclick="liberarMesa(' . $mesa['id'] . ')">🔓 Liberar Mesa</a>';
                            } else {
                                echo '👤 ' . htmlspecialchars($mesa['mesero_nombre']);
                            }
                        } else if ($mesa['estado'] === 'disponible') {
                            echo '<a href="#" class="btn btn-small btn-primary" style="margin-top: 10px;" onclick="ocuparMesa(' . $mesa['id'] . ')">📝 Tomar Pedido</a>';
                        }
                        
                        echo '</div>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Tab: Pedidos Activos -->
        <div id="tab-pedidos" class="tab-content">
            <div class="section">
                <h2>📋 Mis Pedidos Activos</h2>
                
                <?php
                $sql = "SELECT p.*, m.numero_mesa 
                        FROM pedidos p 
                        LEFT JOIN mesas m ON p.mesa_id = m.id 
                        WHERE p.usuario_id = ? AND p.estado IN ('pendiente', 'confirmado', 'preparando') 
                        ORDER BY p.fecha_pedido DESC";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $mesero_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    echo '<table>';
                    echo '<thead><tr>';
                    echo '<th>Número</th><th>Mesa</th><th>Cliente</th><th>Total</th><th>Estado</th><th>Hora</th><th>Acciones</th>';
                    echo '</tr></thead><tbody>';
                    
                    while($pedido = $result->fetch_assoc()) {
                        $badge_class = 'badge-' . strtolower($pedido['estado']);
                        echo '<tr>';
                        echo '<td><strong>' . htmlspecialchars($pedido['numero_pedido']) . '</strong></td>';
                        echo '<td>' . ($pedido['numero_mesa'] ? htmlspecialchars($pedido['numero_mesa']) : 'Domicilio') . '</td>';
                        echo '<td>' . htmlspecialchars($pedido['nombre_cliente']) . '</td>';
                        echo '<td><strong>$' . number_format($pedido['total'], 2) . '</strong></td>';
                        echo '<td><span class="badge ' . $badge_class . '">' . ucfirst($pedido['estado']) . '</span></td>';
                        echo '<td>' . date('H:i', strtotime($pedido['fecha_pedido'])) . '</td>';
                        echo '<td>';
                        echo '<a href="ver_pedido.php?id=' . $pedido['id'] . '" class="btn btn-small btn-primary">👁️ Ver</a>';
                        echo '</td>';
                        echo '</tr>';
                    }
                    
                    echo '</tbody></table>';
                } else {
                    echo '<p style="text-align: center; color: #999; padding: 40px;">No tienes pedidos activos en este momento.</p>';
                }
                $stmt->close();
                ?>
            </div>
        </div>

        <!-- Tab: Nuevo Pedido -->
        <div id="tab-nuevo" class="tab-content">
            <div class="section">
                <h2>➕ Tomar Nuevo Pedido</h2>
                <p style="text-align: center; color: #666; padding: 40px;">
                    Selecciona una mesa disponible en la pestaña "Mesas" para comenzar un nuevo pedido.
                </p>
            </div>
        </div>
    </div>

    <script>
        function cambiarTab(tabName) {
            // Ocultar todos los tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Mostrar el tab seleccionado
            document.getElementById('tab-' + tabName).classList.add('active');
            event.target.classList.add('active');
        }
        
        function ocuparMesa(mesaId) {
            if (confirm('¿Deseas tomar un pedido para esta mesa?')) {
                window.location.href = 'tomar_pedido_mesero.php?mesa_id=' + mesaId;
            }
        }
        
        function liberarMesa(mesaId) {
            if (confirm('¿Deseas liberar esta mesa? Asegúrate de que el pedido esté completado.')) {
                window.location.href = 'liberar_mesa.php?mesa_id=' + mesaId;
            }
        }
        
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
