<?php
session_start();

// Verificar sesión y rol de administrador
require_once 'auth_helper.php';
verificarSesion();
verificarRolORedirect(['admin'], 'login.php');
require_once 'includes/info_negocio.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - <?php echo htmlspecialchars($info_negocio['nombre_restaurante']); ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        
        /* Navbar Superior */
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
        
        .admin-navbar h1 {
            font-size: 1.5em;
            font-weight: 600;
        }
        
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
        
        /* Contenedor Principal */
        .admin-container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        /* Estadísticas Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .stat-card h3 {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .stat-card .number {
            font-size: 2.5em;
            font-weight: bold;
            color: #667eea;
        }
        
        /* Formulario Mejorado */
        .form-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .form-section h2 {
            color: #333;
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 3px solid #667eea;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 0.95em;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 1em;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .checkbox-group {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .checkbox-item input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .btn {
            padding: 14px 30px;
            border: none;
            border-radius: 6px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102,126,234,0.4);
        }
        
        /* Tabla Mejorada */
        .table-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        
        .table-section h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #667eea;
        }
        
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
            letter-spacing: 1px;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        tbody tr {
            transition: background-color 0.3s;
        }
        
        tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .plato-mini-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75em;
            font-weight: bold;
            margin: 2px;
        }
        
        .badge-popular { background: #ff6b6b; color: white; }
        .badge-nuevo { background: #51cf66; color: white; }
        .badge-vegano { background: #20c997; color: white; }
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn-small {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-edit {
            background: #4CAF50;
            color: white;
        }
        
        .btn-delete {
            background: #f44336;
            color: white;
        }
        
        .btn-small:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state img {
            width: 150px;
            opacity: 0.5;
            margin-bottom: 20px;
        }
        
        /* Mensajes Toast */
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #4CAF50;
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            z-index: 1000;
            display: none;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { transform: translateX(400px); }
            to { transform: translateX(0); }
        }
        /* Animaciones para mensajes */
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
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            50% { transform: translateX(10px); }
            75% { transform: translateX(-5px); }
        }
    
    </style>
</head>
<body>

    <!-- Navbar Superior -->
    <div class="admin-navbar">
        <h1>🍽️ Panel de Administración</h1>
        <div class="navbar-actions">
            <a href="admin_pedidos.php">📦 Pedidos</a>
            <a href="admin_usuarios.php">👥 Usuarios</a>
            <a href="config_pagos.php">💳 Configurar Pagos</a>
            <a href="admin_configuracion.php">⚙️ Configuración</a>
            <a href="ver_qr.php" target="_blank">📱 Acceso Móvil</a>
            <a href="index.php" target="_blank">👁️ Ver Menú</a>
            <a href="logout.php">🚪 Cerrar Sesión</a>
        </div>
    </div>

    <div class="admin-container">
        
        <?php
        // Conexión a la base de datos
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "menu_restaurante";

        $conn = new mysqli($servername, $username, $password, $dbname);

        if ($conn->connect_error) {
            die("Conexión fallida: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");

        // Obtener estadísticas
        $stats = [];
        $stats['total'] = $conn->query("SELECT COUNT(*) as count FROM platos")->fetch_assoc()['count'];
        $stats['populares'] = $conn->query("SELECT COUNT(*) as count FROM platos WHERE popular = 1")->fetch_assoc()['count'];
        $stats['nuevos'] = $conn->query("SELECT COUNT(*) as count FROM platos WHERE nuevo = 1")->fetch_assoc()['count'];
        $stats['veganos'] = $conn->query("SELECT COUNT(*) as count FROM platos WHERE vegano = 1")->fetch_assoc()['count'];
        ?>

        <!-- Tarjetas de Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>📊 Total de Platos</h3>
                <div class="number"><?php echo $stats['total']; ?></div>
            </div>
            <div class="stat-card">
                <h3>⭐ Platos Populares</h3>
                <div class="number"><?php echo $stats['populares']; ?></div>
            </div>
            <div class="stat-card">
                <h3>✨ Platos Nuevos</h3>
                <div class="number"><?php echo $stats['nuevos']; ?></div>
            </div>
            <div class="stat-card">
                <h3>🌱 Platos Veganos</h3>
                <div class="number"><?php echo $stats['veganos']; ?></div>
            </div>
        </div>
        <!-- Mensajes de Feedback -->
        <?php if(isset($_GET['success'])): ?>
        <div style="background: #d4edda; border-left: 4px solid #28a745; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; animation: slideDown 0.5s ease;">
        <strong>✅ ¡Éxito!</strong> El plato ha sido guardado correctamente.
        </div>
        <?php endif; ?>

         <?php if(isset($_GET['deleted'])): ?>
          <div style="background: #d4edda; border-left: 4px solid #28a745; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; animation: slideDown 0.5s ease;">
           <strong>✅ ¡Éxito!</strong> El plato ha sido eliminado correctamente.
            </div>
              <?php endif; ?>

               <?php if(isset($_GET['error'])): ?>
            <div style="background: #f8d7da; border-left: 4px solid #dc3545; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; animation: shake 0.5s ease;">
         <strong>⚠️ Error:</strong> <?php echo htmlspecialchars($_GET['error']); ?>
       </div>
      <?php endif; ?>
        <!-- Formulario para Añadir Plato -->
        <div class="form-section">
            <h2>➕ Añadir Nuevo Plato</h2>
            
            <form action="insertar_plato_con_imagen.php" method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nombre">Nombre del Plato *</label>
                        <input type="text" id="nombre" name="nombre" required>
                    </div>

                    <div class="form-group">
                        <label for="precio">Precio ($) *</label>
                        <input type="number" id="precio" name="precio" step="0.01" required>
                    </div>

                    <div class="form-group">
                        <label for="categoria">Categoría *</label>
                        <select id="categoria" name="categoria" required>
                            <option value="Entradas">🥗 Entradas</option>
                            <option value="Platos Principales">🍖 Platos Principales</option>
                            <option value="Postres">🍰 Postres</option>
                            <option value="Bebidas">🥤 Bebidas</option>
                            <option value="General">📌 General</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="imagen">Imagen del Plato *</label>
                        <input type="file" id="imagen" name="imagen" accept="image/*" required>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 20px;">
                    <label for="descripcion">Descripción *</label>
                    <textarea id="descripcion" name="descripcion" required></textarea>
                </div>

                <div class="form-group" style="margin-top: 20px;">
                    <label>Características Especiales</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="checkbox" id="popular" name="popular" value="1">
                            <label for="popular">⭐ Popular</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="nuevo" name="nuevo" value="1">
                            <label for="nuevo">✨ Nuevo</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="vegano" name="vegano" value="1">
                            <label for="vegano">🌱 Vegano</label>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top: 25px; width: 100%;">
                    💾 Guardar Plato en el Menú
                </button>
            </form>
        </div>

        <!-- Tabla de Platos Existentes -->
        <div class="table-section">
            <h2>📋 Platos Existentes</h2>
            
            <!-- Filtros -->
            <div class="filters">
                <input type="text" id="searchPlato" placeholder="🔍 Buscar plato..." onkeyup="filtrarTabla()">
                <select id="filterCategoria" onchange="filtrarTabla()">
                    <option value="">Todas las categorías</option>
                    <option value="Entradas">Entradas</option>
                    <option value="Platos Principales">Platos Principales</option>
                    <option value="Postres">Postres</option>
                    <option value="Bebidas">Bebidas</option>
                    <option value="General">General</option>
                </select>
            </div>
            
            <?php
            // Consulta mejorada con todas las columnas
            $sql = "SELECT id, nombre, descripcion, precio, imagen_ruta, 
                    COALESCE(categoria, 'General') as categoria,
                    COALESCE(popular, 0) as popular,
                    COALESCE(nuevo, 0) as nuevo,
                    COALESCE(vegano, 0) as vegano
                    FROM platos 
                    ORDER BY categoria ASC, nombre ASC";
            
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                echo '<table id="platosTable">';
                echo '<thead>';
                echo '<tr>';
                echo '<th>Imagen</th>';
                echo '<th>Nombre</th>';
                echo '<th>Categoría</th>';
                echo '<th>Precio</th>';
                echo '<th>Características</th>';
                echo '<th>Acciones</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';
                
                while($row = $result->fetch_assoc()) {
                    echo '<tr data-categoria="' . htmlspecialchars($row["categoria"]) . '" data-nombre="' . htmlspecialchars($row["nombre"]) . '">';
                    
                    // Imagen
                    echo '<td>';
                    if (!empty($row["imagen_ruta"])) {
                        echo '<img src="' . htmlspecialchars($row["imagen_ruta"]) . '" alt="' . htmlspecialchars($row["nombre"]) . '" class="plato-mini-img">';
                    } else {
                        echo '<div style="width:60px;height:60px;background:#eee;border-radius:8px;display:flex;align-items:center;justify-content:center;">📷</div>';
                    }
                    echo '</td>';
                    
                    // Nombre
                    echo '<td><strong>' . htmlspecialchars($row["nombre"]) . '</strong></td>';
                    
                    // Categoría
                    echo '<td>' . htmlspecialchars($row["categoria"]) . '</td>';
                    
                    // Precio
                    echo '<td><strong>$' . number_format($row["precio"], 2, '.', ',') . '</strong></td>';
                    
                    // Badges
                    echo '<td>';
                    if ($row['popular']) echo '<span class="badge badge-popular">⭐ Popular</span>';
                    if ($row['nuevo']) echo '<span class="badge badge-nuevo">✨ Nuevo</span>';
                    if ($row['vegano']) echo '<span class="badge badge-vegano">🌱 Vegano</span>';
                    echo '</td>';
                    
                    // Acciones
                    echo '<td>';
                    echo '<div class="action-buttons">';
                    echo '<a href="editar_plato.php?id=' . $row["id"] . '" class="btn-small btn-edit">✏️ Editar</a>';
                    echo '<a href="borrar_plato.php?id=' . $row["id"] . '" class="btn-small btn-delete" onclick="return confirm(\'¿Estás seguro de eliminar este plato?\')">🗑️ Borrar</a>';
                    echo '</div>';
                    echo '</td>';
                    
                    echo '</tr>';
                }
                
                echo '</tbody>';
                echo '</table>';
            } else {
                echo '<div class="empty-state">';
                echo '<h3>📭 No hay platos en el menú</h3>';
                echo '<p>Comienza agregando tu primer plato usando el formulario de arriba.</p>';
                echo '</div>';
            }

            $conn->close();
            ?>
        </div>

    </div>

    <script>
        // Función para filtrar la tabla
        function filtrarTabla() {
            const searchTerm = document.getElementById('searchPlato').value.toLowerCase();
            const categoriaFilter = document.getElementById('filterCategoria').value;
            const rows = document.querySelectorAll('#platosTable tbody tr');
            
            rows.forEach(row => {
                const nombre = row.dataset.nombre.toLowerCase();
                const categoria = row.dataset.categoria;
                
                const matchSearch = nombre.includes(searchTerm);
                const matchCategoria = !categoriaFilter || categoria === categoriaFilter;
                
                if (matchSearch && matchCategoria) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Mostrar mensaje de éxito si viene de insertar
        <?php if(isset($_GET['success'])): ?>
        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.textContent = '✅ Plato guardado exitosamente';
        toast.style.display = 'block';
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
        <?php endif; ?>
    </script>
    <script>
        // Auto-ocultar mensajes después de 5 segundos
        setTimeout(function() {
            const alerts = document.querySelectorAll('[style*="slideDown"], [style*="shake"]');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>
