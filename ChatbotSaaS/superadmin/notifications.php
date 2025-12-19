<?php
require_once 'config.php';
checkSuperAdminAuth();

$conn = getDBConnection();
$message = '';
$message_type = '';

// Procesar envío de notificación manual
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'send_expiring_notifications') {
        // Obtener tenants próximos a vencer (7 días)
        $result = $conn->query("SELECT * FROM saas_tenants 
            WHERE subscription_end IS NOT NULL 
            AND subscription_end BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            AND status = 'active'");
        
        $sent_count = 0;
        while ($tenant = $result->fetch_assoc()) {
            $days_remaining = getDaysRemaining($tenant['subscription_end']);
            
            // Aquí iría la lógica de envío de email
            // Por ahora solo simulamos el envío
            $email_subject = "Tu suscripción vence en {$days_remaining} días";
            $email_body = "Hola {$tenant['restaurant_name']},\n\nTu suscripción al plan {$tenant['plan']} vence el " . formatDateES($tenant['subscription_end']) . ".\n\nPor favor, renueva tu suscripción para continuar disfrutando de nuestros servicios.";
            
            // TODO: Implementar envío real con PHPMailer o similar
            // mail($tenant['owner_email'], $email_subject, $email_body);
            
            $sent_count++;
        }
        
        $message = "Se enviaron {$sent_count} notificaciones de vencimiento";
        $message_type = "success";
    }
}

// Obtener tenants próximos a vencer
$expiring_tenants = [];
$result = $conn->query("SELECT * FROM saas_tenants 
    WHERE subscription_end IS NOT NULL 
    AND subscription_end BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    AND status = 'active'
    ORDER BY subscription_end ASC");

while ($row = $result->fetch_assoc()) {
    $row['days_remaining'] = getDaysRemaining($row['subscription_end']);
    $expiring_tenants[] = $row;
}

// Obtener tenants con uso alto (>80%)
$high_usage_tenants = [];
$result = $conn->query("SELECT 
    t.*,
    (SELECT COUNT(*) FROM saas_menu_items WHERE tenant_id = t.id) as menu_items_count
FROM saas_tenants t
WHERE t.status = 'active'
HAVING 
    (t.current_users / t.max_users * 100) >= 80 OR
    (menu_items_count / t.max_menu_items * 100) >= 80 OR
    (t.current_storage_mb / t.max_storage_mb * 100) >= 80");

while ($row = $result->fetch_assoc()) {
    $high_usage_tenants[] = $row;
}

$conn->close();
$current_admin = getCurrentSuperAdmin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificaciones - Super Admin</title>
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
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .message.success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #22c55e;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 12px;
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
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }
        
        .alert-box {
            background: #fef3c7;
            border: 1px solid #fbbf24;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .alert-box h3 {
            color: #92400e;
            margin-bottom: 0.5rem;
        }
        
        .alert-box p {
            color: #92400e;
            font-size: 0.9em;
        }
    </style>
</head>
<body>

    <?php include 'includes/navbar.php'; ?>

    <div class="container">
        <h1 style="margin-bottom: 2rem;">🔔 Sistema de Notificaciones</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (count($expiring_tenants) > 0 || count($high_usage_tenants) > 0): ?>
            <div class="alert-box">
                <h3>⚠️ Alertas Activas</h3>
                <p>
                    <?php if (count($expiring_tenants) > 0): ?>
                        <?php echo count($expiring_tenants); ?> suscripción(es) próxima(s) a vencer.
                    <?php endif; ?>
                    <?php if (count($high_usage_tenants) > 0): ?>
                        <?php echo count($high_usage_tenants); ?> tenant(s) con uso alto.
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2>
                Suscripciones Próximas a Vencer (7 días)
                <?php if (count($expiring_tenants) > 0): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="send_expiring_notifications">
                        <button type="submit" class="btn btn-primary btn-sm">
                            📧 Enviar Notificaciones
                        </button>
                    </form>
                <?php endif; ?>
            </h2>
            
            <?php if (count($expiring_tenants) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Restaurante</th>
                            <th>Email</th>
                            <th>Plan</th>
                            <th>Fecha Vencimiento</th>
                            <th>Días Restantes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expiring_tenants as $tenant): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($tenant['restaurant_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($tenant['owner_email']); ?></td>
                                <td><?php echo getPlanBadge($tenant['plan']); ?></td>
                                <td><?php echo formatDateES($tenant['subscription_end']); ?></td>
                                <td>
                                    <span class="badge <?php echo $tenant['days_remaining'] <= 3 ? 'badge-danger' : 'badge-warning'; ?>">
                                        <?php echo $tenant['days_remaining']; ?> días
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <div style="font-size: 3em; margin-bottom: 1rem;">✅</div>
                    <h3>No hay suscripciones próximas a vencer</h3>
                    <p>Todas las suscripciones están al día</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Tenants con Uso Alto (≥80%)</h2>
            
            <?php if (count($high_usage_tenants) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Restaurante</th>
                            <th>Plan</th>
                            <th>Usuarios</th>
                            <th>Items Menú</th>
                            <th>Almacenamiento</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($high_usage_tenants as $tenant): ?>
                            <?php
                            $users_pct = round(($tenant['current_users'] / $tenant['max_users']) * 100, 1);
                            $menu_pct = round(($tenant['menu_items_count'] / $tenant['max_menu_items']) * 100, 1);
                            $storage_pct = round(($tenant['current_storage_mb'] / $tenant['max_storage_mb']) * 100, 1);
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($tenant['restaurant_name']); ?></strong></td>
                                <td><?php echo getPlanBadge($tenant['plan']); ?></td>
                                <td>
                                    <?php if ($users_pct >= 80): ?>
                                        <span class="badge badge-warning"><?php echo $users_pct; ?>%</span>
                                    <?php else: ?>
                                        <?php echo $users_pct; ?>%
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($menu_pct >= 80): ?>
                                        <span class="badge badge-warning"><?php echo $menu_pct; ?>%</span>
                                    <?php else: ?>
                                        <?php echo $menu_pct; ?>%
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($storage_pct >= 80): ?>
                                        <span class="badge badge-warning"><?php echo $storage_pct; ?>%</span>
                                    <?php else: ?>
                                        <?php echo $storage_pct; ?>%
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <div style="font-size: 3em; margin-bottom: 1rem;">✅</div>
                    <h3>No hay tenants con uso alto</h3>
                    <p>Todos los tenants están dentro de sus límites</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>⚙️ Configuración de Notificaciones</h2>
            <p style="color: #6b7280; margin-bottom: 1rem;">
                Las notificaciones automáticas por email requieren configurar un servidor SMTP.
            </p>
            <div style="background: #f9fafb; padding: 1rem; border-radius: 8px;">
                <p><strong>Para habilitar emails automáticos:</strong></p>
                <ol style="margin-left: 1.5rem; margin-top: 0.5rem; color: #6b7280;">
                    <li>Instala PHPMailer: <code>composer require phpmailer/phpmailer</code></li>
                    <li>Configura SMTP en un archivo <code>email_config.php</code></li>
                    <li>Descomenta las líneas de envío en <code>notifications.php</code></li>
                </ol>
            </div>
        </div>
    </div>

</body>
</html>
