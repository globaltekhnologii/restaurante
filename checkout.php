<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Pedido - Restaurante El Sabor</title>
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
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        
        .top-bar h1 {
            font-size: 1.8em;
        }
        
        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .checkout-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .section-title {
            font-size: 1.5em;
            color: #333;
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            transition: all 0.3s;
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .order-summary {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin: 30px 0;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .summary-item:last-child {
            border-bottom: none;
            font-size: 1.2em;
            font-weight: 700;
            color: #667eea;
            padding-top: 15px;
            margin-top: 10px;
            border-top: 2px solid #667eea;
        }
        
        .btn-submit {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #51cf66 0%, #40c057 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.3em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(81,207,102,0.4);
        }
        
        .btn-back {
            width: 100%;
            padding: 12px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            cursor: pointer;
            margin-top: 15px;
            text-decoration: none;
            display: block;
            text-align: center;
        }
        
        .required {
            color: #dc3545;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .checkout-card {
                padding: 25px;
            }
        }

        /* Estilos para selector de tipo de pedido */
        .order-type-selector {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        .order-type-option {
            position: relative;
        }
        .order-type-option input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }
        .order-type-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .order-type-icon {
            font-size: 2em;
            margin-bottom: 10px;
        }
        .order-type-option input:checked + label {
            border-color: #667eea;
            background: #eef2ff;
            color: #667eea;
        }
        .hidden { display: none !important; }
    </style>
</head>
<body>

    <div class="top-bar">
        <h1>🍽️ Finalizar Pedido</h1>
    </div>

    <div class="container">
        <div class="checkout-card">
            
            <form id="checkoutForm" action="procesar_pedido.php" method="POST">
                
                <!-- TIPO DE PEDIDO -->
                <h2 class="section-title">📋 Tipo de Pedido</h2>
                
                <div class="order-type-selector">
                    <div class="order-type-option">
                        <input type="radio" id="tipo_mesa" name="tipo_pedido_visual" value="mesa">
                        <label for="tipo_mesa" class="order-type-label">
                            <span class="order-type-icon">🍽️</span>
                            <span>Para Mesa</span>
                        </label>
                    </div>
                    <div class="order-type-option">
                        <input type="radio" id="tipo_domicilio" name="tipo_pedido_visual" value="domicilio" checked>
                        <label for="tipo_domicilio" class="order-type-label">
                            <span class="order-type-icon">🏍️</span>
                            <span>Domicilio</span>
                        </label>
                    </div>
                    <div class="order-type-option">
                        <input type="radio" id="tipo_llevar" name="tipo_pedido_visual" value="para_llevar">
                        <label for="tipo_llevar" class="order-type-label">
                            <span class="order-type-icon">📦</span>
                            <span>Para Llevar</span>
                        </label>
                    </div>
                </div>
                
                <h2 class="section-title">📋 Información de Contacto</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre">Nombre Completo <span class="required">*</span></label>
                        <input type="text" id="nombre" name="nombre" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="telefono">Teléfono <span class="required">*</span></label>
                        <input type="tel" id="telefono" name="telefono" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">Email (Opcional)</label>
                    <input type="email" id="email" name="email">
                </div>
                
                <h2 class="section-title" style="margin-top: 40px;">📍 Dirección de Entrega</h2>
                
                <!-- SELECCIÓN DE MESA (Oculto por defecto) -->
                <div id="mesa_section" class="hidden form-group">
                    <label for="mesa_visual">Número de Mesa</label>
                    <select id="mesa_visual" class="form-control" style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px;">
                        <option value="">Seleccione una mesa...</option>
                        <?php
                        // Intentar cargar mesas dinámicamente
                        try {
                            if (file_exists('config.php')) {
                                require_once 'config.php';
                                $conn = getDatabaseConnection();
                                // CORRECCIÓN: Usar 'estado' en lugar de 'ocupada'
                                $sql = "SELECT id, numero_mesa FROM mesas WHERE estado = 'disponible' ORDER BY numero_mesa";
                                $result = $conn->query($sql);
                                
                                if ($result) {
                                    while ($mesa = $result->fetch_assoc()) {
                                        echo "<option value='{$mesa['id']}'>Mesa {$mesa['numero_mesa']}</option>";
                                    }
                                }
                                $conn->close();
                            }
                        } catch (Exception $e) {
                            // Fallback silencioso
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group" id="direccion_container">
                    <label for="direccion">Dirección Completa <span class="required">*</span></label>
                    <textarea id="direccion" name="direccion" required placeholder="Calle, número, apartamento, referencias..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="notas">Notas Adicionales (Opcional)</label>
                    <textarea id="notas" name="notas" placeholder="Especificaciones del pedido, alergias, etc."></textarea>
                </div>
                
                <h2 class="section-title" style="margin-top: 40px;">💰 Resumen del Pedido</h2>
                
                <div class="order-summary" id="orderSummary">
                    <!-- Se llena con JavaScript -->
                </div>
                
                <input type="hidden" id="carritoData" name="carrito">
                <input type="hidden" id="totalData" name="total">
                <input type="hidden" id="tipo_pedido" name="tipo_pedido" value="domicilio">
                <input type="hidden" id="mesa_id" name="mesa_id" value="">
                <input type="hidden" name="metodo_pago" value="efectivo">
                <input type="hidden" name="pago_anticipado" value="0">
                
                <button type="submit" class="btn-submit" onclick="return validarPedido()">
                    ✅ Confirmar Pedido
                </button>
                
                <a href="carrito.php" class="btn-back">← Volver al Carrito</a>
                
            </form>
            
        </div>
    </div>

    <script>
        // Cargar resumen del pedido
        function cargarResumen() {
            const carrito = JSON.parse(localStorage.getItem('carrito')) || [];
            
            if (carrito.length === 0) {
                alert('Tu carrito está vacío');
                window.location.href = 'index.php';
                return;
            }
            
            const subtotal = carrito.reduce((sum, item) => sum + (item.precio * item.cantidad), 0);
            const envio = 5.00;
            const total = subtotal + envio;
            
            // Mostrar resumen
            const summaryHTML = `
                <div style="margin-bottom: 20px;">
                    ${carrito.map(item => `
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span>${item.nombre} x ${item.cantidad}</span>
                            <span>$${(item.precio * item.cantidad).toFixed(2)}</span>
                        </div>
                    `).join('')}
                </div>
                <div class="summary-item">
                    <span>Subtotal:</span>
                    <span>$${subtotal.toFixed(2)}</span>
                </div>
                <div class="summary-item">
                    <span>Envío:</span>
                    <span>$${envio.toFixed(2)}</span>
                </div>
                <div class="summary-item">
                    <span>Total a Pagar:</span>
                    <span>$${total.toFixed(2)}</span>
                </div>
            `;
            
            document.getElementById('orderSummary').innerHTML = summaryHTML;
            
            // Guardar datos en campos ocultos
            document.getElementById('carritoData').value = JSON.stringify(carrito);
            document.getElementById('totalData').value = total.toFixed(2);
        }
        
        // Validar y enviar formulario
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (this.checkValidity()) {
                this.submit();
            } else {
                alert('Por favor completa todos los campos obligatorios');
            }
        });
        
        // Cargar al inicio
        cargarResumen();

        // Lógica para selector de tipo de pedido
        document.querySelectorAll('input[name="tipo_pedido_visual"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const tipo = this.value;
                document.getElementById('tipo_pedido').value = tipo;
                
                const mesaSection = document.getElementById('mesa_section');
                const direccionContainer = document.getElementById('direccion_container');
                const direccionInput = document.getElementById('direccion');
                
                if (tipo === 'mesa') {
                    mesaSection.classList.remove('hidden');
                    direccionContainer.classList.add('hidden');
                    // Llenar dirección con valor dummy para pasar validación HTML5
                    direccionInput.value = 'Mesa ' + (document.getElementById('mesa_visual').options[document.getElementById('mesa_visual').selectedIndex]?.text || '');
                } else if (tipo === 'para_llevar') {
                    mesaSection.classList.add('hidden');
                    direccionContainer.classList.add('hidden');
                    direccionInput.value = 'Para Llevar - Recogida en local';
                } else {
                    mesaSection.classList.add('hidden');
                    direccionContainer.classList.remove('hidden');
                    if (direccionInput.value.startsWith('Mesa') || direccionInput.value.startsWith('Para Llevar')) {
                        direccionInput.value = '';
                    }
                }
            });
        });

        // Actualizar mesa_id oculto
        document.getElementById('mesa_visual').addEventListener('change', function() {
            document.getElementById('mesa_id').value = this.value;
            if (this.value) {
                document.getElementById('direccion').value = 'Mesa ' + this.options[this.selectedIndex].text;
            }
        });

        function validarPedido() {
            const tipo = document.getElementById('tipo_pedido').value;
            if (tipo === 'mesa' && !document.getElementById('mesa_id').value) {
                alert('Por favor selecciona una mesa');
                return false;
            }
            return true;
        }
    </script>
    
    <!-- Autocomplete de Clientes -->
    <script src="js/cliente_autocomplete.js"></script>

<?php
// Integración del Chatbot SaaS
if (file_exists(__DIR__ . '/includes/chatbot_widget.php')) {
    include __DIR__ . '/includes/chatbot_widget.php';
}
?>

</body>
</html>