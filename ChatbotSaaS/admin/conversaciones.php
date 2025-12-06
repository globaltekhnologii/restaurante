<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

$conn = new mysqli("localhost", "root", "", "menu_restaurante");
$tenant_id = $_SESSION['tenant_id'];

// Obtener conversaciones
$result = $conn->query("SELECT c.*, COUNT(m.id) as message_count 
    FROM saas_conversations c
    LEFT JOIN saas_messages m ON m.conversation_id = c.id
    WHERE c.tenant_id = $tenant_id
    GROUP BY c.id
    ORDER BY c.updated_at DESC
    LIMIT 50");

$conversations = [];
while ($row = $result->fetch_assoc()) {
    $conversations[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conversaciones - <?php echo htmlspecialchars($_SESSION['restaurant_name']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f9fafb; }
        
        .header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-left { display: flex; align-items: center; gap: 12px; }
        .logo { width: 40px; height: 40px; background: linear-gradient(135deg, #f97316, #ea580c); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .header-title h1 { font-size: 18px; color: #1f2937; }
        .header-title p { font-size: 13px; color: #6b7280; }
        
        .nav-links { display: flex; gap: 12px; }
        .nav-links a { padding: 8px 16px; color: #6b7280; text-decoration: none; border-radius: 6px; transition: all 0.2s; }
        .nav-links a:hover, .nav-links a.active { background: #f3f4f6; color: #f97316; }
        
        .container { max-width: 1200px; margin: 0 auto; padding: 24px; }
        .page-title { font-size: 28px; color: #1f2937; margin-bottom: 24px; }
        
        .card { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 24px; }
        
        table { width: 100%; border-collapse: collapse; }
        th { background: #f9fafb; padding: 12px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; border-bottom: 1px solid #e5e7eb; }
        td { padding: 16px 12px; border-bottom: 1px solid #f3f4f6; }
        tr:hover { background: #f9fafb; }
        
        .badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .badge.success { background: #dcfce7; color: #166534; }
        .badge.info { background: #dbeafe; color: #1e40af; }
        .badge.warning { background: #fef3c7; color: #92400e; }
        
        .empty-state { text-align: center; padding: 60px 20px; color: #6b7280; }
        .empty-state-icon { font-size: 64px; margin-bottom: 16px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <div class="logo">🤖</div>
            <div class="header-title">
                <h1><?php echo htmlspecialchars($_SESSION['restaurant_name']); ?></h1>
                <p>Historial de Conversaciones</p>
            </div>
        </div>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="menu.php">Menú</a>
            <a href="configuracion.php">Configuración</a>
            <a href="conversaciones.php" class="active">Conversaciones</a>
            <a href="logout.php">Salir</a>
        </div>
    </div>

    <div class="container">
        <h1 class="page-title">Historial de Conversaciones</h1>

        <div class="card">
            <?php if (!empty($conversations)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID Sesión</th>
                            <th>Cliente</th>
                            <th>Mensajes</th>
                            <th>Estado</th>
                            <th>Pedido</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($conversations as $conv): ?>
                            <tr>
                                <td><code><?php echo substr($conv['session_id'], 0, 20); ?>...</code></td>
                                <td>
                                    <?php if ($conv['customer_name']): ?>
                                        <strong><?php echo htmlspecialchars($conv['customer_name']); ?></strong><br>
                                    <?php endif; ?>
                                    <?php if ($conv['customer_phone']): ?>
                                        <small style="color: #6b7280;"><?php echo htmlspecialchars($conv['customer_phone']); ?></small>
                                    <?php else: ?>
                                        <small style="color: #6b7280;">Anónimo</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $conv['message_count']; ?> mensajes</td>
                                <td>
                                    <?php
                                    $badges = [
                                        'active' => '<span class="badge info">Activa</span>',
                                        'completed' => '<span class="badge success">Completada</span>',
                                        'abandoned' => '<span class="badge warning">Abandonada</span>'
                                    ];
                                    echo $badges[$conv['status']] ?? $conv['status'];
                                    ?>
                                </td>
                                <td>
                                    <?php echo $conv['order_placed'] ? '✅ Sí' : '❌ No'; ?>
                                </td>
                                <td>
                                    <small><?php echo date('d/m/Y H:i', strtotime($conv['created_at'])); ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">💬</div>
                    <h3>No hay conversaciones aún</h3>
                    <p>Las conversaciones aparecerán aquí cuando los usuarios interactúen con tu chatbot</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
