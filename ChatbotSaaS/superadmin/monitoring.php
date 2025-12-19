<?php
require_once 'config.php';
checkSuperAdminAuth();

$conn = getDBConnection();

// Obtener estadísticas de uso por tenant
$tenants_usage = [];
$result = $conn->query("SELECT 
    t.id,
    t.restaurant_name,
    t.plan,
    t.max_users,
    t.max_menu_items,
    t.max_storage_mb,
    t.current_users,
    t.current_menu_items,
    t.current_storage_mb,
    COUNT(DISTINCT c.id) as total_conversations,
    COUNT(DISTINCT m.id) as total_messages,
    (SELECT COUNT(*) FROM saas_menu_items WHERE tenant_id = t.id) as menu_items_count
FROM saas_tenants t
LEFT JOIN saas_conversations c ON c.tenant_id = t.id
LEFT JOIN saas_messages m ON m.conversation_id = c.id
WHERE t.status = 'active'
GROUP BY t.id
ORDER BY t.restaurant_name ASC");

while ($row = $result->fetch_assoc()) {
    // Calcular porcentajes de uso
    $row['users_percentage'] = $row['max_users'] > 0 ? round(($row['current_users'] / $row['max_users']) * 100, 1) : 0;
    $row['menu_percentage'] = $row['max_menu_items'] > 0 ? round(($row['menu_items_count'] / $row['max_menu_items']) * 100, 1) : 0;
    $row['storage_percentage'] = $row['max_storage_mb'] > 0 ? round(($row['current_storage_mb'] / $row['max_storage_mb']) * 100, 1) : 0;
    
    $tenants_usage[] = $row;
}

// Estadísticas globales
$stats = [];
$result = $conn->query("SELECT 
    SUM(current_users) as total_users,
    SUM(current_menu_items) as total_menu_items,
    SUM(current_storage_mb) as total_storage_mb,
    (SELECT COUNT(*) FROM saas_conversations WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as conversations_week,
    (SELECT COUNT(*) FROM saas_messages WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as messages_week
FROM saas_tenants WHERE status = 'active'");
$stats = $result->fetch_assoc();

$conn->close();
$current_admin = getCurrentSuperAdmin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoreo de Uso - Super Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f3f4f6;
            color: #1f2937;
        }
        
        .navbar { background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .navbar-brand { font-size: 1.5rem; font-weight: bold; color: #3b82f6; }
        .navbar-menu { display: flex; gap: 2rem; align-items: center; }
        .navbar-menu a { text-decoration: none; color: #6b7280; font-weight: 500; transition: color 0.2s; }
        .navbar-menu a:hover, .navbar-menu a.active { color: #3b82f6; }
        .user-info { display: flex; align-items: center; gap: 0.5rem; color: #6b7280; }
        .dropdown { position: relative; display: inline-block; }
        .dropbtn { background: none; border: none; cursor: pointer; color: #6b7280; }
        .dropdown-content { display: none; position: absolute; right: 0; background-color: #f9f9f9; min-width: 160px; box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2); z-index: 1; }
        .dropdown-content a { color: black; padding: 12px 16px; text-decoration: none; display: block; }
        .dropdown:hover .dropdown-content { display: block; }
        .dropdown-content a:hover { background-color: #f1f1f1; }
        
        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .stat-card .value {
            font-size: 2rem;
            font-weight: bold;
            color: #1f2937;
        }
        
        .stat-card.primary .value {
            color: #3b82f6;
        }
        
        .stat-card.success .value {
            color: #22c55e;
        }
        
        .stat-card.warning .value {
            color: #f59e0b;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .card h2 {
            font-size: 1.25rem;
            margin-bottom: 1rem;
            color: #1f2937;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            text-align: left;
            padding: 0.75rem;
            background: #f9fafb;
            font-weight: 600;
            font-size: 0.875rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        td {
            padding: 0.75rem;
            border-top: 1px solid #e5e7eb;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 0.25rem;
        }
        
        .progress-fill {
            height: 100%;
            transition: width 0.3s;
        }
        
        .progress-fill.low {
            background: #22c55e;
        }
        
        .progress-fill.medium {
            background: #f59e0b;
        }
        
        .progress-fill.high {
            background: #ef4444;
        }
        
        .usage-cell {
            min-width: 150px;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-primary {
            background: #dbeafe;
            color: #1e3a8a;
        }
        
        .badge-dark {
            background: #e5e7eb;
            color: #1f2937;
        }
    </style>
</head>
<body>

    <?php include 'includes/navbar.php'; ?>

    <div class="container">
        <h1 style="margin-bottom: 2rem;">📊 Monitoreo de Uso</h1>
        
        <div class="stats-grid">
            <div class="stat-card primary">
                <h3>Total Usuarios</h3>
                <div class="value"><?php echo $stats['total_users'] ?? 0; ?></div>
            </div>
            
            <div class="stat-card success">
                <h3>Items en Menús</h3>
                <div class="value"><?php echo $stats['total_menu_items'] ?? 0; ?></div>
            </div>
            
            <div class="stat-card warning">
                <h3>Almacenamiento</h3>
                <div class="value"><?php echo round($stats['total_storage_mb'] ?? 0, 1); ?> MB</div>
            </div>
            
            <div class="stat-card primary">
                <h3>Conversaciones (7d)</h3>
                <div class="value"><?php echo $stats['conversations_week'] ?? 0; ?></div>
            </div>
            
            <div class="stat-card success">
                <h3>Mensajes (7d)</h3>
                <div class="value"><?php echo $stats['messages_week'] ?? 0; ?></div>
            </div>
        </div>

        <div class="card">
            <h2>Uso por Restaurante</h2>
            
            <?php if (count($tenants_usage) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Restaurante</th>
                            <th>Plan</th>
                            <th>Usuarios</th>
                            <th>Items Menú</th>
                            <th>Almacenamiento</th>
                            <th>Conversaciones</th>
                            <th>Mensajes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tenants_usage as $tenant): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($tenant['restaurant_name']); ?></strong></td>
                                <td><?php echo getPlanBadge($tenant['plan']); ?></td>
                                <td class="usage-cell">
                                    <div>
                                        <?php echo $tenant['current_users']; ?> / <?php echo $tenant['max_users']; ?>
                                        (<?php echo $tenant['users_percentage']; ?>%)
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill <?php 
                                            echo $tenant['users_percentage'] >= 80 ? 'high' : 
                                                ($tenant['users_percentage'] >= 60 ? 'medium' : 'low'); 
                                        ?>" style="width: <?php echo min($tenant['users_percentage'], 100); ?>%"></div>
                                    </div>
                                </td>
                                <td class="usage-cell">
                                    <div>
                                        <?php echo $tenant['menu_items_count']; ?> / <?php echo $tenant['max_menu_items']; ?>
                                        (<?php echo $tenant['menu_percentage']; ?>%)
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill <?php 
                                            echo $tenant['menu_percentage'] >= 80 ? 'high' : 
                                                ($tenant['menu_percentage'] >= 60 ? 'medium' : 'low'); 
                                        ?>" style="width: <?php echo min($tenant['menu_percentage'], 100); ?>%"></div>
                                    </div>
                                </td>
                                <td class="usage-cell">
                                    <div>
                                        <?php echo round($tenant['current_storage_mb'], 1); ?> / <?php echo $tenant['max_storage_mb']; ?> MB
                                        (<?php echo $tenant['storage_percentage']; ?>%)
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill <?php 
                                            echo $tenant['storage_percentage'] >= 80 ? 'high' : 
                                                ($tenant['storage_percentage'] >= 60 ? 'medium' : 'low'); 
                                        ?>" style="width: <?php echo min($tenant['storage_percentage'], 100); ?>%"></div>
                                    </div>
                                </td>
                                <td><?php echo $tenant['total_conversations']; ?></td>
                                <td><?php echo $tenant['total_messages']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 3rem; color: #6b7280;">
                    No hay tenants activos para monitorear
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
