<?php
session_start();

// Verificar si está logueado
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Conexión a BD
$conn = new mysqli("localhost", "root", "", "menu_restaurante");
$tenant_id = $_SESSION['tenant_id'];

// Obtener estadísticas
$stats = [];

// Total conversaciones
$result = $conn->query("SELECT COUNT(*) as total FROM saas_conversations WHERE tenant_id = $tenant_id");
$stats['conversaciones'] = $result->fetch_assoc()['total'];

// Mensajes hoy
$result = $conn->query("SELECT COUNT(*) as total FROM saas_messages m 
    JOIN saas_conversations c ON c.id = m.conversation_id 
    WHERE c.tenant_id = $tenant_id AND DATE(m.created_at) = CURDATE()");
$stats['mensajes_hoy'] = $result->fetch_assoc()['total'];

// Items en menú
$result = $conn->query("SELECT COUNT(*) as total FROM saas_menu_items WHERE tenant_id = $tenant_id");
$stats['menu_items'] = $result->fetch_assoc()['total'];

// Items disponibles
$result = $conn->query("SELECT COUNT(*) as total FROM saas_menu_items WHERE tenant_id = $tenant_id AND available = TRUE");
$stats['items_disponibles'] = $result->fetch_assoc()['total'];

// Obtener configuración
$result = $conn->query("SELECT * FROM saas_chatbot_config WHERE tenant_id = $tenant_id");
$config = $result->fetch_assoc();

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo htmlspecialchars($_SESSION['restaurant_name']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f9fafb;
        }
        
        /* Header */
        .header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .logo {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #f97316, #ea580c);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .header-title h1 {
            font-size: 18px;
            color: #1f2937;
        }
        
        .header-title p {
            font-size: 13px;
            color: #6b7280;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .user-info {
            text-align: right;
        }
        
        .user-info strong {
            display: block;
            font-size: 14px;
            color: #1f2937;
        }
        
        .user-info span {
            font-size: 12px;
            color: #6b7280;
        }
        
        .logout-btn {
            padding: 8px 16px;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        
        .logout-btn:hover {
            background: #dc2626;
        }
        
        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 24px;
        }
        
        .page-title {
            font-size: 28px;
            color: #1f2937;
            margin-bottom: 24px;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        
        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .stat-card.primary {
            background: linear-gradient(135deg, #f97316, #ea580c);
            color: white;
        }
        
        .stat-card.success {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
        }
        
        .stat-card.info {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }
        
        .stat-card.warning {
            background: linear-gradient(135deg, #eab308, #ca8a04);
            color: white;
        }
        
        .stat-icon {
            font-size: 32px;
            margin-bottom: 12px;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        /* Quick Actions */
        .quick-actions {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 32px;
        }
        
        .quick-actions h2 {
            font-size: 20px;
            color: #1f2937;
            margin-bottom: 16px;
        }
        
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }
        
        .action-btn {
            padding: 16px;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            text-decoration: none;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s;
        }
        
        .action-btn:hover {
            background: #f97316;
            color: white;
            border-color: #f97316;
            transform: translateY(-2px);
        }
        
        .action-icon {
            font-size: 24px;
        }
        
        .action-text strong {
            display: block;
            font-size: 14px;
        }
        
        .action-text span {
            font-size: 12px;
            opacity: 0.7;
        }
        
        /* Bot Status */
        .bot-status {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .bot-status h2 {
            font-size: 20px;
            color: #1f2937;
            margin-bottom: 16px;
        }
        
        .status-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .status-item:last-child {
            border-bottom: none;
        }
        
        .status-label {
            color: #6b7280;
            font-size: 14px;
        }
        
        .status-value {
            color: #1f2937;
            font-weight: 600;
            font-size: 14px;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge.success {
            background: #dcfce7;
            color: #166534;
        }
        
        .badge.warning {
            background: #fef3c7;
            color: #92400e;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <div class="logo">🤖</div>
            <div class="header-title">
                <h1><?php echo htmlspecialchars($_SESSION['restaurant_name']); ?></h1>
                <p>Panel Administrativo</p>
            </div>
        </div>
        <div class="header-right">
            <div class="user-info">
                <strong><?php echo htmlspecialchars($_SESSION['owner_email']); ?></strong>
                <span>Administrador</span>
            </div>
            <a href="logout.php" class="logout-btn">Cerrar Sesión</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <h1 class="page-title">Dashboard</h1>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-icon">💬</div>
                <div class="stat-value"><?php echo $stats['conversaciones']; ?></div>
                <div class="stat-label">Conversaciones Totales</div>
            </div>

            <div class="stat-card success">
                <div class="stat-icon">📨</div>
                <div class="stat-value"><?php echo $stats['mensajes_hoy']; ?></div>
                <div class="stat-label">Mensajes Hoy</div>
            </div>

            <div class="stat-card info">
                <div class="stat-icon">🍕</div>
                <div class="stat-value"><?php echo $stats['menu_items']; ?></div>
                <div class="stat-label">Items en Menú</div>
            </div>

            <div class="stat-card warning">
                <div class="stat-icon">✅</div>
                <div class="stat-value"><?php echo $stats['items_disponibles']; ?></div>
                <div class="stat-label">Items Disponibles</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h2>Acciones Rápidas</h2>
            <div class="actions-grid">
                <a href="menu.php" class="action-btn">
                    <div class="action-icon">🍽️</div>
                    <div class="action-text">
                        <strong>Gestionar Menú</strong>
                        <span>Agregar o editar items</span>
                    </div>
                </a>

                <a href="configuracion.php" class="action-btn">
                    <div class="action-icon">⚙️</div>
                    <div class="action-text">
                        <strong>Configuración</strong>
                        <span>Personalizar chatbot</span>
                    </div>
                </a>

                <a href="conversaciones.php" class="action-btn">
                    <div class="action-icon">📊</div>
                    <div class="action-text">
                        <strong>Conversaciones</strong>
                        <span>Ver historial</span>
                    </div>
                </a>

                <a href="integracion.php" class="action-btn">
                    <div class="action-icon">🔗</div>
                    <div class="action-text">
                        <strong>Integración</strong>
                        <span>Código del widget</span>
                    </div>
                </a>
            </div>
        </div>

        <!-- Bot Status -->
        <div class="bot-status">
            <h2>Estado del Chatbot</h2>
            <div class="status-item">
                <span class="status-label">Nombre del Bot</span>
                <span class="status-value"><?php echo htmlspecialchars($config['chatbot_name']); ?></span>
            </div>
            <div class="status-item">
                <span class="status-label">Proveedor de IA</span>
                <span class="status-value"><?php echo strtoupper($config['ai_provider']); ?></span>
            </div>
            <div class="status-item">
                <span class="status-label">API Key Configurada</span>
                <span class="status-value">
                    <?php echo !empty($config['api_key']) ? '<span class="badge success">✓ Configurada</span>' : '<span class="badge warning">⚠ No configurada</span>'; ?>
                </span>
            </div>
            <div class="status-item">
                <span class="status-label">Delivery Habilitado</span>
                <span class="status-value"><?php echo $config['enable_delivery'] ? 'Sí' : 'No'; ?></span>
            </div>
            <div class="status-item">
                <span class="status-label">Reservaciones Habilitadas</span>
                <span class="status-value"><?php echo $config['enable_reservations'] ? 'Sí' : 'No'; ?></span>
            </div>
        </div>
    </div>
</body>
</html>
