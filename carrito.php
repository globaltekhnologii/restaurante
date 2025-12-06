<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de Compras - Restaurante El Sabor</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        
        /* Top Bar */
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
        }
        
        .top-bar a:hover {
            background: rgba(255,255,255,0.35);
            transform: translateY(-2px);
        }
        
        /* Contenedor */
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        h1 {
            font-size: 2.5em;
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }
        
        /* Layout */
        .cart-layout {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
        }
        
        /* Items del carrito */
        .cart-items {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .cart-item {
            display: flex;
            gap: 20px;
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            align-items: center;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .item-image {
            width: 100px;
            height: 80px;
            object-fit: cover;
            border-radius: 10px;
        }
        
        .item-info {
            flex: 1;
        }
        
        .item-name {
            font-size: 1.2em;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .item-price {
            color: #667eea;
            font-size: 1.1em;
            font-weight: 600;
        }
        
        .item-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f5f7fa;
            padding: 5px 10px;
            border-radius: 8px;
        }
        
        .quantity-control button {
            background: #667eea;
            color: white;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.2em;
            transition: all 0.3s;
        }
        
        .quantity-control button:hover {
            background: #764ba2;
        }
        
        .quantity-control span {
            min-width: 30px;
            text-align: center;
            font-weight: 600;
        }
        
        .btn-remove {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-remove:hover {
            background: #c82333;
        }
        
        /* Resumen */
        .cart-summary {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            height: fit-content;
            position: sticky;
            top: 100px;
        }
        
        .summary-title {
            font-size: 1.5em;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .summary-row:last-of-type {
            border-bottom: 2px solid #667eea;
            font-size: 1.3em;
            font-weight: 700;
            color: #667eea;
        }
        
        .btn-checkout {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.2em;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            transition: all 0.3s;
        }
        
        .btn-checkout:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102,126,234,0.4);
        }
        
        .btn-continue {
            width: 100%;
            padding: 12px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            cursor: pointer;
            margin-top: 10px;
            text-decoration: none;
            display: block;
            text-align: center;
        }
        
        /* Carrito vacío */
        .empty-cart {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .empty-cart-icon {
            font-size: 6em;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .empty-cart h2 {
            color: #333;
            margin-bottom: 15px;
        }
        
        .empty-cart p {
            color: #666;
            margin-bottom: 30px;
        }
        
        /* Responsive */
        @media (max-width: 968px) {
            .cart-layout {
                grid-template-columns: 1fr;
            }
            
            .cart-summary {
                position: static;
            }
            
            .cart-item {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>

    <!-- Top Bar -->
    <div class="top-bar">
        <div class="top-bar-brand">
            <span>🛒</span>
            <span>Carrito de Compras</span>
        </div>
        <a href="index.php">← Volver al Menú</a>
    </div>

    <div class="container">
        <h1>Tu Carrito</h1>
        
        <div id="cart-content">
            <!-- El contenido se carga dinámicamente con JavaScript -->
        </div>
    </div>

    <script>
       // 1. Cargar y mostrar carrito (VERSIÓN CORREGIDA)
        function cargarCarrito() {
            const carrito = JSON.parse(localStorage.getItem('carrito')) || [];
            const container = document.getElementById('cart-content');
            
            if (carrito.length === 0) {
                container.innerHTML = `
                    <div class="empty-cart">
                        <div class="empty-cart-icon">🛒</div>
                        <h2>Tu carrito está vacío</h2>
                        <p>Agrega algunos platos deliciosos de nuestro menú</p>
                        <a href="index.php" class="btn-checkout">Ir al Menú</a>
                    </div>
                `;
                return;
            }
            
            // Calcular totales (Usando Number para evitar errores)
            const subtotal = carrito.reduce((sum, item) => sum + (Number(item.precio) * Number(item.cantidad)), 0);
            const envio = 5.00;
            const total = subtotal + envio;
            
            // Generar HTML
            container.innerHTML = `
                <div class="cart-layout">
                    <div class="cart-items">
                        <h2 style="margin-bottom: 20px;">Productos (${carrito.length})</h2>
                        ${carrito.map((item, index) => `
                            <div class="cart-item">
                                <img src="${item.imagen || 'imagenes_platos/default.jpg'}" alt="${item.nombre}" class="item-image">
                                <div class="item-info">
                                    <div class="item-name">${item.nombre}</div>
                                    <div class="item-price">$${Number(item.precio).toFixed(2)}</div>
                                </div>
                                <div class="item-controls">
                                    <div class="quantity-control">
                                        <button onclick="cambiarCantidad(${index}, -1)">-</button>
                                        <span>${item.cantidad}</span>
                                        <button onclick="cambiarCantidad(${index}, 1)">+</button>
                                    </div>
                                    <div class="item-price" style="min-width: 80px; text-align: right;">
                                        $${(Number(item.precio) * item.cantidad).toFixed(2)}
                                    </div>
                                    <button class="btn-remove" onclick="eliminarItem(${index})">🗑️</button>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                    
                    <div class="cart-summary">
                        <h3 class="summary-title">Resumen del Pedido</h3>
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span>$${subtotal.toFixed(2)}</span>
                        </div>
                        <div class="summary-row">
                            <span>Envío:</span>
                            <span>$${envio.toFixed(2)}</span>
                        </div>
                        <div class="summary-row">
                            <span>Total:</span>
                            <span>$${total.toFixed(2)}</span>
                        </div>
                        <button class="btn-checkout" onclick="location.href='checkout.php'">
                            Proceder al Pago
                        </button>
                        <a href="index.php" class="btn-continue">Continuar Comprando</a>
                    </div>
                </div>
            `;
        }

        // 2. Funciones auxiliares (QUE FALTABAN)
        function cambiarCantidad(index, delta) {
            let carrito = JSON.parse(localStorage.getItem('carrito')) || [];
            
            carrito[index].cantidad += delta;
            
            if (carrito[index].cantidad <= 0) {
                carrito.splice(index, 1); // Eliminar si baja a 0
            }
            
            localStorage.setItem('carrito', JSON.stringify(carrito));
            cargarCarrito();
        }
        
        function eliminarItem(index) {
            if (confirm('¿Eliminar este producto del carrito?')) {
                let carrito = JSON.parse(localStorage.getItem('carrito')) || [];
                carrito.splice(index, 1);
                localStorage.setItem('carrito', JSON.stringify(carrito));
                cargarCarrito();
            }
        }
        
        // 3. Inicializar al cargar la página
        window.onload = cargarCarrito;
    
    </script>

<?php
// Integración del Chatbot SaaS
if (file_exists(__DIR__ . '/includes/chatbot_widget.php')) {
    include __DIR__ . '/includes/chatbot_widget.php';
}
?>

</body>
</html>