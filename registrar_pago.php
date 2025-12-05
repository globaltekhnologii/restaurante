<?php
session_start();
require_once 'auth_helper.php';
verificarSesion();

if (!isset($_GET['pedido_id'])) {
    header("Location: admin_pedidos.php");
    exit;
}

$pedido_id = intval($_GET['pedido_id']);

require_once 'config.php';
$conn = getDatabaseConnection();

// Obtener información del pedido
$stmt = $conn->prepare("SELECT * FROM pedidos WHERE id = ?");
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();

if (!$pedido) {
    die("Pedido no encontrado");
}

// Obtener métodos de pago activos
$metodos_pago = [];
$result = $conn->query("SELECT * FROM metodos_pago_config WHERE activo = 1 ORDER BY orden");
while ($row = $result->fetch_assoc()) {
    $metodos_pago[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Pago - Pedido <?php echo $pedido['numero_pedido']; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f5f7fa;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .navbar a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            background: rgba(255,255,255,0.2);
            border-radius: 5px;
        }
        
        .container {
            max-width: 800px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .card h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #667eea;
        }
        
        .pedido-info {
            background: #f7fafc;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .info-label {
            font-weight: 600;
            color: #666;
        }
        
        .info-value {
            color: #333;
        }
        
        .total {
            font-size: 1.5em;
            font-weight: bold;
            color: #667eea;
            text-align: right;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #e0e0e0;
        }
        
        .metodo-pago {
            border: 3px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .metodo-pago:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102,126,234,0.2);
        }
        
        .metodo-pago.selected {
            border-color: #667eea;
            background: #f0f4ff;
        }
        
        .metodo-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 10px;
        }
        
        .metodo-icon {
            font-size: 2em;
        }
        
        .metodo-nombre {
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
        }
        
        .metodo-detalles {
            display: none;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #e0e0e0;
        }
        
        .metodo-pago.selected .metodo-detalles {
            display: block;
        }
        
        .qr-code {
            max-width: 250px;
            margin: 15px auto;
            display: block;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
        }
        
        .cuenta-info {
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            margin: 10px 0;
        }
        
        .cuenta-numero {
            font-size: 1.3em;
            font-weight: bold;
            color: #667eea;
            text-align: center;
            margin: 10px 0;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 1em;
        }
        
        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
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
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>💳 Registrar Pago</h1>
        <a href="ver_pedido.php?id=<?php echo $pedido_id; ?>">← Volver al Pedido</a>
    </div>

    <div class="container">
        <div class="card">
            <h2>📋 Información del Pedido</h2>
            <div class="pedido-info">
                <div class="info-row">
                    <span class="info-label">Número de Pedido:</span>
                    <span class="info-value"><strong><?php echo htmlspecialchars($pedido['numero_pedido']); ?></strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Cliente:</span>
                    <span class="info-value"><?php echo htmlspecialchars($pedido['nombre_cliente']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Teléfono:</span>
                    <span class="info-value"><?php echo htmlspecialchars($pedido['telefono']); ?></span>
                </div>
                <div class="total">
                    Total a Pagar: $<?php echo number_format($pedido['total'], 0, ',', '.'); ?>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>💰 Selecciona el Método de Pago</h2>
            
            <form id="pagoForm" method="POST" action="procesar_pago.php">
                <input type="hidden" name="pedido_id" value="<?php echo $pedido_id; ?>">
                <input type="hidden" name="monto" value="<?php echo $pedido['total']; ?>">
                <input type="hidden" name="metodo_pago" id="metodo_pago_input" value="">
                
                <?php foreach ($metodos_pago as $metodo): ?>
                <div class="metodo-pago" onclick="seleccionarMetodo('<?php echo $metodo['metodo']; ?>')">
                    <div class="metodo-header">
                        <div class="metodo-icon">
                            <?php 
                            $iconos = [
                                'efectivo' => '💵',
                                'nequi' => '📱',
                                'daviplata' => '📱',
                                'dale' => '📱',
                                'bancolombia' => '🏦'
                            ];
                            echo $iconos[$metodo['metodo']] ?? '💳';
                            ?>
                        </div>
                        <div class="metodo-nombre"><?php echo htmlspecialchars($metodo['nombre_display']); ?></div>
                    </div>
                    
                    <div class="metodo-detalles">
                        <?php if ($metodo['metodo'] !== 'efectivo'): ?>
                            <?php if (!empty($metodo['numero_cuenta'])): ?>
                            <div class="cuenta-info">
                                <div style="text-align: center; color: #666; margin-bottom: 5px;">
                                    Número de Cuenta / Celular:
                                </div>
                                <div class="cuenta-numero">
                                    <?php echo htmlspecialchars($metodo['numero_cuenta']); ?>
                                </div>
                                <?php if (!empty($metodo['nombre_titular'])): ?>
                                <div style="text-align: center; color: #666; font-size: 0.9em;">
                                    A nombre de: <?php echo htmlspecialchars($metodo['nombre_titular']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($metodo['qr_imagen']) && file_exists($metodo['qr_imagen'])): ?>
                            <div style="text-align: center; margin: 15px 0;">
                                <p style="color: #666; margin-bottom: 10px;">Escanea el código QR:</p>
                                <img src="<?php echo htmlspecialchars($metodo['qr_imagen']); ?>" 
                                     class="qr-code" alt="Código QR">
                            </div>
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label>Número de Referencia / Transacción:</label>
                                <input type="text" name="referencia_<?php echo $metodo['metodo']; ?>" 
                                       placeholder="Ej: 123456789" disabled>
                            </div>
                        <?php else: ?>
                            <p style="color: #666; text-align: center; padding: 10px;">
                                El cliente pagará en efectivo al recibir el pedido.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <div class="form-group" style="margin-top: 20px;">
                    <label>Notas Adicionales (Opcional):</label>
                    <textarea name="notas" rows="3" placeholder="Ej: Cliente pagó con billete de $50.000"></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary" id="btnConfirmar" disabled>
                    ✅ Confirmar Pago
                </button>
            </form>
        </div>
    </div>

    <script>
        let metodoSeleccionado = null;
        
        function seleccionarMetodo(metodo) {
            // Remover selección anterior
            document.querySelectorAll('.metodo-pago').forEach(el => {
                el.classList.remove('selected');
                // Deshabilitar inputs
                el.querySelectorAll('input').forEach(input => input.disabled = true);
            });
            
            // Seleccionar nuevo método
            const elemento = event.currentTarget;
            elemento.classList.add('selected');
            
            // Habilitar inputs del método seleccionado
            elemento.querySelectorAll('input').forEach(input => input.disabled = false);
            
            // Guardar método seleccionado
            metodoSeleccionado = metodo;
            document.getElementById('metodo_pago_input').value = metodo;
            document.getElementById('btnConfirmar').disabled = false;
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
