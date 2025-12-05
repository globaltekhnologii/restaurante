<?php
session_start();

// Verificar sesión y rol de cajero
require_once 'auth_helper.php';
verificarSesion();
verificarRolORedirect(['cajero', 'admin'], 'login.php');

require_once 'config.php';
require_once 'includes/info_negocio.php';

$conn = getDatabaseConnection();
$hoy = date('Y-m-d');

// Obtener resumen de pagos del día
$sql_pagos = "SELECT 
                metodo_pago,
                COUNT(*) as cantidad,
                SUM(monto) as total
              FROM pagos 
              WHERE DATE(fecha_pago) = ?
              GROUP BY metodo_pago";

$stmt = $conn->prepare($sql_pagos);
$stmt->bind_param("s", $hoy);
$stmt->execute();
$result_pagos = $stmt->get_result();

$resumen = [];
$total_general = 0;

while ($row = $result_pagos->fetch_assoc()) {
    $resumen[$row['metodo_pago']] = [
        'cantidad' => $row['cantidad'],
        'total' => $row['total']
    ];
    $total_general += $row['total'];
}
$stmt->close();

// Obtener detalle de todos los pagos del día
$sql_detalle = "SELECT 
                    pag.id,
                    pag.pedido_id,
                    ped.numero_pedido,
                    pag.monto,
                    pag.metodo_pago,
                    pag.fecha_pago,
                    ped.nombre_cliente,
                    u.nombre as cajero_nombre
                FROM pagos pag
                LEFT JOIN pedidos ped ON pag.pedido_id = ped.id
                LEFT JOIN usuarios u ON pag.usuario_id = u.id
                WHERE DATE(pag.fecha_pago) = ?
                ORDER BY pag.fecha_pago DESC";

$stmt = $conn->prepare($sql_detalle);
$stmt->bind_param("s", $hoy);
$stmt->execute();
$result_detalle = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cierre de Caja - <?php echo htmlspecialchars($info_negocio['nombre_restaurante']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 3px solid #4caf50;
        }
        
        .header h1 {
            color: #2e7d32;
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .header .fecha {
            color: #666;
            font-size: 1.2em;
        }
        
        .resumen-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .resumen-card {
            background: linear-gradient(135deg, #4caf50 0%, #388e3c 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .resumen-card h3 {
            font-size: 0.9em;
            margin-bottom: 10px;
            opacity: 0.9;
        }
        
        .resumen-card .monto {
            font-size: 2em;
            font-weight: bold;
        }
        
        .resumen-card .cantidad {
            font-size: 0.9em;
            opacity: 0.8;
            margin-top: 5px;
        }
        
        .resumen-card.efectivo {
            background: linear-gradient(135deg, #4caf50 0%, #388e3c 100%);
        }
        
        .resumen-card.tarjeta {
            background: linear-gradient(135deg, #2196f3 0%, #1976d2 100%);
        }
        
        .resumen-card.transferencia {
            background: linear-gradient(135deg, #9c27b0 0%, #7b1fa2 100%);
        }
        
        .resumen-card.total {
            background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
            grid-column: span 2;
        }
        
        .section {
            margin-bottom: 40px;
        }
        
        .section h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table thead {
            background: #f5f5f5;
        }
        
        table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #666;
            border-bottom: 2px solid #e0e0e0;
        }
        
        table td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        table tbody tr:hover {
            background: #f9f9f9;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: bold;
        }
        
        .badge-efectivo {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .badge-tarjeta {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .badge-transferencia {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        
        .actions {
            margin-top: 30px;
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 1em;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #2196f3 0%, #1976d2 100%);
            color: white;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #4caf50 0%, #388e3c 100%);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .actions {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Cierre de Caja</h1>
            <div class="fecha"><?php echo date('d/m/Y'); ?></div>
        </div>

        <!-- Resumen por Método de Pago -->
        <div class="resumen-grid">
            <?php
            $metodos = ['efectivo' => '💵', 'tarjeta' => '💳', 'transferencia' => '📱'];
            foreach ($metodos as $metodo => $icono) {
                $cantidad = isset($resumen[$metodo]) ? $resumen[$metodo]['cantidad'] : 0;
                $total = isset($resumen[$metodo]) ? $resumen[$metodo]['total'] : 0;
                
                echo "<div class='resumen-card $metodo'>";
                echo "<h3>$icono " . ucfirst($metodo) . "</h3>";
                echo "<div class='monto'>$" . number_format($total, 0, ',', '.') . "</div>";
                echo "<div class='cantidad'>$cantidad transacciones</div>";
                echo "</div>";
            }
            ?>
            
            <div class="resumen-card total">
                <h3>💰 Total General</h3>
                <div class="monto">$<?php echo number_format($total_general, 0, ',', '.'); ?></div>
                <div class="cantidad"><?php echo array_sum(array_column($resumen, 'cantidad')); ?> transacciones</div>
            </div>
        </div>

        <!-- Detalle de Transacciones -->
        <div class="section">
            <h2>📋 Detalle de Transacciones</h2>
            
            <table>
                <thead>
                    <tr>
                        <th>Hora</th>
                        <th>Pedido</th>
                        <th>Cliente</th>
                        <th>Método</th>
                        <th>Monto</th>
                        <th>Cajero</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result_detalle->num_rows > 0) {
                        while ($pago = $result_detalle->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . date('H:i', strtotime($pago['fecha_pago'])) . "</td>";
                            echo "<td><strong>" . htmlspecialchars($pago['numero_pedido']) . "</strong></td>";
                            echo "<td>" . htmlspecialchars($pago['nombre_cliente']) . "</td>";
                            echo "<td><span class='badge badge-" . $pago['metodo_pago'] . "'>" . ucfirst($pago['metodo_pago']) . "</span></td>";
                            echo "<td><strong>$" . number_format($pago['monto'], 0, ',', '.') . "</strong></td>";
                            echo "<td>" . htmlspecialchars($pago['cajero_nombre'] ?: 'N/A') . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' style='text-align:center;padding:40px;color:#999;'>";
                        echo "No hay transacciones registradas hoy";
                        echo "</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="actions">
            <button onclick="window.print()" class="btn btn-success">🖨️ Imprimir Cierre</button>
            <a href="cajero.php" class="btn btn-primary">← Volver a Caja</a>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
