<?php
session_start();

// Verificar sesión y rol de administrador
require_once 'auth_helper.php';
verificarSesion();
verificarRolORedirect(['admin'], 'login.php');

require_once 'config.php';
require_once 'includes/csrf_helper.php';
require_once 'includes/tenant_context.php'; // NUEVO: Soporte multi-tenencia

$conn = getDatabaseConnection();

// Obtener tenant_id del usuario actual
$tenant_id = getCurrentTenantId();

// Obtener estadísticas de usuarios (FILTRADO POR TENANT)
$stats = [];
$stats['total'] = $conn->query("SELECT COUNT(*) as count FROM usuarios WHERE tenant_id = $tenant_id")->fetch_assoc()['count'];
$stats['admins'] = $conn->query("SELECT COUNT(*) as count FROM usuarios WHERE tenant_id = $tenant_id AND rol = 'admin'")->fetch_assoc()['count'];
$stats['meseros'] = $conn->query("SELECT COUNT(*) as count FROM usuarios WHERE tenant_id = $tenant_id AND rol = 'mesero'")->fetch_assoc()['count'];
$stats['chefs'] = $conn->query("SELECT COUNT(*) as count FROM usuarios WHERE tenant_id = $tenant_id AND rol = 'chef'")->fetch_assoc()['count'];
$stats['cajeros'] = $conn->query("SELECT COUNT(*) as count FROM usuarios WHERE tenant_id = $tenant_id AND rol = 'cajero'")->fetch_assoc()['count'];
$stats['domiciliarios'] = $conn->query("SELECT COUNT(*) as count FROM usuarios WHERE tenant_id = $tenant_id AND rol = 'domiciliario'")->fetch_assoc()['count'];
$stats['activos'] = $conn->query("SELECT COUNT(*) as count FROM usuarios WHERE tenant_id = $tenant_id AND activo = 1")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Restaurante El Sabor</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        
        /* Navbar */
        .admin-navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .admin-navbar h1 { font-size: 1.5em; font-weight: 600; }
        
        .navbar-actions {
            display: flex;
            gap: 15px;
        }
        
        .navbar-actions a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            background: rgba(255,255,255,0.2);
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .navbar-actions a:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }
        
        /* Container */
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .stat-card h3 {
            font-size: 0.85em;
            color: #666;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .stat-card .number {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
        }
        
        /* Section */
        .section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .section h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #667eea;
        }
        
        /* Buttons */
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102,126,234,0.4);
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 0.9em;
        }
        
        .btn-edit {
            background: #4CAF50;
            color: white;
        }
        
        .btn-delete {
            background: #f44336;
            color: white;
        }
        
        .btn-activate {
            background: #2196F3;
            color: white;
        }
        
        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85em;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        /* Badges */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75em;
            font-weight: bold;
        }
        
        .badge-admin { background: #667eea; color: white; }
        .badge-mesero { background: #48bb78; color: white; }
        .badge-chef { background: #ed8936; color: white; }
        .badge-cajero { background: #9F7AEA; color: white; } /* Purple for Cajero */
        .badge-domiciliario { background: #4299e1; color: white; }
        .badge-activo { background: #51cf66; color: white; }
        .badge-inactivo { background: #868e96; color: white; }
        
        /* Filters */
        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filters input,
        .filters select {
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 1em;
        }
        
        /* Messages */
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            animation: slideDown 0.5s ease;
        }
        
        .message-success {
            background: #d4edda;
            border-left: 4px solid #28a745;
            color: #155724;
        }
        
        .message-error {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            color: #721c24;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <div class="admin-navbar">
        <h1>👥 Gestión de Usuarios</h1>
        <div class="navbar-actions">
            <a href="admin.php">🍽️ Platos</a>
            <a href="admin_pedidos.php">📦 Pedidos</a>
            <a href="index.php" target="_blank">👁️ Ver Menú</a>
            <a href="logout.php">🚪 Cerrar Sesión</a>
        </div>
    </div>

    <div class="container">
        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>👥 Total Usuarios</h3>
                <div class="number"><?php echo $stats['total']; ?></div>
            </div>
            <div class="stat-card">
                <h3>👨‍💼 Administradores</h3>
                <div class="number"><?php echo $stats['admins']; ?></div>
            </div>
            <div class="stat-card">
                <h3>🍽️ Meseros</h3>
                <div class="number"><?php echo $stats['meseros']; ?></div>
            </div>
            <div class="stat-card">
                <h3>👨‍🍳 Chefs</h3>
                <div class="number"><?php echo $stats['chefs']; ?></div>
            </div>
            <div class="stat-card">
                <h3>💰 Cajeros</h3>
                <div class="number"><?php echo $stats['cajeros']; ?></div>
            </div>
            <div class="stat-card">
                <h3>🏍️ Domiciliarios</h3>
                <div class="number"><?php echo $stats['domiciliarios']; ?></div>
            </div>
            <div class="stat-card">
                <h3>✅ Activos</h3>
                <div class="number"><?php echo $stats['activos']; ?></div>
            </div>
        </div>

        <!-- Mensajes -->
        <?php if(isset($_GET['success'])): ?>
        <div class="message message-success">
            <strong>✅ ¡Éxito!</strong> <?php echo htmlspecialchars($_GET['success']); ?>
        </div>
        <?php endif; ?>

        <?php if(isset($_GET['error'])): ?>
        <div class="message message-error">
            <strong>⚠️ Error:</strong> <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
        <?php endif; ?>

        <!-- Botón Agregar Usuario -->
        <div style="margin-bottom: 20px;">
            <a href="#" onclick="mostrarFormulario()" class="btn btn-primary">➕ Agregar Nuevo Usuario</a>
        </div>

        <!-- Formulario Nuevo Usuario (oculto por defecto) -->
        <div id="formNuevoUsuario" class="section" style="display: none;">
            <h2>➕ Nuevo Usuario</h2>
            <form action="insertar_usuario.php" method="POST">
                <?php echo csrf_field(); ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">Usuario *</label>
                        <input type="text" name="usuario" required style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 6px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">Nombre Completo *</label>
                        <input type="text" name="nombre" required style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 6px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">Email</label>
                        <input type="email" name="email" style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 6px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">Teléfono</label>
                        <input type="tel" name="telefono" placeholder="Para domiciliarios" style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 6px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">Rol *</label>
                        <select name="rol" required style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 6px;">
                            <option value="mesero">🍽️ Mesero</option>
                            <option value="chef">👨‍🍳 Chef</option>
                            <option value="cajero">💰 Cajero</option>
                            <option value="domiciliario">🏍️ Domiciliario</option>
                            <option value="admin">👨‍💼 Administrador</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">Contraseña *</label>
                        <input type="password" name="clave" required minlength="6" style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 6px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">Confirmar Contraseña *</label>
                        <input type="password" name="clave_confirm" required minlength="6" style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 6px;">
                    </div>
                </div>
                <div style="margin-top: 20px; display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary">💾 Guardar Usuario</button>
                    <button type="button" onclick="ocultarFormulario()" class="btn" style="background: #868e96; color: white;">❌ Cancelar</button>
                </div>
            </form>
        </div>

        <!-- Lista de Usuarios -->
        <div class="section">
            <h2>📋 Usuarios del Sistema</h2>
            
            <!-- Filtros -->
            <div class="filters">
                <input type="text" id="searchUser" placeholder="🔍 Buscar usuario..." onkeyup="filtrarTabla()">
                <select id="filterRol" onchange="filtrarTabla()">
                    <option value="">Todos los roles</option>
                    <option value="admin">Administrador</option>
                    <option value="mesero">Mesero</option>
                    <option value="chef">Chef</option>
                    <option value="cajero">Cajero</option>
                    <option value="domiciliario">Domiciliario</option>
                </select>
                <select id="filterEstado" onchange="filtrarTabla()">
                    <option value="">Todos los estados</option>
                    <option value="1">Activos</option>
                    <option value="0">Inactivos</option>
                </select>
            </div>

            <?php
            // Obtener todos los usuarios (FILTRADO POR TENANT)
            $sql = "SELECT id, usuario, nombre, email, telefono, rol, activo, fecha_creacion, ultimo_acceso 
                    FROM usuarios 
                    WHERE tenant_id = $tenant_id
                    ORDER BY rol ASC, nombre ASC";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                echo '<table id="usuariosTable">';
                echo '<thead>';
                echo '<tr>';
                echo '<th>Usuario</th>';
                echo '<th>Nombre</th>';
                echo '<th>Email</th>';
                echo '<th>Teléfono</th>';
                echo '<th>Rol</th>';
                echo '<th>Estado</th>';
                echo '<th>Último Acceso</th>';
                echo '<th>Acciones</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';
                
                while($row = $result->fetch_assoc()) {
                    $rolClass = 'badge-' . $row['rol'];
                    $estadoClass = $row['activo'] ? 'badge-activo' : 'badge-inactivo';
                    $estadoTexto = $row['activo'] ? 'Activo' : 'Inactivo';
                    $rolIcono = getIconoRol($row['rol']);
                    $rolNombre = getNombreRol($row['rol']);
                    
                    echo '<tr data-usuario="' . htmlspecialchars($row['usuario']) . '" data-rol="' . $row['rol'] . '" data-activo="' . $row['activo'] . '">';
                    echo '<td><strong>' . htmlspecialchars($row['usuario']) . '</strong></td>';
                    echo '<td>' . htmlspecialchars($row['nombre']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['email']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['telefono'] ?? '-') . '</td>';
                    echo '<td><span class="badge ' . $rolClass . '">' . $rolIcono . ' ' . $rolNombre . '</span></td>';
                    echo '<td><span class="badge ' . $estadoClass . '">' . $estadoTexto . '</span></td>';
                    echo '<td>' . ($row['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($row['ultimo_acceso'])) : 'Nunca') . '</td>';
                    echo '<td>';
                    echo '<div class="action-buttons">';
                    echo '<a href="editar_usuario.php?id=' . $row['id'] . '" class="btn btn-small btn-edit">✏️ Editar</a>';
                    
                    // No permitir desactivar/eliminar al último admin
                    if ($row['rol'] != 'admin' || $stats['admins'] > 1) {
                        if ($row['activo']) {
                            // Cambio: Formulario POST para desactivar
                            echo '<form action="toggle_usuario.php" method="POST" style="display:inline;" onsubmit="return confirm(\'¿Desactivar este usuario?\')">';
                            echo '<input type="hidden" name="id" value="' . $row['id'] . '">';
                            echo '<input type="hidden" name="accion" value="desactivar">';
                            echo csrf_field();
                            echo '<button type="submit" class="btn btn-small btn-delete">🚫 Desactivar</button>';
                            echo '</form>';
                        } else {
                            // Cambio: Formulario POST para activar
                            echo '<form action="toggle_usuario.php" method="POST" style="display:inline;">';
                            echo '<input type="hidden" name="id" value="' . $row['id'] . '">';
                            echo '<input type="hidden" name="accion" value="activar">';
                            echo csrf_field();
                            echo '<button type="submit" class="btn btn-small btn-activate">✅ Activar</button>';
                            echo '</form>';
                        }
                    }
                    
                    echo '</div>';
                    echo '</td>';
                    echo '</tr>';
                }
                
                echo '</tbody>';
                echo '</table>';
            } else {
                echo '<p style="text-align: center; color: #999; padding: 40px;">No hay usuarios en el sistema.</p>';
            }

            $conn->close();
            ?>
        </div>
    </div>

    <script>
        function mostrarFormulario() {
            document.getElementById('formNuevoUsuario').style.display = 'block';
            document.getElementById('formNuevoUsuario').scrollIntoView({ behavior: 'smooth' });
        }

        function ocultarFormulario() {
            document.getElementById('formNuevoUsuario').style.display = 'none';
        }

        function filtrarTabla() {
            const searchTerm = document.getElementById('searchUser').value.toLowerCase();
            const rolFilter = document.getElementById('filterRol').value;
            const estadoFilter = document.getElementById('filterEstado').value;
            const rows = document.querySelectorAll('#usuariosTable tbody tr');
            
            rows.forEach(row => {
                const usuario = row.dataset.usuario.toLowerCase();
                const rol = row.dataset.rol;
                const activo = row.dataset.activo;
                
                const matchSearch = usuario.includes(searchTerm);
                const matchRol = !rolFilter || rol === rolFilter;
                const matchEstado = !estadoFilter || activo === estadoFilter;
                
                if (matchSearch && matchRol && matchEstado) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Auto-ocultar mensajes después de 5 segundos
        setTimeout(function() {
            const messages = document.querySelectorAll('.message');
            messages.forEach(msg => {
                msg.style.transition = 'opacity 0.5s ease';
                msg.style.opacity = '0';
                setTimeout(() => msg.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>
