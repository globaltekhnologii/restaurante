<?php
session_start();
require_once 'auth_helper.php';
verificarSesion();
verificarRolORedirect(['admin'], 'login.php');

require_once 'config.php';
require_once 'includes/info_negocio.php';
$conn = getDatabaseConnection();

// Manejar acciones CRUD
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        switch ($_POST['accion']) {
            case 'crear':
                $nombre = $_POST['nombre'];
                $descripcion = $_POST['descripcion'];
                $categoria = $_POST['categoria'];
                $unidad_medida = $_POST['unidad_medida'];
                $stock_actual = $_POST['stock_actual'];
                $stock_minimo = $_POST['stock_minimo'];
                $stock_maximo = $_POST['stock_maximo'];
                $precio_unitario = $_POST['precio_unitario'];
                
                $sql = "INSERT INTO ingredientes (nombre, descripcion, categoria, unidad_medida, stock_actual, stock_minimo, stock_maximo, precio_unitario) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssdddd", $nombre, $descripcion, $categoria, $unidad_medida, $stock_actual, $stock_minimo, $stock_maximo, $precio_unitario);
                
                if ($stmt->execute()) {
                    $mensaje = "Ingrediente creado exitosamente";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Error al crear ingrediente: " . $conn->error;
                    $tipo_mensaje = "error";
                }
                break;
                
            case 'editar':
                $id = $_POST['id'];
                $nombre = $_POST['nombre'];
                $descripcion = $_POST['descripcion'];
                $categoria = $_POST['categoria'];
                $unidad_medida = $_POST['unidad_medida'];
                $stock_minimo = $_POST['stock_minimo'];
                $stock_maximo = $_POST['stock_maximo'];
                $precio_unitario = $_POST['precio_unitario'];
                
                $sql = "UPDATE ingredientes SET nombre=?, descripcion=?, categoria=?, unidad_medida=?, stock_minimo=?, stock_maximo=?, precio_unitario=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssddd i", $nombre, $descripcion, $categoria, $unidad_medida, $stock_minimo, $stock_maximo, $precio_unitario, $id);
                
                if ($stmt->execute()) {
                    $mensaje = "Ingrediente actualizado exitosamente";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Error al actualizar ingrediente: " . $conn->error;
                    $tipo_mensaje = "error";
                }
                break;
                
            case 'eliminar':
                $id = $_POST['id'];
                $sql = "UPDATE ingredientes SET activo = 0 WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $mensaje = "Ingrediente eliminado exitosamente";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Error al eliminar ingrediente: " . $conn->error;
                    $tipo_mensaje = "error";
                }
                break;
        }
    }
}

// Obtener filtros
$filtro_categoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$buscar = isset($_GET['buscar']) ? $_GET['buscar'] : '';

// Construir query
$sql = "SELECT * FROM ingredientes WHERE activo = 1";
$params = [];
$types = "";

if (!empty($buscar)) {
    $sql .= " AND nombre LIKE ?";
    $params[] = "%$buscar%";
    $types .= "s";
}

if (!empty($filtro_categoria)) {
    $sql .= " AND categoria = ?";
    $params[] = $filtro_categoria;
    $types .= "s";
}

if ($filtro_estado === 'critico') {
    $sql .= " AND stock_actual <= stock_minimo";
} elseif ($filtro_estado === 'agotado') {
    $sql .= " AND stock_actual = 0";
} elseif ($filtro_estado === 'normal') {
    $sql .= " AND stock_actual > stock_minimo";
}

$sql .= " ORDER BY nombre ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$ingredientes = $stmt->get_result();

// Obtener categorías únicas
$categorias = $conn->query("SELECT DISTINCT categoria FROM ingredientes WHERE activo = 1 ORDER BY categoria");
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
    <title>Gestión de Ingredientes - <?php echo htmlspecialchars($info_negocio['nombre_restaurante']); ?></title>
</head>
<body>
    <!-- Navbar -->
    <div class="admin-navbar">
        <h1>📦 Gestión de Ingredientes</h1>
        <div class="navbar-actions">
            <a href="inventario.php">🏠 Dashboard</a>
            <a href="movimientos.php">📝 Movimientos</a>
            <a href="recetas.php">🍽️ Recetas</a>
            <a href="proveedores.php">🏢 Proveedores</a>
            <a href="admin.php">⬅️ Volver</a>
            <div class="theme-switcher-container"></div>
        </div>
    </div>

    <div class="admin-container">
        <!-- Mensaje -->
        <?php if (!empty($mensaje)): ?>
        <div style="padding: 16px; border-radius: 12px; margin-bottom: 24px; background: <?php echo $tipo_mensaje === 'success' ? '#d1fae5' : '#fee2e2'; ?>; color: <?php echo $tipo_mensaje === 'success' ? '#065f46' : '#991b1b'; ?>; border-left: 4px solid <?php echo $tipo_mensaje === 'success' ? '#10b981' : '#ef4444'; ?>;">
            <?php echo htmlspecialchars($mensaje); ?>
        </div>
        <?php endif; ?>

        <!-- Filtros y Búsqueda -->
        <div class="form-section">
            <h2>🔍 Filtros</h2>
            <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; align-items: end;">
                <div class="form-group">
                    <label>Buscar</label>
                    <input type="text" name="buscar" placeholder="Nombre del ingrediente" value="<?php echo htmlspecialchars($buscar); ?>">
                </div>
                <div class="form-group">
                    <label>Categoría</label>
                    <select name="categoria">
                        <option value="">Todas</option>
                        <?php while ($cat = $categorias->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($cat['categoria']); ?>" <?php echo $filtro_categoria === $cat['categoria'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['categoria']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Estado</label>
                    <select name="estado">
                        <option value="">Todos</option>
                        <option value="normal" <?php echo $filtro_estado === 'normal' ? 'selected' : ''; ?>>Normal</option>
                        <option value="critico" <?php echo $filtro_estado === 'critico' ? 'selected' : ''; ?>>Crítico</option>
                        <option value="agotado" <?php echo $filtro_estado === 'agotado' ? 'selected' : ''; ?>>Agotado</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="ingredientes.php" class="btn btn-secondary">Limpiar</a>
            </form>
        </div>

        <!-- Botón Nuevo Ingrediente -->
        <div style="margin-bottom: 24px;">
            <button onclick="mostrarModal()" class="btn btn-primary">+ Nuevo Ingrediente</button>
        </div>

        <!-- Tabla de Ingredientes -->
        <div class="form-section">
            <h2>📋 Lista de Ingredientes</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Stock Actual</th>
                            <th>Mínimo</th>
                            <th>Máximo</th>
                            <th>Unidad</th>
                            <th>Precio Unit.</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($ingredientes->num_rows > 0): ?>
                            <?php while ($ing = $ingredientes->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($ing['nombre']); ?></strong></td>
                                <td><?php echo htmlspecialchars($ing['categoria']); ?></td>
                                <td style="font-weight: 600; color: <?php 
                                    if ($ing['stock_actual'] == 0) echo '#ef4444';
                                    elseif ($ing['stock_actual'] <= $ing['stock_minimo']) echo '#f59e0b';
                                    else echo '#10b981';
                                ?>">
                                    <?php echo number_format($ing['stock_actual'], 2); ?>
                                </td>
                                <td><?php echo number_format($ing['stock_minimo'], 2); ?></td>
                                <td><?php echo number_format($ing['stock_maximo'], 2); ?></td>
                                <td><?php echo $ing['unidad_medida']; ?></td>
                                <td>$<?php echo number_format($ing['precio_unitario'], 0); ?></td>
                                <td>
                                    <?php if ($ing['stock_actual'] == 0): ?>
                                        <span style="background: #fee2e2; color: #991b1b; padding: 4px 12px; border-radius: 9999px; font-size: 0.8125rem; font-weight: 500;">Agotado</span>
                                    <?php elseif ($ing['stock_actual'] <= $ing['stock_minimo']): ?>
                                        <span style="background: #fef3c7; color: #78350f; padding: 4px 12px; border-radius: 9999px; font-size: 0.8125rem; font-weight: 500;">Crítico</span>
                                    <?php else: ?>
                                        <span style="background: #d1fae5; color: #065f46; padding: 4px 12px; border-radius: 9999px; font-size: 0.8125rem; font-weight: 500;">Normal</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button onclick='editarIngrediente(<?php echo json_encode($ing); ?>)' class="btn-small btn-edit">Editar</button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('¿Eliminar este ingrediente?');">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id" value="<?php echo $ing['id']; ?>">
                                        <button type="submit" class="btn-small btn-delete">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="empty-state">No se encontraron ingredientes</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Crear/Editar -->
    <div id="modalIngrediente" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 16px; padding: 32px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto;">
            <h2 id="modalTitulo" style="margin-bottom: 24px;">Nuevo Ingrediente</h2>
            <form id="formIngrediente" method="POST">
                <input type="hidden" name="accion" id="accion" value="crear">
                <input type="hidden" name="id" id="ingrediente_id">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Nombre *</label>
                        <input type="text" name="nombre" id="nombre" required>
                    </div>
                    <div class="form-group">
                        <label>Categoría *</label>
                        <input type="text" name="categoria" id="categoria" required>
                    </div>
                    <div class="form-group">
                        <label>Unidad de Medida *</label>
                        <select name="unidad_medida" id="unidad_medida" required>
                            <option value="kg">Kilogramos (kg)</option>
                            <option value="gramos">Gramos (g)</option>
                            <option value="litros">Litros (L)</option>
                            <option value="ml">Mililitros (ml)</option>
                            <option value="unidades">Unidades</option>
                        </select>
                    </div>
                    <div class="form-group" id="stock_actual_group">
                        <label>Stock Actual *</label>
                        <input type="number" step="0.01" name="stock_actual" id="stock_actual" value="0" required>
                    </div>
                    <div class="form-group">
                        <label>Stock Mínimo *</label>
                        <input type="number" step="0.01" name="stock_minimo" id="stock_minimo" value="0" required>
                    </div>
                    <div class="form-group">
                        <label>Stock Máximo *</label>
                        <input type="number" step="0.01" name="stock_maximo" id="stock_maximo" value="0" required>
                    </div>
                    <div class="form-group">
                        <label>Precio Unitario *</label>
                        <input type="number" step="0.01" name="precio_unitario" id="precio_unitario" value="0" required>
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 16px;">
                    <label>Descripción</label>
                    <textarea name="descripcion" id="descripcion" rows="3"></textarea>
                </div>
                
                <div style="display: flex; gap: 12px; margin-top: 24px; justify-content: flex-end;">
                    <button type="button" onclick="cerrarModal()" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function mostrarModal() {
            document.getElementById('modalIngrediente').style.display = 'flex';
            document.getElementById('modalTitulo').textContent = 'Nuevo Ingrediente';
            document.getElementById('accion').value = 'crear';
            document.getElementById('formIngrediente').reset();
            document.getElementById('stock_actual_group').style.display = 'block';
        }

        function editarIngrediente(ing) {
            document.getElementById('modalIngrediente').style.display = 'flex';
            document.getElementById('modalTitulo').textContent = 'Editar Ingrediente';
            document.getElementById('accion').value = 'editar';
            document.getElementById('ingrediente_id').value = ing.id;
            document.getElementById('nombre').value = ing.nombre;
            document.getElementById('descripcion').value = ing.descripcion || '';
            document.getElementById('categoria').value = ing.categoria;
            document.getElementById('unidad_medida').value = ing.unidad_medida;
            document.getElementById('stock_minimo').value = ing.stock_minimo;
            document.getElementById('stock_maximo').value = ing.stock_maximo;
            document.getElementById('precio_unitario').value = ing.precio_unitario;
            document.getElementById('stock_actual_group').style.display = 'none';
        }

        function cerrarModal() {
            document.getElementById('modalIngrediente').style.display = 'none';
        }

        // Cerrar modal al hacer clic fuera
        document.getElementById('modalIngrediente').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModal();
            }
        });
    </script>

    <!-- Theme Manager -->
    <script src="js/theme-manager.js"></script>
</body>
</html>
<?php $conn->close(); ?>
