<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== TRUE) {
    header("Location: login.php");
    exit;
}

// Verificar que se haya pasado un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: admin.php");
    exit;
}

$id_plato = $_GET['id'];

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

// Obtener datos del plato
$stmt = $conn->prepare("SELECT * FROM platos WHERE id = ?");
$stmt->bind_param("i", $id_plato);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: admin.php");
    exit;
}

$plato = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Plato - <?php echo htmlspecialchars($plato['nombre']); ?></title>
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
        .edit-container {
            max-width: 900px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        /* Breadcrumb */
        .breadcrumb {
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .breadcrumb a {
            color: #667eea;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .breadcrumb a:hover {
            color: #764ba2;
        }
        
        .breadcrumb span {
            color: #999;
            margin: 0 10px;
        }
        
        /* Card Principal */
        .edit-card {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .card-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #667eea;
        }
        
        .card-header h2 {
            color: #333;
            font-size: 2em;
            margin-left: 15px;
        }
        
        .card-icon {
            font-size: 2.5em;
        }
        
        /* Vista Previa de Imagen */
        .image-preview-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .current-image {
            max-width: 300px;
            max-height: 200px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin-bottom: 15px;
        }
        
        .image-info {
            color: #666;
            font-size: 0.9em;
        }
        
        /* Formulario */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 25px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            font-size: 1em;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 14px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            transition: all 0.3s;
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        /* Checkboxes con diseño moderno */
        .checkbox-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
        }
        
        .checkbox-section h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.1em;
        }
        
        .checkbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }
        
        .checkbox-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .checkbox-card input[type="checkbox"] {
            width: 22px;
            height: 22px;
            cursor: pointer;
            accent-color: #667eea;
        }
        
        .checkbox-card label {
            cursor: pointer;
            font-weight: 500;
            flex: 1;
            margin: 0 !important;
        }
        
        .checkbox-card.checked {
            background: linear-gradient(135deg, rgba(102,126,234,0.1) 0%, rgba(118,75,162,0.1) 100%);
            border-color: #667eea;
        }
        
        /* Botones */
        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            flex: 1;
            justify-content: center;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(102,126,234,0.4);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220,53,69,0.4);
        }
        
        /* Alerta de éxito */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            animation: slideDown 0.5s ease;
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
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        /* Sección de peligro */
        .danger-zone {
            background: #fff5f5;
            border: 2px solid #fee;
            border-radius: 8px;
            padding: 20px;
            margin-top: 40px;
        }
        
        .danger-zone h3 {
            color: #c82333;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .danger-zone p {
            color: #666;
            margin-bottom: 15px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .edit-card {
                padding: 25px 20px;
            }
            
            .button-group {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

    <!-- Navbar Superior -->
    <div class="admin-navbar">
        <h1>✏️ Editar Plato</h1>
        <div class="navbar-actions">
            <a href="admin.php">📋 Panel Admin</a>
            <a href="index.php" target="_blank">👁️ Ver Menú</a>
            <a href="logout.php">🚪 Cerrar Sesión</a>
        </div>
    </div>

    <div class="edit-container">
        
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="admin.php">🏠 Panel Admin</a>
            <span>›</span>
            <strong>Editar Plato</strong>
        </div>

        <?php if(isset($_GET['updated'])): ?>
        <div class="alert alert-success">
            <strong>✅ ¡Éxito!</strong> El plato ha sido actualizado correctamente.
        </div>
        <?php endif; ?>

        <!-- Card Principal -->
        <div class="edit-card">
            
            <div class="card-header">
                <span class="card-icon">🍽️</span>
                <h2><?php echo htmlspecialchars($plato['nombre']); ?></h2>
            </div>

            <!-- Vista Previa de Imagen Actual -->
            <?php if (!empty($plato['imagen_ruta'])): ?>
            <div class="image-preview-section">
                <h3 style="margin-bottom: 15px; color: #333;">📸 Imagen Actual</h3>
                <img src="<?php echo htmlspecialchars($plato['imagen_ruta']); ?>" 
                     alt="<?php echo htmlspecialchars($plato['nombre']); ?>" 
                     class="current-image"
                     id="previewImage">
                <p class="image-info">💡 Sube una nueva imagen para reemplazar la actual</p>
            </div>
            <?php endif; ?>

            <!-- Formulario de Edición -->
            <form action="actualizar_plato.php" method="POST" enctype="multipart/form-data">
                
                <input type="hidden" name="id" value="<?php echo $plato['id']; ?>">
                <input type="hidden" name="imagen_actual" value="<?php echo htmlspecialchars($plato['imagen_ruta']); ?>">

                <div class="form-grid">
                    <div class="form-group">
                        <label for="nombre">
                            <span>📝</span> Nombre del Plato *
                        </label>
                        <input type="text" 
                               id="nombre" 
                               name="nombre" 
                               value="<?php echo htmlspecialchars($plato['nombre']); ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="precio">
                            <span>💰</span> Precio ($) *
                        </label>
                        <input type="number" 
                               id="precio" 
                               name="precio" 
                               step="0.01" 
                               value="<?php echo $plato['precio']; ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="categoria">
                            <span>📂</span> Categoría *
                        </label>
                        <select id="categoria" name="categoria" required>
                            <option value="Entradas" <?php echo ($plato['categoria'] == 'Entradas') ? 'selected' : ''; ?>>
                                🥗 Entradas
                            </option>
                            <option value="Platos Principales" <?php echo ($plato['categoria'] == 'Platos Principales') ? 'selected' : ''; ?>>
                                🍖 Platos Principales
                            </option>
                            <option value="Postres" <?php echo ($plato['categoria'] == 'Postres') ? 'selected' : ''; ?>>
                                🍰 Postres
                            </option>
                            <option value="Bebidas" <?php echo ($plato['categoria'] == 'Bebidas') ? 'selected' : ''; ?>>
                                🥤 Bebidas
                            </option>
                            <option value="General" <?php echo ($plato['categoria'] == 'General') ? 'selected' : ''; ?>>
                                📌 General
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="imagen">
                            <span>🖼️</span> Nueva Imagen (opcional)
                        </label>
                        <input type="file" 
                               id="imagen" 
                               name="imagen" 
                               accept="image/*"
                               onchange="previewNewImage(event)">
                    </div>
                </div>

                <div class="form-group">
                    <label for="descripcion">
                        <span>📄</span> Descripción *
                    </label>
                    <textarea id="descripcion" 
                              name="descripcion" 
                              required><?php echo htmlspecialchars($plato['descripcion']); ?></textarea>
                </div>

                <!-- Checkboxes con diseño moderno -->
                <div class="checkbox-section">
                    <h3>🏷️ Características Especiales</h3>
                    <div class="checkbox-grid">
                        
                        <div class="checkbox-card <?php echo ($plato['popular']) ? 'checked' : ''; ?>" 
                             onclick="toggleCheckbox('popular')">
                            <input type="checkbox" 
                                   id="popular" 
                                   name="popular" 
                                   value="1"
                                   <?php echo ($plato['popular']) ? 'checked' : ''; ?>>
                            <label for="popular">⭐ Popular</label>
                        </div>

                        <div class="checkbox-card <?php echo ($plato['nuevo']) ? 'checked' : ''; ?>" 
                             onclick="toggleCheckbox('nuevo')">
                            <input type="checkbox" 
                                   id="nuevo" 
                                   name="nuevo" 
                                   value="1"
                                   <?php echo ($plato['nuevo']) ? 'checked' : ''; ?>>
                            <label for="nuevo">✨ Nuevo</label>
                        </div>

                        <div class="checkbox-card <?php echo ($plato['vegano']) ? 'checked' : ''; ?>" 
                             onclick="toggleCheckbox('vegano')">
                            <input type="checkbox" 
                                   id="vegano" 
                                   name="vegano" 
                                   value="1"
                                   <?php echo ($plato['vegano']) ? 'checked' : ''; ?>>
                            <label for="vegano">🌱 Vegano</label>
                        </div>

                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="button-group">
                    <button type="submit" class="btn btn-primary">
                        💾 Guardar Cambios
                    </button>
                    <a href="admin.php" class="btn btn-secondary">
                        ↩️ Cancelar
                    </a>
                </div>

            </form>

            <!-- Zona de Peligro -->
            <div class="danger-zone">
                <h3>⚠️ Zona de Peligro</h3>
                <p>Una vez que elimines este plato, no podrás recuperarlo.</p>
                <a href="borrar_plato.php?id=<?php echo $plato['id']; ?>" 
                   class="btn btn-danger"
                   onclick="return confirm('⚠️ ¿Estás completamente seguro de eliminar este plato?\n\nNombre: <?php echo htmlspecialchars($plato['nombre']); ?>\n\nEsta acción no se puede deshacer.')">
                    🗑️ Eliminar Plato Permanentemente
                </a>
            </div>

        </div>

    </div>

    <script>
        // Toggle para checkboxes con diseño de cards
        function toggleCheckbox(id) {
            const checkbox = document.getElementById(id);
            const card = checkbox.closest('.checkbox-card');
            
            checkbox.checked = !checkbox.checked;
            
            if (checkbox.checked) {
                card.classList.add('checked');
            } else {
                card.classList.remove('checked');
            }
            
            // Prevenir que el click en el label también active
            event.stopPropagation();
        }

        // Vista previa de nueva imagen
        function previewNewImage(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('previewImage');
                    if (preview) {
                        preview.src = e.target.result;
                    }
                }
                reader.readAsDataURL(file);
            }
        }

        // Prevenir clicks en labels que activen dos veces
        document.querySelectorAll('.checkbox-card label').forEach(label => {
            label.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });
    </script>

    <?php $conn->close(); ?>

</body>
</html>