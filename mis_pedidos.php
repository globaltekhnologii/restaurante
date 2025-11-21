<?php
// Configuración de la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "menu_restaurante";

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8mb4");

// Obtener teléfono del cliente
$telefono = isset($_GET['telefono']) ? trim($_GET['telefono']) : '';
$pedidos = [];

if (!empty($telefono)) {
    $stmt = $conn->prepare("SELECT * FROM pedidos WHERE telefono = ? ORDER BY fecha_pedido DESC");
    $stmt->bind_param("s", $telefono);
    $stmt->execute();
    $result = $stmt->get_result();
    $pedidos = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos - Restaurante El Sabor</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        
        .top-bar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        
        .top-bar h1 {
            color: white;
            font-size: 1.8em;
        }
        
        .top-bar a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            background: rgba(255,255,255,0.2);
            border-radius: 8px;
        }
        
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .search-form {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        .search-form input {
            width: 100%;
            max-width: 400px;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1.1em;
            margin-right: 10px;
        }
        
        .search-form button {
            padding: 15px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
        }
        
        .pedido-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: transform 0.3s;
        }
        
        .pedido-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
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
            color: #333;
        }
        
        .pedido-fecha {
            color: #666;
            font-size: 0.95em;
        }
        
        .estado-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9em;
        }
        
        .estado-pendiente { background: #fff3cd; color: #856404; }
        .estado-confirmado { background: #d1ecf1; color: #0c5460; }
        .estado-preparando { background: #d4edda; color: #155724; }
        .estado-en_camino { background: #cce5ff; color: #004085; }
        .estado-entregado { background: #d4edda; color: #155724; }
        .estado-cancelado { background: #f8d7da; color: #721c24; }
        
        .pedido-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .info-label {
            font-size: 0.9em;
            color: #666;
            font-weight: 600;
        }
        
        .info-value {
            font-size: 1.1em;
            color: #333;
        }
        
        .pedido-total {
            font-size: 1.5em;
            font-weight: 700;
            color: #667eea;
            text-align: right;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #e0e0e0;
        }
        
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .empty-icon {
            font-size: 5em;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .pedido-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .search-form input,
            .search-form button {
                width: 100%;
                margin: 5px 0;
            }
        }
    </style>
</head>
<body>

    <div class="top-bar">
        <h1>📦 Mis Pedidos</h1>
        <a href="index.php">← Volver al Menú</a>
    </div>

    <div class="container">
        
        <div class="search-form">
            <h2 style="margin-bottom: 20px; color: #333;">Consulta tus pedidos</h2>
            <form method="GET" action="" style="display: flex; flex-wrap: wrap; gap: 10px;">
                <input 
                    type="tel" 
                    name="telefono" 
                    placeholder="Ingresa tu número de teléfono" 
                    value="<?php echo htmlspecialchars($telefono); ?>"
                    required
                >
                <button type="submit">🔍 Buscar Pedidos</button>
            </form>
        </div>

        <?php if (empty($telefono)): ?>
            
            <div class="empty-state">
                <div class="empty-icon">📱</div>
                <h2>Ingresa tu número de teléfono</h2>
                <p>Para consultar tus pedidos, ingresa el teléfono que usaste al realizar el pedido.</p>
            </div>
            
        <?php elseif (empty($pedidos)): ?>
            
            <div class="empty-state">
                <div class="empty-icon">📦</div>
                <h2>No se encontraron pedidos</h2>
                <p>No hay pedidos registrados con el teléfono <strong><?php echo htmlspecialchars($telefono); ?></strong></p>
                <a href="index.php" style="display: inline-block; margin-top: 20px; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 8px;">
                    Hacer un Pedido
                </a>
            </div>
            
        <?php else: ?>
            
            <h2 style="margin-bottom: 25px; color: #333;">
                Pedidos encontrados: <?php echo count($pedidos); ?>
            </h2>
            
            <?php foreach ($pedidos as $pedido): 
                $estados_iconos = [
                    'pendiente' => '⏳',
                    'confirmado' => '✅',
                    'preparando' => '👨‍🍳',
                    'en_camino' => '🚚',
                    'entregado' => '✅',
                    'cancelado' => '❌'
                ];
                
                $estados_texto = [
                    'pendiente' => 'Pendiente',
                    'confirmado' => 'Confirmado',
                    'preparando' => 'Preparando',
                    'en_camino' => 'En Camino',
                    'entregado' => 'Entregado',
                    'cancelado' => 'Cancelado'
                ];
            ?>
            
            <div class="pedido-card">
                <div class="pedido-header">
                    <div>
                        <div class="pedido-numero">
                            Pedido <?php echo htmlspecialchars($pedido['numero_pedido']); ?>
                        </div>
                        <div class="pedido-fecha">
                            <?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?>
                        </div>
                    </div>
                    <div class="estado-badge estado-<?php echo $pedido['estado']; ?>">
                        <?php echo $estados_iconos[$pedido['estado']] ?? ''; ?>
                        <?php echo $estados_texto[$pedido['estado']] ?? $pedido['estado']; ?>
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
                        <span class="info-label">Dirección</span>
                        <span class="info-value"><?php echo htmlspecialchars($pedido['direccion']); ?></span>
                    </div>
                </div>
                
                <?php if (!empty($pedido['notas'])): ?>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 15px;">
                    <strong>Notas:</strong> <?php echo htmlspecialchars($pedido['notas']); ?>
                </div>
                <?php endif; ?>
                
                <div class="pedido-total">
                    Total: $<?php echo number_format($pedido['total'], 2); ?>
                </div>
            </div>
            
            <?php endforeach; ?>
            
        <?php endif; ?>

    </div>

</body>
</html>