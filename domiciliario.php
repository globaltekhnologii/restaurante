<?php
session_start();

// Verificar sesión y rol de domiciliario
require_once 'auth_helper.php';
verificarSesion();
verificarRolORedirect(['domiciliario'], 'login.php');

require_once 'config.php';
require_once 'includes/info_negocio.php';
$conn = getDatabaseConnection();

// Obtener información del domiciliario
$domiciliario_id = $_SESSION['user_id'];
$domiciliario_nombre = $_SESSION['nombre'];

// Obtener estadísticas
$stats = [];

// Entregas del día
$hoy = date('Y-m-d');
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM pedidos WHERE domiciliario_id = ? AND DATE(fecha_pedido) = ?");
$stmt->bind_param("is", $domiciliario_id, $hoy);
$stmt->execute();
$stats['entregas_hoy'] = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// Entregas completadas hoy
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM pedidos WHERE domiciliario_id = ? AND DATE(fecha_pedido) = ? AND estado = 'entregado'");
$stmt->bind_param("is", $domiciliario_id, $hoy);
$stmt->execute();
$stats['completadas_hoy'] = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// Entregas pendientes (asignadas pero no entregadas)
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM pedidos WHERE domiciliario_id = ? AND estado IN ('listo', 'en_camino')");
$stmt->bind_param("i", $domiciliario_id);
$stmt->execute();
$stats['pendientes'] = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// Entregas en camino
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM pedidos WHERE domiciliario_id = ? AND estado = 'en_camino'");
$stmt->bind_param("i", $domiciliario_id);
$stmt->execute();
$stats['en_camino'] = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// Pedidos listos para recoger (sin asignar domiciliario)
$stats['listos'] = $conn->query("SELECT COUNT(*) as count FROM pedidos WHERE domiciliario_id IS NULL AND estado = 'listo' AND tipo_pedido = 'domicilio'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/themes.css">
    <link rel="stylesheet" href="css/animations.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/admin-modern.css">
    <title>Panel Domiciliario - <?php echo htmlspecialchars($info_negocio['nombre_restaurante']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        
        /* Navbar - Color azul cielo para domiciliario */
        .domiciliario-navbar {
            background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
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
        
        .domiciliario-navbar h1 { 
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
            color: #4299e1;
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
            border-bottom: 3px solid #4299e1;
        }
        
        /* Entregas Grid */
        .entregas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .entrega-card {
            background: white;
            border: 3px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        .entrega-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .entrega-card.en_camino {
            border-color: #4299e1;
            background: #ebf8ff;
        }
        
        .entrega-card.preparando {
            border-color: #ed8936;
            background: #fffaf0;
        }
        
        .entrega-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .entrega-numero {
            font-size: 1.2em;
            font-weight: bold;
        }
        
        .entrega-tiempo {
            font-size: 0.9em;
            color: #666;
        }
        
        .entrega-cliente {
            margin: 15px 0;
            padding: 15px;
            background: #f7fafc;
            border-radius: 8px;
        }
        
        .cliente-info {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .cliente-info div {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .entrega-footer {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #e0e0e0;
        }
        
        .entrega-total {
            font-size: 1.2em;
            font-weight: bold;
            color: #4299e1;
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
            background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
            color: white;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
        }
        
        /* Badges */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75em;
            font-weight: bold;
        }
        
        .badge-preparando { background: #ed8936; color: white; }
        .badge-en_camino { background: #4299e1; color: white; }
        .badge-entregado { background: #48bb78; color: white; }
        
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
            color: #4299e1;
            border-bottom-color: #4299e1;
        }
        
        .tab:hover {
            color: #4299e1;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
    </style>
    <link rel="stylesheet" href="css/auto_refresh.css">
</head>
<body>
    <!-- Navbar -->
    <div class="domiciliario-navbar">
        <h1>🏍️ Panel de Entregas
            <span id="badge-my_deliveries" class="badge" style="display:none;">0</span>
        </h1>
        <div class="navbar-actions">
            <span>👤 <?php echo htmlspecialchars($domiciliario_nombre); ?></span>
            <a href="index.php" target="_blank">👁️ Ver Menú</a>
            <div class="theme-switcher-container"></div>
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
                <h3>📦 Entregas Hoy</h3>
                <div class="number"><?php echo $stats['entregas_hoy']; ?></div>
            </div>
            <div class="stat-card">
                <h3>✅ Completadas Hoy</h3>
                <div class="number"><?php echo $stats['completadas_hoy']; ?></div>
            </div>
            <div class="stat-card">
                <h3>🔥 Pendientes</h3>
                <div class="number"><?php echo $stats['pendientes']; ?></div>
            </div>
            <div class="stat-card">
                <h3>🚗 En Camino</h3>
                <div class="number"><?php echo $stats['en_camino']; ?></div>
            </div>
            <div class="stat-card">
                <h3>📋 Listos para Recoger</h3>
                <div class="number"><?php echo $stats['listos']; ?></div>
            </div>
        </div>

        <!-- Botón Flotante para Activar GPS -->
        <div id="gps-activator" style="position: fixed; bottom: 80px; right: 20px; z-index: 1000;">
            <button onclick="if(navigator.geolocation){alert('🛰️ Buscando satélites GPS...\n\nEspera 30-60 segundos\nSal al aire libre para mejor señal');navigator.geolocation.getCurrentPosition(function(p){alert('✅ GPS Activado!\n\nPrecisión: ±'+p.coords.accuracy.toFixed(0)+'m\nLat:'+p.coords.latitude+'\nLng:'+p.coords.longitude);location.reload();},function(e){alert('❌ Error GPS ('+e.code+'): '+e.message+'\n\nAsegúrate de:\n• Estar al aire libre\n• GPS activado en el teléfono\n• Permisos dados al navegador');},{enableHighAccuracy:true,timeout:30000,maximumAge:0});}else{alert('❌ Tu navegador no soporta GPS');}" style="background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); color: white; border: none; padding: 15px 25px; border-radius: 50px; font-size: 1.1em; font-weight: 600; box-shadow: 0 4px 15px rgba(72, 187, 120, 0.4); cursor: pointer; display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 1.3em;">📍</span>
                <span>Activar GPS</span>
            </button>
        </div>

        <!-- Mensajes -->
        <?php if(isset($_GET['success'])): ?>
        <div class="message message-success">
            <strong>✅ ¡Éxito!</strong> <?php echo htmlspecialchars($_GET['success']); ?>
        </div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab active" onclick="cambiarTab('asignadas')">🏍️ Mis Entregas</button>
            <button class="tab" onclick="cambiarTab('disponibles')">📋 Disponibles</button>
            <button class="tab" onclick="cambiarTab('historial')">📜 Historial</button>
        </div>

        <!-- Tab: Mis Entregas -->
        <div id="tab-asignadas" class="tab-content active">
            <div class="section">
                <h2>🏍️ Mis Entregas Activas</h2>
                
                <div class="entregas-grid">
                    <?php
                    $sql = "SELECT p.* 
                            FROM pedidos p 
                            WHERE p.domiciliario_id = ? AND p.estado IN ('listo', 'en_camino') 
                            ORDER BY p.fecha_pedido ASC";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $domiciliario_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        while($entrega = $result->fetch_assoc()) {
                            $estado_class = strtolower($entrega['estado']);
                            $badge_class = 'badge-' . $estado_class;
                            
                            // Calcular tiempo
                            $tiempo_pedido = strtotime($entrega['fecha_pedido']);
                            $tiempo_actual = time();
                            $minutos = floor(($tiempo_actual - $tiempo_pedido) / 60);
                            
                            echo '<div class="entrega-card ' . $estado_class . '">';
                            echo '<div class="entrega-header">';
                            echo '<div class="entrega-numero">🧾 ' . htmlspecialchars($entrega['numero_pedido']) . '</div>';
                            echo '<div class="entrega-tiempo">⏱️ ' . $minutos . ' min</div>';
                            echo '</div>';
                            
                            echo '<div><span class="badge ' . $badge_class . '">' . ucfirst(str_replace('_', ' ', $entrega['estado'])) . '</span></div>';
                            
                            echo '<div class="entrega-cliente">';
                            echo '<div class="cliente-info">';
                            echo '<div><strong>👤 Cliente:</strong> ' . htmlspecialchars($entrega['nombre_cliente']) . '</div>';
                            echo '<div><strong>📞 Teléfono:</strong> ' . htmlspecialchars($entrega['telefono']) . '</div>';
                            echo '<div><strong>📍 Dirección:</strong> ' . htmlspecialchars($entrega['direccion']) . '</div>';
                            if ($entrega['notas']) {
                                echo '<div><strong>📝 Notas:</strong> ' . htmlspecialchars($entrega['notas']) . '</div>';
                            }
                            echo '</div>';
                            echo '</div>';
                            
                            echo '<div class="entrega-footer">';
                            echo '<div class="entrega-total">💰 Total: $' . number_format($entrega['total'], 2) . '</div>';
                            
                            if ($entrega['estado'] === 'listo') {
                                echo '<a href="salir_entrega.php?id=' . $entrega['id'] . '" class="btn btn-primary" style="margin-bottom: 5px;">🚗 Salir a Entregar</a>';
                            } else if ($entrega['estado'] === 'en_camino') {
                                echo '<a href="confirmar_entrega.php?id=' . $entrega['id'] . '" class="btn btn-success" style="margin-bottom: 5px;">✅ Confirmar Entrega</a>';
                            }
                            
                            echo '<a href="ver_factura.php?id=' . $entrega['id'] . '&print=true" target="_blank" class="btn" style="background: #2b6cb0; color: white; font-size: 0.9em;">📄 Imprimir Factura</a>';
                            
                            echo '</div>';
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="empty-state">';
                        echo '<div class="emoji">😌</div>';
                        echo '<h3>No tienes entregas asignadas</h3>';
                        echo '<p>Revisa la pestaña "Disponibles" para tomar nuevas entregas.</p>';
                        echo '</div>';
                    }
                    $stmt->close();
                    ?>
                </div>
            </div>
        </div>

        <!-- Tab: Disponibles -->
        <div id="tab-disponibles" class="tab-content">
            <div class="section">
                <h2>📋 Pedidos Listos para Recoger</h2>
                
                <div class="entregas-grid">
                    <?php
                    $sql = "SELECT p.* 
                            FROM pedidos p 
                            WHERE p.domiciliario_id IS NULL AND p.estado = 'listo' 
                            AND p.tipo_pedido = 'domicilio'
                            ORDER BY p.fecha_pedido ASC";
                    $result = $conn->query($sql);
                    
                    if ($result->num_rows > 0) {
                        while($pedido = $result->fetch_assoc()) {
                            echo '<div class="entrega-card">';
                            echo '<div class="entrega-header">';
                            echo '<div class="entrega-numero">🧾 ' . htmlspecialchars($pedido['numero_pedido']) . '</div>';
                            echo '</div>';
                            
                            echo '<div class="entrega-cliente">';
                            echo '<div class="cliente-info">';
                            echo '<div><strong>👤 Cliente:</strong> ' . htmlspecialchars($pedido['nombre_cliente']) . '</div>';
                            echo '<div><strong>📞 Teléfono:</strong> ' . htmlspecialchars($pedido['telefono']) . '</div>';
                            echo '<div><strong>📍 Dirección:</strong> ' . htmlspecialchars($pedido['direccion']) . '</div>';
                            echo '</div>';
                            echo '</div>';
                            
                            echo '<div class="entrega-footer">';
                            echo '<div class="entrega-total">💰 Total: $' . number_format($pedido['total'], 2) . '</div>';
                            echo '<a href="tomar_entrega.php?id=' . $pedido['id'] . '" class="btn btn-warning">📦 Tomar Entrega</a>';
                            echo '</div>';
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="empty-state">';
                        echo '<div class="emoji">📭</div>';
                        echo '<h3>No hay pedidos disponibles</h3>';
                        echo '<p>Todos los pedidos están asignados o no hay entregas pendientes.</p>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Tab: Historial -->
        <div id="tab-historial" class="tab-content">
            <div class="section">
                <h2>📜 Historial de Entregas</h2>
                
                <?php
                $sql = "SELECT p.* 
                        FROM pedidos p 
                        WHERE p.domiciliario_id = ? AND p.estado = 'entregado' 
                        ORDER BY p.hora_entrega DESC 
                        LIMIT 20";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $domiciliario_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    echo '<table style="width: 100%; border-collapse: collapse; margin-top: 20px;">';
                    echo '<thead style="background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%); color: white;">';
                    echo '<tr>';
                    echo '<th style="padding: 15px; text-align: left;">Pedido</th>';
                    echo '<th style="padding: 15px; text-align: left;">Cliente</th>';
                    echo '<th style="padding: 15px; text-align: left;">Dirección</th>';
                    echo '<th style="padding: 15px; text-align: left;">Total</th>';
                    echo '<th style="padding: 15px; text-align: left;">Entregado</th>';
                    echo '</tr>';
                    echo '</thead><tbody>';
                    
                    while($entrega = $result->fetch_assoc()) {
                        echo '<tr style="border-bottom: 1px solid #e0e0e0;">';
                        echo '<td style="padding: 15px;"><strong>' . htmlspecialchars($entrega['numero_pedido']) . '</strong></td>';
                        echo '<td style="padding: 15px;">' . htmlspecialchars($entrega['nombre_cliente']) . '</td>';
                        echo '<td style="padding: 15px;">' . htmlspecialchars($entrega['direccion']) . '</td>';
                        echo '<td style="padding: 15px;"><strong>$' . number_format($entrega['total'], 2) . '</strong></td>';
                        echo '<td style="padding: 15px;">' . ($entrega['hora_entrega'] ? date('d/m/Y H:i', strtotime($entrega['hora_entrega'])) : '-') . '</td>';
                        echo '</tr>';
                    }
                    
                    echo '</tbody></table>';
                } else {
                    echo '<div class="empty-state">';
                    echo '<div class="emoji">📭</div>';
                    echo '<h3>No hay entregas en el historial</h3>';
                    echo '<p>Tus entregas completadas aparecerán aquí.</p>';
                    echo '</div>';
                }
                $stmt->close();
                ?>
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
    
    <!-- Sistema de notificaciones -->
    <script src="js/notifications.js"></script>
    <script>
        const notificationConfig = {
            apiUrl: 'api/check_updates.php',
            pollInterval: 5000,
            soundEnabled: true
        };
    </script>
    
    <!-- Auto-Refresh System -->
    <script src="js/auto_refresh.js"></script>
    <script>
        let pedidosRefresh;
        let lastPedidosCount = 0;
        
        // Función para renderizar pedidos del domiciliario
        function renderPedidosDomicilio(data) {
            if (!data.pedidos || data.pedidos.length === 0) {
                return `<div class="empty-state">
                    <div class="emoji">🏍️</div>
                    <h3>No hay pedidos para entregar</h3>
                    <p>Todos los pedidos han sido entregados</p>
                </div>`;
            }
            
            let html = '';
            data.pedidos.forEach(pedido => {
                const estadoClass = pedido.estado;
                const badgeClass = `badge-${estadoClass}`;
                const esMio = pedido.es_mio;
                
                html += `<div class="entrega-card ${estadoClass} ${esMio ? 'mi-pedido' : ''}">
                    <div class="entrega-header">
                        <div>
                            <div class="entrega-numero">${pedido.numero_pedido}</div>
                            <div class="entrega-cliente">👤 ${pedido.nombre_cliente}</div>
                        </div>
                        <span class="badge ${badgeClass}">${capitalize(pedido.estado)}</span>
                    </div>
                    
                    <div class="cliente-info">
                        <div>📞 ${pedido.telefono}</div>
                        <div>📍 ${pedido.direccion}</div>
                        <div>💵 Total: $${formatNumber(pedido.total)}</div>
                        <div>⏰ ${pedido.hora}</div>
                    </div>`;
                
                html += `<div class="entrega-actions">`;
                
                if (pedido.estado === 'listo' && !esMio) {
                    html += `<a href="tomar_pedido.php?pedido_id=${pedido.id}" class="btn btn-primary">
                        🏍️ Tomar Pedido
                    </a>`;
                } else if (pedido.estado === 'en_camino' && esMio) {
                    html += `<a href="cambiar_estado.php?pedido_id=${pedido.id}&nuevo_estado=entregado" class="btn btn-success">
                        ✅ Marcar como Entregado
                    </a>`;
                }
                
                html += `<a href="ver_pedido.php?id=${pedido.id}" class="btn btn-secondary">
                    👁️ Ver Detalles
                </a></div></div>`;
            });
            
            return html;
        }
        
        function capitalize(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }
        
        function formatNumber(num) {
            return Math.round(num).toLocaleString('es-CO');
        }
        
        // Inicializar auto-refresh
        document.addEventListener('DOMContentLoaded', function() {
            pedidosRefresh = new AutoRefresh({
                endpoint: 'api/get_pedidos_domiciliario.php',
                targetElement: '.entregas-grid',
                interval: 8000, // 8 segundos
                renderFunction: renderPedidosDomicilio,
                onNewItems: function(newItems) {
                    ToastNotification.show(
                        `🏍️ ${newItems.length} nuevo(s) pedido(s) para entregar`,
                        'new',
                        5000
                    );
                    NotificationSound.play('new_order');
                },
                onUpdate: function(data) {
                    if (data.total !== lastPedidosCount) {
                        lastPedidosCount = data.total;
                    }
                }
            });
            
            pedidosRefresh.start();
            console.log('✅ Auto-refresh iniciado para panel de domiciliario');
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
    
    <!-- Theme Manager -->
    <script src="js/theme-manager.js"></script>

    <!-- GEOLOCALIZACIÓN EN TIEMPO REAL -->
    <script>
        // Verificar si hay entregas "en camino" asignadas a este domiciliario
        // Esta lógica se podría mejorar trayendo una flag desde PHP, pero lo haremos verificando el DOM por simplicidad
        // o mejor, siempre intentar trackear si estamos en el panel, y el servidor decide si guardar o no 
        // (pero para ahorrar batería, mejor solo si hay entregas activas).
        
        let trackingInterval;
        let watchId;
        const DO_TRACKING = <?php echo ($stats['en_camino'] > 0) ? 'true' : 'false'; ?>;
        
        // Función para solicitar permiso GPS manualmente
        function solicitarPermisoGPS() {
            if (!navigator.geolocation) {
                alert("❌ Tu navegador no soporta geolocalización.\n\nUsa Chrome, Firefox o Safari actualizado.");
                return;
            }
            
            // Ocultar botón
            const btn = document.getElementById('gps-activator');
            if (btn) btn.style.display = 'none';
            
            // Solicitar ubicación una vez para forzar el permiso
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    alert("✅ ¡GPS Activado!\n\nAhora tu ubicación se compartirá automáticamente.");
                    // Iniciar tracking automático
                    if (DO_TRACKING && !watchId) {
                        iniciarTracking();
                    }
                },
                function(error) {
                    // Mostrar botón de nuevo
                    if (btn) btn.style.display = 'block';
                    
                    let mensaje = "";
                    switch(error.code) {
                        case 1:
                            mensaje = "⚠️ PERMISO DENEGADO\n\nPara activar GPS:\n\n1. Toca el candado 🔒 junto a la URL\n2. Busca 'Ubicación'\n3. Selecciona 'Permitir'\n4. Toca este botón de nuevo";
                            break;
                        case 2:
                            mensaje = "⚠️ No se puede obtener tu ubicación\n\nVerifica que:\n• GPS esté activado en tu teléfono\n• Tengas buena señal\n• Estés al aire libre o cerca de una ventana";
                            break;
                        case 3:
                            mensaje = "⏱️ Tiempo agotado\n\nIntenta de nuevo en un momento.";
                            break;
                    }
                    alert(mensaje);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        }
        
        if (DO_TRACKING) {
            iniciarTracking();
        }

        function iniciarTracking() {
            if (!navigator.geolocation) {
                console.error("Geolocalización no soportada por este navegador.");
                alert("❌ Tu navegador no soporta geolocalización. Usa Chrome, Firefox o Safari.");
                return;
            }

            console.log("📍 Iniciando rastreo de ubicación...");
            
            // Mostrar indicador visual
            const navbar = document.querySelector('.domiciliario-navbar');
            const trackingBadge = document.createElement('span');
            trackingBadge.innerHTML = '📡 Tracking Activo';
            trackingBadge.className = 'badge';
            trackingBadge.id = 'tracking-badge';
            trackingBadge.style.backgroundColor = '#48bb78';
            trackingBadge.style.marginLeft = '10px';
            trackingBadge.style.animation = 'pulse 2s infinite';
            navbar.querySelector('h1').appendChild(trackingBadge);

            // Solicitar permiso explícitamente primero
            navigator.permissions.query({name: 'geolocation'}).then(function(result) {
                console.log("Estado permiso GPS:", result.state);
                
                if (result.state === 'denied') {
                    alert("⚠️ PERMISO DENEGADO\n\nPara compartir tu ubicación:\n1. Ve a Configuración del navegador\n2. Busca 'Permisos' o 'Ubicación'\n3. Permite el acceso para este sitio\n4. Recarga la página");
                    document.getElementById('tracking-badge').style.backgroundColor = '#f44336';
                    document.getElementById('tracking-badge').innerHTML = '❌ GPS Bloqueado';
                    return;
                }
            }).catch(err => {
                console.log("Permissions API no disponible, intentando directamente");
            });

            // Configuración optimizada para GPS real del teléfono
            const gpsOptions = {
                enableHighAccuracy: true,  // Fuerza GPS en lugar de WiFi/IP
                timeout: 30000,             // 30 segundos para obtener señal GPS
                maximumAge: 0               // No usar caché, siempre pedir ubicación fresca
            };
            
            // Opción 1: watchPosition (tracking continuo)
            watchId = navigator.geolocation.watchPosition(
                enviarPosicion, 
                errorPosicion, 
                gpsOptions
            );
            
            console.log("🛰️ Esperando señal GPS del satélite... (puede tardar 30-60 seg)");
        }

        function enviarPosicion(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            const accuracy = position.coords.accuracy;
            
            console.log(`📍 Ubicación: ${lat}, ${lng} (precisión: ${accuracy.toFixed(0)}m)`);
            
            // Actualizar badge con coordenadas
            const badge = document.getElementById('tracking-badge');
            if (badge) {
                badge.innerHTML = `📡 GPS Activo (±${accuracy.toFixed(0)}m)`;
                badge.style.backgroundColor = accuracy < 50 ? '#48bb78' : '#ed8936';
            }

            fetch('api/actualizar_ubicacion.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    latitud: lat,
                    longitud: lng
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log("✅ Ubicación actualizada en servidor");
                } else {
                    console.warn("⚠️ Error actualizando servidor:", data.error);
                }
            })
            .catch(error => console.error("Error red:", error));
        }

        function errorPosicion(err) {
            console.warn(`ERROR GPS (${err.code}): ${err.message}`);
            
            const badge = document.getElementById('tracking-badge');
            if (badge) {
                badge.style.backgroundColor = '#f44336';
                badge.innerHTML = '❌ Error GPS';
            }
            
            let mensaje = "";
            switch(err.code) {
                case 1: // PERMISSION_DENIED
                    mensaje = "⚠️ PERMISO DENEGADO\n\nDebes permitir el acceso a tu ubicación:\n1. Toca el ícono 🔒 o ⓘ en la barra de direcciones\n2. Activa 'Ubicación'\n3. Recarga la página";
                    break;
                case 2: // POSITION_UNAVAILABLE
                    mensaje = "⚠️ No se puede obtener tu ubicación.\n\nAsegúrate de:\n- Tener GPS activado\n- Estar en un lugar con buena señal\n- Dar permiso al navegador";
                    break;
                case 3: // TIMEOUT
                    mensaje = "⏱️ Tiempo agotado. Reintentando...";
                    break;
            }
            
            if (err.code === 1) {
                alert(mensaje);
            } else {
                console.log(mensaje);
            }
        }
        }
        
        // Estilo para pulse
        const style = document.createElement('style');
        style.innerHTML = `
            @keyframes pulse {
                0% { opacity: 1; transform: scale(1); }
                50% { opacity: 0.7; transform: scale(1.05); }
                100% { opacity: 1; transform: scale(1); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
<?php $conn->close(); ?>
