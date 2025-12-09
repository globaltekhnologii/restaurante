<?php
session_start();
require_once 'auth_helper.php';
verificarSesion();
verificarRolORedirect(['admin', 'cajero'], 'login.php');

require_once 'config.php';
require_once 'includes/info_negocio.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/admin-modern.css">
    <title>Reportes y Estadísticas - <?php echo htmlspecialchars($info_negocio['nombre_restaurante']); ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar h1 {
            font-size: 1.5em;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .navbar-links {
            display: flex;
            gap: 15px;
        }
        
        .navbar-links a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            background: rgba(255,255,255,0.2);
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .navbar-links a:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .filters {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .filters h2 {
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .filter-group label {
            display: block;
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
        }
        
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
        }
        
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102,126,234,0.4);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .stat-card .icon {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .stat-card .label {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 5px;
        }
        
        .stat-card .value {
            font-size: 2em;
            font-weight: bold;
            color: #333;
        }
        
        .stat-card.primary { border-left: 4px solid #667eea; }
        .stat-card.success { border-left: 4px solid #4caf50; }
        .stat-card.warning { border-left: 4px solid #ff9800; }
        .stat-card.info { border-left: 4px solid #2196f3; }
        
        .chart-container {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .chart-container h3 {
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .chart-wrapper {
            position: relative;
            height: 400px;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .tab {
            padding: 12px 24px;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            color: #666;
            transition: all 0.3s;
        }
        
        .tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .table-container {
            overflow-x: auto;
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
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .loading::after {
            content: '...';
            animation: dots 1.5s infinite;
        }
        
        @keyframes dots {
            0%, 20% { content: '.'; }
            40% { content: '..'; }
            60%, 100% { content: '...'; }
        }
        
        .quick-filters {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .quick-filter {
            padding: 8px 16px;
            background: #f5f5f5;
            border: 2px solid #e0e0e0;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9em;
        }
        
        .quick-filter:hover,
        .quick-filter.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>📊 Reportes y Estadísticas</h1>
        <div class="navbar-links">
            <?php if ($_SESSION['rol'] === 'admin'): ?>
                <a href="admin.php">← Panel Admin</a>
            <?php else: ?>
                <a href="cajero.php">← Panel Caja</a>
            <?php endif; ?>
            <a href="logout.php">Cerrar Sesión</a>
        </div>
    </div>

    <div class="container">
        <!-- Filtros -->
        <div class="filters">
            <h2>🔍 Filtros de Búsqueda</h2>
            
            <div class="quick-filters" style="margin-bottom: 20px;">
                <div class="quick-filter active" onclick="setQuickFilter(event, 'hoy')">Hoy</div>
                <div class="quick-filter" onclick="setQuickFilter(event, 'semana')">Esta Semana</div>
                <div class="quick-filter" onclick="setQuickFilter(event, 'mes')">Este Mes</div>
                <div class="quick-filter" onclick="setQuickFilter(event, 'personalizado')">Personalizado</div>
            </div>
            
            <div class="filter-row" id="customFilters" style="display: none;">
                <div class="filter-group">
                    <label>Fecha Inicio</label>
                    <input type="date" id="fecha_inicio" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="filter-group">
                    <label>Fecha Fin</label>
                    <input type="date" id="fecha_fin" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="filter-group" style="display: flex; align-items: flex-end;">
                    <button class="btn btn-primary" onclick="cargarReportes()">🔎 Buscar</button>
                </div>
            </div>
        </div>

        <!-- Estadísticas Rápidas -->
        <div class="stats-grid" id="statsGrid">
            <div class="stat-card primary">
                <div class="icon">💰</div>
                <div class="label">Total Ventas</div>
                <div class="value" id="stat-ventas">$0</div>
            </div>
            <div class="stat-card success">
                <div class="icon">📦</div>
                <div class="label">Total Pedidos</div>
                <div class="value" id="stat-pedidos">0</div>
            </div>
            <div class="stat-card warning">
                <div class="icon">🎯</div>
                <div class="label">Ticket Promedio</div>
                <div class="value" id="stat-promedio">$0</div>
            </div>
            <div class="stat-card info">
                <div class="icon">📈</div>
                <div class="label">Venta Máxima</div>
                <div class="value" id="stat-maxima">$0</div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab active" onclick="switchTab('ventas')">📊 Ventas por Período</button>
            <button class="tab" onclick="switchTab('productos')">🍽️ Productos Más Vendidos</button>
            <button class="tab" onclick="switchTab('dashboard')">📈 Dashboard</button>
        </div>

        <!-- Tab: Ventas -->
        <div class="tab-content active" id="tab-ventas">
            <div class="chart-container">
                <h3>📊 Ventas Diarias</h3>
                <div class="chart-wrapper">
                    <canvas id="chartVentas"></canvas>
                </div>
            </div>
            
            <div class="chart-container">
                <h3>💳 Desglose por Método de Pago</h3>
                <div class="chart-wrapper" style="height: 300px;">
                    <canvas id="chartMetodos"></canvas>
                </div>
            </div>
        </div>

        <!-- Tab: Productos -->
        <div class="tab-content" id="tab-productos">
            <div class="chart-container">
                <h3>🏆 Top 10 Productos por Cantidad</h3>
                <div class="chart-wrapper">
                    <canvas id="chartProductosCantidad"></canvas>
                </div>
            </div>
            
            <div class="chart-container">
                <h3>💵 Top 10 Productos por Ingresos</h3>
                <div class="chart-wrapper">
                    <canvas id="chartProductosIngresos"></canvas>
                </div>
            </div>
            
            <div class="chart-container">
                <h3>📋 Detalle de Productos</h3>
                <div class="table-container">
                    <table id="tableProductos">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th>Cantidad Vendida</th>
                                <th>Ingresos Totales</th>
                                <th>Precio Promedio</th>
                            </tr>
                        </thead>
                        <tbody id="tableProductosBody">
                            <tr><td colspan="5" class="loading">Cargando datos</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab: Dashboard -->
        <div class="tab-content" id="tab-dashboard">
            <div class="chart-container">
                <h3>📈 Tendencia de Ventas (Últimos 7 Días)</h3>
                <div class="chart-wrapper">
                    <canvas id="chartTendencia"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script src="js/reportes.js"></script>
    
    <!-- Theme Manager -->
    <script src="js/theme-manager.js"></script>
</body>
</html>
