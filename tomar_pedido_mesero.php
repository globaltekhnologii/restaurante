<?php
session_start();

// Verificar sesión y rol de mesero
require_once 'auth_helper.php';
verificarSesion();
verificarRolORedirect(['mesero'], 'login.php');

require_once 'config.php';
$conn = getDatabaseConnection();

$mesero_id = $_SESSION['user_id'];
$mesero_nombre = $_SESSION['nombre'];

// Obtener mesa_id del parámetro
$mesa_id = isset($_GET['mesa_id']) ? intval($_GET['mesa_id']) : 0;
$tipo_pedido = 'mesa'; // Por defecto es pedido en mesa

// Si hay mesa_id, obtener información de la mesa
$mesa = null;
if ($mesa_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM mesas WHERE id = ? AND estado = 'disponible'");
    $stmt->bind_param("i", $mesa_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $mesa = $result->fetch_assoc();
    } else {
        header("Location: mesero.php?error=" . urlencode("Mesa no disponible"));
        exit;
    }
    $stmt->close();
} else {
    // Si no hay mesa, es pedido a domicilio
    $tipo_pedido = 'domicilio';
}

// Obtener platos disponibles del menú
$platos = [];
$sql = "SELECT * FROM platos ORDER BY categoria, nombre";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $platos[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tomar Pedido - Restaurante El Sabor</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        
        .navbar {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar h1 { font-size: 1.3em; }
        
        .navbar a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            background: rgba(255,255,255,0.2);
            border-radius: 5px;
        }
        
        .container {
            max-width: 1600px;
            margin: 20px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 20px;
        }
        
        /* Sección de Menú */
        .menu-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .menu-section h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #48bb78;
        }
        
        .search-box {
            margin-bottom: 20px;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 1em;
        }
        
        .platos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            max-height: calc(100vh - 300px);
            overflow-y: auto;
        }
        
        .plato-card {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
        }
        
        .plato-card:hover {
            border-color: #48bb78;
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(72,187,120,0.2);
        }
        
        .plato-card.selected {
            border-color: #48bb78;
            background: #f0fff4;
        }
        
        .plato-imagen {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 6px;
            margin-bottom: 10px;
        }
        
        .plato-nombre {
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 0.95em;
        }
        
        .plato-precio {
            color: #48bb78;
            font-weight: bold;
            font-size: 1.1em;
        }
        
        /* Sección de Carrito */
        .cart-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: sticky;
            top: 20px;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }
        
        .cart-section h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #48bb78;
        }
        
        .mesa-info {
            background: #e6ffed;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #48bb78;
        }
        
        .mesa-info strong {
            display: block;
            margin-bottom: 5px;
        }
        
        .cart-items {
            margin-bottom: 20px;
        }
        
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
            gap: 10px;
        }
        
        .cart-item-info {
            flex: 1;
        }
        
        .cart-item-nombre {
            font-weight: 600;
            font-size: 0.9em;
        }
        
        .cart-item-precio {
            color: #666;
            font-size: 0.85em;
        }
        
        .cart-item-controls {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .qty-btn {
            width: 28px;
            height: 28px;
            border: none;
            background: #48bb78;
            color: white;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .qty-btn:hover {
            background: #38a169;
        }
        
        .qty-display {
            width: 35px;
            text-align: center;
            font-weight: bold;
        }
        
        .remove-btn {
            background: #f44336;
            color: white;
            border: none;
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8em;
        }
        
        .cart-total {
            background: #f7fafc;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .cart-total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .cart-total-final {
            font-size: 1.3em;
            font-weight: bold;
            color: #48bb78;
            padding-top: 10px;
            border-top: 2px solid #e0e0e0;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            font-size: 0.9em;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 0.95em;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 60px;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 6px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(72,187,120,0.4);
        }
        
        .btn-primary:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        
        .empty-cart {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }
        
        .empty-cart-icon {
            font-size: 3em;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>📝 Tomar Nuevo Pedido</h1>
        <a href="mesero.php">← Volver</a>
    </div>

    <div class="container">
        <!-- Sección de Menú -->
        <div class="menu-section">
            <h2>🍽️ Menú Disponible</h2>
            
            <div class="search-box">
                <input type="text" id="searchPlatos" placeholder="🔍 Buscar platos..." onkeyup="filtrarPlatos()">
            </div>
            
            <div class="platos-grid" id="platosGrid">
                <?php foreach ($platos as $plato): ?>
                <div class="plato-card" data-id="<?php echo $plato['id']; ?>" 
                     data-nombre="<?php echo htmlspecialchars($plato['nombre']); ?>"
                     data-precio="<?php echo $plato['precio']; ?>"
                     data-categoria="<?php echo htmlspecialchars($plato['categoria']); ?>"
                     onclick="agregarAlCarrito(<?php echo $plato['id']; ?>, '<?php echo htmlspecialchars($plato['nombre']); ?>', <?php echo $plato['precio']; ?>)">
                    <?php if (!empty($plato['imagen_ruta'])): ?>
                        <img src="<?php echo htmlspecialchars($plato['imagen_ruta']); ?>" 
                             alt="<?php echo htmlspecialchars($plato['nombre']); ?>" 
                             class="plato-imagen"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="plato-imagen" style="background: #e0e0e0; display: none; align-items: center; justify-content: center; color: #999; font-size: 2em;">🍽️</div>
                    <?php else: ?>
                        <div class="plato-imagen" style="background: #e0e0e0; display: flex; align-items: center; justify-content: center; color: #999; font-size: 2em;">🍽️</div>
                    <?php endif; ?>
                    <div class="plato-nombre"><?php echo htmlspecialchars($plato['nombre']); ?></div>
                    <div class="plato-precio">$<?php echo number_format($plato['precio'], 2); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Sección de Carrito -->
        <div class="cart-section">
            <h2>🛒 Pedido</h2>
            
            <?php if ($mesa): ?>
            <div class="mesa-info">
                <strong>Mesa: <?php echo htmlspecialchars($mesa['numero_mesa']); ?></strong>
                <span>Capacidad: <?php echo $mesa['capacidad']; ?> personas</span>
            </div>
            <?php endif; ?>
            
            <form id="pedidoForm" action="procesar_pedido_mesero.php" method="POST">
                <input type="hidden" name="mesa_id" value="<?php echo $mesa_id; ?>">
                <input type="hidden" name="tipo_pedido" value="<?php echo $tipo_pedido; ?>">
                <input type="hidden" name="items" id="itemsInput">
                
                <div class="cart-items" id="cartItems">
                    <div class="empty-cart">
                        <div class="empty-cart-icon">🛒</div>
                        <p>Selecciona platos del menú</p>
                    </div>
                </div>
                
                <div class="cart-total" id="cartTotal" style="display: none;">
                    <div class="cart-total-row">
                        <span>Subtotal:</span>
                        <span id="subtotal">$0.00</span>
                    </div>
                    <div class="cart-total-row cart-total-final">
                        <span>Total:</span>
                        <span id="total">$0.00</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Nombre del Cliente *</label>
                    <input type="text" name="nombre_cliente" required>
                </div>
                
                <div class="form-group">
                    <label>Teléfono *</label>
                    <input type="tel" name="telefono" required>
                </div>
                
                <?php if ($tipo_pedido === 'domicilio'): ?>
                <div class="form-group">
                    <label>Dirección de Entrega *</label>
                    <input type="text" name="direccion" required>
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label>Notas Especiales</label>
                    <textarea name="notas" placeholder="Ej: Sin cebolla, extra picante..."></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary" id="btnConfirmar" disabled>
                    ✅ Confirmar Pedido
                </button>
            </form>
        </div>
    </div>

    <script>
        let carrito = [];
        
        function agregarAlCarrito(id, nombre, precio) {
            const existente = carrito.find(item => item.id === id);
            
            if (existente) {
                existente.cantidad++;
            } else {
                carrito.push({ id, nombre, precio, cantidad: 1 });
            }
            
            actualizarCarrito();
        }
        
        function cambiarCantidad(id, delta) {
            const item = carrito.find(i => i.id === id);
            if (item) {
                item.cantidad += delta;
                if (item.cantidad <= 0) {
                    eliminarItem(id);
                } else {
                    actualizarCarrito();
                }
            }
        }
        
        function eliminarItem(id) {
            carrito = carrito.filter(i => i.id !== id);
            actualizarCarrito();
        }
        
        function actualizarCarrito() {
            const cartItemsDiv = document.getElementById('cartItems');
            const cartTotalDiv = document.getElementById('cartTotal');
            const btnConfirmar = document.getElementById('btnConfirmar');
            const itemsInput = document.getElementById('itemsInput');
            
            if (carrito.length === 0) {
                cartItemsDiv.innerHTML = '<div class="empty-cart"><div class="empty-cart-icon">🛒</div><p>Selecciona platos del menú</p></div>';
                cartTotalDiv.style.display = 'none';
                btnConfirmar.disabled = true;
                itemsInput.value = '';
                return;
            }
            
            let html = '';
            let subtotal = 0;
            
            carrito.forEach(item => {
                const itemTotal = item.precio * item.cantidad;
                subtotal += itemTotal;
                
                html += `
                    <div class="cart-item">
                        <div class="cart-item-info">
                            <div class="cart-item-nombre">${item.nombre}</div>
                            <div class="cart-item-precio">$${item.precio.toFixed(2)} c/u</div>
                        </div>
                        <div class="cart-item-controls">
                            <button type="button" class="qty-btn" onclick="cambiarCantidad(${item.id}, -1)">-</button>
                            <span class="qty-display">${item.cantidad}</span>
                            <button type="button" class="qty-btn" onclick="cambiarCantidad(${item.id}, 1)">+</button>
                            <button type="button" class="remove-btn" onclick="eliminarItem(${item.id})">🗑️</button>
                        </div>
                    </div>
                `;
            });
            
            cartItemsDiv.innerHTML = html;
            cartTotalDiv.style.display = 'block';
            
            document.getElementById('subtotal').textContent = '$' + subtotal.toFixed(2);
            document.getElementById('total').textContent = '$' + subtotal.toFixed(2);
            
            btnConfirmar.disabled = false;
            
            // Guardar items en input hidden
            itemsInput.value = JSON.stringify(carrito);
            
            // Marcar platos seleccionados
            document.querySelectorAll('.plato-card').forEach(card => {
                const id = parseInt(card.dataset.id);
                if (carrito.find(i => i.id === id)) {
                    card.classList.add('selected');
                } else {
                    card.classList.remove('selected');
                }
            });
        }
        
        function filtrarPlatos() {
            const searchTerm = document.getElementById('searchPlatos').value.toLowerCase();
            const cards = document.querySelectorAll('.plato-card');
            
            cards.forEach(card => {
                const nombre = card.dataset.nombre.toLowerCase();
                const categoria = card.dataset.categoria.toLowerCase();
                
                if (nombre.includes(searchTerm) || categoria.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        // Validar formulario antes de enviar
        document.getElementById('pedidoForm').addEventListener('submit', function(e) {
            if (carrito.length === 0) {
                e.preventDefault();
                alert('Debes agregar al menos un plato al pedido');
                return false;
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>
