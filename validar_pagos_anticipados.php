<?php
session_start();
require_once 'auth_helper.php';
verificarSesion();
verificarRolORedirect(['admin'], 'login.php');

require_once 'config.php';
$conn = getDatabaseConnection();

// Obtener pedidos con pago anticipado pendiente de validación
$sql = "SELECT p.*, 
        CASE 
            WHEN p.tipo_pedido = 'mesa' THEN CONCAT('Mesa ', m.numero_mesa)
            WHEN p.tipo_pedido = 'domicilio' THEN 'Domicilio'
            WHEN p.tipo_pedido = 'para_llevar' THEN 'Para Llevar'
        END as tipo_display
        FROM pedidos p
        LEFT JOIN mesas m ON p.mesa_id = m.id
        WHERE p.pago_anticipado = 1 
        AND p.pago_validado = 0
        ORDER BY p.fecha_pedido DESC";

$pedidos_pendientes = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validar Pagos Anticipados - Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 15px 30px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        
        .navbar h1 {
            font-size: 1.5em;
        }
        
        .navbar a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            background: rgba(255,255,255,0.2);
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .navbar a:hover {
            background: rgba(255,255,255,0.35);
        }
        
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        
        .pedido-card {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        
        .pedido-card:hover {
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102,126,234,0.2);
        }
        
        .pedido-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .pedido-numero {
            font-size: 1.3em;
            font-weight: 700;
            color: #667eea;
        }
        
        .pedido-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .info-label {
            font-size: 0.85em;
            color: #666;
            font-weight: 600;
        }
        
        .info-value {
            font-size: 1.1em;
            color: #333;
        }
        
        .pago-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .pago-details h4 {
            color: #667eea;
            margin-bottom: 15px;
        }
        
        .referencia {
            font-size: 1.2em;
            font-weight: 700;
            color: #333;
            background: white;
            padding: 12px;
            border-radius: 8px;
            border: 2px dashed #667eea;
            text-align: center;
            margin: 10px 0;
        }
        
        .actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .btn {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-aprobar {
            background: linear-gradient(135deg, #51cf66 0%, #40c057 100%);
            color: white;
        }
        
        .btn-aprobar:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(81,207,102,0.4);
        }
        
        .btn-rechazar {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            color: white;
        }
        
        .btn-rechazar:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255,107,107,0.4);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state-icon {
            font-size: 5em;
            margin-bottom: 20px;
        }
        
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .badge-tipo {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .badge-metodo {
            background: #f3e5f5;
            color: #7b1fa2;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>💳 Validar Pagos Anticipados</h1>
        <a href="admin.php">← Volver al Admin</a>
    </div>

    <div class="container">
        <div class="card">
            <h2 style="margin-bottom: 25px; color: #333;">
                📋 Pagos Pendientes de Validación
            </h2>

            <?php if ($pedidos_pendientes->num_rows > 0): ?>
                <?php while ($pedido = $pedidos_pendientes->fetch_assoc()): ?>
                <div class="pedido-card">
                    <div class="pedido-header">
                        <div class="pedido-numero">
                            <?php echo htmlspecialchars($pedido['numero_pedido']); ?>
                        </div>
                        <div>
                            <span class="badge badge-tipo"><?php echo $pedido['tipo_display']; ?></span>
                            <span class="badge badge-metodo"><?php echo ucfirst($pedido['metodo_pago_seleccionado']); ?></span>
                        </div>
                    </div>

                    <div class="pedido-info">
                        <div class="info-item">
                            <span class="info-label">Cliente</span>
                            <span class="info-value"><?php echo htmlspecialchars($pedido['nombre_cliente']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Teléfono</span>
                            <span class="info-value"><?php echo htmlspecialchars($pedido['telefono']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Total</span>
                            <span class="info-value" style="color: #667eea; font-weight: 700;">
                                $<?php echo number_format($pedido['total'], 2); ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Fecha</span>
                            <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?></span>
                        </div>
                    </div>

                    <div class="pago-details">
                        <h4>💳 Información del Pago</h4>
                        <p><strong>Método:</strong> <?php echo ucfirst($pedido['metodo_pago_seleccionado']); ?></p>
                        <p><strong>Referencia de Transacción:</strong></p>
                        <div class="referencia">
                            <?php echo htmlspecialchars($pedido['referencia_pago_anticipado']); ?>
                        </div>
                    </div>

                    <div class="actions">
                        <form method="POST" action="validar_pago_anticipado.php" style="flex: 1;">
                            <input type="hidden" name="pedido_id" value="<?php echo $pedido['id']; ?>">
                            <input type="hidden" name="accion" value="aprobar">
                            <button type="submit" class="btn btn-aprobar">
                                ✅ Aprobar Pago
                            </button>
                        </form>
                        
                        <form method="POST" action="validar_pago_anticipado.php" style="flex: 1;">
                            <input type="hidden" name="pedido_id" value="<?php echo $pedido['id']; ?>">
                            <input type="hidden" name="accion" value="rechazar">
                            <button type="submit" class="btn btn-rechazar" 
                                    onclick="return confirm('¿Estás seguro de rechazar este pago? El pedido será cancelado.')">
                                ❌ Rechazar Pago
                            </button>
                        </form>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">✅</div>
                    <h3>No hay pagos pendientes de validación</h3>
                    <p>Todos los pagos anticipados han sido procesados</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
