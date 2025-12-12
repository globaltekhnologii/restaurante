<?php
require_once 'config.php';
checkSuperAdminAuth();

$conn = getDBConnection();

// Obtener estadísticas
$stats = [];

// Total de tenants
$result = $conn->query("SELECT COUNT(*) as total FROM saas_tenants");
$stats['total_tenants'] = $result->fetch_assoc()['total'];

// Tenants activos
$result = $conn->query("SELECT COUNT(*) as total FROM saas_tenants WHERE status = 'active'");
$stats['active_tenants'] = $result->fetch_assoc()['total'];

// Tenants suspendidos
$result = $conn->query("SELECT COUNT(*) as total FROM saas_tenants WHERE status = 'suspended'");
$stats['suspended_tenants'] = $result->fetch_assoc()['total'];

// Total de pagos este mes
$result = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM saas_payments 
                       WHERE MONTH(payment_date) = MONTH(CURRENT_DATE()) 
                       AND YEAR(payment_date) = YEAR(CURRENT_DATE())
                       AND status = 'completado'");
$stats['monthly_revenue'] = $result->fetch_assoc()['total'];

// Tenants próximos a vencer (7 días)
$result = $conn->query("SELECT COUNT(*) as total FROM saas_tenants 
                       WHERE subscription_end IS NOT NULL 
                       AND subscription_end BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                       AND status = 'active'");
$stats['expiring_soon'] = $result->fetch_assoc()['total'];

// Actividad reciente (últimos 5 tenants)
$recent_tenants = [];
$result = $conn->query("SELECT id, restaurant_name, owner_email, plan, status, created_at 
                       FROM saas_tenants 
                       ORDER BY created_at DESC 
                       LIMIT 5");
while ($row = $result->fetch_assoc()) {
    $recent_tenants[] = $row;
}

// Pagos recientes
$recent_payments = [];
$result = $conn->query("SELECT p.*, t.restaurant_name 
                       FROM saas_payments p
                       JOIN saas_tenants t ON p.tenant_id = t.id
                       ORDER BY p.payment_date DESC, p.created_at DESC
                       LIMIT 5");
while ($row = $result->fetch_assoc()) {
    $recent_payments[] = $row;
}

$conn->close();

$current_admin = getCurrentSuperAdmin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Super Admin</title>
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
        
    <?php include 'includes/navbar.php'; ?>        
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6b7280;
        }
        
        .container {
            max-width: 1200px;
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
        
        .stat-card.danger .value {
            color: #ef4444;
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
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .badge-primary {
            background: #dbeafe;
            color: #1e3a8a;
        }
        
        .badge-dark {
            background: #e5e7eb;
            color: #1f2937;
        }
        
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2563eb;
        }
        
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #6b7280;
        }
    </style>
</head>
<body>

    <div class="container">
        <h1 style="margin-bottom: 2rem;">Dashboard</h1>
        
        <div class="stats-grid">
            <div class="stat-card primary">
                <h3>Total Restaurantes</h3>
                <div class="value"><?php echo $stats['total_tenants']; ?></div>
            </div>
            
            <div class="stat-card success">
                <h3>Activos</h3>
                <div class="value"><?php echo $stats['active_tenants']; ?></div>
            </div>
            
            <div class="stat-card warning">
                <h3>Próximos a Vencer</h3>
                <div class="value"><?php echo $stats['expiring_soon']; ?></div>
            </div>
            
            <div class="stat-card danger">
                <h3>Suspendidos</h3>
                <div class="value"><?php echo $stats['suspended_tenants']; ?></div>
            </div>
            
            <div class="stat-card success">
                <h3>Ingresos del Mes</h3>
                <div class="value"><?php echo formatCurrency($stats['monthly_revenue']); ?></div>
            </div>
        </div>

        <div class="card">
            <h2>Restaurantes Recientes</h2>
            <?php if (count($recent_tenants) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Restaurante</th>
                            <th>Email</th>
                            <th>Plan</th>
                            <th>Estado</th>
                            <th>Fecha Registro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_tenants as $tenant): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($tenant['restaurant_name']); ?></td>
                                <td><?php echo htmlspecialchars($tenant['owner_email']); ?></td>
                                <td><?php echo getPlanBadge($tenant['plan']); ?></td>
                                <td><?php echo getStatusBadge($tenant['status']); ?></td>
                                <td><?php echo formatDateES($tenant['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">No hay restaurantes registrados</div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Pagos Recientes</h2>
            <?php if (count($recent_payments) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Restaurante</th>
                            <th>Monto</th>
                            <th>Fecha</th>
                            <th>Método</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_payments as $payment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($payment['restaurant_name']); ?></td>
                                <td><?php echo formatCurrency($payment['amount']); ?></td>
                                <td><?php echo formatDateES($payment['payment_date']); ?></td>
                                <td><?php echo ucfirst($payment['payment_method']); ?></td>
                                <td>
                                    <?php 
                                    $status_badges = [
                                        'completado' => 'badge-success',
                                        'pendiente' => 'badge-warning',
                                        'fallido' => 'badge-danger'
                                    ];
                                    $badge_class = $status_badges[$payment['status']] ?? 'badge-info';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo ucfirst($payment['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">No hay pagos registrados</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
