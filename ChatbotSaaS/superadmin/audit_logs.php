<?php
require_once 'config.php';
checkSuperAdminAuth();

$conn = getDBConnection();

// Filtros
$filter_action = $_GET['action'] ?? '';
$filter_entity = $_GET['entity'] ?? '';
$filter_date = $_GET['date'] ?? '';

// Construir query con filtros
$where_clauses = [];
$params = [];
$types = '';

if ($filter_action) {
    $where_clauses[] = "action = ?";
    $params[] = $filter_action;
    $types .= 's';
}

if ($filter_entity) {
    $where_clauses[] = "entity_type = ?";
    $params[] = $filter_entity;
    $types .= 's';
}

if ($filter_date) {
    $where_clauses[] = "DATE(created_at) = ?";
    $params[] = $filter_date;
    $types .= 's';
}

$where_sql = count($where_clauses) > 0 ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

$sql = "SELECT * FROM audit_logs $where_sql ORDER BY created_at DESC LIMIT 100";
$stmt = $conn->prepare($sql);

if (count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$logs = [];
while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}

// Obtener acciones únicas para el filtro
$actions = [];
$result_actions = $conn->query("SELECT DISTINCT action FROM audit_logs ORDER BY action");
while ($row = $result_actions->fetch_assoc()) {
    $actions[] = $row['action'];
}

// Obtener tipos de entidad únicos
$entities = [];
$result_entities = $conn->query("SELECT DISTINCT entity_type FROM audit_logs WHERE entity_type IS NOT NULL ORDER BY entity_type");
while ($row = $result_entities->fetch_assoc()) {
    $entities[] = $row['entity_type'];
}

$stmt->close();
$conn->close();
$current_admin = getCurrentSuperAdmin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs de Auditoría - Super Admin</title>
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
        
        .filters {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: end;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #374151;
            font-size: 0.875rem;
        }
        
        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .btn {
            padding: 0.5rem 1.5rem;
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
        
        .btn-secondary {
            background: #6b7280;
            color: white;
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
            font-size: 0.875rem;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-create {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-update {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .badge-delete {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .badge-suspend {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-default {
            background: #e5e7eb;
            color: #1f2937;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }
    </style>
</head>
<body>

    <?php include 'includes/navbar.php'; ?>

    <div class="container">
        <h1 style="margin-bottom: 2rem;">📝 Logs de Auditoría</h1>
        
        <form method="GET" class="filters">
            <div class="filter-group">
                <label>Acción</label>
                <select name="action">
                    <option value="">Todas</option>
                    <?php foreach ($actions as $action): ?>
                        <option value="<?php echo htmlspecialchars($action); ?>" 
                                <?php echo $filter_action === $action ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($action); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Tipo de Entidad</label>
                <select name="entity">
                    <option value="">Todas</option>
                    <?php foreach ($entities as $entity): ?>
                        <option value="<?php echo htmlspecialchars($entity); ?>"
                                <?php echo $filter_entity === $entity ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($entity); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Fecha</label>
                <input type="date" name="date" value="<?php echo htmlspecialchars($filter_date); ?>">
            </div>
            
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="audit_logs.php" class="btn btn-secondary">Limpiar</a>
        </form>

        <div class="card">
            <h2>Registro de Actividad</h2>
            
            <?php if (count($logs) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Fecha/Hora</th>
                            <th>Admin</th>
                            <th>Acción</th>
                            <th>Entidad</th>
                            <th>Detalles</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($log['admin_email'] ?? 'Sistema'); ?></td>
                                <td>
                                    <?php
                                    $action_lower = strtolower($log['action']);
                                    $badge_class = 'badge-default';
                                    if (strpos($action_lower, 'create') !== false) $badge_class = 'badge-create';
                                    elseif (strpos($action_lower, 'update') !== false || strpos($action_lower, 'edit') !== false) $badge_class = 'badge-update';
                                    elseif (strpos($action_lower, 'delete') !== false) $badge_class = 'badge-delete';
                                    elseif (strpos($action_lower, 'suspend') !== false) $badge_class = 'badge-suspend';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo htmlspecialchars($log['action']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($log['entity_type']): ?>
                                        <?php echo htmlspecialchars($log['entity_type']); ?>
                                        <?php if ($log['entity_id']): ?>
                                            #<?php echo $log['entity_id']; ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?php echo htmlspecialchars($log['details'] ?? '-'); ?>
                                </td>
                                <td><?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <div style="font-size: 3em; margin-bottom: 1rem;">📋</div>
                    <h3>No hay registros</h3>
                    <p>No se encontraron logs con los filtros aplicados</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
