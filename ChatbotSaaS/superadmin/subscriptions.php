<?php
require_once 'config.php';
checkSuperAdminAuth();

$conn = getDBConnection();
$message = '';
$message_type = '';

// Procesar registro de pago
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register_payment') {
    $tenant_id = intval($_POST['tenant_id']);
    $amount = floatval($_POST['amount']);
    $payment_date = $_POST['payment_date'];
    $payment_method = $_POST['payment_method'];
    $reference_number = trim($_POST['reference_number']);
    $notes = trim($_POST['notes']);
    $extend_days = intval($_POST['extend_days']);
    
    // Insertar pago
    $stmt = $conn->prepare("INSERT INTO saas_payments 
        (tenant_id, amount, payment_date, payment_method, reference_number, notes, status) 
        VALUES (?, ?, ?, ?, ?, ?, 'completado')");
    
    $stmt->bind_param("idssss", $tenant_id, $amount, $payment_date, $payment_method, $reference_number, $notes);
    
    if ($stmt->execute()) {
        // Extender suscripción
        $stmt2 = $conn->prepare("UPDATE saas_tenants 
                                SET subscription_end = DATE_ADD(COALESCE(subscription_end, CURDATE()), INTERVAL ? DAY),
                                    next_billing_date = DATE_ADD(COALESCE(next_billing_date, CURDATE()), INTERVAL ? DAY),
                                    status = 'active'
                                WHERE id = ?");
        $stmt2->bind_param("iii", $extend_days, $extend_days, $tenant_id);
        $stmt2->execute();
        $stmt2->close();
        
        $message = "Pago registrado y suscripción extendida exitosamente";
        $message_type = "success";
    } else {
        $message = "Error al registrar pago: " . $stmt->error;
        $message_type = "error";
    }
    
    $stmt->close();
}

// Obtener todos los pagos
$payments = [];
$result = $conn->query("SELECT p.*, t.restaurant_name, t.owner_email 
                       FROM saas_payments p
                       JOIN saas_tenants t ON p.tenant_id = t.id
                       ORDER BY p.payment_date DESC, p.created_at DESC");
while ($row = $result->fetch_assoc()) {
    $payments[] = $row;
}

// Obtener tenants para el formulario
$tenants = [];
$result = $conn->query("SELECT id, restaurant_name, owner_email, plan, monthly_fee, subscription_end 
                       FROM saas_tenants 
                       ORDER BY restaurant_name");
while ($row = $result->fetch_assoc()) {
    $tenants[] = $row;
}

// Estadísticas de pagos
$stats = [];

// Total recaudado
$result = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM saas_payments WHERE status = 'completado'");
$stats['total_revenue'] = $result->fetch_assoc()['total'];

// Este mes
$result = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM saas_payments 
                       WHERE MONTH(payment_date) = MONTH(CURRENT_DATE()) 
                       AND YEAR(payment_date) = YEAR(CURRENT_DATE())
                       AND status = 'completado'");
$stats['monthly_revenue'] = $result->fetch_assoc()['total'];

// Mes anterior
$result = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM saas_payments 
                       WHERE MONTH(payment_date) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))
                       AND YEAR(payment_date) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))
                       AND status = 'completado'");
$stats['last_month_revenue'] = $result->fetch_assoc()['total'];

// Total de pagos
$result = $conn->query("SELECT COUNT(*) as total FROM saas_payments");
$stats['total_payments'] = $result->fetch_assoc()['total'];

$conn->close();
$current_admin = getCurrentSuperAdmin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Suscripciones - Super Admin</title>
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
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
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
            color: #22c55e;
        }
        
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
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
        
        .btn-primary:hover {
            background: #2563eb;
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
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
        
        .message.error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
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
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #374151;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
    </style>
</head>
<body>

    <?php include 'includes/navbar.php'; ?>

    <div class="container">
        <div class="header">
            <h1>Gestión de Suscripciones y Pagos</h1>
            <div style="display: flex; gap: 10px;">
                <a href="export_data.php?type=payments" class="btn btn-success">
                    📥 Exportar CSV
                </a>
                <button class="btn btn-primary" onclick="openModal('paymentModal')">
                    💰 Registrar Pago
                </button>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Recaudado</h3>
                <div class="value"><?php echo formatCurrency($stats['total_revenue']); ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Este Mes</h3>
                <div class="value"><?php echo formatCurrency($stats['monthly_revenue']); ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Mes Anterior</h3>
                <div class="value"><?php echo formatCurrency($stats['last_month_revenue']); ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Total Pagos</h3>
                <div class="value"><?php echo $stats['total_payments']; ?></div>
            </div>
        </div>

        <div class="card">
            <h2 style="margin-bottom: 1rem;">Historial de Pagos</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Restaurante</th>
                        <th>Monto</th>
                        <th>Fecha Pago</th>
                        <th>Método</th>
                        <th>Referencia</th>
                        <th>Estado</th>
                        <th>Notas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($payments) > 0): ?>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?php echo $payment['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($payment['restaurant_name']); ?></strong><br>
                                    <small style="color: #6b7280;"><?php echo htmlspecialchars($payment['owner_email']); ?></small>
                                </td>
                                <td><strong><?php echo formatCurrency($payment['amount']); ?></strong></td>
                                <td><?php echo formatDateES($payment['payment_date']); ?></td>
                                <td><?php echo ucfirst($payment['payment_method']); ?></td>
                                <td><?php echo htmlspecialchars($payment['reference_number'] ?? 'N/A'); ?></td>
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
                                <td><?php echo htmlspecialchars($payment['notes'] ?? '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 2rem; color: #6b7280;">
                                No hay pagos registrados
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Registrar Pago -->
    <div id="paymentModal" class="modal">
        <div class="modal-content">
            <h2 style="margin-bottom: 1.5rem;">Registrar Pago</h2>
            <form method="POST">
                <input type="hidden" name="action" value="register_payment">
                
                <div class="form-group">
                    <label>Restaurante *</label>
                    <select name="tenant_id" id="tenant_select" required onchange="updatePaymentInfo()">
                        <option value="">Seleccionar restaurante...</option>
                        <?php foreach ($tenants as $tenant): ?>
                            <option value="<?php echo $tenant['id']; ?>" 
                                    data-plan="<?php echo $tenant['plan']; ?>"
                                    data-fee="<?php echo $tenant['monthly_fee']; ?>"
                                    data-end="<?php echo $tenant['subscription_end']; ?>">
                                <?php echo htmlspecialchars($tenant['restaurant_name']); ?> 
                                (<?php echo htmlspecialchars($tenant['owner_email']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="tenant_info" style="display: none; padding: 1rem; background: #f9fafb; border-radius: 8px; margin-bottom: 1rem;">
                    <p><strong>Plan:</strong> <span id="info_plan"></span></p>
                    <p><strong>Tarifa Mensual:</strong> <span id="info_fee"></span></p>
                    <p><strong>Vencimiento Actual:</strong> <span id="info_end"></span></p>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Monto (COP) *</label>
                        <input type="number" name="amount" id="payment_amount" step="1000" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Fecha de Pago *</label>
                        <input type="date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Método de Pago *</label>
                        <select name="payment_method" required>
                            <option value="transferencia">Transferencia</option>
                            <option value="efectivo">Efectivo</option>
                            <option value="tarjeta">Tarjeta</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Extender Suscripción *</label>
                        <select name="extend_days" required>
                            <option value="30" selected>30 días (1 mes)</option>
                            <option value="60">60 días (2 meses)</option>
                            <option value="90">90 días (3 meses)</option>
                            <option value="180">180 días (6 meses)</option>
                            <option value="365">365 días (1 año)</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Número de Referencia</label>
                    <input type="text" name="reference_number" placeholder="Ej: TRX-12345">
                </div>
                
                <div class="form-group">
                    <label>Notas</label>
                    <textarea name="notes" rows="2" placeholder="Información adicional..."></textarea>
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary">Registrar Pago</button>
                    <button type="button" class="btn btn-danger" onclick="closeModal('paymentModal')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        function updatePaymentInfo() {
            const select = document.getElementById('tenant_select');
            const option = select.options[select.selectedIndex];
            
            if (option.value) {
                const plan = option.getAttribute('data-plan');
                const fee = parseFloat(option.getAttribute('data-fee'));
                const end = option.getAttribute('data-end');
                
                document.getElementById('info_plan').textContent = plan.charAt(0).toUpperCase() + plan.slice(1);
                document.getElementById('info_fee').textContent = formatCurrency(fee);
                document.getElementById('info_end').textContent = end || 'No establecido';
                document.getElementById('payment_amount').value = fee;
                document.getElementById('tenant_info').style.display = 'block';
            } else {
                document.getElementById('tenant_info').style.display = 'none';
            }
        }
        
        function formatCurrency(amount) {
            return '$' + amount.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }
        
        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }
    </script>
</body>
</html>
