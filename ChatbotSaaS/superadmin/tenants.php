<?php
require_once 'config.php';
checkSuperAdminAuth();

$conn = getDBConnection();
$message = '';
$message_type = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        // Crear nuevo tenant
        $restaurant_name = trim($_POST['restaurant_name']);
        $owner_email = trim($_POST['owner_email']);
        $password = $_POST['password'];
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $plan = $_POST['plan'];
        $monthly_fee = floatval($_POST['monthly_fee']);
        
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Calcular fechas de suscripción
        $subscription_start = date('Y-m-d');
        $subscription_end = date('Y-m-d', strtotime('+30 days'));
        $next_billing_date = date('Y-m-d', strtotime('+30 days'));
        
        // Iniciar transacción
        $conn->begin_transaction();
        
        try {
            // Generar tenant_key único
            $tenant_key = 'tenant_' . uniqid() . '_' . bin2hex(random_bytes(8));
            
            // 1. Crear el tenant
            $stmt = $conn->prepare("INSERT INTO saas_tenants 
                (restaurant_name, owner_email, owner_password, phone, address, plan, 
                 subscription_start, subscription_end, next_billing_date, monthly_fee, tenant_key, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
            
            $stmt->bind_param("sssssssssds", 
                $restaurant_name, $owner_email, $password_hash, $phone, $address, $plan,
                $subscription_start, $subscription_end, $next_billing_date, $monthly_fee, $tenant_key
            );
            
            $stmt->execute();
            $new_tenant_id = $conn->insert_id;
            $stmt->close();
            
            // 2. Crear usuario administrador para el tenant
            $admin_username = 'admin_' . $new_tenant_id;
            $admin_nombre = 'Administrador';
            $admin_password_hash = password_hash($password, PASSWORD_DEFAULT); // Usar la misma contraseña
            
            $stmt = $conn->prepare("INSERT INTO usuarios (tenant_id, usuario, clave, nombre, email, rol, activo) 
                                    VALUES (?, ?, ?, ?, ?, 'admin', 1)");
            $stmt->bind_param("issss", $new_tenant_id, $admin_username, $admin_password_hash, $admin_nombre, $owner_email);
            $stmt->execute();
            $stmt->close();
            
            // 3. Crear configuración inicial para el tenant
            $stmt = $conn->prepare("INSERT INTO configuracion_sistema 
                (tenant_id, nombre_restaurante, direccion, telefono, email, logo_url) 
                VALUES (?, ?, ?, ?, ?, 'img/logo_default.png')");
            $stmt->bind_param("issss", $new_tenant_id, $restaurant_name, $address, $phone, $owner_email);
            $stmt->execute();
            $stmt->close();
            
            // Confirmar transacción
            $conn->commit();
            
            $message = "Restaurante creado exitosamente.<br>
                       <strong>Usuario admin:</strong> $admin_username<br>
                       <strong>Contraseña:</strong> (la misma del tenant)<br>
                       <strong>Tenant Key:</strong> $tenant_key";
            $message_type = "success";
            
        } catch (Exception $e) {
            // Revertir en caso de error
            $conn->rollback();
            $message = "Error al crear restaurante: " . $e->getMessage();
            $message_type = "error";
        }
    }
    elseif ($action === 'update_status') {
        // Actualizar estado
        $tenant_id = intval($_POST['tenant_id']);
        $new_status = $_POST['status'];
        
        $stmt = $conn->prepare("UPDATE saas_tenants SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $tenant_id);
        
        if ($stmt->execute()) {
            $message = "Estado actualizado exitosamente";
            $message_type = "success";
        } else {
            $message = "Error al actualizar estado";
            $message_type = "error";
        }
        
        $stmt->close();
    }
    elseif ($action === 'update_plan') {
        // Actualizar plan
        $tenant_id = intval($_POST['tenant_id']);
        $new_plan = $_POST['plan'];
        $monthly_fee = floatval($_POST['monthly_fee']);
        
        $stmt = $conn->prepare("UPDATE saas_tenants SET plan = ?, monthly_fee = ? WHERE id = ?");
        $stmt->bind_param("sdi", $new_plan, $monthly_fee, $tenant_id);
        
        if ($stmt->execute()) {
            $message = "Plan actualizado exitosamente";
            $message_type = "success";
        } else {
            $message = "Error al actualizar plan";
            $message_type = "error";
        }
        
        $stmt->close();
    }
    elseif ($action === 'extend_subscription') {
        // Extender suscripción
        $tenant_id = intval($_POST['tenant_id']);
        $days = intval($_POST['days']);
        
        $stmt = $conn->prepare("UPDATE saas_tenants 
                               SET subscription_end = DATE_ADD(COALESCE(subscription_end, CURDATE()), INTERVAL ? DAY),
                                   next_billing_date = DATE_ADD(COALESCE(next_billing_date, CURDATE()), INTERVAL ? DAY)
                               WHERE id = ?");
        $stmt->bind_param("iii", $days, $days, $tenant_id);
        
        if ($stmt->execute()) {
            $message = "Suscripción extendida por $days días";
            $message_type = "success";
        } else {
            $message = "Error al extender suscripción";
            $message_type = "error";
        }
        
        $stmt->close();
    }
    elseif ($action === 'delete') {
        // Eliminar tenant
        $tenant_id = intval($_POST['tenant_id']);
        
        $stmt = $conn->prepare("DELETE FROM saas_tenants WHERE id = ?");
        $stmt->bind_param("i", $tenant_id);
        
        if ($stmt->execute()) {
            $message = "Restaurante eliminado exitosamente";
            $message_type = "success";
        } else {
            $message = "Error al eliminar restaurante";
            $message_type = "error";
        }
        
        $stmt->close();
    }
}

// Obtener todos los tenants
$tenants = [];
$result = $conn->query("SELECT * FROM saas_tenants ORDER BY created_at DESC");
while ($row = $result->fetch_assoc()) {
    $tenants[] = $row;
}

$conn->close();
$current_admin = getCurrentSuperAdmin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Restaurantes - Super Admin</title>
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
        
        .navbar {
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: #3b82f6;
        }
        
        .navbar-menu {
            display: flex;
            gap: 2rem;
            align-items: center;
        }
        
        .navbar-menu a {
            text-decoration: none;
            color: #6b7280;
            font-weight: 500;
            transition: color 0.2s;
        }
        
        .navbar-menu a:hover, .navbar-menu a.active {
            color: #3b82f6;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6b7280;
        }
        
        .dropdown {
            position: relative;
            display: inline-block;
        }
        
        .dropbtn {
            background: none;
            border: none;
            cursor: pointer;
            color: #6b7280;
        }
        
        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
        }
        
        .dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }
        
        .dropdown:hover .dropdown-content {
            display: block;
        }
        
        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }
        
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
        
        .btn-success {
            background: #22c55e;
            color: white;
        }
        
        .btn-warning {
            background: #f59e0b;
            color: white;
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 12px;
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
            max-width: 500px;
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
        
        .actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 0.5rem;
        }
        
        .status-indicator.active {
            background: #22c55e;
        }
        
        .status-indicator.suspended {
            background: #f59e0b;
        }
        
        .status-indicator.cancelled {
            background: #ef4444;
        }
    </style>
</head>
<body>

    <?php include 'includes/navbar.php'; ?>

    <div class="container">
        <div class="header">
            <h1>Gestión de Restaurantes</h1>
            <div style="display: flex; gap: 10px;">
                <a href="export_data.php?type=tenants" class="btn btn-success">
                    📥 Exportar CSV
                </a>
                <button class="btn btn-primary" onclick="openModal('createModal')">
                    ➕ Nuevo Restaurante
                </button>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Restaurante</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Plan</th>
                        <th>Tenant Key</th>
                        <th>Estado</th>
                        <th>Suscripción</th>
                        <th>Días Restantes</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tenants as $tenant): ?>
                        <?php $days_remaining = getDaysRemaining($tenant['subscription_end']); ?>
                        <tr>
                            <td><?php echo $tenant['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($tenant['restaurant_name']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($tenant['owner_email']); ?></td>
                            <td><?php echo htmlspecialchars($tenant['phone'] ?? 'N/A'); ?></td>
                            <td><?php echo getPlanBadge($tenant['plan']); ?></td>
                            <td>
                                <?php if (!empty($tenant['tenant_key'])): ?>
                                    <code style="font-size: 11px; background: #f3f4f6; padding: 2px 6px; border-radius: 4px;" 
                                          title="<?php echo htmlspecialchars($tenant['tenant_key']); ?>">
                                        <?php echo htmlspecialchars(substr($tenant['tenant_key'], 0, 20)); ?>...
                                    </code>
                                <?php else: ?>
                                    <span style="color: #ef4444;">No generado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-indicator <?php echo $tenant['status']; ?>"></span>
                                <?php echo getStatusBadge($tenant['status']); ?>
                            </td>
                            <td><?php echo formatDateES($tenant['subscription_end']); ?></td>
                            <td>
                                <?php if ($days_remaining !== null): ?>
                                    <?php if ($days_remaining < 0): ?>
                                        <span class="badge badge-danger">Expirado</span>
                                    <?php elseif ($days_remaining <= 7): ?>
                                        <span class="badge badge-warning"><?php echo $days_remaining; ?> días</span>
                                    <?php else: ?>
                                        <span class="badge badge-success"><?php echo $days_remaining; ?> días</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <?php if ($tenant['status'] === 'active'): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="tenant_id" value="<?php echo $tenant['id']; ?>">
                                            <input type="hidden" name="status" value="suspended">
                                            <button type="submit" class="btn btn-warning btn-sm">Suspender</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="tenant_id" value="<?php echo $tenant['id']; ?>">
                                            <input type="hidden" name="status" value="active">
                                            <button type="submit" class="btn btn-success btn-sm">Activar</button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <button class="btn btn-primary btn-sm" 
                                            onclick="openEditPlanModal(<?php echo $tenant['id']; ?>, '<?php echo $tenant['plan']; ?>', <?php echo $tenant['monthly_fee']; ?>)">
                                        Editar Plan
                                    </button>
                                    
                                    <button class="btn btn-success btn-sm" 
                                            onclick="openExtendModal(<?php echo $tenant['id']; ?>)">
                                        Extender
                                    </button>
                                    
                                    <?php if (!empty($tenant['tenant_key'])): ?>
                                        <a href="generate_tenant_config.php?tenant_id=<?php echo $tenant['id']; ?>" 
                                           class="btn btn-primary btn-sm" 
                                           title="Descargar archivo de configuración">
                                            📥 Config
                                        </a>
                                    <?php endif; ?>
                                    
                                    <form method="POST" style="display:inline;" 
                                          onsubmit="return confirm('¿Estás seguro de eliminar este restaurante?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="tenant_id" value="<?php echo $tenant['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Crear Restaurante -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <h2 style="margin-bottom: 1.5rem;">Nuevo Restaurante</h2>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                
                <div class="form-group">
                    <label>Nombre del Restaurante *</label>
                    <input type="text" name="restaurant_name" required>
                </div>
                
                <div class="form-group">
                    <label>Email del Propietario *</label>
                    <input type="email" name="owner_email" required>
                </div>
                
                <div class="form-group">
                    <label>Contraseña *</label>
                    <input type="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="text" name="phone">
                </div>
                
                <div class="form-group">
                    <label>Dirección</label>
                    <textarea name="address" rows="2"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Plan *</label>
                    <select name="plan" required>
                        <option value="basic">Básico</option>
                        <option value="pro">Pro</option>
                        <option value="enterprise">Enterprise</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Tarifa Mensual (COP) *</label>
                    <input type="number" name="monthly_fee" value="50000" step="1000" required>
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary">Crear Restaurante</button>
                    <button type="button" class="btn btn-danger" onclick="closeModal('createModal')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Editar Plan -->
    <div id="editPlanModal" class="modal">
        <div class="modal-content">
            <h2 style="margin-bottom: 1.5rem;">Editar Plan</h2>
            <form method="POST">
                <input type="hidden" name="action" value="update_plan">
                <input type="hidden" name="tenant_id" id="edit_tenant_id">
                
                <div class="form-group">
                    <label>Plan *</label>
                    <select name="plan" id="edit_plan" required>
                        <option value="basic">Básico</option>
                        <option value="pro">Pro</option>
                        <option value="enterprise">Enterprise</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Tarifa Mensual (COP) *</label>
                    <input type="number" name="monthly_fee" id="edit_monthly_fee" step="1000" required>
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary">Actualizar Plan</button>
                    <button type="button" class="btn btn-danger" onclick="closeModal('editPlanModal')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Extender Suscripción -->
    <div id="extendModal" class="modal">
        <div class="modal-content">
            <h2 style="margin-bottom: 1.5rem;">Extender Suscripción</h2>
            <form method="POST">
                <input type="hidden" name="action" value="extend_subscription">
                <input type="hidden" name="tenant_id" id="extend_tenant_id">
                
                <div class="form-group">
                    <label>Días a Extender *</label>
                    <select name="days" required>
                        <option value="7">7 días</option>
                        <option value="15">15 días</option>
                        <option value="30" selected>30 días (1 mes)</option>
                        <option value="60">60 días (2 meses)</option>
                        <option value="90">90 días (3 meses)</option>
                        <option value="180">180 días (6 meses)</option>
                        <option value="365">365 días (1 año)</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-success">Extender</button>
                    <button type="button" class="btn btn-danger" onclick="closeModal('extendModal')">Cancelar</button>
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
        
        function openEditPlanModal(tenantId, plan, monthlyFee) {
            document.getElementById('edit_tenant_id').value = tenantId;
            document.getElementById('edit_plan').value = plan;
            document.getElementById('edit_monthly_fee').value = monthlyFee;
            openModal('editPlanModal');
        }
        
        function openExtendModal(tenantId) {
            document.getElementById('extend_tenant_id').value = tenantId;
            openModal('extendModal');
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
