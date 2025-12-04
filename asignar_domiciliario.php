<?php
session_start();

// Verificar sesión y rol de admin
require_once 'auth_helper.php';
verificarSesion();
verificarRolORedirect(['admin'], 'login.php');

require_once 'config.php';

// Validar parámetros
if (!isset($_GET['id'])) {
    header("Location: admin_pedidos.php?error=" . urlencode("ID de pedido no especificado"));
    exit;
}

$pedido_id = intval($_GET['id']);

$conn = getDatabaseConnection();

// Verificar que el pedido existe y es para domicilio
$stmt = $conn->prepare("SELECT * FROM pedidos WHERE id = ? AND direccion IS NOT NULL AND direccion != ''");
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    header("Location: admin_pedidos.php?error=" . urlencode("Pedido no encontrado o no es para domicilio"));
    exit;
}

$pedido = $result->fetch_assoc();
$stmt->close();

// Obtener domiciliarios disponibles (activos)
$domiciliarios = [];
$result = $conn->query("SELECT id, nombre FROM usuarios WHERE rol = 'domiciliario' AND activo = 1 ORDER BY nombre");
while ($row = $result->fetch_assoc()) {
    $domiciliarios[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignar Domiciliario - Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar h1 { font-size: 1.3em; }
        
        .container {
            max-width: 600px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .section h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #667eea;
        }
        
        .pedido-info {
            background: #f7fafc;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        
        .pedido-info div {
            margin-bottom: 8px;
        }
        
        .pedido-info strong {
            color: #667eea;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 1em;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            flex: 1;
            padding: 14px;
            border: none;
            border-radius: 6px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-secondary {
            background: #868e96;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>🏍️ Asignar Domiciliario</h1>
    </div>

    <div class="container">
        <div class="section">
            <h2>Asignar Domiciliario al Pedido</h2>
            
            <div class="pedido-info">
                <div><strong>Pedido:</strong> <?php echo htmlspecialchars($pedido['numero_pedido']); ?></div>
                <div><strong>Cliente:</strong> <?php echo htmlspecialchars($pedido['nombre_cliente']); ?></div>
                <div><strong>Dirección:</strong> <?php echo htmlspecialchars($pedido['direccion']); ?></div>
                <div><strong>Total:</strong> $<?php echo number_format($pedido['total'], 2); ?></div>
            </div>

            <?php if (empty($domiciliarios)): ?>
                <p style="text-align: center; color: #999; padding: 40px;">
                    No hay domiciliarios disponibles en este momento.
                </p>
                <div class="btn-group">
                    <a href="admin_pedidos.php" class="btn btn-secondary">← Volver</a>
                </div>
            <?php else: ?>
                <form action="procesar_asignar_domiciliario.php" method="POST">
                    <input type="hidden" name="pedido_id" value="<?php echo $pedido_id; ?>">
                    
                    <div class="form-group">
                        <label>Seleccionar Domiciliario:</label>
                        <select name="domiciliario_id" required>
                            <option value="">-- Selecciona un domiciliario --</option>
                            <?php foreach ($domiciliarios as $dom): ?>
                                <option value="<?php echo $dom['id']; ?>" <?php echo ($pedido['domiciliario_id'] == $dom['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dom['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">✅ Asignar</button>
                        <a href="admin_pedidos.php" class="btn btn-secondary">❌ Cancelar</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
