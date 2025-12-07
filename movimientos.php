<?php
session_start();
require_once 'auth_helper.php';
verificarSesion();
verificarRolORedirect(['admin', 'chef'], 'login.php');

require_once 'config.php';
require_once 'includes/info_negocio.php';
$conn = getDatabaseConnection();

$mensaje = '';
$tipo_mensaje = '';

// Registrar movimiento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar'])) {
    $ingrediente_id = $_POST['ingrediente_id'];
    $tipo_movimiento = $_POST['tipo_movimiento'];
    $cantidad = $_POST['cantidad'];
    $motivo = $_POST['motivo'];
    $proveedor_id = !empty($_POST['proveedor_id']) ? $_POST['proveedor_id'] : null;
    $usuario_id = $_SESSION['user_id'];
    
    // Obtener stock actual
    $stock_query = $conn->query("SELECT stock_actual FROM ingredientes WHERE id = $ingrediente_id");
    $stock_actual = $stock_query->fetch_assoc()['stock_actual'];
    
    // Calcular nuevo stock
    $stock_nuevo = $stock_actual;
    if ($tipo_movimiento === 'entrada' || $tipo_movimiento === 'ajuste') {
        $stock_nuevo += $cantidad;
    } else {
        $stock_nuevo -= $cantidad;
    }
    
    // Validar que no quede negativo
    if ($stock_nuevo < 0) {
        $mensaje = "Error: El stock no puede ser negativo";
        $tipo_mensaje = "error";
    } else {
        // Registrar movimiento
        $sql = "INSERT INTO movimientos_inventario (ingrediente_id, tipo_movimiento, cantidad, stock_anterior, stock_nuevo, motivo, proveedor_id, usuario_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isdddsii", $ingrediente_id, $tipo_movimiento, $cantidad, $stock_actual, $stock_nuevo, $motivo, $proveedor_id, $usuario_id);
        
        if ($stmt->execute()) {
            // Actualizar stock
            $conn->query("UPDATE ingredientes SET stock_actual = $stock_nuevo WHERE id = $ingrediente_id");
            $mensaje = "Movimiento registrado exitosamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al registrar movimiento: " . $conn->error;
            $tipo_mensaje = "error";
        }
    }
}

// Obtener ingredientes
$ingredientes = $conn->query("SELECT id, nombre, stock_actual, unidad_medida FROM ingredientes WHERE activo = 1 ORDER BY nombre");

// Obtener proveedores
$proveedores = $conn->query("SELECT id, nombre FROM proveedores WHERE activo = 1 ORDER BY nombre");

// Obtener historial de movimientos
$filtro_ingrediente = isset($_GET['ingrediente']) ? $_GET['ingrediente'] : '';
$filtro_tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';

$sql_historial = "SELECT m.*, i.nombre as ingrediente_nombre, i.unidad_medida, u.nombre as usuario_nombre, p.nombre as proveedor_nombre 
                  FROM movimientos_inventario m 
                  JOIN ingredientes i ON m.ingrediente_id = i.id 
                  JOIN usuarios u ON m.usuario_id = u.id 
                  LEFT JOIN proveedores p ON m.proveedor_id = p.id 
                  WHERE 1=1";

if (!empty($filtro_ingrediente)) {
    $sql_historial .= " AND m.ingrediente_id = $filtro_ingrediente";
}
if (!empty($filtro_tipo)) {
    $sql_historial .= " AND m.tipo_movimiento = '$filtro_tipo'";
}

$sql_historial .= " ORDER BY m.fecha_movimiento DESC LIMIT 50";
$historial = $conn->query($sql_historial);
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
    <title>Movimientos de Inventario - <?php echo htmlspecialchars($info_negocio['nombre_restaurante']); ?></title>
</head>
<body>
    <div class="admin-navbar">
        <h1>📝 Movimientos de Inventario</h1>
        <div class="navbar-actions">
            <a href="inventario.php">🏠 Dashboard</a>
            <a href="ingredientes.php">📦 Ingredientes</a>
            <a href="recetas.php">🍽️ Recetas</a>
            <a href="proveedores.php">🏢 Proveedores</a>
            <div class="theme-switcher-container"></div>
        </div>
    </div>

    <div class="admin-container">
        <?php if (!empty($mensaje)): ?>
        <div style="padding: 16px; border-radius: 12px; margin-bottom: 24px; background: <?php echo $tipo_mensaje === 'success' ? '#d1fae5' : '#fee2e2'; ?>; color: <?php echo $tipo_mensaje === 'success' ? '#065f46' : '#991b1b'; ?>; border-left: 4px solid <?php echo $tipo_mensaje === 'success' ? '#10b981' : '#ef4444'; ?>;">
            <?php echo htmlspecialchars($mensaje); ?>
        </div>
        <?php endif; ?>

        <!-- Formulario de Registro -->
        <div class="form-section">
            <h2>➕ Registrar Movimiento</h2>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Ingrediente *</label>
                        <select name="ingrediente_id" required onchange="actualizarStock(this)">
                            <option value="">Seleccionar...</option>
                            <?php while ($ing = $ingredientes->fetch_assoc()): ?>
                            <option value="<?php echo $ing['id']; ?>" data-stock="<?php echo $ing['stock_actual']; ?>" data-unidad="<?php echo $ing['unidad_medida']; ?>">
                                <?php echo htmlspecialchars($ing['nombre']); ?> (Stock: <?php echo $ing['stock_actual']; ?> <?php echo $ing['unidad_medida']; ?>)
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Tipo de Movimiento *</label>
                        <select name="tipo_movimiento" required>
                            <option value="entrada">📥 Entrada</option>
                            <option value="salida">📤 Salida</option>
                            <option value="ajuste">🔧 Ajuste</option>
                            <option value="merma">⚠️ Merma</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Cantidad *</label>
                        <input type="number" step="0.01" name="cantidad" required min="0.01">
                    </div>
                    
                    <div class="form-group">
                        <label>Proveedor (opcional)</label>
                        <select name="proveedor_id">
                            <option value="">Ninguno</option>
                            <?php 
                            $proveedores->data_seek(0);
                            while ($prov = $proveedores->fetch_assoc()): 
                            ?>
                            <option value="<?php echo $prov['id']; ?>"><?php echo htmlspecialchars($prov['nombre']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 16px;">
                    <label>Motivo *</label>
                    <textarea name="motivo" rows="2" required placeholder="Describe el motivo del movimiento..."></textarea>
                </div>
                
                <div style="margin-top: 24px;">
                    <button type="submit" name="registrar" class="btn btn-primary">Registrar Movimiento</button>
                </div>
            </form>
        </div>

        <!-- Filtros -->
        <div class="form-section">
            <h2>🔍 Filtrar Historial</h2>
            <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; align-items: end;">
                <div class="form-group">
                    <label>Ingrediente</label>
                    <select name="ingrediente">
                        <option value="">Todos</option>
                        <?php 
                        $ingredientes->data_seek(0);
                        while ($ing = $ingredientes->fetch_assoc()): 
                        ?>
                        <option value="<?php echo $ing['id']; ?>" <?php echo $filtro_ingrediente == $ing['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($ing['nombre']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tipo</label>
                    <select name="tipo">
                        <option value="">Todos</option>
                        <option value="entrada" <?php echo $filtro_tipo === 'entrada' ? 'selected' : ''; ?>>Entrada</option>
                        <option value="salida" <?php echo $filtro_tipo === 'salida' ? 'selected' : ''; ?>>Salida</option>
                        <option value="ajuste" <?php echo $filtro_tipo === 'ajuste' ? 'selected' : ''; ?>>Ajuste</option>
                        <option value="merma" <?php echo $filtro_tipo === 'merma' ? 'selected' : ''; ?>>Merma</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="movimientos.php" class="btn btn-secondary">Limpiar</a>
            </form>
        </div>

        <!-- Historial -->
        <div class="form-section">
            <h2>📋 Historial de Movimientos</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Ingrediente</th>
                            <th>Tipo</th>
                            <th>Cantidad</th>
                            <th>Stock Anterior</th>
                            <th>Stock Nuevo</th>
                            <th>Motivo</th>
                            <th>Usuario</th>
                            <th>Proveedor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($historial->num_rows > 0): ?>
                            <?php while ($mov = $historial->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($mov['fecha_movimiento'])); ?></td>
                                <td><strong><?php echo htmlspecialchars($mov['ingrediente_nombre']); ?></strong></td>
                                <td>
                                    <?php
                                    $badges = [
                                        'entrada' => ['bg' => '#d1fae5', 'color' => '#065f46', 'icon' => '📥'],
                                        'salida' => ['bg' => '#fee2e2', 'color' => '#991b1b', 'icon' => '📤'],
                                        'ajuste' => ['bg' => '#dbeafe', 'color' => '#1e40af', 'icon' => '🔧'],
                                        'merma' => ['bg' => '#fef3c7', 'color' => '#78350f', 'icon' => '⚠️']
                                    ];
                                    $badge = $badges[$mov['tipo_movimiento']];
                                    ?>
                                    <span style="background: <?php echo $badge['bg']; ?>; color: <?php echo $badge['color']; ?>; padding: 4px 12px; border-radius: 9999px; font-size: 0.8125rem; font-weight: 500;">
                                        <?php echo $badge['icon']; ?> <?php echo ucfirst($mov['tipo_movimiento']); ?>
                                    </span>
                                </td>
                                <td><strong><?php echo number_format($mov['cantidad'], 2); ?> <?php echo $mov['unidad_medida']; ?></strong></td>
                                <td><?php echo number_format($mov['stock_anterior'], 2); ?></td>
                                <td style="color: <?php echo $mov['stock_nuevo'] > $mov['stock_anterior'] ? '#10b981' : '#ef4444'; ?>; font-weight: 600;">
                                    <?php echo number_format($mov['stock_nuevo'], 2); ?>
                                </td>
                                <td><?php echo htmlspecialchars($mov['motivo']); ?></td>
                                <td><?php echo htmlspecialchars($mov['usuario_nombre']); ?></td>
                                <td><?php echo $mov['proveedor_nombre'] ? htmlspecialchars($mov['proveedor_nombre']) : '-'; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="empty-state">No hay movimientos registrados</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="js/theme-manager.js"></script>
</body>
</html>
<?php $conn->close(); ?>
