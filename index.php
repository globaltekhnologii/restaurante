<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurante El Sabor - Menú Dinámico</title>
    <link rel="stylesheet" href="style.css" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        
        /* Top Bar Mejorada */
        .top-bar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .top-bar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .top-bar-brand span {
            color: white;
            font-size: 1.5em;
        }
        
        .top-bar-brand strong {
            color: white;
            font-size: 1.3em;
            font-weight: 600;
        }
        
        .top-bar a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            background: rgba(255,255,255,0.2);
            border-radius: 8px;
            transition: all 0.3s;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .top-bar a:hover {
            background: rgba(255,255,255,0.35);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        /* Header Principal */
        header {
            text-align: center;
            padding: 60px 20px 40px;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        header h1 {
            font-size: 2.8em;
            color: #333;
            margin-bottom: 10px;
            animation: fadeInDown 0.8s ease;
        }
        
        header p {
            font-size: 1.1em;
            color: #666;
            animation: fadeInUp 0.8s ease;
        }
        
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Sección de Búsqueda y Filtros */
        .search-filter-section {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .search-wrapper {
            position: relative;
            margin-bottom: 25px;
        }
        
        .search-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.3em;
            color: #999;
            pointer-events: none;
        }
        
        .search-box {
            width: 100%;
            padding: 18px 20px 18px 55px;
            font-size: 1.1em;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            transition: all 0.3s;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .search-box:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 4px 16px rgba(102,126,234,0.2);
        }
        
        .clear-search {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            background: #f0f0f0;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .clear-search:hover {
            background: #e0e0e0;
        }
        
        .filter-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }
        
        .filter-btn {
            padding: 12px 24px;
            border: 2px solid #667eea;
            background: white;
            color: #667eea;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 1em;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .filter-btn:hover {
            background: #f0f4ff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102,126,234,0.2);
        }
        
        .filter-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
            box-shadow: 0 4px 12px rgba(102,126,234,0.4);
        }
        
        /* Contador de Resultados */
        .results-info {
            text-align: center;
            color: #666;
            font-size: 1em;
            margin-bottom: 30px;
            padding: 10px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        /* Contenedor del Menú */
        .menu-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px 60px;
        }
        
        .menu-container > h2 {
            text-align: center;
            font-size: 2.5em;
            color: #333;
            margin-bottom: 50px;
            position: relative;
            padding-bottom: 15px;
        }
        
        .menu-container > h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 2px;
        }
        
        /* Sección de Categoría */
        .categoria-section {
            margin-bottom: 60px;
            animation: fadeIn 0.6s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .categoria-titulo {
            font-size: 2.2em;
            color: #333;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 3px solid #667eea;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .categoria-count {
            font-size: 0.5em;
            color: #999;
            font-weight: normal;
        }
        
        /* Tarjetas de Platos */
        .plato {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .plato:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 28px rgba(0,0,0,0.15);
        }
        
        .plato-contenido {
            display: flex;
            gap: 20px;
            padding: 20px;
        }
        
        .plato-imagen {
            width: 200px;
            height: 150px;
            object-fit: cover;
            border-radius: 12px;
            transition: transform 0.4s;
        }
        
        .plato:hover .plato-imagen {
            transform: scale(1.05);
        }
        
        .plato-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .plato-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
            gap: 15px;
        }
        
        .nombre-plato {
            font-size: 1.5em;
            font-weight: 600;
            color: #333;
            line-height: 1.3;
        }
        
        .precio {
            font-size: 1.6em;
            font-weight: 700;
            color: #667eea;
            white-space: nowrap;
        }
        
        .descripcion {
            color: #666;
            line-height: 1.6;
            font-size: 1em;
        }
        
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.75em;
            font-weight: 600;
            margin-left: 8px;
            margin-top: 5px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .badge-popular {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
        }
        
        .badge-nuevo {
            background: linear-gradient(135deg, #51cf66, #40c057);
            color: white;
        }
        
        .badge-vegano {
            background: linear-gradient(135deg, #20c997, #12b886);
            color: white;
        }
        
        /* Sin Resultados */
        .no-results {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .no-results-icon {
            font-size: 5em;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .no-results h3 {
            font-size: 1.8em;
            color: #333;
            margin-bottom: 10px;
        }
        
        .no-results p {
            color: #666;
            font-size: 1.1em;
        }
        
        /* Mensajes de Error */
        .mensaje-error, .mensaje-vacio {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin: 40px auto;
            max-width: 600px;
        }
        
        .mensaje-error {
            border-left: 5px solid #dc3545;
        }
        
        .mensaje-vacio {
            border-left: 5px solid #ffc107;
        }
        
        /* Loading Skeleton */
        .skeleton {
            animation: skeleton-loading 1s linear infinite alternate;
        }
        
        @keyframes skeleton-loading {
            0% { background-color: #f0f0f0; }
            100% { background-color: #e0e0e0; }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .top-bar {
                padding: 12px 15px;
            }
            
            .top-bar-brand strong {
                font-size: 1em;
            }
            
            header h1 {
                font-size: 2em;
            }
            
            .plato-contenido {
                flex-direction: column;
            }
            
            .plato-imagen {
                width: 100%;
                height: 200px;
            }
            
            .plato-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .filter-buttons {
                gap: 8px;
            }
            
            .filter-btn {
                padding: 10px 18px;
                font-size: 0.9em;
            }
        }


        /* Estilo para el botón de agregar al carrito */
.btn-add-cart {
    width: 100%;
    padding: 12px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    margin-top: 15px;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-add-cart:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102,126,234,0.3);
}

.btn-add-cart:active {
    transform: scale(0.98);
}
    </style>
</head>
<body>
<?php
// ============================================
// CONFIGURACIÓN Y CONEXIÓN A LA BASE DE DATOS
// ============================================

require_once 'config.php';

$error = false;
$platos = [];
$categorias = [];
$totalPlatos = 0;

try {
    $conn = getDatabaseConnection();
    
    // Consulta optimizada
    $sql = "SELECT nombre, descripcion, precio, imagen_ruta, 
            COALESCE(categoria, 'General') as categoria,
            COALESCE(popular, 0) as popular,
            COALESCE(nuevo, 0) as nuevo,
            COALESCE(vegano, 0) as vegano
            FROM platos 
            ORDER BY categoria ASC, nombre ASC";
    
    $result = $conn->query($sql);
    
    if ($result) {
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $platos[] = $row;
                if (!isset($categorias[$row['categoria']])) {
                    $categorias[$row['categoria']] = [];
                }
                $categorias[$row['categoria']][] = $row;
                $totalPlatos++;
            }
        }
    } else {
        error_log("Error en la consulta SQL: " . $conn->error);
        $error = true;
    }
    
    closeDatabaseConnection($conn);
    
} catch (Exception $e) {
    error_log("Error en index.php: " . $e->getMessage());
    $error = true;
}
?>

    <!-- Barra Superior -->
    <div class="top-bar">
        <div class="top-bar-brand">
            <span>🍽️</span>
            <strong>Restaurante El Sabor</strong>
        </div>
        
        <div style="display: flex; gap: 10px;">
            <a href="carrito.php" style="background: #ff6b6b; font-weight: bold;">
                <span>🛒</span>
                <span id="texto-carrito">Ver Carrito (0)</span>
            </a>

            <a href="login.php">
                <span>👤</span>
                <span>Panel Admin</span>
            </a>
        </div>
    </div>

    <!-- Header Principal -->
    <header>
        <h1>Bienvenidos a Restaurante El Sabor</h1>
        <p>Menú cargado automáticamente desde la Base de Datos</p>
    </header>

    <!-- Sección de Búsqueda y Filtros -->
    <div class="search-filter-section">
        
        <!-- Buscador -->
        <div class="search-wrapper">
            <span class="search-icon">🔍</span>
            <input 
                type="text" 
                id="searchBox" 
                class="search-box" 
                placeholder="Buscar platos por nombre o descripción..."
                onkeyup="filtrarPlatos()"
            >
            <button class="clear-search" id="clearSearch" onclick="limpiarBusqueda()">✕</button>
        </div>

        <!-- Filtros por Categoría -->
        <div class="filter-buttons">
            <button class="filter-btn active" onclick="filtrarCategoria('todas', event)">
                <span>📋</span>
                <span>Todas</span>
            </button>
            <?php foreach (array_keys($categorias) as $cat): ?>
                <button class="filter-btn" onclick="filtrarCategoria('<?php echo htmlspecialchars($cat); ?>', event)">
                    <span>
                    <?php 
                    $iconos = [
                        'Entradas' => '🥗',
                        'Platos Principales' => '🍖',
                        'Postres' => '🍰',
                        'Bebidas' => '🥤',
                        'General' => '🍽️'
                    ];
                    echo $iconos[$cat] ?? '📌'; 
                    ?>
                    </span>
                    <span><?php echo htmlspecialchars($cat); ?></span>
                </button>
            <?php endforeach; ?>
        </div>
        
        <!-- Contador de Resultados -->
        <div class="results-info" id="resultsInfo">
            Mostrando <strong id="resultCount"><?php echo $totalPlatos; ?></strong> plato(s)
        </div>
    </div>

    <!-- Contenedor del Menú -->
    <div class="menu-container">
        <h2>Nuestro Menú</h2>
        
        <?php if ($error): ?>
            <div class="mensaje-error">
                <strong>⚠️ Lo sentimos</strong><br>
                Estamos experimentando problemas técnicos. Por favor, intenta nuevamente en unos minutos.
            </div>
            
        <?php elseif (empty($platos)): ?>
            <div class="mensaje-vacio">
                <strong>📋 El menú está vacío</strong><br>
                Actualmente no hay platos disponibles. Vuelve pronto.
            </div>
            
        <?php else: ?>
            <!-- Platos Agrupados por Categoría -->
            <?php foreach ($categorias as $nombreCategoria => $platosCategoria): ?>
                <div class="categoria-section" data-categoria="<?php echo htmlspecialchars($nombreCategoria); ?>">
                    <h3 class="categoria-titulo">
                        <span><?php echo htmlspecialchars($nombreCategoria); ?></span>
                        <span class="categoria-count">(<?php echo count($platosCategoria); ?>)</span>
                    </h3>
                    
                    <?php foreach ($platosCategoria as $plato): ?>
                        <div class="plato" 
                             data-nombre="<?php echo htmlspecialchars($plato['nombre']); ?>" 
                             data-descripcion="<?php echo htmlspecialchars($plato['descripcion']); ?>">
                            <div class="plato-contenido">
                                <?php if (!empty($plato["imagen_ruta"])): ?>
                                    <img 
                                        src="<?php echo htmlspecialchars($plato["imagen_ruta"], ENT_QUOTES, 'UTF-8'); ?>" 
                                        alt="<?php echo htmlspecialchars($plato["nombre"], ENT_QUOTES, 'UTF-8'); ?>" 
                                        class="plato-imagen"
                                        loading="lazy"
                                    >
                                <?php endif; ?>
                                
                                <div class="plato-info">
                                    <div class="plato-header">
                                        <div>
                                            <div class="nombre-plato">
                                                <?php echo htmlspecialchars($plato["nombre"], ENT_QUOTES, 'UTF-8'); ?>
                                            </div>
                                            <div>
                                                <?php if ($plato['popular']): ?>
                                                    <span class="badge badge-popular">⭐ Popular</span>
                                                <?php endif; ?>
                                                
                                                <?php if ($plato['nuevo']): ?>
                                                    <span class="badge badge-nuevo">✨ Nuevo</span>
                                                <?php endif; ?>
                                                
                                                <?php if ($plato['vegano']): ?>
                                                    <span class="badge badge-vegano">🌱 Vegano</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <span class="precio">
                                            $<?php echo number_format($plato["precio"], 2, '.', ','); ?>
                                        </span>
                                    </div>
                                    <div class="descripcion">
                                        <?php echo htmlspecialchars($plato["descripcion"], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <button 
                                        class="btn-add-cart" 
                                        onclick="agregarAlCarrito(<?php echo htmlspecialchars(json_encode([
                                            'id' => $plato['nombre'],
                                            'nombre' => $plato['nombre'],
                                            'precio' => $plato['precio'],
                                            'imagen' => $plato['imagen_ruta']
                                        ]), ENT_QUOTES, 'UTF-8'); ?>)"
                                    >
                                        🛒 Agregar al Carrito
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
            
            <!-- Sin Resultados -->
            <div id="noResults" class="no-results" style="display: none;">
                <div class="no-results-icon">🔍</div>
                <h3>No se encontraron resultados</h3>
                <p>Intenta con otra búsqueda o categoría</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
          
        // 1. Función para actualizar el contador rojo de la barra superior
        function actualizarContadorVisual() {
            const carrito = JSON.parse(localStorage.getItem('carrito')) || [];
            const totalItems = carrito.reduce((total, item) => total + item.cantidad, 0);
            
            const spanCarrito = document.getElementById('texto-carrito');
            if (spanCarrito) {
                spanCarrito.innerText = `Ver Carrito (${totalItems})`;
            }
        }

        // 2. Función principal para agregar productos
        function agregarAlCarrito(producto) {
            let carrito = JSON.parse(localStorage.getItem('carrito')) || [];
            
            // Verificar si ya existe para sumar cantidad
            const indice = carrito.findIndex(item => item.nombre === producto.nombre);
            
            if (indice !== -1) {
                carrito[indice].cantidad++;
            } else {
                producto.cantidad = 1;
                carrito.push(producto);
            }
            
            // Guardar y actualizar
            localStorage.setItem('carrito', JSON.stringify(carrito));
            actualizarContadorVisual(); // <--- ¡ESTA LÍNEA ES CLAVE!
            
            // Feedback visual
            alert(`¡${producto.nombre} agregado! 🛒`);
            
            // Animación opcional del icono
            const cartIcon = document.querySelector('.top-bar-brand');
            if(cartIcon) {
                cartIcon.style.transform = "scale(1.2)";
                setTimeout(() => cartIcon.style.transform = "scale(1)", 200);
            }
        }

        // 3. Función para actualizar contador de resultados de búsqueda
        function actualizarContador() {
            const platosVisibles = document.querySelectorAll('.plato:not([style*="display: none"])').length;
            const countElement = document.getElementById('resultCount');
            if(countElement) countElement.textContent = platosVisibles;
        }

        // 4. Función para filtrar por búsqueda
        function filtrarPlatos() {
            const searchTerm = document.getElementById('searchBox').value.toLowerCase().trim();
            const platos = document.querySelectorAll('.plato');
            const clearBtn = document.getElementById('clearSearch');
            let visibleCount = 0;

            if(clearBtn) clearBtn.style.display = searchTerm ? 'flex' : 'none';

            platos.forEach(plato => {
                const nombre = plato.dataset.nombre.toLowerCase();
                const descripcion = plato.dataset.descripcion.toLowerCase();
                
                if (nombre.includes(searchTerm) || descripcion.includes(searchTerm)) {
                    plato.style.display = '';
                    visibleCount++;
                } else {
                    plato.style.display = 'none';
                }
            });

            // Ocultar categorías vacías
            document.querySelectorAll('.categoria-section').forEach(section => {
                const platosVisibles = Array.from(section.querySelectorAll('.plato'))
                    .filter(p => p.style.display !== 'none').length;
                section.style.display = platosVisibles > 0 ? '' : 'none';
            });

            const noResults = document.getElementById('noResults');
            if(noResults) noResults.style.display = visibleCount === 0 ? 'block' : 'none';
            
            actualizarContador();
        }

        // 5. Función para limpiar búsqueda
        function limpiarBusqueda() {
            document.getElementById('searchBox').value = '';
            const clearBtn = document.getElementById('clearSearch');
            if(clearBtn) clearBtn.style.display = 'none';
            filtrarPlatos();
        }

        // 6. Función para filtrar por categoría
        function filtrarCategoria(categoria, event) {
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.currentTarget.classList.add('active');

            limpiarBusqueda();

            const secciones = document.querySelectorAll('.categoria-section');
            
            if (categoria === 'todas') {
                secciones.forEach(seccion => seccion.style.display = '');
                document.querySelectorAll('.plato').forEach(plato => plato.style.display = '');
            } else {
                secciones.forEach(seccion => {
                    if (seccion.dataset.categoria === categoria) {
                        seccion.style.display = '';
                        seccion.querySelectorAll('.plato').forEach(plato => plato.style.display = '');
                    } else {
                        seccion.style.display = 'none';
                    }
                });
            }

            const noResults = document.getElementById('noResults');
            if(noResults) noResults.style.display = 'none';
            actualizarContador();
        }

        // Eventos al cargar
        document.addEventListener('DOMContentLoaded', () => {
            actualizarContadorVisual();
            
            // Smooth scroll
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const menuContainer = document.querySelector('.menu-container');
                    if(menuContainer) {
                        window.scrollTo({
                            top: menuContainer.offsetTop - 100,
                            behavior: 'smooth'
                        });
                    }
                });
            });
        });
    

    </script>

</body>
</html>