<?php
session_start();
require_once 'auth_helper.php';
verificarSesion();
verificarRolORedirect(['admin'], 'login.php');

require_once 'config.php';
require_once 'includes/info_negocio.php';
$conn = getDatabaseConnection();

$mensaje = '';
$tipo_mensaje = '';

// Manejar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['agregar_ingrediente'])) {
        $plato_id = $_POST['plato_id'];
        $ingrediente_id = $_POST['ingrediente_id'];
        $cantidad = $_POST['cantidad'];
        $unidad = $_POST['unidad'];
        
        $sql = "INSERT INTO recetas (plato_id, ingrediente_id, cantidad_necesaria, unidad_medida) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iids", $plato_id, $ingrediente_id, $cantidad, $unidad);
        
        if ($stmt->execute()) {
            $mensaje = "Ingrediente agregado a la receta";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error: " . $conn->error;
            $tipo_mensaje = "error";
        }
    } elseif (isset($_POST['eliminar'])) {
        $id = $_POST['id'];
        $conn->query("DELETE FROM recetas WHERE id = $id");
        $mensaje = "Ingrediente eliminado de la receta";
        $tipo_mensaje = "success";
    }
}

// Obtener platos
$platos = $conn->query("SELECT id, nombre, precio FROM platos ORDER BY nombre");

// Obtener ingredientes
$ingredientes = $conn->query("SELECT id, nombre, unidad_medida, stock_actual FROM ingredientes WHERE activo = 1 ORDER BY nombre");

// Obtener plato seleccionado
$plato_seleccionado = isset($_GET['plato']) ? $_GET['plato'] : '';
$receta_actual = null;
if (!empty($plato_seleccionado)) {
    $plato_id_safe = intval($plato_seleccionado);
    $sql = "SELECT r.*, i.nombre as ingrediente_nombre, i.stock_actual, i.unidad_medida as unidad_stock 
            FROM recetas r 
            JOIN ingredientes i ON r.ingrediente_id = i.id 
            WHERE r.plato_id = $plato_id_safe";
    $receta_actual = $conn->query($sql);
}
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
    <title>Gestión de Recetas - <?php echo htmlspecialchars($info_negocio['nombre_restaurante']); ?></title>
</head>
<body>
    <div class="admin-navbar">
        <h1>🍽️ Gestión de Recetas</h1>
        <div class="navbar-actions">
            <a href="inventario.php">🏠 Dashboard</a>
            <a href="ingredientes.php">📦 Ingredientes</a>
            <a href="movimientos.php">📝 Movimientos</a>
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

        <!-- Selector de Plato -->
        <div class="form-section">
            <h2>🍽️ Seleccionar Plato</h2>
            <form method="GET" style="display: grid; grid-template-columns: 1fr auto; gap: 16px; align-items: end;">
                <div class="form-group">
                    <label>Plato</label>
                    <select name="plato" onchange="this.form.submit()">
                        <option value="">Seleccionar plato...</option>
                        <?php while ($plato = $platos->fetch_assoc()): ?>
                        <option value="<?php echo $plato['id']; ?>" <?php echo $plato_seleccionado == $plato['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($plato['nombre']); ?> - $<?php echo number_format($plato['precio'], 0); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </form>
        </div>

        <?php if (!empty($plato_seleccionado)): ?>
        <!-- Agregar Ingrediente -->
        <div class="form-section">
            <h2>➕ Agregar Ingrediente a la Receta</h2>
            <form method="POST">
                <input type="hidden" name="plato_id" value="<?php echo $plato_seleccionado; ?>">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Ingrediente *</label>
                        <select name="ingrediente_id" required>
                            <option value="">Seleccionar...</option>
                            <?php 
                            $ingredientes->data_seek(0);
                            while ($ing = $ingredientes->fetch_assoc()): 
                            ?>
                            <option value="<?php echo $ing['id']; ?>">
                                <?php echo htmlspecialchars($ing['nombre']); ?> (Stock: <?php echo $ing['stock_actual']; ?> <?php echo $ing['unidad_medida']; ?>)
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Cantidad *</label>
                        <input type="number" step="0.01" name="cantidad" required min="0.01">
                    </div>
                    <div class="form-group">
                        <label>Unidad *</label>
                        <select name="unidad" required>
                            <option value="kg">Kilogramos (kg)</option>
                            <option value="gramos">Gramos (g)</option>
                            <option value="litros">Litros (L)</option>
                            <option value="ml">Mililitros (ml)</option>
                            <option value="unidades">Unidades</option>
                        </select>
                    </div>
                </div>
                <div style="margin-top: 16px;">
                    <button type="submit" name="agregar_ingrediente" class="btn btn-primary">Agregar Ingrediente</button>
                </div>
            </form>
        </div>

        <!-- Receta Actual -->
        <div class="form-section">
            <h2>📋 Ingredientes de la Receta</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Ingrediente</th>
                            <th>Cantidad Necesaria</th>
                            <th>Stock Disponible</th>
                            <th>Porciones Posibles</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($receta_actual && $receta_actual->num_rows > 0): ?>
                            <?php while ($rec = $receta_actual->fetch_assoc()): ?>
                            <?php 
                                $porciones = 0;
                                if ($rec['cantidad_necesaria'] > 0) {
                                    $porciones = floor($rec['stock_actual'] / $rec['cantidad_necesaria']); 
                                }
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($rec['ingrediente_nombre']); ?></strong></td>
                                <td><?php echo number_format($rec['cantidad_necesaria'], 2); ?> <?php echo $rec['unidad_medida']; ?></td>
                                <td style="color: <?php echo $rec['stock_actual'] < $rec['cantidad_necesaria'] ? '#ef4444' : '#10b981'; ?>; font-weight: 600;">
                                    <?php echo number_format($rec['stock_actual'], 2); ?> <?php echo $rec['unidad_stock']; ?>
                                </td>
                                <td style="font-weight: 600;">
                                    <?php echo $porciones; ?> porciones
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('¿Eliminar este ingrediente?');">
                                        <input type="hidden" name="id" value="<?php echo $rec['id']; ?>">
                                        <button type="submit" name="eliminar" class="btn-small btn-delete">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="empty-state">No hay ingredientes en esta receta</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else: ?>
        <div class="empty-state" style="padding: 60px; text-align: center;">
            <p style="font-size: 1.2em; color: #6b7280;">Selecciona un plato para gestionar su receta</p>
        </div>
        <?php endif; ?>
    </div>

    <script src="js/theme-manager.js"></script>
</body>
</html>
<?php $conn->close(); ?>
