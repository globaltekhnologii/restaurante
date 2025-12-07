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

// CRUD de proveedores
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        switch ($_POST['accion']) {
            case 'crear':
                $sql = "INSERT INTO proveedores (nombre, contacto, telefono, email, direccion, notas) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssss", $_POST['nombre'], $_POST['contacto'], $_POST['telefono'], $_POST['email'], $_POST['direccion'], $_POST['notas']);
                
                if ($stmt->execute()) {
                    $mensaje = "Proveedor creado exitosamente";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Error: " . $conn->error;
                    $tipo_mensaje = "error";
                }
                break;
                
            case 'editar':
                $sql = "UPDATE proveedores SET nombre=?, contacto=?, telefono=?, email=?, direccion=?, notas=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssssi", $_POST['nombre'], $_POST['contacto'], $_POST['telefono'], $_POST['email'], $_POST['direccion'], $_POST['notas'], $_POST['id']);
                
                if ($stmt->execute()) {
                    $mensaje = "Proveedor actualizado exitosamente";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Error: " . $conn->error;
                    $tipo_mensaje = "error";
                }
                break;
                
            case 'eliminar':
                $conn->query("UPDATE proveedores SET activo = 0 WHERE id = " . $_POST['id']);
                $mensaje = "Proveedor eliminado exitosamente";
                $tipo_mensaje = "success";
                break;
        }
    }
}

$proveedores = $conn->query("SELECT * FROM proveedores WHERE activo = 1 ORDER BY nombre");
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
    <title>Gestión de Proveedores - <?php echo htmlspecialchars($info_negocio['nombre_restaurante']); ?></title>
</head>
<body>
    <div class="admin-navbar">
        <h1>🏢 Gestión de Proveedores</h1>
        <div class="navbar-actions">
            <a href="inventario.php">🏠 Dashboard</a>
            <a href="ingredientes.php">📦 Ingredientes</a>
            <a href="movimientos.php">📝 Movimientos</a>
            <a href="recetas.php">🍽️ Recetas</a>
            <div class="theme-switcher-container"></div>
        </div>
    </div>

    <div class="admin-container">
        <?php if (!empty($mensaje)): ?>
        <div style="padding: 16px; border-radius: 12px; margin-bottom: 24px; background: <?php echo $tipo_mensaje === 'success' ? '#d1fae5' : '#fee2e2'; ?>; color: <?php echo $tipo_mensaje === 'success' ? '#065f46' : '#991b1b'; ?>; border-left: 4px solid <?php echo $tipo_mensaje === 'success' ? '#10b981' : '#ef4444'; ?>;">
            <?php echo htmlspecialchars($mensaje); ?>
        </div>
        <?php endif; ?>

        <div style="margin-bottom: 24px;">
            <button onclick="mostrarModal()" class="btn btn-primary">+ Nuevo Proveedor</button>
        </div>

        <div class="form-section">
            <h2>📋 Lista de Proveedores</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Contacto</th>
                            <th>Teléfono</th>
                            <th>Email</th>
                            <th>Dirección</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($proveedores->num_rows > 0): ?>
                            <?php while ($prov = $proveedores->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($prov['nombre']); ?></strong></td>
                                <td><?php echo htmlspecialchars($prov['contacto']); ?></td>
                                <td><?php echo htmlspecialchars($prov['telefono']); ?></td>
                                <td><?php echo htmlspecialchars($prov['email']); ?></td>
                                <td><?php echo htmlspecialchars($prov['direccion']); ?></td>
                                <td>
                                    <button onclick='editarProveedor(<?php echo json_encode($prov); ?>)' class="btn-small btn-edit">Editar</button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('¿Eliminar este proveedor?');">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id" value="<?php echo $prov['id']; ?>">
                                        <button type="submit" class="btn-small btn-delete">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="empty-state">No hay proveedores registrados</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div id="modalProveedor" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 16px; padding: 32px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto;">
            <h2 id="modalTitulo">Nuevo Proveedor</h2>
            <form id="formProveedor" method="POST">
                <input type="hidden" name="accion" id="accion" value="crear">
                <input type="hidden" name="id" id="proveedor_id">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Nombre *</label>
                        <input type="text" name="nombre" id="nombre" required>
                    </div>
                    <div class="form-group">
                        <label>Contacto</label>
                        <input type="text" name="contacto" id="contacto">
                    </div>
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="tel" name="telefono" id="telefono">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" id="email">
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 16px;">
                    <label>Dirección</label>
                    <textarea name="direccion" id="direccion" rows="2"></textarea>
                </div>
                
                <div class="form-group" style="margin-top: 16px;">
                    <label>Notas</label>
                    <textarea name="notas" id="notas" rows="3"></textarea>
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
            document.getElementById('modalProveedor').style.display = 'flex';
            document.getElementById('modalTitulo').textContent = 'Nuevo Proveedor';
            document.getElementById('accion').value = 'crear';
            document.getElementById('formProveedor').reset();
        }

        function editarProveedor(prov) {
            document.getElementById('modalProveedor').style.display = 'flex';
            document.getElementById('modalTitulo').textContent = 'Editar Proveedor';
            document.getElementById('accion').value = 'editar';
            document.getElementById('proveedor_id').value = prov.id;
            document.getElementById('nombre').value = prov.nombre;
            document.getElementById('contacto').value = prov.contacto || '';
            document.getElementById('telefono').value = prov.telefono || '';
            document.getElementById('email').value = prov.email || '';
            document.getElementById('direccion').value = prov.direccion || '';
            document.getElementById('notas').value = prov.notas || '';
        }

        function cerrarModal() {
            document.getElementById('modalProveedor').style.display = 'none';
        }

        document.getElementById('modalProveedor').addEventListener('click', function(e) {
            if (e.target === this) cerrarModal();
        });
    </script>

    <script src="js/theme-manager.js"></script>
</body>
</html>
<?php $conn->close(); ?>
