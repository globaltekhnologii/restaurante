<?php
// admin_clientes.php - Panel de administración de clientes
session_start();
require_once 'config.php';
require_once 'auth_helper.php';
require_once 'includes/clientes_helper.php';

verificarSesion();
verificarRolORedirect(['admin', 'mesero']);

$conn = getDatabaseConnection();

// Manejar acciones
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        switch ($_POST['accion']) {
            case 'agregar':
                $nombre = $conn->real_escape_string($_POST['nombre']);
                $apellido = $conn->real_escape_string($_POST['apellido']);
                $telefono = $conn->real_escape_string($_POST['telefono']);
                $email = $conn->real_escape_string($_POST['email']);
                $direccion = $conn->real_escape_string($_POST['direccion']);
                $ciudad = $conn->real_escape_string($_POST['ciudad']);
                $notas = $conn->real_escape_string($_POST['notas']);
                
                $sql = "INSERT INTO clientes (nombre, apellido, telefono, email, direccion_principal, ciudad, notas) 
                        VALUES ('$nombre', '$apellido', '$telefono', '$email', '$direccion', '$ciudad', '$notas')";
                
                if ($conn->query($sql)) {
                    $mensaje = "Cliente agregado exitosamente";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Error al agregar cliente: " . $conn->error;
                    $tipo_mensaje = "error";
                }
                break;
                
            case 'editar':
                $id = (int)$_POST['id'];
                $nombre = $conn->real_escape_string($_POST['nombre']);
                $apellido = $conn->real_escape_string($_POST['apellido']);
                $telefono = $conn->real_escape_string($_POST['telefono']);
                $email = $conn->real_escape_string($_POST['email']);
                $direccion = $conn->real_escape_string($_POST['direccion']);
                $ciudad = $conn->real_escape_string($_POST['ciudad']);
                $notas = $conn->real_escape_string($_POST['notas']);
                
                $sql = "UPDATE clientes 
                        SET nombre = '$nombre', apellido = '$apellido', telefono = '$telefono', 
                            email = '$email', direccion_principal = '$direccion', ciudad = '$ciudad', notas = '$notas'
                        WHERE id = $id";
                
                if ($conn->query($sql)) {
                    $mensaje = "Cliente actualizado exitosamente";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Error al actualizar cliente: " . $conn->error;
                    $tipo_mensaje = "error";
                }
                break;
                
            case 'eliminar':
                $id = (int)$_POST['id'];
                $sql = "UPDATE clientes SET activo = 0 WHERE id = $id";
                
                if ($conn->query($sql)) {
                    $mensaje = "Cliente eliminado exitosamente";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Error al eliminar cliente: " . $conn->error;
                    $tipo_mensaje = "error";
                }
                break;
        }
    }
}

// Obtener estadísticas
$stats = obtenerEstadisticasClientes($conn);

// Paginación y búsqueda
$busqueda = isset($_GET['buscar']) ? $_GET['buscar'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$where = "WHERE activo = 1";
if (!empty($busqueda)) {
    $busqueda_esc = $conn->real_escape_string($busqueda);
    $where .= " AND (nombre LIKE '%$busqueda_esc%' OR apellido LIKE '%$busqueda_esc%' OR telefono LIKE '%$busqueda_esc%')";
}

$sql_clientes = "SELECT * FROM clientes $where ORDER BY nombre, apellido LIMIT $limit OFFSET $offset";
$result_clientes = $conn->query($sql_clientes);

$sql_total = "SELECT COUNT(*) as total FROM clientes $where";
$total_clientes = $conn->query($sql_total)->fetch_assoc()['total'];
$total_paginas = ceil($total_clientes / $limit);

// Ver detalle de cliente
$cliente_detalle = null;
if (isset($_GET['ver'])) {
    $cliente_detalle = obtenerClientePorId($conn, $_GET['ver']);
    if ($cliente_detalle) {
        $cliente_detalle['direcciones'] = obtenerDireccionesCliente($conn, $_GET['ver']);
        $cliente_detalle['historial'] = obtenerHistorialCliente($conn, $_GET['ver']);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Clientes - Panel Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        
        .top-bar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 15px 30px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        
        .top-bar h1 { font-size: 1.5em; }
        
        .top-bar a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            background: rgba(255,255,255,0.2);
            border-radius: 6px;
            transition: all 0.3s;
        }
        
        .top-bar a:hover { background: rgba(255,255,255,0.35); }
        
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-left: 4px solid #667eea;
        }
        
        .stat-card h3 {
            font-size: 0.95em;
            color: #666;
            margin-bottom: 10px;
        }
        
        .stat-card .value {
            font-size: 2.5em;
            font-weight: bold;
            color: #333;
        }
        
        .controls {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
        }
        
        .search-box {
            flex: 1;
            min-width: 300px;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102,126,234,0.4); }
        
        .btn-success { background: #28a745; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-info { background: #17a2b8; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        
        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        tr:hover { background: #f8f9fa; }
        
        .actions {
            display: flex;
            gap: 8px;
        }
        
        .actions button {
            padding: 6px 12px;
            font-size: 0.9em;
        }
        
        .pagination {
            margin-top: 20px;
            display: flex;
            gap: 8px;
            justify-content: center;
        }
        
        .pagination a, .pagination span {
            padding: 8px 16px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 6px;
            text-decoration: none;
            color: #333;
        }
        
        .pagination a:hover { background: #f0f4ff; }
        .pagination .active { background: #667eea; color: white; border-color: #667eea; }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active { display: flex; }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-header h2 { font-size: 1.5em; }
        
        .close {
            font-size: 2em;
            cursor: pointer;
            color: #999;
        }
        
        .close:hover { color: #333; }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            font-family: inherit;
        }
        
        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .mensaje {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .mensaje.success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .mensaje.error { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        
        .historial-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        .historial-item h4 { margin-bottom: 5px; }
        .historial-item p { color: #666; font-size: 0.9em; margin: 3px 0; }
    </style>
</head>
<body>
    <div class="top-bar">
        <h1>👥 Gestión de Clientes</h1>
        <div>
            <a href="admin.php">← Volver al Panel</a>
        </div>
    </div>
    
    <div class="container">
        <?php if ($mensaje): ?>
        <div class="mensaje <?php echo $tipo_mensaje; ?>">
            <?php echo htmlspecialchars($mensaje); ?>
        </div>
        <?php endif; ?>
        
        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Clientes</h3>
                <div class="value"><?php echo $stats['total_clientes']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Nuevos Este Mes</h3>
                <div class="value"><?php echo $stats['nuevos_mes']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Con Pedidos</h3>
                <div class="value"><?php echo $stats['con_pedidos']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Clientes Activos</h3>
                <div class="value"><?php echo $total_clientes; ?></div>
            </div>
        </div>
        
        <!-- Controles -->
        <div class="controls">
            <form method="GET" style="flex: 1; display: flex; gap: 10px;">
                <input type="text" name="buscar" class="search-box" 
                       placeholder="Buscar por nombre, apellido o teléfono..." 
                       value="<?php echo htmlspecialchars($busqueda); ?>">
                <button type="submit" class="btn btn-secondary">🔍 Buscar</button>
            </form>
            <button class="btn btn-primary" onclick="abrirModalAgregar()">➕ Agregar Cliente</button>
            <a href="?exportar=excel" class="btn btn-success">📊 Exportar Excel</a>
        </div>
        
        <!-- Tabla de clientes -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Teléfono</th>
                        <th>Email</th>
                        <th>Ciudad</th>
                        <th>Pedidos</th>
                        <th>Último Pedido</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($cliente = $result_clientes->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $cliente['id']; ?></td>
                        <td><?php echo htmlspecialchars($cliente['nombre'] . ' ' . ($cliente['apellido'] ?? '')); ?></td>
                        <td><?php echo htmlspecialchars($cliente['telefono'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($cliente['email'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($cliente['ciudad'] ?? ''); ?></td>
                        <td><?php echo $cliente['total_pedidos']; ?></td>
                        <td><?php echo $cliente['ultimo_pedido'] ? date('d/m/Y', strtotime($cliente['ultimo_pedido'])) : '-'; ?></td>
                        <td>
                            <div class="actions">
                                <button class="btn btn-info" onclick="verDetalle(<?php echo $cliente['id']; ?>)">Ver</button>
                                <button class="btn btn-primary" onclick="editarCliente(<?php echo $cliente['id']; ?>)">Editar</button>
                                <button class="btn btn-danger" onclick="eliminarCliente(<?php echo $cliente['id']; ?>)">Eliminar</button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Paginación -->
        <?php if ($total_paginas > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <a href="?page=<?php echo $i; ?><?php echo $busqueda ? '&buscar=' . urlencode($busqueda) : ''; ?>" 
                   class="<?php echo $i == $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Modal Agregar/Editar -->
    <div id="modalCliente" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitulo">Agregar Cliente</h2>
                <span class="close" onclick="cerrarModal()">&times;</span>
            </div>
            <form method="POST" id="formCliente">
                <input type="hidden" name="accion" id="accion" value="agregar">
                <input type="hidden" name="id" id="cliente_id">
                
                <div class="form-group">
                    <label>Nombre *</label>
                    <input type="text" name="nombre" id="nombre" required>
                </div>
                
                <div class="form-group">
                    <label>Apellido</label>
                    <input type="text" name="apellido" id="apellido">
                </div>
                
                <div class="form-group">
                    <label>Teléfono *</label>
                    <input type="text" name="telefono" id="telefono" required>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="email">
                </div>
                
                <div class="form-group">
                    <label>Dirección</label>
                    <input type="text" name="direccion" id="direccion">
                </div>
                
                <div class="form-group">
                    <label>Ciudad</label>
                    <input type="text" name="ciudad" id="ciudad">
                </div>
                
                <div class="form-group">
                    <label>Notas</label>
                    <textarea name="notas" id="notas" rows="3"></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Guardar</button>
            </form>
        </div>
    </div>
    
    <script>
        function abrirModalAgregar() {
            document.getElementById('modalTitulo').textContent = 'Agregar Cliente';
            document.getElementById('accion').value = 'agregar';
            document.getElementById('formCliente').reset();
            document.getElementById('modalCliente').classList.add('active');
        }
        
        function editarCliente(id) {
            fetch(`api/clientes.php?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const c = data.cliente;
                        document.getElementById('modalTitulo').textContent = 'Editar Cliente';
                        document.getElementById('accion').value = 'editar';
                        document.getElementById('cliente_id').value = c.id;
                        document.getElementById('nombre').value = c.nombre;
                        document.getElementById('apellido').value = c.apellido || '';
                        document.getElementById('telefono').value = c.telefono;
                        document.getElementById('email').value = c.email || '';
                        document.getElementById('direccion').value = c.direccion_principal || '';
                        document.getElementById('ciudad').value = c.ciudad || '';
                        document.getElementById('notas').value = c.notas || '';
                        document.getElementById('modalCliente').classList.add('active');
                    }
                });
        }
        
        function eliminarCliente(id) {
            if (confirm('¿Estás seguro de eliminar este cliente?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function verDetalle(id) {
            window.location.href = `?ver=${id}`;
        }
        
        function cerrarModal() {
            document.getElementById('modalCliente').classList.remove('active');
        }
        
        // Cerrar modal al hacer clic fuera
        document.getElementById('modalCliente').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModal();
            }
        });
    </script>
</body>
</html>
<?php

// Exportar a Excel
if (isset($_GET['exportar']) && $_GET['exportar'] === 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="clientes_' . date('Y-m-d') . '.xls"');
    
    $sql_export = "SELECT id, nombre, apellido, telefono, email, direccion_principal, ciudad, 
                   total_pedidos, ultimo_pedido, fecha_registro 
                   FROM clientes WHERE activo = 1 ORDER BY nombre, apellido";
    $result_export = $conn->query($sql_export);
    
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Apellido</th><th>Teléfono</th><th>Email</th><th>Dirección</th><th>Ciudad</th><th>Total Pedidos</th><th>Último Pedido</th><th>Fecha Registro</th></tr>";
    
    while ($row = $result_export->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['nombre'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['apellido'] ?? '') . "</td>";
        echo "<td>" . ($row['telefono'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['email'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['direccion_principal'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['ciudad'] ?? '') . "</td>";
        echo "<td>" . $row['total_pedidos'] . "</td>";
        echo "<td>" . $row['ultimo_pedido'] . "</td>";
        echo "<td>" . $row['fecha_registro']. "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    $conn->close();
    exit;
}

$conn->close();
?>
