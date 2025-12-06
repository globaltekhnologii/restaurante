<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

$conn = new mysqli("localhost", "root", "", "menu_restaurante");
$tenant_id = $_SESSION['tenant_id'];

// Manejar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $stmt = $conn->prepare("INSERT INTO saas_menu_items (tenant_id, name, category, price, description, available) VALUES (?, ?, ?, ?, ?, ?)");
        $available = isset($_POST['available']) ? 1 : 0;
        $stmt->bind_param("issdsi", $tenant_id, $_POST['name'], $_POST['category'], $_POST['price'], $_POST['description'], $available);
        $stmt->execute();
        $success = "Item agregado exitosamente";
    } elseif ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM saas_menu_items WHERE id = ? AND tenant_id = ?");
        $stmt->bind_param("ii", $_POST['item_id'], $tenant_id);
        $stmt->execute();
        $success = "Item eliminado";
    } elseif ($action === 'toggle') {
        $stmt = $conn->prepare("UPDATE saas_menu_items SET available = NOT available WHERE id = ? AND tenant_id = ?");
        $stmt->bind_param("ii", $_POST['item_id'], $tenant_id);
        $stmt->execute();
        $success = "Estado actualizado";
    }
}

// Obtener items del menú
$result = $conn->query("SELECT * FROM saas_menu_items WHERE tenant_id = $tenant_id ORDER BY category, name");
$menu_items = [];
while ($row = $result->fetch_assoc()) {
    $menu_items[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Menú - <?php echo htmlspecialchars($_SESSION['restaurant_name']); ?></title>
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
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .page-title { font-size: 28px; color: #1f2937; }
        
        .btn { padding: 12px 24px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; text-decoration: none; display: inline-block; }
        .btn-primary { background: linear-gradient(135deg, #f97316, #ea580c); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(249, 115, 22, 0.4); }
        .btn-danger { background: #ef4444; color: white; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        
        .success { background: #dcfce7; border-left: 4px solid #22c55e; padding: 12px; margin-bottom: 20px; border-radius: 4px; color: #166534; }
        
        .card { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 24px; margin-bottom: 24px; }
        
        table { width: 100%; border-collapse: collapse; }
        th { background: #f9fafb; padding: 12px; text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; border-bottom: 1px solid #e5e7eb; }
        td { padding: 16px 12px; border-bottom: 1px solid #f3f4f6; }
        tr:hover { background: #f9fafb; }
        
        .badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .badge.success { background: #dcfce7; color: #166534; }
        .badge.warning { background: #fef3c7; color: #92400e; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; border-radius: 12px; padding: 24px; max-width: 500px; width: 90%; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-title { font-size: 20px; color: #1f2937; }
        .close-modal { background: none; border: none; font-size: 24px; cursor: pointer; color: #6b7280; }
        
        .form-group { margin-bottom: 16px; }
        label { display: block; font-weight: 600; color: #374151; margin-bottom: 6px; font-size: 14px; }
        input, select, textarea { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #f97316; box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1); }
        textarea { resize: vertical; min-height: 80px; }
        
        .checkbox-group { display: flex; align-items: center; gap: 8px; }
        .checkbox-group input[type="checkbox"] { width: auto; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <div class="logo">🤖</div>
            <div class="header-title">
                <h1><?php echo htmlspecialchars($_SESSION['restaurant_name']); ?></h1>
                <p>Gestión de Menú</p>
            </div>
        </div>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="menu.php" class="active">Menú</a>
            <a href="configuracion.php">Configuración</a>
            <a href="conversaciones.php">Conversaciones</a>
            <a href="logout.php">Salir</a>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Gestión de Menú</h1>
            <button class="btn btn-primary" onclick="openModal()">+ Agregar Item</button>
        </div>

        <?php if (isset($success)): ?>
            <div class="success">✅ <?php echo $success; ?></div>
        <?php endif; ?>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Precio</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($menu_items as $item): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                <?php if ($item['description']): ?>
                                    <br><small style="color: #6b7280;"><?php echo htmlspecialchars($item['description']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($item['category']); ?></td>
                            <td><strong>$<?php echo number_format($item['price'], 0, ',', '.'); ?></strong></td>
                            <td>
                                <?php if ($item['available']): ?>
                                    <span class="badge success">Disponible</span>
                                <?php else: ?>
                                    <span class="badge warning">Agotado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <?php echo $item['available'] ? 'Marcar Agotado' : 'Marcar Disponible'; ?>
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('¿Eliminar este item?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($menu_items)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px; color: #6b7280;">
                                No hay items en el menú. ¡Agrega el primero!
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Agregar Item -->
    <div class="modal" id="addModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Agregar Item al Menú</h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label>Nombre del Item *</label>
                    <input type="text" name="name" required placeholder="Ej: Pizza Margarita">
                </div>

                <div class="form-group">
                    <label>Categoría *</label>
                    <select name="category" required>
                        <option value="">Seleccionar...</option>
                        <option value="Entradas">Entradas</option>
                        <option value="Platos Principales">Platos Principales</option>
                        <option value="Pizzas">Pizzas</option>
                        <option value="Hamburguesas">Hamburguesas</option>
                        <option value="Bebidas">Bebidas</option>
                        <option value="Postres">Postres</option>
                        <option value="Otros">Otros</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Precio (COP) *</label>
                    <input type="number" name="price" required placeholder="25000" step="100">
                </div>

                <div class="form-group">
                    <label>Descripción</label>
                    <textarea name="description" placeholder="Describe el item..."></textarea>
                </div>

                <div class="form-group checkbox-group">
                    <input type="checkbox" name="available" id="available" checked>
                    <label for="available" style="margin: 0;">Disponible</label>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Guardar Item</button>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('addModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('addModal').classList.remove('active');
        }
        
        // Cerrar modal al hacer clic fuera
        document.getElementById('addModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>
</body>
</html>
